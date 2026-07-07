<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Fanvil HTTP ConfigManApp autoprovision (supplement to SIP NOTIFY check-sync).
 */
class Zts_FanvilHttpNotifyService
{
	/**
	 * @return array<int,array{method:string,path:string,body:?string}>
	 */
	public static function autoprovisionAttempts()
	{
		$attempts = array();
		$bases = array('/cgi-bin/ConfigManApp.com', '/cgi-bin/ConfigManApp');
		$post_bodies = array(
			'key=Autoprovision&Request=Autoprovision',
			'key=Autoprovision&request=Autoprovision',
			'key=Autoprovision&Apply=Apply',
			'key=Autoprovision&Submit=Autoprovision',
			'Request=Autoprovision&key=Autoprovision',
			'key=Autoprovision&action=Autoprovision',
			'key=Autoprovision&cmd=download',
		);
		foreach ($bases as $base)
		{
			foreach ($post_bodies as $body)
			{
				$attempts[] = array('method' => 'POST', 'path' => $base, 'body' => $body);
			}
			$attempts[] = array('method' => 'POST', 'path' => $base.'?key=Autoprovision', 'body' => 'request=1');
			$attempts[] = array('method' => 'POST', 'path' => $base.'?key=Autoprovision', 'body' => '');
			$attempts[] = array('method' => 'GET', 'path' => $base.'?key=Autoprovision&Request=Autoprovision', 'body' => null);
			$attempts[] = array('method' => 'GET', 'path' => $base.'?key=Autoprovision&request=Autoprovision', 'body' => null);
		}

		return $attempts;
	}

	/**
	 * @param string          $ip_literal
	 * @param string          $admin_password
	 * @param string|int|bool $trust_all_certs
	 * @param string          $admin_user      Web UI login (default admin; network MMI may rename)
	 * @return string status token
	 */
	public static function runAutoprovision($ip_literal, $admin_password, $trust_all_certs, $admin_user = 'admin')
	{
		if (trim((string) $admin_password) === '')
		{
			return 'skipped_no_credentials';
		}
		$host = Zts_ProvisioningUrlService::notifyHostForUrl($ip_literal);
		if ($host === '')
		{
			return 'invalid_ip';
		}
		$verify = !($trust_all_certs === '1' || $trust_all_certs === 1 || $trust_all_certs === true);
		$user = trim((string) $admin_user);
		if ($user === '')
		{
			$user = 'admin';
		}
		$userpwd = $user.':'.$admin_password;

		if (!function_exists('curl_init'))
		{
			return 'no_curl';
		}

		$last = 'curl_no_success';
		foreach (array('http', 'https') as $scheme)
		{
			$referer = $scheme.'://'.$host.'/cgi-bin/ConfigManApp.com?key=Autoprovision';
			foreach (self::autoprovisionAttempts() as $att)
			{
				$method = $att['method'];
				$path = $att['path'];
				$body = $att['body'];
				$url = $scheme.'://'.$host.$path;
				$ch = curl_init($url);
				$opts = array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CONNECTTIMEOUT => 5,
					CURLOPT_TIMEOUT => 15,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_MAXREDIRS => 5,
					CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
					CURLOPT_USERPWD => $userpwd,
					CURLOPT_USERAGENT => 'FreePBX-'.Zts_ModuleBranding::DISPLAY_NAME.'/1.0 (Fanvil Autoprovision)',
					CURLOPT_SSL_VERIFYPEER => $verify ? 1 : 0,
					CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
					CURLOPT_HTTPHEADER => array('Referer: '.$referer),
				);
				if ($method === 'POST')
				{
					$opts[CURLOPT_POST] = true;
					$opts[CURLOPT_POSTFIELDS] = (string) $body;
					$opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
				}
				else
				{
					$opts[CURLOPT_HTTPGET] = true;
				}
				curl_setopt_array($ch, $opts);
				curl_exec($ch);
				$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$errno = curl_errno($ch);
				$errstr = curl_error($ch);
				curl_close($ch);

				if ($code >= 200 && $code < 300)
				{
					$tag = $method.' '.$path;

					return 'ok_'.$scheme.'_'.$code.'|'.$tag;
				}
				if ($code === 401)
				{
					$last = 'http_401';
				}
				elseif ($code === 403)
				{
					$last = 'http_403';
				}
				elseif ($code > 0)
				{
					$last = $scheme.'_http_'.$code;
				}
				elseif ($errno !== 0)
				{
					$last = 'curl_'.$errno.'_'.preg_replace('/\s+/', '_', substr(trim($errstr), 0, 40));
				}
			}
		}

		return $last;
	}
}
