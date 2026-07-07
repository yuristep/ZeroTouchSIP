<?php
// SPDX-License-Identifier: GPL-3.0-or-later
/**
 * Module upgrade: symlinks, settings migrations, UTF-8 schema.
 */
if (!defined('FREEPBX_IS_AUTH'))
{
	die('No direct script access allowed');
}

global $amp_conf;

require_once dirname(__FILE__) . '/includes/bootstrap.php';

$current = Zts_ModuleIdentifiers::RAWNAME;
$webroot = rtrim((string) $amp_conf['AMPWEBROOT'], '/');
$softwarePath = Zts_ProvisioningPaths::provisioningModuleDir($webroot).'/';
$primaryProv = $webroot.'/'.Zts_ProvisioningPaths::primaryWebSegment();

$nameMigration = Zts_DeviceNamingService::migrateExistingDeviceNamesIfNeeded();
if (!empty($nameMigration['already_done']))
{
	out('Device names: {extension}-{model}-{mac4} migration already completed (zts_settings).');
}
else
{
	out(sprintf(
		'Device names: %d updated to {extension}-{model}-{mac4}, %d marked manual, %d unchanged.',
		$nameMigration['updated'],
		$nameMigration['manual_marked'],
		$nameMigration['skipped']
	));
}

$provLogUp = Zts_ProvisioningLogConfig::upgradeStoredPathIfLegacy();
if ($provLogUp !== '')
{
	out('Provisioning log file path migrated to '.$provLogUp.' (see deployment/logrotate-zerotouchsip-provision).');
}

global $db;
$legacyNtp = sql("SELECT value FROM zts_settings WHERE keyword='default_ntp_server'", 'getOne');
if ($legacyNtp !== '' && $legacyNtp !== false)
{
	$hasNew = sql("SELECT value FROM zts_settings WHERE keyword='default_ntp_server1'", 'getOne');
	if ($hasNew === '' || $hasNew === false)
	{
		sql("REPLACE INTO zts_settings (keyword, value) VALUES ('default_ntp_server1','".$db->escapeSimple($legacyNtp)."')");
		out('General Settings: migrated default_ntp_server → default_ntp_server1.');
	}
}
$hasJson = sql("SELECT value FROM zts_settings WHERE keyword='".Zts_GeneralPhoneSecurityService::SETTING_JSON."'", 'getOne');
if ($hasJson === '' || $hasJson === false)
{
	$general = Zts_SettingsRepository::fetchAll();
	$rows = Zts_GeneralPhoneSecurityService::rowsFromGeneral($general);
	sql("REPLACE INTO zts_settings (keyword, value) VALUES ('".Zts_GeneralPhoneSecurityService::SETTING_JSON."','".$db->escapeSimple(json_encode($rows))."')");
	out('General Settings: seeded default phone security JSON.');
}

$_zts_utf8 = Zts_DatabaseSchema::migrateUtf8TextStorageIfNeeded();
if ($_zts_utf8 > 0)
{
	out('Database: converted '.$_zts_utf8.' zts_* table(s) to utf8mb4 (Unicode labels/JSON).');
}

$_zts_val_col = sql("SHOW COLUMNS FROM zts_settings WHERE Field='value'", 'getRow', DB_FETCHMODE_ASSOC);
if (is_array($_zts_val_col) && isset($_zts_val_col['Type']) && stripos((string) $_zts_val_col['Type'], 'varchar(255)') !== false)
{
	sql('ALTER TABLE zts_settings MODIFY `value` MEDIUMTEXT NOT NULL');
	out('zts_settings.value widened to MEDIUMTEXT (Line Key templates JSON).');
}

$hasLkTpl = sql("SELECT value FROM zts_settings WHERE keyword='".Zts_LinekeyTemplateService::SETTING_JSON."'", 'getOne');
if ($hasLkTpl === '' || $hasLkTpl === false)
{
	sql("REPLACE INTO zts_settings (keyword, value) VALUES ('".Zts_LinekeyTemplateService::SETTING_JSON."','"
		.$db->escapeSimple(Zts_LinekeyTemplateService::toJson(Zts_LinekeyTemplateService::defaultTemplates()))."')");
	out('General Settings: seeded default Line Key templates JSON.');
}

$_zts_net_ids = sql('SELECT id FROM zts_networks', 'getCol');
if (is_array($_zts_net_ids))
{
	foreach ($_zts_net_ids as $_zts_nid)
	{
		$_zts_nid = (string) $_zts_nid;
		$exists = sql(
			"SELECT value FROM zts_network_settings WHERE id='".$db->escapeSimple($_zts_nid)."' AND keyword='fanvil_config_version'",
			'getOne'
		);
		if ($exists === '' || $exists === false)
		{
			sql(
				"INSERT INTO zts_network_settings (id, keyword, value) VALUES ('"
				.$db->escapeSimple($_zts_nid)."', 'fanvil_config_version', '"
				.$db->escapeSimple(Zts_FanvilConfigVersionService::DEFAULT_VERSION)."')"
			);
			out('Network '.$_zts_nid.': added fanvil_config_version='.Zts_FanvilConfigVersionService::DEFAULT_VERSION);
		}
	}
}

$_zts_codec_migrated = Zts_NetworkCodecRegistry::migrateLegacyDefaultCodecPrioritiesIfNeeded();
if ($_zts_codec_migrated > 0)
{
	out('Codec Settings: updated '.$_zts_codec_migrated.' network(s) to Opus, G.722, PCMU, PCMA priority order.');
}

$_zts_net_val_col = sql("SHOW COLUMNS FROM zts_network_settings WHERE Field='value'", 'getRow', DB_FETCHMODE_ASSOC);
if (is_array($_zts_net_val_col) && isset($_zts_net_val_col['Type']) && stripos((string) $_zts_net_val_col['Type'], 'varchar(255)') !== false)
{
	sql('ALTER TABLE zts_network_settings MODIFY `value` MEDIUMTEXT NOT NULL');
	out('zts_network_settings.value widened to MEDIUMTEXT (Wi-Fi profiles JSON).');
}

sql('DELETE FROM zts_network_settings WHERE keyword IN (\'wifi_country_code\', \'wifi_enable\', \'wifi_push\')');
out('Network Wi-Fi: removed obsolete network-level wifi_enable/wifi_push/wifi_country_code settings.');

foreach (Zts_GeneralSipPnpService::installDefaults() as $_zts_pnp_k => $_zts_pnp_v)
{
	$exists = sql("SELECT value FROM zts_settings WHERE keyword='".$db->escapeSimple($_zts_pnp_k)."'", 'getOne');
	if ($exists === '' || $exists === false)
	{
		sql("INSERT INTO zts_settings (keyword, value) VALUES ('".$db->escapeSimple($_zts_pnp_k)."', '".$db->escapeSimple($_zts_pnp_v)."')");
		out('General Settings: added '.$_zts_pnp_k.'='.$_zts_pnp_v);
	}
}

if (!is_link($primaryProv) && is_dir($softwarePath))
{
	if (@symlink($softwarePath, $primaryProv))
	{
		out('Created '.$primaryProv);
	}
}
elseif (is_link($primaryProv) && !Zts_ProvisioningPaths::pathsReferToSameLocation(readlink($primaryProv), $softwarePath) && is_dir($softwarePath))
{
	@unlink($primaryProv);
	if (@symlink($softwarePath, $primaryProv))
	{
		out('Updated provisioning symlink '.$primaryProv);
	}
}

$newModDir = $webroot.'/admin/modules/'.$current;
$assetsNew = $webroot.'/admin/assets/'.$current;
if (is_dir($newModDir.'/assets') && !is_link($assetsNew))
{
	@symlink($newModDir.'/assets', $assetsNew);
	out('Linked admin/assets/'.$current);
}

out('Done. Open Admin → Connectivity → ZeroTouchSIP (config.php?type=setup&display='.$current.').');

?>
