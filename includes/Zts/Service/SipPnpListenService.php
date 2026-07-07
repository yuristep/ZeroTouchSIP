<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * SIP PnP multicast listener (SUBSCRIBE ua-profile → 200 OK + NOTIFY with profile URL).
 * Uses SOCK_RAW on 224.0.1.75:5060 (coexists with Asterisk on 0.0.0.0:5060).
 *
 * @see https://github.com/boffart/MikoServerPnP
 */
class Zts_SipPnpListenService
{
	/** @var array<string,string> */
	private $general;

	/** @var bool */
	private $debug;

	/** @var string[] */
	private $interfaces;

	/**
	 * @param array<string,string> $general zts_settings
	 * @param bool                 $debug
	 */
	public function __construct(array $general, $debug = false)
	{
		$this->general = $general;
		$this->debug = $debug;
		$this->interfaces = self::discoverInterfaceNames();
	}

	/**
	 * @return string[]
	 */
	public static function discoverInterfaceNames()
	{
		$out = array();
		$proc = @file('/proc/sys/net/ipv4/conf/index', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (is_array($proc))
		{
			foreach ($proc as $line)
			{
				$name = trim($line);
				if ($name !== '' && $name !== 'lo' && preg_match('/^(eth|ens|enp|eno|bond|br)/', $name))
				{
					$out[] = $name;
				}
			}
		}
		if ($out === array())
		{
			exec('ls /sys/class/net 2>/dev/null', $names);
			if (is_array($names))
			{
				foreach ($names as $name)
				{
					$name = trim((string) $name);
					if ($name !== '' && $name !== 'lo')
					{
						$out[] = $name;
					}
				}
			}
		}

		return array_values(array_unique($out));
	}

	/**
	 * @return void
	 */
	public function listen()
	{
		$opts = Zts_GeneralSipPnpService::listenerOptions($this->general);
		if (!$opts['listener'])
		{
			$this->log('sip_pnp_listener_enable is off in General Settings.', LOG_WARNING);
			return;
		}
		$mc = $opts['multicast'];
		$port = $opts['port'];
		if (!extension_loaded('sockets'))
		{
			$this->log('PHP sockets extension required.', LOG_ERR);
			return;
		}
		$sock = @socket_create(AF_INET, SOCK_RAW, SOL_UDP);
		if (!$sock)
		{
			$this->log('SOCK_RAW create failed: '.socket_strerror(socket_last_error()), LOG_ERR);
			return;
		}
		socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
		$joined = 0;
		foreach ($this->interfaces as $eth)
		{
			if (@socket_set_option($sock, IPPROTO_IP, MCAST_JOIN_GROUP, array(
				'group' => $mc,
				'interface' => $eth,
			)))
			{
				$joined++;
			}
			elseif ($this->debug)
			{
				$this->log('MCAST_JOIN_GROUP on '.$eth.' failed: '.socket_strerror(socket_last_error($sock)), LOG_WARNING);
			}
		}
		if ($joined === 0)
		{
			$this->log('No interface joined multicast '.$mc, LOG_ERR);
			socket_close($sock);
			return;
		}
		if (!@socket_bind($sock, $mc, $port))
		{
			$this->log('socket_bind '.$mc.':'.$port.' failed: '.socket_strerror(socket_last_error($sock)), LOG_ERR);
			socket_close($sock);
			return;
		}
		Zts_SipPnpSecureUrlService::writeListenerPid(getmypid());
		$this->log('Listening (SOCK_RAW) on '.$mc.':'.$port.' if='.implode(',', $this->interfaces).' pid='.getmypid());
		while (true)
		{
			$packet = '';
			$len = @socket_recv($sock, $packet, 65535, 0);
			if ($len === false || $len < 1)
			{
				continue;
			}
			$sip = self::sipPayloadFromIpPacket($packet);
			if ($sip === '')
			{
				continue;
			}
			$headers = $this->parseSubscribe($sip);
			if ($headers !== array())
			{
				$this->sendPnPResponse($headers);
			}
		}
	}

	/**
	 * Strip IPv4 + UDP headers from SOCK_RAW datagram (MikoServerPnP-style).
	 *
	 * @param string $packet
	 * @return string SIP message or empty
	 */
	public static function sipPayloadFromIpPacket($packet)
	{
		$packet = (string) $packet;
		if (strlen($packet) < 28)
		{
			return '';
		}
		$ihl = ord($packet[0]) & 0x0f;
		if ($ihl < 5)
		{
			return '';
		}
		$ipHeaderLen = $ihl * 4;
		if (strlen($packet) <= $ipHeaderLen + 8)
		{
			return '';
		}
		$udpPayload = substr($packet, $ipHeaderLen + 8);

		return trim($udpPayload);
	}

	/**
	 * @param string $row_data
	 * @return array<string,mixed>
	 */
	private function parseSubscribe($row_data)
	{
		$rows = preg_split("/\r\n|\n|\r/", $row_data);
		if (!is_array($rows) || count($rows) < 1)
		{
			return array();
		}
		$first = trim((string) $rows[0]);
		if (stripos($first, 'SUBSCRIBE') !== 0)
		{
			return array();
		}
		$headers = array(
			'method' => 'SUBSCRIBE',
			'mac' => '',
			'phone_ip' => '',
			'phone_port' => '5060',
			'vendor' => '',
		);
		$pos = strrpos($first, '@');
		if ($pos !== false && $pos >= 12)
		{
			$headers['mac'] = strtolower(substr($first, $pos - 12, 12));
		}
		unset($rows[0]);
		$raw = array();
		foreach ($rows as $row)
		{
			$row = trim((string) $row);
			if ($row === '')
			{
				continue;
			}
			$colon = strpos($row, ':');
			if ($colon === false)
			{
				continue;
			}
			$h = trim(substr($row, 0, $colon));
			$raw[$h] = $row;
			if ($h === 'Via')
			{
				if (preg_match('/(\d+\.\d+\.\d+\.\d+):?(\d*)/', $row, $m))
				{
					$headers['phone_ip'] = $m[1];
					if (!empty($m[2]))
					{
						$headers['phone_port'] = $m[2];
					}
				}
			}
			elseif ($h === 'Event')
			{
				if (preg_match('/vendor="([^"]+)"/i', $row, $m))
				{
					$headers['vendor'] = strtolower($m[1]);
				}
			}
		}
		$headers['Via'] = isset($raw['Via']) ? $raw['Via'] : '';
		$headers['To'] = isset($raw['To']) ? $raw['To'] : '';
		$headers['From'] = isset($raw['From']) ? $raw['From'] : '';
		$headers['Call-ID'] = isset($raw['Call-ID']) ? $raw['Call-ID'] : '';
		if ($headers['mac'] === '' || $headers['phone_ip'] === '')
		{
			return array();
		}
		if (!$this->macAllowed($headers['mac']))
		{
			if ($this->debug)
			{
				$this->log('MAC not in zts_devices: '.$headers['mac'], LOG_NOTICE);
			}
			return array();
		}
		$this->log(sprintf(
			'PnP SUBSCRIBE mac=%s ip=%s vendor=%s',
			$headers['mac'],
			$headers['phone_ip'],
			$headers['vendor']
		));

		return $headers;
	}

	/**
	 * @param string $mac
	 * @return bool
	 */
	private function macAllowed($mac)
	{
		global $db;
		if (!isset($db))
		{
			return true;
		}
		$mac = strtoupper($mac);
		$row = sql('SELECT id FROM zts_devices WHERE mac="'.$db->escapeSimple($mac).'" LIMIT 1', 'getRow', DB_FETCHMODE_ASSOC);

		return is_array($row) && !empty($row['id']);
	}

	/**
	 * @param array<string,mixed> $headers
	 * @return void
	 */
	private function sendPnPResponse(array $headers)
	{
		$url = Zts_GeneralSipPnpService::profileUrlForMac(
			$this->general,
			(string) $headers['mac'],
			(string) $headers['vendor'],
			getmypid()
		);
		if ($url === '')
		{
			$this->log('Empty profile URL for mac '.$headers['mac'], LOG_WARNING);
			return;
		}
		$ip = (string) $headers['phone_ip'];
		$port = (int) $headers['phone_port'];
		if ($port < 1)
		{
			$port = 5060;
		}
		$sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if (!$sock)
		{
			return;
		}
		$ack = "SIP/2.0 200 OK\r\n"
			.$headers['Via']."\r\n"
			."Contact: \r\n"
			.$headers['To']."\r\n"
			.$headers['From']."\r\n"
			.$headers['Call-ID']."\r\n"
			.'CSeq: 1 '.$headers['method']."\r\n"
			."Expires: 0\r\n"
			."Content-Length: 0\r\n\r\n";
		$this->sendUdp($sock, $ip, $port, $ack);
		$notify = 'NOTIFY sip:'.$ip.':'.$port." SIP/2.0\r\n"
			.$headers['Via']."\r\n"
			."Max-Forwards: 20\r\n"
			."Contact: \r\n"
			.$headers['To']."\r\n"
			.$headers['From']."\r\n"
			.$headers['Call-ID']."\r\n"
			."CSeq: 3 NOTIFY\r\n"
			."Content-Type: application/url\r\n"
			."Subscription-State: terminated;reason=timeout\r\n"
			.'Event: ua-profile;profile-type="device";vendor="ZeroTouchSIP";model="PnP";version="1"'."\r\n"
			.'Content-Length: '.strlen($url)."\r\n\r\n"
			.$url;
		$this->sendUdp($sock, $ip, $port, $notify);
		socket_close($sock);
		if ($this->debug)
		{
			$this->log("NOTIFY url={$url}");
		}
	}

	/**
	 * @param resource $sock
	 * @param string   $ip
	 * @param int      $port
	 * @param string   $msg
	 * @return void
	 */
	private function sendUdp($sock, $ip, $port, $msg)
	{
		@socket_sendto($sock, $msg, strlen($msg), 0, $ip, $port);
	}

	/**
	 * @param string   $text
	 * @param int|null $level
	 * @return void
	 */
	private function log($text, $level = null)
	{
		$line = '[ZeroTouchSIP PnP] '.$text;
		if (function_exists('openlog'))
		{
			openlog('zerotouchsip-pnp', LOG_PID | LOG_PERROR, LOG_DAEMON);
			syslog($level ?? LOG_INFO, $line);
			closelog();
		}
		if ($this->debug)
		{
			fwrite(STDERR, $line."\n");
		}
	}
}
