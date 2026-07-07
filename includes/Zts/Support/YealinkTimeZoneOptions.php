<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Yealink local_time.time_zone (-12..+14) and local_time.summer_time.
 */
class Zts_YealinkTimeZoneOptions
{
	/** @return array<string,string> offset => label */
	public static function options()
	{
		$out = array();
		for ($i = -12; $i <= 14; $i++)
		{
			$key = (string) $i;
			$label = ($i === 0)
				? '(UTC) GMT'
				: '(UTC'.($i > 0 ? '+' : '').$i.')';
			$out[$key] = $label;
		}
		$out['-12'] = '(UTC-12) International Date Line West';
		$out['-11'] = '(UTC-11) American Samoa';
		$out['-10'] = '(UTC-10) Hawaii-Aleutian';
		$out['-9'] = '(UTC-9) Alaska Time';
		$out['-8'] = '(UTC-8) Pacific Time';
		$out['-7'] = '(UTC-7) Mountain Time';
		$out['-6'] = '(UTC-6) Central Time';
		$out['-5'] = '(UTC-5) Eastern Time';
		$out['-4'] = '(UTC-4) Atlantic Time';
		$out['-3'] = '(UTC-3) Buenos Aires';
		$out['-2'] = '(UTC-2) Mid-Atlantic';
		$out['-1'] = '(UTC-1) Azores';
		$out['0'] = '(UTC) GMT, London';
		$out['1'] = '(UTC+1) Central Europe';
		$out['2'] = '(UTC+2) Eastern Europe';
		$out['3'] = '(UTC+3) Moscow, East Africa';
		$out['4'] = '(UTC+4) Dubai, Samara';
		$out['5'] = '(UTC+5) Islamabad';
		$out['6'] = '(UTC+6) Almaty';
		$out['7'] = '(UTC+7) Bangkok';
		$out['8'] = '(UTC+8) Beijing, Singapore';
		$out['9'] = '(UTC+9) Tokyo, Seoul';
		$out['10'] = '(UTC+10) Sydney';
		$out['11'] = '(UTC+11) Magadan';
		$out['12'] = '(UTC+12) Auckland';
		$out['13'] = '(UTC+13) Samoa';
		$out['14'] = '(UTC+14) Kiribati';

		return $out;
	}

	/** @return string */
	public static function defaultValue()
	{
		return '3';
	}

	/**
	 * @param array<string,string> $settings
	 * @return string
	 */
	public static function selectedFromSettings(array $settings)
	{
		if (isset($settings['time_zone']) && (string) $settings['time_zone'] !== '')
		{
			$key = (string) $settings['time_zone'];
			if (isset(self::options()[$key]))
			{
				return $key;
			}
			if (preg_match('/^[+-]?(\d+)$/', $key, $m))
			{
				$norm = (string) (int) $m[1];
				if ($key[0] === '-')
				{
					$norm = '-'.$norm;
				}
				if (isset(self::options()[$norm]))
				{
					return $norm;
				}
			}
		}
		$fanvilTim = Zts_FanvilTimeZoneOptions::selectedFromSettings($settings);
		$hours = Zts_FanvilTimeZoneOptions::cfgHoursFromTim($fanvilTim);
		if (preg_match('/^[+-]?(\d+)$/', $hours, $m))
		{
			$norm = (string) (int) $m[1];
			if ($hours[0] === '-')
			{
				$norm = '-'.$norm;
			}
			if (isset(self::options()[$norm]))
			{
				return $norm;
			}
		}

		return self::defaultValue();
	}

	/** @return array<string,string> */
	public static function daylightSavingChoices()
	{
		return array(
			'0' => _('Disabled'),
			'1' => _('Enabled'),
			'2' => _('Automatic'),
		);
	}

	/** @return string */
	public static function defaultDaylightSaving()
	{
		return '2';
	}
}
