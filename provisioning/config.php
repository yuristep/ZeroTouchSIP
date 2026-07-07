<?php
/**
 * Yealink MAC-Specific Configuration Generator
 * URL: /zerotouchsip/config.php?mac={MAC}
 * Returns: {MAC}.cfg
 *
 * @license GPL-3.0-or-later
 */

$bootstrap_settings['freepbx_auth'] = false;
$bootstrap_settings['skip_astman'] = true;

$zts_freepbx_conf = getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf';
if (!@include_once($zts_freepbx_conf))
{
	header('HTTP/1.1 500 Internal Server Error');
	header('Content-Type: text/plain; charset=UTF-8');
	echo "FreePBX bootstrap not found.\n";
	exit;
}

require_once __DIR__ . '/../includes/Zts/ModuleBranding.php';
require_once __DIR__ . '/../includes/Zts/Support/FanvilTimeZoneOptions.php';
require_once __DIR__ . '/../includes/Zts/Support/FanvilLanguageOptions.php';
require_once __DIR__ . '/../includes/Zts/Support/YealinkTimeZoneOptions.php';
require_once __DIR__ . '/../includes/Zts/Support/NetworkCodecRegistry.php';
require_once __DIR__ . '/../includes/Zts/Service/FreepbxSipCodecService.php';
require_once __DIR__ . '/../includes/Zts/Service/NetworkCodecMapper.php';
require_once __DIR__ . '/../includes/Zts/Service/FanvilConfigVersionService.php';
require_once __DIR__ . '/../includes/Zts/Service/FanvilDeviceConfigService.php';
require_once __DIR__ . '/../includes/Zts/Service/YealinkDeviceConfigService.php';
require_once __DIR__ . '/../includes/Zts/Service/NetworkWifiProfileService.php';
require_once __DIR__ . '/../includes/Zts/Service/DeviceWifiSettingsService.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Ensure global $db is available
global $db;

// Get MAC address and convert to uppercase for database lookup
if (!isset($_GET['mac']))
{
	zts_send_forbidden();
}

$mac = strtoupper($_GET['mac']);

// Validate MAC format (12 hex digits)
if (preg_match('/^([0-9A-F]{12})$/', $mac) != 1)
{
	zts_send_forbidden();
}

// Lookup IP to determine if authentication or SSL is required
$network = zts_get_networks_ip($_SERVER['REMOTE_ADDR']);
zts_check_network($network);

if (is_file(__DIR__ . DIRECTORY_SEPARATOR . '.fanvil_boot_trace'))
{
	error_log(Zts_ModuleBranding::logTag('Config') . ' mac=' . $mac . ' uri=' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '') . ' ip=' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') . ' ua=' . substr(isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '', 0, 200));
}

zts_provisioning_log('prov_enter', array(
	'mac' => $mac,
	'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
	'network' => is_array($network) && !empty($network['name']) ? $network['name'] : (isset($network['id']) ? 'id:'.$network['id'] : ''),
));

// Look up device by MAC
$device_id = zts_lookup_mac($mac);

// Auto-register phone if it doesn't exist
if(!$device_id)
{
	if (!zts_provisioning_mac_allows_autoreg($mac))
	{
		zts_provisioning_log('autoreg_skipped_placeholder_mac', array(
			'mac' => $mac,
			'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
		));
		zts_provisioning_fanvil_placeholder_mac_response($network);
	}

	global $db;

	// Extract model and firmware from User-Agent
	$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$model_number = zts_detect_model($user_agent);
	$detected_vendor = zts_detect_vendor($user_agent, $model_number);

	$firmware = zts_detect_firmware($user_agent, $detected_vendor);

	// Auto-create phone with descriptive name.
	$auto_name = ($detected_vendor === 'fanvil' ? $model_number : ("T" . $model_number)) . "-" . $mac;

	zts_provisioning_log('autoreg_begin', array(
		'mac' => $mac,
		'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
		'ua_model' => $model_number,
		'detected_vendor_ua' => $detected_vendor,
		'planned_name' => $auto_name,
		'planned_model_field' => ($detected_vendor === 'fanvil' ? $model_number : ('T'.$model_number)),
	));

	try {
		sql("INSERT INTO zts_devices (name, mac, model, firmware_version, lastconfig, lastip)
			VALUES (
				'".$db->escapeSimple($auto_name)."',
				'".$db->escapeSimple($mac)."',
				'".$db->escapeSimple($detected_vendor === 'fanvil' ? $model_number : ('T'.$model_number))."',
				'".$db->escapeSimple($firmware)."',
				now(),
				'".$db->escapeSimple($_SERVER['REMOTE_ADDR'])."'
			)");

		$device_id = sql("SELECT LAST_INSERT_ID()",'getOne');

		// Match GUI save shape: 16 SIP line slots with NULL FreePBX device until admin assigns.
		$line_seed_count = sql("SELECT COUNT(*) FROM zts_device_lines WHERE id = '".$db->escapeSimple($device_id)."'", 'getOne');
		if ((int) $line_seed_count === 0)
		{
			for ($seed_l = 1; $seed_l <= 16; $seed_l++)
			{
				sql("INSERT INTO zts_device_lines (id, lineid, deviceid) VALUES ('".
					$db->escapeSimple($device_id)."','".$db->escapeSimple($seed_l)."', NULL)");
			}
		}

		zts_provisioning_log('autoreg_created', array(
			'mac' => $mac,
			'device_id' => $device_id,
			'name' => $auto_name,
			'model_stored' => ($detected_vendor === 'fanvil' ? $model_number : ('T'.$model_number)),
			'detected_vendor_ua' => $detected_vendor,
			'firmware' => $firmware,
			'line_slots_seeded' => 16,
		));
	} catch (Exception $e) {
		zts_provisioning_log('autoreg_failed', array(
			'mac' => $mac,
			'error' => $e->getMessage(),
		));
		error_log("Yealink auto-discovery failed: " . $e->getMessage());
		zts_send_error('500 Internal Server Error', 'Failed to register phone: ' . $e->getMessage());
	}
}
else
{
	global $db;

	zts_provisioning_log('device_exists_refresh', array(
		'mac' => $mac,
		'device_id' => $device_id,
	));

	// Update existing device info
	$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$model_number = zts_detect_model($user_agent);
	$detected_vendor = zts_detect_vendor($user_agent, $model_number);
	$firmware = zts_detect_firmware($user_agent, $detected_vendor);

	$new_model = ($detected_vendor === 'fanvil' ? $model_number : ('T'.$model_number));
	$row = sql("SELECT name, model FROM zts_devices WHERE id = '".$db->escapeSimple($device_id)."'", 'getRow', DB_FETCHMODE_ASSOC);
	$existing_name = (is_array($row) && isset($row['name'])) ? $row['name'] : '';
	$existing_model = (is_array($row) && isset($row['model'])) ? trim((string) $row['model']) : '';
	if ($new_model === 'T00')
	{
		$parsed = zts_model_from_device_name($existing_name);
		if ($parsed !== '' && $parsed !== 'T00')
		{
			$new_model = $parsed;
		}
		elseif ($existing_model !== '' && $existing_model !== 'T00')
		{
			$new_model = $existing_model;
		}
	}

	sql("UPDATE zts_devices SET
		lastconfig = now(),
		lastip = '".$db->escapeSimple($_SERVER['REMOTE_ADDR'])."',
		model = '".$db->escapeSimple($new_model)."',
		firmware_version = '".$db->escapeSimple($firmware)."'
		WHERE id = '".$db->escapeSimple($device_id)."'");
}

Zts_GeneralPhoneDefaultsService::applyToDeviceIfEligible($device_id);

// Load device configuration
$device = zts_get_phones_edit($device_id);
$global = zts_get_general_edit();
$network = Zts_GeneralPhoneDefaultsService::overlayDeviceTimeOnNetwork($network, $device);
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$device_model_for_cfg = zts_device_effective_model($device);
$profile_vendor = isset($device['settings']['provisioning_profile']) ? $device['settings']['provisioning_profile'] : 'auto';
$detected_vendor = zts_detect_vendor($user_agent, $device_model_for_cfg);
$vendor = ($profile_vendor === 'fanvil' || $profile_vendor === 'yealink') ? $profile_vendor : $detected_vendor;
// Explicit "Yealink" profile must not force Yealink text format onto Fanvil hardware (H2U/H5/…).
if ($profile_vendor === 'yealink' && zts_detect_vendor('', $device_model_for_cfg) === 'fanvil')
{
	$vendor = 'fanvil';
}

$prov_network_label = '';
if (is_array($network))
{
	if (!empty($network['name']))
	{
		$prov_network_label = (string) $network['name'];
	}
	elseif (isset($network['id']))
	{
		$prov_network_label = 'id:' . $network['id'];
	}
}
zts_provisioning_log('config_vendor', array(
	'mac' => $mac,
	'device_id' => $device_id,
	'device_name' => isset($device['name']) ? $device['name'] : '',
	'model_db' => isset($device['model']) ? $device['model'] : '',
	'model_effective' => $device_model_for_cfg,
	'profile_vendor' => $profile_vendor,
	'detected_vendor' => $detected_vendor,
	'vendor_final' => $vendor,
	'network' => $prov_network_label,
	'prov_protocol' => isset($network['settings']['prov_protocol']) ? $network['settings']['prov_protocol'] : '',
));

// Set content type
header('Content-Type: text/plain');

if ($vendor === 'fanvil')
{
	$fanvilResult = Zts_FanvilDeviceConfigService::build(array(
		'device' => $device,
		'network' => $network,
		'global' => $global,
		'mac' => $mac,
		'device_id' => $device_id,
		'model' => $device_model_for_cfg,
	));
	if (!$fanvilResult['ok'])
	{
		zts_provisioning_log('fanvil_503_no_sip', array(
			'mac' => $mac,
			'device_id' => $device_id,
		));
		http_response_code(503);
		header('Content-Type: text/plain; charset=UTF-8');
		echo $fanvilResult['message'];
		exit;
	}

	$cfg = $fanvilResult['lines'];
	$meta = $fanvilResult['meta'];
	$body_len = 0;
	foreach ($cfg as $line)
	{
		$body_len += strlen($line) + 2;
	}
	zts_provisioning_log('fanvil_ok', array(
		'mac' => $mac,
		'device_id' => $device_id,
		'sip_lines_built' => isset($meta['sip_lines_built']) ? $meta['sip_lines_built'] : 0,
		'family' => isset($meta['family']) ? $meta['family'] : '',
		'is_h2' => isset($meta['is_h2']) ? $meta['is_h2'] : 0,
		'is_h5' => isset($meta['is_h5']) ? $meta['is_h5'] : 0,
		'is_h6' => isset($meta['is_h6']) ? $meta['is_h6'] : 0,
		'response_bytes_est' => $body_len,
	));

	if (!headers_sent())
	{
		header('X-ZeroTouchSIP-Vendor: fanvil');
		header('X-ZeroTouchSIP-Sip-Lines: ' . (int)(isset($meta['sip_lines_built']) ? $meta['sip_lines_built'] : 0));
		header('X-ZeroTouchSIP-Device-Id: ' . (int) $device_id);
		header('X-ZeroTouchSIP-Fanvil-Family: ' . (isset($meta['family']) ? $meta['family'] : ''));
	}
	error_log(sprintf(
		Zts_ModuleBranding::DISPLAY_NAME.' provisioning: mac=%s vendor=fanvil family=%s sip_lines=%d device_id=%s client_ip=%s',
		$mac,
		isset($meta['family']) ? (string) $meta['family'] : '',
		(int)(isset($meta['sip_lines_built']) ? $meta['sip_lines_built'] : 0),
		(string) $device_id,
		isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : ''
	));

	foreach ($cfg as $line)
	{
		echo $line . "\r\n";
	}
	exit;
}

$yealinkResult = Zts_YealinkDeviceConfigService::build(array(
	'device' => $device,
	'network' => $network,
	'global' => $global,
	'mac' => $mac,
	'device_id' => $device_id,
	'model' => $device_model_for_cfg,
));
if (!$yealinkResult['ok'])
{
	zts_provisioning_log('yealink_503_no_sip', array(
		'mac' => $mac,
		'device_id' => $device_id,
	));
	http_response_code(503);
	header('Content-Type: text/plain; charset=UTF-8');
	echo $yealinkResult['message'];
	exit;
}

$cfg = $yealinkResult['lines'];
$meta = $yealinkResult['meta'];
$body_len = 0;
foreach ($cfg as $line)
{
	$body_len += strlen($line) + 1;
}
zts_provisioning_log('yealink_ok', array(
	'mac' => $mac,
	'device_id' => $device_id,
	'sip_lines_built' => isset($meta['sip_lines_built']) ? $meta['sip_lines_built'] : 0,
	'family' => isset($meta['family']) ? $meta['family'] : '',
	'response_bytes_est' => $body_len,
));

if (!headers_sent())
{
	header('X-ZeroTouchSIP-Vendor: yealink');
	header('X-ZeroTouchSIP-Sip-Lines: '.(int) (isset($meta['sip_lines_built']) ? $meta['sip_lines_built'] : 0));
	header('X-ZeroTouchSIP-Device-Id: '.(int) $device_id);
	header('X-ZeroTouchSIP-Yealink-Family: '.(isset($meta['family']) ? $meta['family'] : ''));
}

foreach ($cfg as $line)
{
	echo $line."\n";
}

exit;
