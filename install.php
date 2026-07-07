<?php
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

global $db;

require_once dirname(__FILE__) . '/includes/bootstrap.php';

global $amp_conf;
if (is_array($amp_conf) && !empty($amp_conf['AMPVERSION']) && version_compare((string) $amp_conf['AMPVERSION'], '17.0.0', '<'))
{
	die_freepbx('ZeroTouchSIP requires FreePBX 17.0.0 or newer.');
}

$zts_install_server_name = (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== '')
	? (string) $_SERVER['SERVER_NAME']
	: ((function_exists('gethostname') && gethostname() !== false) ? gethostname() : 'localhost');

$sql = array();

if (Zts_DatabaseSchema::schemaAlreadyPresent())
{
	out('Database schema already present (zts_*); skipping CREATE TABLE in install.php.');
}
else
{
// =============================================================================
// GLOBAL SETTINGS TABLE
// =============================================================================

$sql[]='CREATE TABLE IF NOT EXISTS `zts_settings` (
  `keyword` varchar(50) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

$sql[]="INSERT IGNORE INTO `zts_settings` (`keyword`, `value`) VALUES
('auto_provision_repeat_minutes', '1440'),
('device_user_password', 'user'),
('device_admin_password', 'admin'),
('default_backlight_time', '60'),
('default_lang', 'English'),
('default_ntp_server1', 'pool.ntp.org'),
('default_ntp_server2', ''),
('default_time_zone_fanvil', '-20'),
('default_time_zone', '-5'),
('default_time_zone_name', 'United States-Eastern Time'),
('default_daylight_saving_time', '2'),
('default_daylight_saving_time_fanvil', '1'),
('default_provisioning_profile', 'auto'),
('device_admin_username', 'admin'),
('device_user_username', 'user'),
('security_trust_certificates', '0');";

$sql[] = "INSERT IGNORE INTO `zts_settings` (`keyword`, `value`) VALUES
	('provisioning_log_mode', 'off'),
	('provisioning_log_file', '".$db->escapeSimple(Zts_ProvisioningLogConfig::defaultFilePath())."');";

foreach (Zts_GeneralSipPnpService::installDefaults() as $_zts_pnp_k => $_zts_pnp_v)
{
	$sql[] = "INSERT IGNORE INTO `zts_settings` (`keyword`, `value`) VALUES ('".$db->escapeSimple($_zts_pnp_k)."', '".$db->escapeSimple($_zts_pnp_v)."');";
}

// =============================================================================
// NETWORK CONFIGURATION TABLES
// =============================================================================

$sql[]='CREATE TABLE IF NOT EXISTS `zts_networks` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `cidr` varchar(18) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cidr` (`cidr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

$sql[]="INSERT IGNORE INTO `zts_networks` (`id`, `name`, `cidr`) VALUES
('-1', 'Default Network', '0.0.0.0/0');";

$sql[]='CREATE TABLE IF NOT EXISTS `zts_network_settings` (
  `id` int(11) NOT NULL,
  `keyword` varchar(50) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`id`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

$sql[]="INSERT IGNORE INTO zts_network_settings (id, keyword, value) VALUES
('-1', 'prov_protocol', 'HTTPS'),
('-1', 'fanvil_config_version', '2.0004'),
('-1', 'prov_username', 'yealink'),
('-1', 'prov_password', 'yealink'),
('-1', 'sip_server_address', '" . $db->escapeSimple($zts_install_server_name) . "'),
('-1', 'sip_server_port', '5060'),
('-1', 'sip_server_transport', '0'),
('-1', 'sip_server_expires', '3600'),
('-1', 'nat_keepalive_interval', '30'),
('-1', 'ntp_server1', 'pool.ntp.org'),
('-1', 'ntp_server2', ''),
('-1', 'time_zone', '3'),
('-1', 'time_zone_fanvil', '12'),
('-1', 'time_zone_name', '(UTC+3) East Africa Time,Baghdad,Moscow,Ankara,Istanbul'),
('-1', 'daylight_saving_time', '2'),
('-1', 'daylight_saving_time_fanvil', '1');";

foreach (Zts_NetworkCodecRegistry::defaultCodecSettings() as $_zts_codec_k => $_zts_codec_v)
{
	$sql[] = "INSERT IGNORE INTO zts_network_settings (id, keyword, value) VALUES ('-1', '"
		.$db->escapeSimple($_zts_codec_k)."', '".$db->escapeSimple($_zts_codec_v)."');";
}

// =============================================================================
// DEVICE (PHONE) TABLES
// =============================================================================

$sql[]='CREATE TABLE IF NOT EXISTS `zts_devices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `mac` varchar(12) NOT NULL,
  `model` varchar(30) NOT NULL,
  `firmware_version` varchar(30) NOT NULL,
  `lastconfig` datetime NOT NULL,
  `lastip` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mac` (`mac`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

$sql[]='CREATE TABLE IF NOT EXISTS `zts_device_settings` (
  `id` int(11) NOT NULL,
  `keyword` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

$sql[]='CREATE TABLE IF NOT EXISTS `zts_device_lines` (
  `id` int(11) NOT NULL,
  `lineid` int(11) NOT NULL,
  `deviceid` int(11) NULL,
  PRIMARY KEY (`id`,`lineid`),
  KEY `deviceid` (`deviceid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

$sql[]='CREATE TABLE IF NOT EXISTS `zts_device_line_settings` (
  `id` int(11) NOT NULL,
  `lineid` int(11) NOT NULL,
  `keyword` varchar(30) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`,`lineid`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

$sql[]='CREATE TABLE IF NOT EXISTS `zts_device_linekeys` (
  `id` int(11) NOT NULL,
  `linekeyid` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `line` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  `label` varchar(30) NOT NULL,
  `extension` varchar(20) NOT NULL,
  `pickup_value` varchar(20) NOT NULL,
  PRIMARY KEY (`id`,`linekeyid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

// Execute all SQL statements
foreach ($sql as $statement){
	$check = $db->query($statement);
	if (DB::IsError($check)){
		die_freepbx( "Can not execute $statement : " . $check->getMessage() .  "\n");
	}
}

}

// =============================================================================
// DIRECTORY STRUCTURE & SYMLINKS
// =============================================================================

define("LOCAL_PATH", $amp_conf['AMPWEBROOT'] . '/admin/modules/zerotouchsip/');
define("SOFTWARE_PATH", Zts_ProvisioningPaths::provisioningModuleDir($amp_conf['AMPWEBROOT']).'/');
define("PROVISIONING_PATH_PRIMARY", rtrim((string) $amp_conf['AMPWEBROOT'], '/\\').'/zerotouchsip');

// Link module assets to FreePBX assets folder
if(!is_link($amp_conf['AMPWEBROOT'] . "/admin/assets/zerotouchsip"))
{
	out('Creating symlink to assets');
	if (!symlink(LOCAL_PATH . "assets", $amp_conf['AMPWEBROOT'] . "/admin/assets/zerotouchsip")) {
		out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", web assets link not created!</strong>");
	}
}

// Create directory for phone software/configs
foreach(array('', 'logs', 'configs', 'contacts') as $folder)
{
	if(!file_exists(SOFTWARE_PATH.$folder))
	{
		out("Creating provisioning webroot " . Zts_ProvisioningPaths::SHARED_PROVISIONING_MODULE_SUBDIR . " " . (empty($folder) ? 'root' : $folder) . " directory");
		if(!mkdir(SOFTWARE_PATH.$folder, 0775)) {
			out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", provisioning webroot directory not created!</strong>");
		}
	}
}

// Remove all old file links in software folder
foreach(scandir(SOFTWARE_PATH) as $item)
{
	if(is_file(SOFTWARE_PATH . $item) && is_link(SOFTWARE_PATH . $item)) {
		if(!unlink(SOFTWARE_PATH . $item)) {
			out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", unable to remove web provisioning file link!</strong>");
		}
	}
}

// Link provisioning files to software folder
foreach(scandir(LOCAL_PATH . "provisioning/") as $item)
{
	if(is_file(LOCAL_PATH . "provisioning/" . $item) && $item != 'index.html') {
		$dst = SOFTWARE_PATH . $item;
		if (is_link($dst) || is_file($dst)) {
			if (!@unlink($dst)) {
				out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", unable to replace web provisioning file link " . htmlspecialchars($item) . "!</strong>");
				continue;
			}
		}
		if (!symlink(LOCAL_PATH . "provisioning/" . $item, $dst)) {
			out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", web provisioning file link not created!</strong>");
		}
	}
}

// Primary path /zerotouchsip.
if (!is_link(PROVISIONING_PATH_PRIMARY))
{
	out('Creating symlink to web provisioner (ZeroTouchSIP /zerotouchsip)');
	if (!symlink(SOFTWARE_PATH, PROVISIONING_PATH_PRIMARY)) {
		out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", ZeroTouchSIP provisioning link not created!</strong>");
	}
}
elseif (!Zts_ProvisioningPaths::pathsReferToSameLocation(readlink(PROVISIONING_PATH_PRIMARY), SOFTWARE_PATH))
{
	out('Replacing mismatched /zerotouchsip symlink');
	@unlink(PROVISIONING_PATH_PRIMARY);
	if (!symlink(SOFTWARE_PATH, PROVISIONING_PATH_PRIMARY)) {
		out("<strong>Unable to recreate /zerotouchsip provisioning link!</strong>");
	}
}

$nameMigration = Zts_DeviceNamingService::migrateExistingDeviceNamesIfNeeded();
if (!empty($nameMigration['already_done']))
{
	out('Device names: {extension}-{model}-{mac4} migration already completed.');
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

if (class_exists('Zts_I18n') && Zts_I18n::compileCatalogs())
{
	out('i18n: compiled zerotouchsip.mo (en_US, ru_RU).');
}
else
{
	out('i18n: skipped .mo compile (install gettext / msgfmt on the PBX; see README).');
}

?>
