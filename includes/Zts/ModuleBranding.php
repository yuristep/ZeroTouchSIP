<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Neutral module branding (display only). System id: {@see Zts_ModuleIdentifiers::RAWNAME}.
 */
class Zts_ModuleBranding
{
	const DISPLAY_NAME = 'ZeroTouchSIP';
	const MENU_LABEL = 'ZeroTouchSIP';
	const DESCRIPTION = 'Configure and manage Yealink/Fanvil SIP phones on your FreePBX system.';
	const INTRO_HINT = 'Use the tabs below to open Phones, Networks, or General Settings.';
	const PUBLISHER = 'YURI STEP. — Net Production —';
	const REPO_URL = 'https://github.com/yuristep/ZeroTouchSIP.git';
	const LICENSE_LABEL = 'GPLv3+';
	const LICENSE_URL = 'https://www.gnu.org/licenses/gpl-3.0.txt';

	/** @deprecated Legacy session key — do not rename without migration. */
	const LEGACY_NOTIFY_SESSION_KEY = 'zts_notify_results';

	/**
	 * Prefix for Apache / PHP error_log trace lines (grep-friendly).
	 *
	 * @param string $scope e.g. Router, Boot, Config, Prov
	 * @return string e.g. [ZeroTouchSIP Router]
	 */
	public static function logTag($scope)
	{
		return '['.self::DISPLAY_NAME.' '.trim((string) $scope).']';
	}

	public static function displayName()
	{
		return _(self::DISPLAY_NAME);
	}

	public static function menuLabel()
	{
		return _(self::MENU_LABEL);
	}

	public static function description()
	{
		return _('Configure and manage Yealink/Fanvil SIP phones on your FreePBX system.');
	}

	public static function introHint()
	{
		return _('Use the tabs below to open Phones, Networks, or General Settings.');
	}

	/**
	 * module.xml fields for About / Publisher UI (cached).
	 *
	 * @return array{publisher:string,version:string,name:string,license:string,licenselink:string,more_info:string}
	 */
	public static function moduleManifest()
	{
		static $cache = null;
		if ($cache !== null)
		{
			return $cache;
		}
		$cache = array(
			'publisher' => self::PUBLISHER,
			'version' => '',
			'name' => self::DISPLAY_NAME,
			'license' => self::LICENSE_LABEL,
			'licenselink' => self::LICENSE_URL,
			'more_info' => self::REPO_URL,
		);
		$xmlFile = dirname(__DIR__, 2).'/module.xml';
		if (!is_readable($xmlFile))
		{
			return $cache;
		}
		$xml = @simplexml_load_file($xmlFile);
		if (!$xml)
		{
			return $cache;
		}
		foreach (array('publisher', 'version', 'name', 'license') as $key)
		{
			if (isset($xml->{$key}) && trim((string) $xml->{$key}) !== '')
			{
				$cache[$key] = trim((string) $xml->{$key});
			}
		}
		if (isset($xml->{'more-info'}) && trim((string) $xml->{'more-info'}) !== '')
		{
			$cache['more_info'] = trim((string) $xml->{'more-info'});
		}
		if (isset($xml->licenselink) && trim((string) $xml->licenselink) !== '')
		{
			$cache['licenselink'] = trim((string) $xml->licenselink);
		}

		return $cache;
	}
}
