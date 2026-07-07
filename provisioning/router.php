<?php
/**
 * Yealink URL router for standard provisioning filenames.
 *
 * Supported:
 *  - /zerotouchsip/y0000000000XX.cfg
 *  - /zerotouchsip/{MAC}.cfg
 *  - /zerotouchsip/{FanvilCommon}.cfg (f0… per router)
 *
 * *.boot is not served (phones must use MAC.cfg / model common .cfg only).
 *
 * @license GPL-3.0-or-later
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = basename((string) $path);

require_once __DIR__ . '/../includes/Zts/ModuleBranding.php';

if (is_file(__DIR__ . DIRECTORY_SEPARATOR . '.fanvil_boot_trace'))
{
	error_log(Zts_ModuleBranding::logTag('Router') . ' uri=' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '') . ' file=' . $file . ' ip=' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') . ' ua=' . substr(isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '', 0, 200));
}

if (preg_match('/\.boot$/i', $file) === 1) {
	header('HTTP/1.1 404 Not Found');
	header('Content-Type: text/plain; charset=UTF-8');
	echo "Not found\n";
	exit;
}

if (preg_match('/^(f0[0-9A-Za-z\.\-]+)\.cfg$/i', $file, $m) === 1) {
    $_GET['modelcfg'] = $m[1];
    require __DIR__ . '/fanvil_common.php';
    exit;
}

if (preg_match('/^y0000000000([0-9]{2,3})\.cfg$/i', $file, $m) === 1) {
    $_GET['model'] = $m[1];
    require __DIR__ . '/common.php';
    exit;
}

if (preg_match('/^([0-9A-F]{12})\.cfg$/i', $file, $m) === 1) {
    $_GET['mac'] = strtoupper($m[1]);
    require __DIR__ . '/config.php';
    exit;
}

header('HTTP/1.1 404 Not Found');
header('Content-Type: text/plain; charset=UTF-8');
echo "Not found\n";
exit;
?>
