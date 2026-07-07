<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Dedicated provisioning trace log: default path, logrotate hints.
 * Sangoma FreePBX (RHEL family): /var/log/httpd/. Debian/Ubuntu: /var/log/apache2/.
 */
class Zts_ProvisioningLogConfig
{
	const FILENAME = 'zerotouchsip-provision.log';

	/**
	 * Preferred path for new installs and migrations (directory detected at runtime).
	 *
	 * @return string absolute path under /var/log
	 */
	public static function defaultFilePath()
	{
		if (@is_dir('/var/log/httpd'))
		{
			return '/var/log/httpd/'.self::FILENAME;
		}
		if (@is_dir('/var/log/apache2'))
		{
			return '/var/log/apache2/'.self::FILENAME;
		}

		return '/var/log/'.self::FILENAME;
	}

	/**
	 * One-time: migrate stored path when settings still point at an old log basename.
	 *
	 * @return string empty if no change, else new path for out() message
	 */
	public static function upgradeStoredPathIfLegacy()
	{
		global $db;

		$cur = sql("SELECT value FROM zts_settings WHERE keyword='provisioning_log_file'", 'getOne');
		if ($cur === null || $cur === false)
		{
			return '';
		}
		$curt = trim((string) $cur);
		if ($curt === '' || substr($curt, -strlen(self::FILENAME)) === self::FILENAME)
		{
			return '';
		}

		$base = dirname($curt);
		$newPath = '';
		if ($base === '/var/log/apache2' && @is_dir('/var/log/httpd'))
		{
			$newPath = '/var/log/httpd/'.self::FILENAME;
		}
		else
		{
			$newPath = $base.'/'.self::FILENAME;
		}

		if ($newPath === '' || $newPath === $curt)
		{
			return '';
		}

		$safe = '';
		if (function_exists('zts_provisioning_log_file_safe_path'))
		{
			$safe = zts_provisioning_log_file_safe_path($newPath);
		}
		else
		{
			$safe = (strpos($newPath, '/var/log/') === 0 && strpos($newPath, '..') === false) ? $newPath : '';
		}
		if ($safe === '')
		{
			return '';
		}

		sql("UPDATE zts_settings SET value='".$db->escapeSimple($safe)."' WHERE keyword='provisioning_log_file'");

		return $safe;
	}
}
