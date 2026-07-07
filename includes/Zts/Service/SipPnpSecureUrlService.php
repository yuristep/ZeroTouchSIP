<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * One-time PnP profile URLs: ?mac=...&hash=... with HMAC-SHA256 tokens.
 *
 * @see https://habr.com/ru/articles/471522/
 * @see https://github.com/boffart/MikoServerPnP
 */
class Zts_SipPnpSecureUrlService
{
	const HASH_LENGTH = 64;

	const DEFAULT_BAN_MAX_FAILURES = 5;

	const DEFAULT_BAN_SECONDS = 3600;

	/**
	 * @return string
	 */
	public static function spoolDir()
	{
		if (@is_dir('/var/spool/asterisk'))
		{
			return '/var/spool/asterisk/zerotouchsip-pnp';
		}

		return sys_get_temp_dir().'/zerotouchsip-pnp';
	}

	/**
	 * @return string
	 */
	public static function pidFilePath()
	{
		return self::spoolDir().'/listener.pid';
	}

	/**
	 * @return string
	 */
	private static function secretFilePath()
	{
		return self::spoolDir().'/secret.key';
	}

	/**
	 * @param int $pid
	 * @return void
	 */
	public static function writeListenerPid($pid)
	{
		self::ensureSpoolDir();
		self::writeSpoolFile(self::pidFilePath(), (string) (int) $pid, 0644);
	}

	/**
	 * @return int
	 */
	public static function readListenerPid()
	{
		$path = self::pidFilePath();
		if (!is_file($path))
		{
			return 0;
		}
		$pid = (int) trim((string) file_get_contents($path));

		return $pid > 0 ? $pid : 0;
	}

	/**
	 * HMAC-SHA256 over MAC + date + listener PID. Listener PID keeps old token
	 * semantics while server secret prevents guessing.
	 *
	 * @param string   $mac 12 hex, any case
	 * @param int|null $pid listener process id; 0 = read from pid file
	 * @param string|null $date Y-m-d; null = today (server TZ)
	 * @return string
	 */
	public static function hashForMac($mac, $pid = null, $date = null)
	{
		$mac = strtolower(preg_replace('/[^0-9a-f]/', '', (string) $mac));
		if (strlen($mac) !== 12)
		{
			return '';
		}
		if ($pid === null)
		{
			$pid = self::readListenerPid();
		}
		$pid = (int) $pid;
		if ($pid < 1)
		{
			$pid = getmypid();
		}
		if ($date === null)
		{
			$date = date('Y-m-d');
		}

		$secret = self::serverSecret();
		if ($secret === '')
		{
			return '';
		}

		return hash_hmac('sha256', $mac.'|'.$date.'|'.$pid, $secret);
	}

	/**
	 * NOTIFY URL: {base}/pnp.php?mac=…&hash=…
	 *
	 * @param string $baseUrl provisioning base without trailing slash
	 * @param string $mac
	 * @param int    $listenerPid
	 * @return string
	 */
	public static function profileUrl($baseUrl, $mac, $listenerPid)
	{
		$mac = strtolower(preg_replace('/[^0-9a-f]/', '', (string) $mac));
		if (strlen($mac) !== 12)
		{
			return '';
		}
		$baseUrl = rtrim((string) $baseUrl, '/');
		if ($baseUrl === '')
		{
			return '';
		}
		$hash = self::hashForMac($mac, (int) $listenerPid);
		if ($hash === '')
		{
			return '';
		}
		self::rememberIssuedToken($mac, $hash);

		return $baseUrl.'/pnp.php?mac='.rawurlencode($mac).'&hash='.rawurlencode($hash);
	}

	/**
	 * @param string $mac
	 * @param string $hash
	 * @return void
	 */
	private static function rememberIssuedToken($mac, $hash)
	{
		self::ensureSubdir('issued');
		$path = self::spoolDir().'/issued/'.preg_replace('/[^a-f0-9]/', '', $hash).'.json';
		$payload = array(
			'mac' => $mac,
			'hash' => $hash,
			'issued' => time(),
			'date' => date('Y-m-d'),
			'pid' => self::readListenerPid(),
		);
		self::writeSpoolFile($path, json_encode($payload), 0644);
	}

	/**
	 * @param array<string,string> $general
	 * @return bool
	 */
	public static function isSecureUrlEnabled(array $general)
	{
		if (!isset($general['sip_pnp_secure_urls']))
		{
			return true;
		}

		return (string) $general['sip_pnp_secure_urls'] === '1';
	}

	/**
	 * @param array<string,string> $general
	 * @return int
	 */
	public static function banMaxFailures(array $general)
	{
		$n = isset($general['sip_pnp_ban_max_failures']) ? (int) $general['sip_pnp_ban_max_failures'] : self::DEFAULT_BAN_MAX_FAILURES;
		if ($n < 1)
		{
			$n = 1;
		}
		if ($n > 100)
		{
			$n = 100;
		}

		return $n;
	}

	/**
	 * @param array<string,string> $general
	 * @return int
	 */
	public static function banSeconds(array $general)
	{
		$n = isset($general['sip_pnp_ban_seconds']) ? (int) $general['sip_pnp_ban_seconds'] : self::DEFAULT_BAN_SECONDS;
		if ($n < 60)
		{
			$n = 60;
		}
		if ($n > 86400 * 7)
		{
			$n = 86400 * 7;
		}

		return $n;
	}

	/**
	 * @param string $clientIp
	 * @return bool
	 */
	public static function isIpBanned($clientIp)
	{
		$clientIp = trim((string) $clientIp);
		if ($clientIp === '' || !filter_var($clientIp, FILTER_VALIDATE_IP))
		{
			return false;
		}
		$bans = self::loadBanMap();
		if (!isset($bans[$clientIp]))
		{
			return false;
		}
		if ((int) $bans[$clientIp] > time())
		{
			return true;
		}
		unset($bans[$clientIp]);
		self::saveBanMap($bans);

		return false;
	}

	/**
	 * @param string               $mac
	 * @param string               $hash
	 * @param string               $clientIp
	 * @param array<string,string> $general
	 * @return bool
	 */
	public static function authorize($mac, $hash, $clientIp, array $general = array())
	{
		if (self::isIpBanned($clientIp))
		{
			return false;
		}
		$mac = strtolower(preg_replace('/[^0-9a-f]/', '', (string) $mac));
		$hash = strtolower(preg_replace('/[^a-f0-9]/', '', (string) $hash));
		if (strlen($mac) !== 12 || strlen($hash) < 8)
		{
			return false;
		}
		$pid = self::readListenerPid();
		if ($pid > 0 && self::hashMatches($mac, $hash, $pid, date('Y-m-d')))
		{
			self::consumeIssuedToken($hash);

			return true;
		}
		if (self::issuedTokenValid($mac, $hash))
		{
			self::consumeIssuedToken($hash);

			return true;
		}

		return false;
	}

	/**
	 * @param string $mac
	 * @param string $hash
	 * @param int    $pid
	 * @param string $date
	 * @return bool
	 */
	private static function hashMatches($mac, $hash, $pid, $date)
	{
		$expected = self::hashForMac($mac, $pid, $date);
		if ($expected !== '' && hash_equals($expected, $hash))
		{
			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	private static function serverSecret()
	{
		self::ensureSpoolDir();
		$path = self::secretFilePath();
		if (is_file($path))
		{
			$secret = trim((string) file_get_contents($path));
			if (preg_match('/^[a-f0-9]{64}$/', $secret))
			{
				return $secret;
			}
		}
		if (function_exists('random_bytes'))
		{
			$secret = bin2hex(random_bytes(32));
		}
		else
		{
			$secret = hash('sha256', uniqid('', true).mt_rand().microtime(true));
		}
		if (!self::writeSpoolFile($path, $secret."\n", 0660))
		{
			return '';
		}

		return $secret;
	}

	/**
	 * @param string $mac
	 * @param string $hash
	 * @return bool
	 */
	private static function issuedTokenValid($mac, $hash)
	{
		$path = self::spoolDir().'/issued/'.preg_replace('/[^a-f0-9]/', '', $hash).'.json';
		if (!is_file($path))
		{
			return false;
		}
		$data = json_decode((string) file_get_contents($path), true);
		if (!is_array($data))
		{
			return false;
		}

		return isset($data['mac']) && strtolower((string) $data['mac']) === $mac;
	}

	/**
	 * @param string $hash
	 * @return void
	 */
	private static function consumeIssuedToken($hash)
	{
		$path = self::spoolDir().'/issued/'.preg_replace('/[^a-f0-9]/', '', $hash).'.json';
		if (is_file($path))
		{
			@unlink($path);
		}
	}

	/**
	 * @param string               $clientIp
	 * @param array<string,string> $general
	 * @return void
	 */
	public static function recordInvalidAttempt($clientIp, array $general = array())
	{
		$clientIp = trim((string) $clientIp);
		if ($clientIp === '' || !filter_var($clientIp, FILTER_VALIDATE_IP))
		{
			return;
		}
		self::ensureSubdir('fail');
		$failPath = self::spoolDir().'/fail/'.preg_replace('/[^0-9a-fA-F:\\.]/', '_', $clientIp).'.cnt';
		$count = is_file($failPath) ? (int) file_get_contents($failPath) : 0;
		$count++;
		if (!self::writeSpoolFile($failPath, (string) $count, 0660))
		{
			return;
		}
		$max = self::banMaxFailures($general);
		if ($count >= $max)
		{
			$bans = self::loadBanMap();
			$bans[$clientIp] = time() + self::banSeconds($general);
			self::saveBanMap($bans);
			@unlink($failPath);
			if (function_exists('openlog'))
			{
				openlog('zerotouchsip-pnp', LOG_PID | LOG_PERROR, LOG_AUTH);
				syslog(LOG_WARNING, 'PnP ban IP '.$clientIp.' after '.$count.' invalid hash attempts');
				closelog();
			}
		}
	}

	/**
	 * @return array<string,int>
	 */
	private static function loadBanMap()
	{
		$path = self::spoolDir().'/banned.json';
		if (!is_file($path))
		{
			return array();
		}
		$data = json_decode((string) file_get_contents($path), true);

		return is_array($data) ? $data : array();
	}

	/**
	 * @param array<string,int> $bans
	 * @return void
	 */
	private static function saveBanMap(array $bans)
	{
		self::ensureSpoolDir();
		$path = self::spoolDir().'/banned.json';
		self::writeSpoolFile($path, json_encode($bans), 0660);
	}

	/**
	 * Web server (pnp.php) and listener share spool via group asterisk on FreePBX.
	 *
	 * @return string
	 */
	private static function spoolGroupName()
	{
		return 'asterisk';
	}

	/**
	 * @param string $subdir e.g. issued, fail
	 * @return void
	 */
	private static function ensureSubdir($subdir)
	{
		self::ensureSpoolDir();
		$path = self::spoolDir().'/'.trim($subdir, '/');
		if (!is_dir($path))
		{
			@mkdir($path, 0770, true);
		}
		self::applySpoolOwnership($path, true);
	}

	/**
	 * @return void
	 */
	private static function ensureSpoolDir()
	{
		$dir = self::spoolDir();
		if (!is_dir($dir))
		{
			@mkdir($dir, 0770, true);
		}
		self::applySpoolOwnership($dir, true);
	}

	/**
	 * @param string $path
	 * @param string $content
	 * @param int    $mode
	 * @return bool
	 */
	private static function writeSpoolFile($path, $content, $mode)
	{
		self::ensureSpoolDir();
		$dir = dirname($path);
		if ($dir !== self::spoolDir() && !is_dir($dir))
		{
			@mkdir($dir, 0770, true);
			self::applySpoolOwnership($dir, true);
		}
		if (@file_put_contents($path, $content, LOCK_EX) === false)
		{
			return false;
		}
		self::applySpoolOwnership($path, false, $mode);

		return true;
	}

	/**
	 * @param string $path
	 * @param bool   $isDir
	 * @param int    $fileMode
	 * @return void
	 */
	private static function applySpoolOwnership($path, $isDir, $fileMode = 0660)
	{
		if (PHP_OS_FAMILY !== 'Linux' || !function_exists('posix_getgrnam'))
		{
			return;
		}
		$gr = @posix_getgrnam(self::spoolGroupName());
		if (!is_array($gr) || !isset($gr['gid']))
		{
			return;
		}
		@chgrp($path, (int) $gr['gid']);
		@chmod($path, $isDir ? 0770 : $fileMode);
	}
}
