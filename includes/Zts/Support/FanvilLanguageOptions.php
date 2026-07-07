<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Map ZeroTouchSIP phone language labels to Fanvil cfg values (family-specific).
 */
class Zts_FanvilLanguageOptions
{
	/** @var array<string,string> ZTS label => Fanvil ISO (H6/W611 Default Language) */
	private static $iso = array(
		'English' => 'en',
		'Russian' => 'ru',
		'Spanish' => 'es',
		'French' => 'fr',
		'German' => 'de',
		'Italian' => 'it',
		'Portuguese' => 'pt',
	);

	/** @var array<string,string> ZTS label => Fanvil H5 Language numeric */
	private static $h5Numeric = array(
		'English' => '0',
		'Russian' => '6',
		'Spanish' => '1',
		'French' => '2',
		'German' => '3',
		'Italian' => '4',
		'Portuguese' => '5',
	);

	/**
	 * @param string $family Zts_FanvilDeviceConfigService::FAMILY_*
	 * @param string $phoneLang device phone_lang or ''
	 * @param string $globalDefault default_lang from General Settings
	 * @return string
	 */
	public static function cfgValue($family, $phoneLang, $globalDefault = 'English')
	{
		$zts = trim((string) $phoneLang);
		if ($zts === '')
		{
			$zts = trim((string) $globalDefault);
		}
		if ($zts === '')
		{
			$zts = 'English';
		}

		if ($family === Zts_FanvilDeviceConfigService::FAMILY_H5)
		{
			return isset(self::$h5Numeric[$zts]) ? self::$h5Numeric[$zts] : '0';
		}
		if ($family === Zts_FanvilDeviceConfigService::FAMILY_H2)
		{
			return '0';
		}

		return isset(self::$iso[$zts]) ? self::$iso[$zts] : 'en';
	}
}
