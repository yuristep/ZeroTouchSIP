#!/usr/bin/env php
<?php
// SPDX-License-Identifier: GPL-3.0-or-later
/**
 * One-shot CLI: rename auto-pattern phone names to {extension}-{model}-{mac4}.
 * Usage on PBX: php /var/www/html/admin/modules/zerotouchsip/bin/migrate-device-names.php [--force]
 */

if (php_sapi_name() !== 'cli')
{
	die("CLI only\n");
}

$force = in_array('--force', $argv, true);

$bootstrap_settings['freepbx_auth'] = false;
if (!@include_once getenv('FREEPBX_CONF') ?: '/etc/freepbx.conf')
{
	fwrite(STDERR, "Cannot load FreePBX bootstrap\n");
	exit(1);
}

if (!defined('FREEPBX_IS_AUTH'))
{
	define('FREEPBX_IS_AUTH', true);
}

require dirname(__DIR__) . '/includes/bootstrap.php';

if ($force)
{
	$settings = Zts_SettingsRepository::fetchAll();
	unset($settings[Zts_DeviceNamingService::SETTING_NAMES_EXT_MODEL_MAC4_MIGRATED]);
	unset($settings[Zts_DeviceNamingService::SETTING_NAMES_EXT_MODEL_MIGRATED]);
	Zts_SettingsRepository::saveAll($settings);
	echo "Cleared migration flag; running full migration...\n";
}

$stats = Zts_DeviceNamingService::migrateExistingDeviceNamesIfNeeded($force);

if (!empty($stats['already_done']))
{
	echo "Migration already completed (use --force to run again).\n";
	exit(0);
}

printf(
	"Done: %d renamed, %d marked name_manual, %d skipped.\n",
	$stats['updated'],
	$stats['manual_marked'],
	$stats['skipped']
);

exit(0);
