<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * SIP Plug and Play (RFC 6080 ua-profile multicast) — Fanvil AUTOUPDATE PNP block + PnP listener settings.
 *
 * @see https://github.com/boffart/MikoServerPnP
 */
class Zts_GeneralSipPnpService
{
	const KEY_ENABLE = 'sip_pnp_enable';
	const KEY_MULTICAST = 'sip_pnp_multicast';
	const KEY_PORT = 'sip_pnp_port';
	const KEY_TRANSPORT = 'sip_pnp_transport';
	const KEY_INTERVAL = 'sip_pnp_interval';
	const KEY_LISTENER = 'sip_pnp_listener_enable';
	const KEY_CFG_BASE = 'sip_pnp_cfg_base_url';
	const KEY_SECURE_URLS = 'sip_pnp_secure_urls';
	const KEY_BAN_MAX_FAILURES = 'sip_pnp_ban_max_failures';
	const KEY_BAN_SECONDS = 'sip_pnp_ban_seconds';

	const DEFAULT_MULTICAST = '224.0.1.75';
	const DEFAULT_PORT = '5060';
	const DEFAULT_TRANSPORT = '0';
	const DEFAULT_INTERVAL = '1';

	/** Fanvil PNP Transport: 0=UDP, 1=TCP, 2=TLS */
	const TRANSPORT_CHOICES = array('0', '1', '2');

	/**
	 * @return array<string,string>
	 */
	public static function installDefaults()
	{
		return array(
			self::KEY_ENABLE => '1',
			self::KEY_MULTICAST => self::DEFAULT_MULTICAST,
			self::KEY_PORT => self::DEFAULT_PORT,
			self::KEY_TRANSPORT => self::DEFAULT_TRANSPORT,
			self::KEY_INTERVAL => self::DEFAULT_INTERVAL,
			self::KEY_LISTENER => '0',
			self::KEY_CFG_BASE => '',
			self::KEY_SECURE_URLS => '1',
			self::KEY_BAN_MAX_FAILURES => (string) Zts_SipPnpSecureUrlService::DEFAULT_BAN_MAX_FAILURES,
			self::KEY_BAN_SECONDS => (string) Zts_SipPnpSecureUrlService::DEFAULT_BAN_SECONDS,
		);
	}

	/**
	 * @return string[]
	 */
	public static function storageKeys()
	{
		return array(
			self::KEY_ENABLE,
			self::KEY_MULTICAST,
			self::KEY_PORT,
			self::KEY_TRANSPORT,
			self::KEY_INTERVAL,
			self::KEY_LISTENER,
			self::KEY_CFG_BASE,
			self::KEY_SECURE_URLS,
			self::KEY_BAN_MAX_FAILURES,
			self::KEY_BAN_SECONDS,
		);
	}

	/**
	 * @param array<string,string> $post
	 * @param array<string,string> $current
	 * @return array<string,string>
	 */
	public static function normalizeFromPost(array $post, array $current = array())
	{
		$out = array();
		$out[self::KEY_ENABLE] = !empty($post[self::KEY_ENABLE]) ? '1' : '0';
		$out[self::KEY_LISTENER] = !empty($post[self::KEY_LISTENER]) ? '1' : '0';

		$mc = isset($post[self::KEY_MULTICAST]) ? trim((string) $post[self::KEY_MULTICAST]) : self::DEFAULT_MULTICAST;
		if (!filter_var($mc, FILTER_VALIDATE_IP))
		{
			$mc = self::DEFAULT_MULTICAST;
		}
		$out[self::KEY_MULTICAST] = $mc;

		$port = isset($post[self::KEY_PORT]) ? (int) $post[self::KEY_PORT] : (int) self::DEFAULT_PORT;
		if ($port < 1 || $port > 65535)
		{
			$port = (int) self::DEFAULT_PORT;
		}
		$out[self::KEY_PORT] = (string) $port;

		$tr = isset($post[self::KEY_TRANSPORT]) ? (string) $post[self::KEY_TRANSPORT] : self::DEFAULT_TRANSPORT;
		$out[self::KEY_TRANSPORT] = in_array($tr, self::TRANSPORT_CHOICES, true) ? $tr : self::DEFAULT_TRANSPORT;

		$iv = isset($post[self::KEY_INTERVAL]) ? (int) $post[self::KEY_INTERVAL] : (int) self::DEFAULT_INTERVAL;
		if ($iv < 0)
		{
			$iv = 0;
		}
		if ($iv > 99)
		{
			$iv = 99;
		}
		$out[self::KEY_INTERVAL] = (string) $iv;

		$base = isset($post[self::KEY_CFG_BASE]) ? trim((string) $post[self::KEY_CFG_BASE]) : '';
		$base = rtrim($base, '/');
		if ($base !== '' && !preg_match('#^https?://#i', $base))
		{
			$base = 'https://'.$base;
		}
		$out[self::KEY_CFG_BASE] = $base;
		$out[self::KEY_SECURE_URLS] = !empty($post[self::KEY_SECURE_URLS]) ? '1' : '0';
		$maxFail = isset($post[self::KEY_BAN_MAX_FAILURES]) ? (int) $post[self::KEY_BAN_MAX_FAILURES] : Zts_SipPnpSecureUrlService::DEFAULT_BAN_MAX_FAILURES;
		$out[self::KEY_BAN_MAX_FAILURES] = (string) max(1, min(100, $maxFail));
		$banSec = isset($post[self::KEY_BAN_SECONDS]) ? (int) $post[self::KEY_BAN_SECONDS] : Zts_SipPnpSecureUrlService::DEFAULT_BAN_SECONDS;
		$out[self::KEY_BAN_SECONDS] = (string) max(60, min(86400 * 7, $banSec));

		return $out;
	}

	/**
	 * @param array<string,string> $general
	 * @return bool
	 */
	public static function isFanvilPnpEnabled(array $general)
	{
		return isset($general[self::KEY_ENABLE]) && (string) $general[self::KEY_ENABLE] === '1';
	}

	/**
	 * Fanvil AUTOUPDATE module lines (--Sip Pnp List--).
	 *
	 * @param array<string,string> $general
	 * @return array<int,string>
	 */
	public static function fanvilConfigLines(array $general)
	{
		$defaults = self::installDefaults();
		foreach ($defaults as $k => $v)
		{
			if (!isset($general[$k]) || (string) $general[$k] === '')
			{
				$general[$k] = $v;
			}
		}

		$enable = self::isFanvilPnpEnabled($general) ? '1' : '0';

		return array(
			'--Sip Pnp List--   :',
			'PNP Enable         :'.$enable,
			'PNP IP             :'.(string) $general[self::KEY_MULTICAST],
			'PNP Port           :'.(string) $general[self::KEY_PORT],
			'PNP Transport      :'.(string) $general[self::KEY_TRANSPORT],
			'PNP Interval       :'.(string) $general[self::KEY_INTERVAL],
		);
	}

	/**
	 * NOTIFY profile URL (Yealink/Snom/Fanvil MAC.cfg via router.php).
	 *
	 * @param array<string,string> $general
	 * @param string               $mac 12 hex lower
	 * @param string               $vendor event vendor lower
	 * @param int                  $listenerPid PnP listener PID (0 = read pid file)
	 * @return string
	 */
	public static function profileUrlForMac(array $general, $mac, $vendor = '', $listenerPid = 0)
	{
		$mac = strtolower(preg_replace('/[^0-9a-f]/', '', (string) $mac));
		if (strlen($mac) !== 12)
		{
			return '';
		}
		$base = self::resolveCfgBaseUrl($general);
		if ($base === '')
		{
			return '';
		}
		if (Zts_SipPnpSecureUrlService::isSecureUrlEnabled($general))
		{
			$pid = (int) $listenerPid;
			if ($pid < 1)
			{
				$pid = Zts_SipPnpSecureUrlService::readListenerPid();
			}
			if ($pid < 1)
			{
				$pid = getmypid();
			}

			return Zts_SipPnpSecureUrlService::profileUrl($base, $mac, $pid);
		}
		$ext = 'cfg';
		if ($vendor === 'snom')
		{
			$ext = 'xml';
		}

		return $base.'/'.$mac.'.'.$ext;
	}

	/**
	 * HTTPS provisioning base (…/zerotouchsip) for NOTIFY profile URL.
	 *
	 * @param array<string,string> $general
	 * @return string
	 */
	public static function resolveCfgBaseUrl(array $general)
	{
		$base = isset($general[self::KEY_CFG_BASE]) ? rtrim(trim((string) $general[self::KEY_CFG_BASE]), '/') : '';
		if ($base !== '')
		{
			return $base;
		}
		$host = '';
		if (!empty($_SERVER['HTTP_HOST']))
		{
			$host = trim((string) $_SERVER['HTTP_HOST']);
		}
		elseif (!empty($_SERVER['SERVER_NAME']))
		{
			$host = trim((string) $_SERVER['SERVER_NAME']);
		}
		if ($host === '')
		{
			global $amp_conf;
			if (is_array($amp_conf))
			{
				if (!empty($amp_conf['FREEPBX_SERVER']))
				{
					$host = trim((string) $amp_conf['FREEPBX_SERVER']);
				}
				elseif (!empty($amp_conf['FREEPBX_SERVER_IP']))
				{
					$host = trim((string) $amp_conf['FREEPBX_SERVER_IP']);
				}
			}
			if ($host === '' && function_exists('gethostname'))
			{
				$hn = gethostname();
				if ($hn !== false)
				{
					$host = (string) $hn;
				}
			}
		}
		$host = preg_replace('#:\d+$#', '', preg_replace('#^https?://#i', '', $host));
		if ($host === '')
		{
			return '';
		}

		return Zts_ProvisioningPaths::baseUrl('https', $host);
	}

	/**
	 * @param array<string,string> $general
	 * @return array{multicast:string,port:int,listener:bool,cfg_base:string}
	 */
	public static function listenerOptions(array $general)
	{
		$d = self::installDefaults();
		foreach ($d as $k => $v)
		{
			if (!isset($general[$k]) || (string) $general[$k] === '')
			{
				$general[$k] = $v;
			}
		}

		return array(
			'multicast' => (string) $general[self::KEY_MULTICAST],
			'port' => (int) $general[self::KEY_PORT],
			'listener' => isset($general[self::KEY_LISTENER]) && (string) $general[self::KEY_LISTENER] === '1',
			'cfg_base' => (string) $general[self::KEY_CFG_BASE],
			'fanvil_pnp' => self::isFanvilPnpEnabled($general),
		);
	}
}
