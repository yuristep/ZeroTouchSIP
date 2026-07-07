<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Default time settings in General Settings (same semantics as Network Time Settings).
 */
class Zts_GeneralTimeSettingsMapper
{
	/** @return string[] */
	public static function storageKeys()
	{
		return array(
			'default_ntp_server1',
			'default_ntp_server2',
			'default_time_zone',
			'default_time_zone_fanvil',
			'default_time_zone_name',
			'default_daylight_saving_time',
			'default_daylight_saving_time_fanvil',
		);
	}

	/**
	 * @param array<string,string> $general
	 * @return array<string,string> keys without default_ prefix for NetworkTimeSettingsMapper
	 */
	public static function timeSlice(array $general)
	{
		$slice = array();
		foreach (self::storageKeys() as $key)
		{
			if (isset($general[$key]))
			{
				$slice[substr($key, 8)] = $general[$key];
			}
		}
		if (!isset($slice['ntp_server1']) && isset($general['default_ntp_server']))
		{
			$slice['ntp_server1'] = (string) $general['default_ntp_server'];
		}
		if (!isset($slice['ntp_server2']))
		{
			$slice['ntp_server2'] = '';
		}

		return $slice;
	}

	/**
	 * @param array<string,string> $general
	 * @param array              $post
	 * @return array<string,string>
	 */
	public static function applyFromPost(array $general, array $post)
	{
		$mapped = array();
		if (isset($post['default_time_zone']))
		{
			$mapped['time_zone'] = (string) $post['default_time_zone'];
		}
		if (isset($post['default_daylight_saving_time']))
		{
			$mapped['daylight_saving_time'] = (string) $post['default_daylight_saving_time'];
		}
		foreach (array('ntp_server1', 'ntp_server2') as $ntpKey)
		{
			$postKey = 'default_'.$ntpKey;
			if (isset($post[$postKey]))
			{
				$mapped[$ntpKey] = (string) $post[$postKey];
			}
		}

		$time = Zts_NetworkTimeSettingsMapper::applyFromPost(array(), $mapped);
		foreach ($time as $key => $val)
		{
			$general['default_'.$key] = $val;
		}
		unset($general['default_ntp_server'], $general['default_time_zone_offset']);

		return $general;
	}
}
