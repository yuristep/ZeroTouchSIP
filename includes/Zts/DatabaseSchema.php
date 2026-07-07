<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * ZeroTouchSIP database table names (prefix zts_).
 */
class Zts_DatabaseSchema
{
	const SETTINGS = 'zts_settings';
	const NETWORKS = 'zts_networks';
	const NETWORK_SETTINGS = 'zts_network_settings';
	const DEVICES = 'zts_devices';
	const DEVICE_SETTINGS = 'zts_device_settings';
	const DEVICE_LINES = 'zts_device_lines';
	const DEVICE_LINE_SETTINGS = 'zts_device_line_settings';
	const DEVICE_LINEKEYS = 'zts_device_linekeys';

	/**
	 * @return string[]
	 */
	public static function allCurrentTables()
	{
		return array(
			self::SETTINGS,
			self::NETWORKS,
			self::NETWORK_SETTINGS,
			self::DEVICES,
			self::DEVICE_SETTINGS,
			self::DEVICE_LINES,
			self::DEVICE_LINE_SETTINGS,
			self::DEVICE_LINEKEYS,
		);
	}

	/**
	 * True when install.php should not CREATE TABLE.
	 *
	 * @return bool
	 */
	public static function schemaAlreadyPresent()
	{
		return self::tableExists(self::DEVICES);
	}

	/**
	 * @param string $table
	 * @return bool
	 */
	public static function tableExists($table)
	{
		global $db;
		$table = trim((string) $table);
		if ($table === '')
		{
			return false;
		}
		$row = sql("SHOW TABLES LIKE '".$db->escapeSimple($table)."'", 'getOne');

		return $row === $table;
	}

	const UTF8MB4_COLLATION = 'utf8mb4_unicode_ci';

	/**
	 * Ensure zts_* tables store UTF-8 (Line Key labels, Wi-Fi JSON, device names, etc.).
	 *
	 * @return int number of tables converted
	 */
	public static function migrateUtf8TextStorageIfNeeded()
	{
		if (!self::tableExists(self::SETTINGS))
		{
			return 0;
		}

		$col = sql("SHOW FULL COLUMNS FROM `".self::SETTINGS."` WHERE Field='value'", 'getRow', DB_FETCHMODE_ASSOC);
		$collation = is_array($col) && isset($col['Collation']) ? strtolower((string) $col['Collation']) : '';
		if ($collation !== '' && strpos($collation, 'utf8') !== false)
		{
			return 0;
		}

		$converted = 0;
		foreach (self::allCurrentTables() as $table)
		{
			if (!self::tableExists($table))
			{
				continue;
			}
			sql('ALTER TABLE `'.$table.'` CONVERT TO CHARACTER SET utf8mb4 COLLATE '.self::UTF8MB4_COLLATION);
			$converted++;
			self::log('Converted '.$table.' to utf8mb4.');
		}

		if ($converted > 0 && self::tableExists(self::SETTINGS))
		{
			$type = sql("SHOW COLUMNS FROM `".self::SETTINGS."` WHERE Field='value'", 'getRow', DB_FETCHMODE_ASSOC);
			if (is_array($type) && isset($type['Type']) && stripos((string) $type['Type'], 'varchar(255)') !== false)
			{
				sql('ALTER TABLE `'.self::SETTINGS.'` MODIFY `value` MEDIUMTEXT NOT NULL');
				self::log('zts_settings.value widened to MEDIUMTEXT.');
			}
		}

		return $converted;
	}

	/**
	 * @param string $message
	 * @return void
	 */
	private static function log($message)
	{
		if (function_exists('out'))
		{
			out($message);
		}
	}
}
