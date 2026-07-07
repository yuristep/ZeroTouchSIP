<?php
/**
 * SIP PnP secure profile download: ?mac=...&hash=... (HMAC-SHA256 token).
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

require_once __DIR__.'/../includes/bootstrap.php';

$mac = isset($_GET['mac']) ? (string) $_GET['mac'] : '';
$hash = isset($_GET['hash']) ? (string) $_GET['hash'] : '';
$clientIp = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

$general = array();
if (function_exists('zts_get_general_edit'))
{
	$general = zts_get_general_edit();
}

if (!Zts_SipPnpSecureUrlService::isSecureUrlEnabled($general))
{
	header('HTTP/1.1 403 Forbidden');
	header('Content-Type: text/plain; charset=UTF-8');
	echo "Forbidden\n";
	exit;
}

if (!Zts_SipPnpSecureUrlService::authorize($mac, $hash, $clientIp, $general))
{
	Zts_SipPnpSecureUrlService::recordInvalidAttempt($clientIp, $general);
	if (function_exists('zts_provisioning_log'))
	{
		zts_provisioning_log('pnp_hash_denied', array(
			'mac' => $mac,
			'hash' => $hash,
			'ip' => $clientIp,
		));
	}
	if (function_exists('zts_send_forbidden'))
	{
		zts_send_forbidden();
	}
	header('HTTP/1.1 403 Forbidden');
	header('Content-Type: text/plain; charset=UTF-8');
	echo "Forbidden\n";
	exit;
}

$mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));
if (strlen($mac) !== 12)
{
	Zts_SipPnpSecureUrlService::recordInvalidAttempt($clientIp, $general);
	zts_send_forbidden();
}

if (function_exists('zts_provisioning_log'))
{
	zts_provisioning_log('pnp_hash_ok', array('mac' => $mac, 'ip' => $clientIp));
}

$_GET['mac'] = $mac;
require __DIR__.'/config.php';
