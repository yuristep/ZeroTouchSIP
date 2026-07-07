<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Maps unified network time UI values to Yealink and Fanvil provisioning fields.
 */
class Zts_NetworkTimeSettingsMapper
{
	/** @return array<string,string> UI time_zone value (Fanvil TIM) => label */
	public static function uiTimeZoneOptions()
	{
		return Zts_FanvilTimeZoneOptions::options();
	}

	/**
	 * @param array<string,string> $settings
	 * @return string Fanvil TIM value for &lt;select name="time_zone"&gt;
	 */
	public static function uiTimeZoneSelected(array $settings)
	{
		return Zts_FanvilTimeZoneOptions::selectedFromSettings($settings);
	}

	/**
	 * @param array<string,string> $settings
	 * @param string              $timUi Fanvil TIM_SntpZone from form
	 * @return array<string,string>
	 */
	public static function applyTimeZoneUiValue(array $settings, $timUi)
	{
		$tim = trim((string) $timUi);
		if ($tim === '' || !isset(Zts_FanvilTimeZoneOptions::options()[$tim]))
		{
			$tim = Zts_FanvilTimeZoneOptions::defaultValue();
		}
		$settings['time_zone_fanvil'] = $tim;
		$settings['time_zone'] = self::yealinkTimeZoneFromFanvilTim($tim);
		$label = Zts_FanvilTimeZoneOptions::labelForValue($tim);
		if ($label !== '')
		{
			$settings['time_zone_name'] = $label;
		}

		return $settings;
	}

	/**
	 * Yealink local_time.time_zone (-12..+14); half-hour Fanvil zones map to whole hour.
	 *
	 * @param string|int $fanvilTim
	 * @return string
	 */
	public static function yealinkTimeZoneFromFanvilTim($fanvilTim)
	{
		$hours = Zts_FanvilTimeZoneOptions::cfgHoursFromTim($fanvilTim);
		if (preg_match('/^([+-]?)(\d+)$/', $hours, $m))
		{
			$v = (int) $m[2];
			if ($m[1] === '-')
			{
				$v = -$v;
			}
			if ($v < -12)
			{
				$v = -12;
			}
			if ($v > 14)
			{
				$v = 14;
			}

			return (string) $v;
		}
		if (preg_match('/^([+-]?)(\d+):30$/', $hours, $m))
		{
			$v = (int) $m[2];
			if ($m[1] === '-')
			{
				$v = -$v;
			}
			if ($v < -12)
			{
				$v = -12;
			}
			if ($v > 14)
			{
				$v = 14;
			}

			return (string) $v;
		}

		return Zts_YealinkTimeZoneOptions::defaultValue();
	}

	/** @return array<string,string> */
	public static function uiDaylightSavingOptions()
	{
		return Zts_YealinkTimeZoneOptions::daylightSavingChoices();
	}

	/**
	 * @param array<string,string> $settings
	 * @return string Yealink summer_time value for UI
	 */
	public static function uiDaylightSavingSelected(array $settings)
	{
		$yealink = isset($settings['daylight_saving_time']) ? (string) $settings['daylight_saving_time'] : '';
		if (in_array($yealink, array('0', '1', '2'), true))
		{
			return $yealink;
		}
		$fanvil = isset($settings['daylight_saving_time_fanvil']) ? (string) $settings['daylight_saving_time_fanvil'] : '';

		return self::yealinkDaylightFromFanvil($fanvil);
	}

	/**
	 * Fanvil: 0 Disabled, 1 Automatic, 2 Manual.
	 * Yealink: 0 Disabled, 1 Enabled, 2 Automatic.
	 *
	 * @param string $fanvilDst
	 * @return string
	 */
	public static function yealinkDaylightFromFanvil($fanvilDst)
	{
		switch ((string) $fanvilDst)
		{
			case '0':
				return '0';
			case '1':
				return '2';
			case '2':
				return '1';
			default:
				return Zts_YealinkTimeZoneOptions::defaultDaylightSaving();
		}
	}

	/**
	 * @param string $yealinkDst
	 * @return string
	 */
	public static function fanvilDaylightFromYealink($yealinkDst)
	{
		switch ((string) $yealinkDst)
		{
			case '0':
				return '0';
			case '1':
				return '2';
			case '2':
				return '1';
			default:
				return Zts_FanvilTimeZoneOptions::defaultDaylightSaving();
		}
	}

	/**
	 * @param array<string,string> $settings
	 * @param string              $uiDst Yealink semantic from form (0/1/2)
	 * @return array<string,string>
	 */
	public static function applyDaylightSavingUiValue(array $settings, $uiDst)
	{
		$yealink = in_array((string) $uiDst, array('0', '1', '2'), true)
			? (string) $uiDst
			: Zts_YealinkTimeZoneOptions::defaultDaylightSaving();
		$settings['daylight_saving_time'] = $yealink;
		$settings['daylight_saving_time_fanvil'] = self::fanvilDaylightFromYealink($yealink);

		return $settings;
	}

	/**
	 * @param array<string,string> $settings
	 * @param array              $post
	 * @return array<string,string>
	 */
	public static function applyFromPost(array $settings, array $post)
	{
		$tim = isset($post['time_zone']) ? (string) $post['time_zone'] : '';
		$settings = self::applyTimeZoneUiValue($settings, $tim);
		$dst = isset($post['daylight_saving_time']) ? (string) $post['daylight_saving_time'] : '';

		return self::applyDaylightSavingUiValue($settings, $dst);
	}
}
