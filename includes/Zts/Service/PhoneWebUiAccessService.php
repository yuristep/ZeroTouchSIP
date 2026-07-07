<?php

// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * phones_list: direct links to phone web UI by Last IP (browser saved credentials).
 */
class Zts_PhoneWebUiAccessService
{
	/**
	 * @param array<int,array> $devices
	 * @return void
	 */
	public static function enrichListWebUiUrls(array &$devices)
	{
		foreach ($devices as $k => $device)
		{
			$lastip = isset($device['lastip']) ? (string) $device['lastip'] : '';
			$scheme = self::directWebUiSchemeForDevice($device);
			$url = $lastip !== '' ? self::directWebUiUrlLabel($lastip, $scheme) : '';
			$devices[$k]['web_ui_url'] = $url;
			$devices[$k]['web_ui_url_label'] = $url;
		}
	}

	/**
	 * Yealink handsets expose HTTPS web UI; Fanvil uses HTTP.
	 *
	 * @param array $device phones_list row (model, name, optional prov_profile / settings)
	 * @return string http|https
	 */
	public static function directWebUiSchemeForDevice(array $device)
	{
		$model = isset($device['model']) ? (string) $device['model'] : '';
		$name = isset($device['name']) ? (string) $device['name'] : '';
		$prov = '';
		if (isset($device['prov_profile']))
		{
			$prov = (string) $device['prov_profile'];
		}
		elseif (isset($device['settings']) && is_array($device['settings'])
			&& isset($device['settings']['provisioning_profile']))
		{
			$prov = (string) $device['settings']['provisioning_profile'];
		}
		if (Zts_NotifyVendorHeuristic::isFanvil($model, $name, $prov))
		{
			return 'http';
		}

		return 'https';
	}

	/**
	 * Display URL for list link and title (no credentials in URL).
	 *
	 * @param string $lastip
	 * @param string $scheme http|https
	 * @return string
	 */
	public static function directWebUiUrlLabel($lastip, $scheme = 'http')
	{
		$host = Zts_ProvisioningUrlService::notifyHostForUrl($lastip);
		if ($host === '')
		{
			return '';
		}
		$scheme = ($scheme === 'https') ? 'https' : 'http';

		return $scheme.'://'.$host.'/';
	}
}
