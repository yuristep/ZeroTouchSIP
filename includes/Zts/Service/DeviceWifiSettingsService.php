<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Per-phone Wi-Fi provisioning flags and selected network profile (SSID).
 */
class Zts_DeviceWifiSettingsService
{
	const KEY_ENABLE = 'wifi_enable';
	const KEY_PUSH = 'wifi_push';
	const KEY_PROFILE_SSID = 'wifi_profile_ssid';

	/**
	 * @return array<string,string>
	 */
	public static function defaultSettings()
	{
		return array(
			self::KEY_ENABLE => '0',
			self::KEY_PUSH => '0',
			self::KEY_PROFILE_SSID => '',
		);
	}

	/**
	 * @param array<string,string> $settings
	 * @return bool
	 */
	public static function isEnabled(array $settings)
	{
		return trim((string) (isset($settings[self::KEY_ENABLE]) ? $settings[self::KEY_ENABLE] : '0')) === '1';
	}

	/**
	 * @param array<string,string> $settings
	 * @return bool
	 */
	public static function shouldPush(array $settings)
	{
		if (!self::isEnabled($settings))
		{
			return false;
		}

		return trim((string) (isset($settings[self::KEY_PUSH]) ? $settings[self::KEY_PUSH] : '0')) === '1';
	}

	/**
	 * @param array<string,string> $settings
	 * @return string
	 */
	public static function selectedSsid(array $settings)
	{
		return trim((string) (isset($settings[self::KEY_PROFILE_SSID]) ? $settings[self::KEY_PROFILE_SSID] : ''));
	}

	/**
	 * @param array $post
	 * @return array<string,string>
	 */
	public static function parseFromPost(array $post)
	{
		return array(
			self::KEY_ENABLE => !empty($post[self::KEY_ENABLE]) ? '1' : '0',
			self::KEY_PUSH => !empty($post[self::KEY_PUSH]) ? '1' : '0',
			self::KEY_PROFILE_SSID => isset($post[self::KEY_PROFILE_SSID])
				? trim((string) $post[self::KEY_PROFILE_SSID]) : '',
		);
	}

	/**
	 * @param array $device zts_devices row
	 * @return array|null network edit row
	 */
	public static function resolveNetworkForDevice(array $device)
	{
		$lastip = isset($device['lastip']) ? trim((string) $device['lastip']) : '';
		if ($lastip !== '' && function_exists('zts_get_networks_ip'))
		{
			$network = zts_get_networks_ip($lastip);
			if (is_array($network))
			{
				return $network;
			}
		}

		return Zts_NetworkRepository::findForEdit('-1');
	}

	/**
	 * @param array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}> $profiles
	 * @return array<int,array{value:string,label:string}>
	 */
	public static function profileSelectOptions(array $profiles)
	{
		$out = array();
		foreach ($profiles as $profile)
		{
			$ssid = isset($profile['ssid']) ? trim((string) $profile['ssid']) : '';
			if ($ssid === '')
			{
				continue;
			}
			$label = isset($profile['label']) ? trim((string) $profile['label']) : '';
			$display = ($label !== '') ? $label.' ('.$ssid.')' : $ssid;
			$out[] = array('value' => $ssid, 'label' => $display);
		}

		return $out;
	}

	/**
	 * @param array $device
	 * @param bool $isNew
	 * @return bool
	 */
	public static function showWifiSection(array $device, $isNew)
	{
		if ($isNew)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param array<string,string> $deviceSettings
	 * @param array<string,string> $networkSettings
	 * @return array{ok:bool,errors:string[]}
	 */
	public static function validate(array $deviceSettings, array $networkSettings)
	{
		$errors = array();
		if (self::shouldPush($deviceSettings) && !self::isEnabled($deviceSettings))
		{
			$errors[] = _('Enable Wi-Fi provisioning when pushing settings to the phone.');
		}
		if (self::shouldPush($deviceSettings))
		{
			$profiles = Zts_NetworkWifiProfileService::fromSettings($networkSettings);
			if (count($profiles) > 0 && self::selectedSsid($deviceSettings) === '')
			{
				$errors[] = _('Select a Wi-Fi profile (SSID) from the provisioning network.');
			}
			elseif (self::selectedSsid($deviceSettings) !== '')
			{
				$found = false;
				foreach ($profiles as $profile)
				{
					if ($profile['ssid'] === self::selectedSsid($deviceSettings))
					{
						$found = true;
						break;
					}
				}
				if (!$found)
				{
					$errors[] = _('Selected Wi-Fi profile is not defined on the provisioning network.');
				}
			}
		}

		return array('ok' => count($errors) < 1, 'errors' => $errors);
	}
}
