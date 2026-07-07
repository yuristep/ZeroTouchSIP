<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Provisioning base URLs. Generated device configs use /zerotouchsip.
 */
class Zts_ProvisioningUrlService
{
	/**
	 * Admin UI: HTTPS provisioning URL.
	 *
	 * @param string $serverName
	 * @return array{primary:string}
	 */
	public static function publicUrls($serverName)
	{
		$host = Zts_InputValidator::trimString($serverName);
		if ($host === '')
		{
			return array('primary' => '');
		}

		return array(
			'primary' => Zts_ProvisioningPaths::baseUrl('https', $host),
		);
	}

	/**
	 * Format IP for URL host (bracket IPv6).
	 *
	 * @param string $ip_literal
	 * @return string
	 */
	public static function notifyHostForUrl($ip_literal)
	{
		$ip_literal = trim((string) $ip_literal);
		if (filter_var($ip_literal, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
		{
			return $ip_literal;
		}
		if (filter_var($ip_literal, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
		{
			return '['.$ip_literal.']';
		}

		return '';
	}

	/**
	 * Host part for provisioning URLs (FQDN or bracketed IPv6).
	 *
	 * @param string $host
	 * @return string
	 */
	public static function hostLiteral($host)
	{
		$host = trim((string) $host);
		if ($host === '')
		{
			return '';
		}
		if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
		{
			return $host;
		}
		if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
		{
			return '['.$host.']';
		}

		return $host;
	}

	/**
	 * Scheme for URLs returned to the phone (manifest / redirects).
	 *
	 * @param array $network zts_get_networks_edit row
	 * @return string http|https
	 */
	public static function responseScheme($network)
	{
		if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
		{
			$p = strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
			if ($p === 'https')
			{
				return 'https';
			}
		}
		if (!empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off')
		{
			return 'https';
		}
		if (is_array($network) && isset($network['settings']['prov_protocol']) && $network['settings']['prov_protocol'] === 'HTTPS')
		{
			return 'https';
		}

		return 'http';
	}

	/**
	 * HTTP Host for provisioning base (no path), from request or SIP/FQDN network field.
	 *
	 * @param array $network
	 * @return string
	 */
	public static function requestHost($network)
	{
		$h = '';
		if (!empty($_SERVER['HTTP_X_FORWARDED_HOST']))
		{
			$h = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
		}
		elseif (!empty($_SERVER['HTTP_HOST']))
		{
			$h = trim((string) $_SERVER['HTTP_HOST']);
		}
		elseif (!empty($_SERVER['SERVER_NAME']))
		{
			$h = trim((string) $_SERVER['SERVER_NAME']);
		}
		$h = preg_replace('#^https?://#i', '', $h);
		$h = preg_replace('#/.*$#', '', $h);
		if (strpos($h, ':') !== false && preg_match('/^\[.+\]$/', $h) !== 1)
		{
			$h = preg_replace('#:\d+$#', '', $h);
		}
		if ($h !== '')
		{
			return $h;
		}
		$sip = isset($network['settings']['sip_server_address']) ? trim((string) $network['settings']['sip_server_address']) : '';
		if ($sip !== '')
		{
			return $sip;
		}

		return '';
	}

	/**
	 * Host for Yealink auto_provision.server.url in generated .cfg (network SIP address first).
	 *
	 * @param array $network
	 * @return string
	 */
	public static function yealinkConfigProvisionHost($network)
	{
		$provision_host = '';
		if (is_array($network) && !empty($network['settings']['sip_server_address']))
		{
			$provision_host = trim((string) $network['settings']['sip_server_address']);
		}
		elseif (!empty($_SERVER['HTTP_HOST']))
		{
			$provision_host = trim((string) $_SERVER['HTTP_HOST']);
		}
		elseif (!empty($_SERVER['SERVER_NAME']))
		{
			$provision_host = trim((string) $_SERVER['SERVER_NAME']);
		}
		$provision_host = preg_replace('#^https?://#i', '', $provision_host);
		$provision_host = rtrim($provision_host, '/');

		return $provision_host;
	}

	/**
	 * Yealink auto_provision.server.url written into generated MAC .cfg (primary path).
	 *
	 * @param array $network
	 * @return string e.g. https://pbx.example.com/zerotouchsip
	 */
	public static function yealinkConfigAutoProvisionUrl($network)
	{
		$host = self::yealinkConfigProvisionHost($network);
		if ($host === '')
		{
			return '';
		}
		$scheme = 'http';
		if (is_array($network) && isset($network['settings']['prov_protocol']) && $network['settings']['prov_protocol'] === 'HTTPS')
		{
			$scheme = 'https';
		}

		return $scheme.'://'.$host.'/'.Zts_ProvisioningPaths::primaryWebSegment();
	}

	/**
	 * Base URL for Fanvil manifests / redirects (respects forwarded HTTPS).
	 *
	 * @param array $network
	 * @return string
	 */
	public static function deviceBaseUrl($network)
	{
		$scheme = self::responseScheme($network);
		$host = self::hostLiteral(self::requestHost($network));
		if ($host === '')
		{
			return '';
		}

		return $scheme.'://'.$host.'/'.Zts_ProvisioningPaths::primaryWebSegment();
	}
}
