<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Fanvil TIM_SntpZone (quarter-hour units) and TIM_DaylightSetEnable_RW.
 */
class Zts_FanvilTimeZoneOptions
{
	/** @return array<string,string> TIM value => label */
	public static function options()
	{
		return array(
			'-48' => _('(UTC-12) International Date Line West'),
			'-44' => _('(UTC-11) American Samoa'),
			'-40' => _('(UTC-10) Hawaii-Aleutian,Alaska-Aleutian'),
			'-38' => _('(UTC-9:30) French Polynesia'),
			'-36' => _('(UTC-9) Alaska Time'),
			'-32' => _('(UTC-8) Vancouver,Whitehorse,Tijuana,Mexicali,Pacific Time'),
			'-28' => _('(UTC-7) Edmonton,Calgary,Mazatlan,Chihuahua,Mountain Time,United States-MST no DST'),
			'-24' => _('(UTC-6) Manitoba,Easter Islands,Mexico City,Acapulco,Central Time'),
			'-20' => _('(UTC-5) Nassau,Montreal,Ottawa,Quebec,Havana,Eastern Time'),
			'-18' => _('(UTC-4:30) Caracas'),
			'-16' => _('(UTC-4) Halifax,Saint John,Santiago,Asuncion,Bermuda,Falkland Islands,Trinidad Tobago'),
			'-14' => _('(UTC-3:30) New Foundland'),
			'-12' => _('(UTC-3) Nuuk,Buenos Aires,no DST,DST'),
			'-10' => _('(UTC-2:30) Newfoundland and Labrador'),
			'-8' => _('(UTC-2) no DST,Mid-Atlantic,Greenland'),
			'-4' => _('(UTC-1) Azores,Cape Verde Is'),
			'0' => _('(UTC) GMT,Torshavn,Dublin,Lisboa,Porto,Funchal,Las Palmas,London,Morocco'),
			'4' => _('(UTC+1) Tirane,Vienna,Brussels,Caicos,Chad,Madrid,Zagreb,Prague,Kopenhagen,Paris,Berlin,Budapest,Rome,Luxembourg,Skopje,Amsterdam,Windhoek,Warsaw,Stockholm'),
			'8' => _('(UTC+2) Tallinn,Helsinki,Gaza,Athens,Tel Aviv,Amman,Riga,Beirut,Kishinev,Kaliningrad,Bucharest,Damascus,Kyiv,Odessa,South Africa'),
			'12' => _('(UTC+3) East Africa Time,Baghdad,Moscow,Ankara,Istanbul'),
			'14' => _('(UTC+3:30) Tehran'),
			'16' => _('(UTC+4) Yerevan,Baku,Tbilisi,Aktau,Samara,Dubai'),
			'18' => _('(UTC+4:30) Kabul'),
			'20' => _('(UTC+5) Aqtobe,Bishkek,Islamabad,Chelyabinsk'),
			'22' => _('(UTC+5:30) Calcutta,Sri Jayawardenepura'),
			'23' => _('(UTC+5:45) Katmandu'),
			'24' => _('(UTC+6) Astana,Almaty,Omsk'),
			'26' => _('(UTC+6:30) Naypyitaw'),
			'28' => _('(UTC+7) Krasnoyarsk,Novosibirsk,Bangkok'),
			'32' => _('(UTC+8) Beijing,Taipei,Singapore,Perth,Irkutsk, Ulan-Ude'),
			'35' => _('(UTC+8:45) Eucla'),
			'36' => _('(UTC+9) Seoul,Tokyo,Yakutsk, Chita'),
			'38' => _('(UTC+9:30) Adelaide,Darwin'),
			'40' => _('(UTC+10) Sydney,Melbourne,Canberra,Brisbane,Hobart,Vladivostok'),
			'42' => _('(UTC+10:30) Lord Howe Islands'),
			'44' => _('(UTC+11) Noumea,Srednekolymsk Time'),
			'46' => _('(UTC+11:30) Norfolk Island'),
			'48' => _('(UTC+12) Wellington,Auckland,Kamchatka Time'),
			'51' => _('(UTC+12:45) Chatham Islands'),
			'52' => _('(UTC+13) Nukualofa,Apia'),
			'54' => _('(UTC+13:30) Chatham Islands'),
			'56' => _('(UTC+14) Kiribati'),
		);
	}

	/** @return string */
	public static function defaultValue()
	{
		return '12';
	}

	/**
	 * @param string $timValue
	 * @return string
	 */
	public static function labelForValue($timValue)
	{
		$opts = self::options();
		$key = (string) $timValue;

		return isset($opts[$key]) ? $opts[$key] : '';
	}

	/**
	 * Fanvil VOIP file "Time Zone" hour offset from TIM_SntpZone.
	 *
	 * @param string|int $timValue
	 * @return string
	 */
	public static function cfgHoursFromTim($timValue)
	{
		$tim = (int) $timValue;
		if ($tim === 23)
		{
			return '5:45';
		}
		if ($tim === 35)
		{
			return '8:45';
		}
		if ($tim === 0)
		{
			return '0';
		}
		$neg = $tim < 0;
		$abs = abs($tim);
		$h = intdiv($abs, 4);
		$q = $abs % 4;
		$fraction = ($q === 2) ? ':30' : '';
		$body = (string) $h.$fraction;
		if ($neg)
		{
			return '-'.$body;
		}

		return $body;
	}

	/**
	 * @param array<string,string> $settings
	 * @return string TIM value
	 */
	public static function selectedFromSettings(array $settings)
	{
		if (isset($settings['time_zone_fanvil']) && (string) $settings['time_zone_fanvil'] !== '')
		{
			$key = (string) $settings['time_zone_fanvil'];
			if (isset(self::options()[$key]))
			{
				return $key;
			}
		}
		$legacyHours = isset($settings['time_zone']) ? trim((string) $settings['time_zone']) : '';
		if ($legacyHours !== '' && preg_match('/^[+-]?\d+(?::\d+)?$/', $legacyHours))
		{
			foreach (self::options() as $tim => $label)
			{
				if (self::cfgHoursFromTim($tim) === $legacyHours)
				{
					return (string) $tim;
				}
			}
			if (preg_match('/^[+-]?(\d+)$/', $legacyHours, $m))
			{
				$timGuess = (int) $m[1] * 4;
				if ($legacyHours[0] === '-')
				{
					$timGuess = -$timGuess;
				}
				elseif ($legacyHours[0] !== '+')
				{
					$timGuess = (int) $legacyHours * 4;
				}
				$key = (string) $timGuess;
				if (isset(self::options()[$key]))
				{
					return $key;
				}
			}
		}

		return self::defaultValue();
	}

	/** @return array<string,string> */
	public static function daylightSavingChoices()
	{
		return array(
			'0' => _('Disabled'),
			'1' => _('Automatic'),
			'2' => _('Manual'),
		);
	}

	/** @return string */
	public static function defaultDaylightSaving()
	{
		return '1';
	}
}
