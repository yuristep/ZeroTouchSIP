<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Fanvil VOIP cfg first-line Version token (Current Config Version on phone).
 */
class Zts_FanvilConfigVersionService
{
	const SETTING_KEY = 'fanvil_config_version';

	const DEFAULT_VERSION = '2.0004';

	/**
	 * @param string $version
	 * @return bool
	 */
	public static function isValid($version)
	{
		$version = trim((string) $version);

		return $version !== '' && preg_match('/^\d{1,2}\.\d{1,4}$/', $version) === 1;
	}

	/**
	 * @param string $version
	 * @return string
	 */
	public static function normalize($version)
	{
		$version = trim((string) $version);
		if (self::isValid($version))
		{
			return $version;
		}

		return self::DEFAULT_VERSION;
	}

	/**
	 * @param array|null $network zts_get_networks_edit row
	 * @return string
	 */
	public static function forNetwork($network)
	{
		if (!is_array($network) || empty($network['settings']) || !is_array($network['settings']))
		{
			return self::DEFAULT_VERSION;
		}
		$raw = isset($network['settings'][self::SETTING_KEY]) ? (string) $network['settings'][self::SETTING_KEY] : '';

		return self::normalize($raw);
	}
}
