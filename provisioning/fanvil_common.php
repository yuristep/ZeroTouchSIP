<?php
/**
 * Fanvil common/model configuration response.
 * Handles requests like /zerotouchsip/f0H5hw1.100.cfg
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

$network = zts_get_networks_ip($_SERVER['REMOTE_ADDR']);
zts_check_network($network);

$modelcfg = isset($_GET['modelcfg']) ? $_GET['modelcfg'] : '';

$global = zts_get_general_edit();
header('Content-Type: text/plain; charset=UTF-8');
echo zts_fanvil_common_probe_voip_body($modelcfg, $network, $global);
