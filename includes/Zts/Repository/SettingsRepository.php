<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * zts_settings key/value store.
 */
class Zts_SettingsRepository
{
	/**
	 * @return array<string,string>
	 */
	public static function fetchAll()
	{
		$results = sql("SELECT keyword, value FROM zts_settings", 'getAll', DB_FETCHMODE_ASSOC);
		$settings = array();
		if (!is_array($results))
		{
			return $settings;
		}
		foreach ($results as $result)
		{
			$settings[$result['keyword']] = $result['value'];
		}

		return $settings;
	}

	/**
	 * @param array<string,string> $settings
	 * @return void
	 */
	public static function saveAll(array $settings)
	{
		global $db;

		$entries = array();
		foreach ($settings as $key => $val)
		{
			$entries[] = '\''.$db->escapeSimple($key).'\',\''.$db->escapeSimple($val).'\'';
		}
		if (count($entries) < 1)
		{
			return;
		}

		sql("REPLACE INTO zts_settings (keyword, value) VALUES (".implode('),(', $entries).")");
	}
}
