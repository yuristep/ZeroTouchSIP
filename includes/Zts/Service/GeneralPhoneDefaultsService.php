<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Applies General Settings → Default Phone Settings to newly discovered phones (no line bindings).
 */
class Zts_GeneralPhoneDefaultsService
{
	const FLAG_APPLIED = 'general_defaults_applied';

	/**
	 * @param array<string,string> $general
	 * @return array<string,string> device_settings keywords
	 */
	public static function deviceSettingsFromGeneral(array $general)
	{
		$security = Zts_GeneralPhoneSecurityService::rowsFromGeneral($general);
		$admin = $security[0];
		$out = array(
			self::FLAG_APPLIED => '1',
			'provisioning_profile' => isset($general['default_provisioning_profile'])
				? (string) $general['default_provisioning_profile'] : $admin['profile'],
			'phone_backlight_time' => isset($general['default_backlight_time']) ? (string) $general['default_backlight_time'] : '60',
			'phone_lang' => isset($general['default_lang']) ? (string) $general['default_lang'] : 'English',
		);
		foreach (Zts_GeneralTimeSettingsMapper::timeSlice($general) as $key => $val)
		{
			$out[$key] = $val;
		}

		return $out;
	}

	/**
	 * Device-specific default time (after applyToDeviceIfEligible) overrides network SNTP/time zone.
	 *
	 * @param array|null $network
	 * @param array      $device
	 * @return array
	 */
	public static function overlayDeviceTimeOnNetwork($network, array $device)
	{
		if (!is_array($network))
		{
			return array('settings' => array());
		}
		if (!isset($network['settings']) || !is_array($network['settings']))
		{
			$network['settings'] = array();
		}
		if (!isset($device['settings'][self::FLAG_APPLIED]) || (string) $device['settings'][self::FLAG_APPLIED] !== '1')
		{
			return $network;
		}
		$timeKeys = array(
			'ntp_server1',
			'ntp_server2',
			'time_zone',
			'time_zone_fanvil',
			'time_zone_name',
			'daylight_saving_time',
			'daylight_saving_time_fanvil',
		);
		foreach ($timeKeys as $key)
		{
			if (isset($device['settings'][$key]) && (string) $device['settings'][$key] !== '')
			{
				$network['settings'][$key] = (string) $device['settings'][$key];
			}
		}

		return $network;
	}

	/**
	 * @param array $device zts_devices row with lines
	 * @return bool
	 */
	public static function deviceHasLineBindings(array $device)
	{
		if (!isset($device['lines']) || !is_array($device['lines']))
		{
			return false;
		}
		foreach ($device['lines'] as $line)
		{
			if (isset($line['deviceid']) && $line['deviceid'] !== null && (string) $line['deviceid'] !== '')
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string|int $deviceId
	 * @return bool true if settings were written
	 */
	public static function applyToDeviceIfEligible($deviceId)
	{
		$deviceId = Zts_InputValidator::trimString($deviceId);
		if ($deviceId === '')
		{
			return false;
		}

		$device = Zts_DeviceRepository::findForEdit($deviceId);
		if (self::deviceHasLineBindings($device))
		{
			return false;
		}
		if (isset($device['settings'][self::FLAG_APPLIED]) && (string) $device['settings'][self::FLAG_APPLIED] === '1')
		{
			return false;
		}

		$general = zts_get_general_edit();
		$toWrite = self::deviceSettingsFromGeneral($general);
		global $db;
		foreach ($toWrite as $keyword => $value)
		{
			sql("REPLACE INTO zts_device_settings (id, keyword, value) VALUES ('".
				$db->escapeSimple($deviceId)."','".$db->escapeSimple($keyword)."','".$db->escapeSimple($value)."')");
		}

		return true;
	}

	/**
	 * Merge defaults into empty device form (Add Phone).
	 *
	 * @param array $device
	 * @return array
	 */
	public static function mergeIntoNewDeviceForm(array $device)
	{
		$general = zts_get_general_edit();
		$defaults = self::deviceSettingsFromGeneral($general);
		if (!isset($device['settings']) || !is_array($device['settings']))
		{
			$device['settings'] = array();
		}
		foreach ($defaults as $key => $val)
		{
			if (!isset($device['settings'][$key]) || (string) $device['settings'][$key] === '')
			{
				$device['settings'][$key] = $val;
			}
		}

		return $device;
	}
}
