<?php
/**
 * Yealink Boot File Generator
 * Returns: y000000000000.boot
 * This file tells the phone which config files to load
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

// Detect phone model from User-Agent
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$model = zts_detect_model($user_agent);

// Resolve MAC address from query or User-Agent.
// Standard Yealink boot requests usually do not include ?mac=, so relying only
// on query string prevents MAC-specific config from being loaded.
$mac = '';
if (isset($_GET['mac'])) {
	$mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', (string) $_GET['mac']));
} elseif (preg_match('/([0-9A-Fa-f]{2}(?::[0-9A-Fa-f]{2}){5})/', $user_agent, $mac_matches) === 1) {
	$mac = strtoupper(str_replace(':', '', $mac_matches[1]));
}
if ($mac !== '' && !preg_match('/^[0-9A-F]{12}$/', $mac)) {
	$mac = '';
}

// Provisioning ACL first (no DB work for blocked clients).
$network = zts_get_networks_ip($_SERVER['REMOTE_ADDR']);
zts_check_network($network);

// Fanvil firmware often requests F00000000000.boot (and similar) before the real MAC.cfg.
// Our router treats any 12-hex .boot as "<MAC>.boot" → here $mac becomes F00000000000.
// Without this branch, the phone gets a Yealink-style stub and may abort the whole boot provision cycle.
if (!empty($mac) && preg_match('/^[0-9A-F]{12}$/', $mac) && !zts_provisioning_mac_allows_autoreg($mac))
{
	zts_provisioning_fanvil_placeholder_mac_response($network);
}

// Vendor from UA alone mis-classifies Fanvil when the .boot request has no "Fanvil" token
// If this MAC already exists in ZeroTouchSIP inventory, use DB name/model.
$vendor_model_for_boot = $model;
$boot_device_id = null;
if (!empty($mac) && preg_match('/^[0-9A-F]{12}$/', $mac))
{
	global $db;
	$boot_device_id = zts_lookup_mac($mac);
	if ($boot_device_id)
	{
		$boot_row = sql("SELECT name, model FROM zts_devices WHERE id = '".$db->escapeSimple($boot_device_id)."'", 'getRow', DB_FETCHMODE_ASSOC);
		if (is_array($boot_row))
		{
			$vendor_model_for_boot = zts_device_effective_model(array(
				'name' => isset($boot_row['name']) ? $boot_row['name'] : '',
				'model' => isset($boot_row['model']) ? $boot_row['model'] : '',
			));
		}
	}
}
$vendor = zts_detect_vendor($user_agent, $vendor_model_for_boot);

if (is_file(__DIR__ . DIRECTORY_SEPARATOR . '.fanvil_boot_trace'))
{
	error_log(Zts_ModuleBranding::logTag('Boot') . ' uri=' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '') . ' mac=' . $mac . ' vendor=' . $vendor . ' boot_device_id=' . ($boot_device_id ? (string) $boot_device_id : '') . ' ip=' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') . ' ua=' . substr($user_agent, 0, 200));
}

zts_provisioning_log('boot_decision', array(
	'mac' => $mac,
	'boot_device_id' => $boot_device_id,
	'ua_model' => $model,
	'vendor_model' => $vendor_model_for_boot,
	'vendor' => $vendor,
	'next' => ($vendor === 'fanvil' && !empty($mac) && preg_match('/^[0-9A-F]{12}$/', $mac))
		? (is_file(__DIR__ . DIRECTORY_SEPARATOR . '.fanvil_boot_manifest') ? 'fanvil_boot_manifest' : 'require_full_config')
		: 'boot_stub',
));

// Fanvil <MAC>.boot: default inline full VOIP (config.php). Optional: touch provisioning/.fanvil_boot_manifest for INI manifest + separate .cfg fetch.
if ($vendor === 'fanvil' && !empty($mac) && preg_match('/^[0-9A-F]{12}$/', $mac))
{
	if (is_file(__DIR__ . DIRECTORY_SEPARATOR . '.fanvil_boot_manifest'))
	{
		header('Content-Type: text/plain; charset=UTF-8');
		http_response_code(200);
		$body = zts_fanvil_boot_manifest_body($mac, $network, $vendor_model_for_boot);
		zts_provisioning_log('fanvil_boot_manifest', array(
			'mac' => $mac,
			'bytes' => strlen($body),
			'base' => zts_fanvil_provisioning_base_url($network),
		));
		echo $body;
		exit;
	}
	$_GET['mac'] = $mac;
	require __DIR__ . '/config.php';
	exit;
}

// Set content type
header('Content-Type: text/plain');

// Generate boot file
echo "#!version:1.0.0.1\n\n";
echo "# Yealink Boot Configuration\n";
echo "# Generated: " . date('Y-m-d H:i:s') . "\n\n";

// Include common CFG based on model
if($model != '00')
{
	echo "include:config \"y0000000000" . $model . ".cfg\"\n";
}

// Include MAC-specific CFG if MAC provided
if(!empty($mac) && preg_match('/^[0-9A-F]{12}$/', $mac))
{
	echo "include:config \"" . $mac . ".cfg\"\n";
}

?>
