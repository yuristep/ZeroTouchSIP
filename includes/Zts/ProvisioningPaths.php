<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * HTTP provisioning URL path segments (webroot symlinks).
 */
class Zts_ProvisioningPaths
{
	const PRIMARY_WEB_SEGMENT = 'zerotouchsip';

	/**
	 * Directory under admin/modules/ for router.php, fanvil_common.php, .htaccess, logs/, configs/.
	 * Symlink target for /zerotouchsip.
	 */
	const SHARED_PROVISIONING_MODULE_SUBDIR = '_zts_provisioning';

	public static function primaryWebSegment()
	{
		return self::PRIMARY_WEB_SEGMENT;
	}

	/** @return string directory basename only, e.g. _zts_provisioning */
	public static function sharedProvisioningModuleSubdir()
	{
		return self::SHARED_PROVISIONING_MODULE_SUBDIR;
	}

	/**
	 * @param string $scheme http|https
	 * @param string $host host or host:port
	 * @return string e.g. https://pbx.example.com/zerotouchsip
	 */
	public static function baseUrl($scheme, $host)
	{
		$scheme = strtolower(trim((string) $scheme));
		if ($scheme !== 'https')
		{
			$scheme = 'http';
		}
		$host = trim((string) $host);
		if ($host === '')
		{
			return '';
		}

		return $scheme.'://'.$host.'/'.self::PRIMARY_WEB_SEGMENT;
	}

	/**
	 * Absolute path to provisioning module directory (no trailing slash).
	 *
	 * @param string $webroot AMPWEBROOT
	 * @return string
	 */
	public static function provisioningModuleDir($webroot)
	{
		$webroot = rtrim((string) $webroot, '/\\');

		return $webroot.'/admin/modules/'.self::SHARED_PROVISIONING_MODULE_SUBDIR;
	}

	/**
	 * Compare symlink targets ignoring trailing slashes.
	 *
	 * @param string $a
	 * @param string $b
	 * @return bool
	 */
	public static function pathsReferToSameLocation($a, $b)
	{
		$a = rtrim(str_replace('\\', '/', (string) $a), '/');
		$b = rtrim(str_replace('\\', '/', (string) $b), '/');

		return $a === $b;
	}
}
