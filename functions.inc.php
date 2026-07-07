<?php
/* $Id */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

require_once dirname(__FILE__) . '/includes/bootstrap.php';

// =============================================================================
// FREEPBX HOOKS
// =============================================================================

function zerotouchsip_configpageinit($pagename)
{
	global $currentcomponent;

	if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'devices' && isset($_REQUEST['extdisplay']))
	{
		$currentcomponent->addguifunc('zerotouchsip_configpageload', 8);
	}
}

function zerotouchsip_configpageload($pagename)
{
	global $currentcomponent;
	global $db;

	if($_REQUEST['extdisplay'] !== false)
	{
		$extId = Zts_InputValidator::positiveInt($_REQUEST['extdisplay']);
		if ($extId < 1)
		{
			return;
		}
		$phones = sql("SELECT zts_devices.id, zts_devices.name, zts_devices.mac FROM zts_devices
			INNER JOIN zts_device_lines ON zts_devices.id = zts_device_lines.id
			WHERE zts_device_lines.deviceid = '".$db->escapeSimple((string) $extId)."'",'getAll',DB_FETCHMODE_ASSOC);

		foreach($phones as $phone)
		{
			$editURL = $_SERVER['PHP_SELF'].'?'.http_build_query(array(
				'display' => Zts_ModuleIdentifiers::RAWNAME,
				Zts_ModuleIdentifiers::FORM_PARAM => 'phones_edit',
				'edit' => $phone['id'],
			));
			$tlabel = sprintf(_('Edit in %s: %s (%s)'), Zts_ModuleBranding::displayName(), $phone['name'], $phone['mac']);
			$label = '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/telephone_edit.png"/>&nbsp;'.$tlabel.'</span>';
			$currentcomponent->addguielem('_top', new gui_link('zts_edit_phone', $label, $editURL, true, false), 0);
		}
	}
}

function zerotouchsip_get_config($engine)
{
	global $db;
	global $core_conf;

	switch ($engine) {
		case "asterisk":
			if (isset($core_conf) && is_a($core_conf, "core_conf") && (method_exists($core_conf, 'addSipNotify'))) {
				$core_conf->addSipNotify('yealink-check-cfg', array('Event' => 'check-sync', 'Content-Length' => '0'));
				$core_conf->addSipNotify('yealink-reboot', array('Event' => 'check-sync;reboot=true', 'Content-Length' => '0'));
			}
			break;
	}
}

// =============================================================================
// PHONE MANAGEMENT FUNCTIONS
// =============================================================================

/**
 * SQL scalar subquery: number of phone lines with a FreePBX device assigned (matches list column content).
 *
 * @return string
 */
function zts_phones_list_assigned_lines_count_sql()
{
	return '(SELECT COUNT(*) FROM zts_device_lines ydl WHERE ydl.id = zts_devices.id AND ydl.deviceid IS NOT NULL)';
}

/**
 * SQL expression for sorting the Lines column: numeric extension of the first assigned line (MIN(lineid)),
 * same source as the list view (users.extension, else devices.dial). Phones with no line sort last (99999999).
 *
 * @return string
 */
function zts_phones_list_lines_extension_sort_sql()
{
	return 'COALESCE((SELECT CAST(COALESCE(NULLIF(TRIM(u.extension),\'\'), NULLIF(TRIM(d.dial),\'\'), \'0\') AS UNSIGNED)
		FROM zts_device_lines ydl
		INNER JOIN devices d ON d.id = ydl.deviceid
		LEFT OUTER JOIN users u ON d.user = u.extension
		WHERE ydl.id = zts_devices.id AND ydl.deviceid IS NOT NULL
		AND ydl.lineid = (SELECT MIN(ydl2.lineid) FROM zts_device_lines ydl2 WHERE ydl2.id = zts_devices.id AND ydl2.deviceid IS NOT NULL)), 99999999)';
}

function zts_phones_list_sort_map()
{
	return array(
		'name' => 'zts_devices.name',
		'mac' => 'zts_devices.mac',
		'vendor' => "(CASE WHEN UPPER(zts_devices.model) LIKE '%FANVIL%' OR zts_devices.model REGEXP '^H[0-9]' THEN 'Fanvil' ELSE 'Yealink' END)",
		'model' => 'zts_devices.model',
		'firmware' => 'zts_devices.firmware_version',
		'lines' => zts_phones_list_lines_extension_sort_sql(),
		'lastconfig' => 'zts_devices.lastconfig',
		'lastip' => 'zts_devices.lastip',
		'pjsip' => 'zts_devices.mac',
	);
}

/**
 * Sort rank for phones list PJSIP column (online first when ascending).
 *
 * @param string $state online|offline|na|unknown
 * @return int
 */
function zts_phones_list_pjsip_sort_rank($state)
{
	$state = is_string($state) ? strtolower(trim($state)) : '';
	switch ($state)
	{
		case 'online':
			return 0;
		case 'offline':
			return 1;
		case 'na':
			return 2;
		case 'unknown':
		default:
			return 3;
	}
}

/**
 * @param array<int,array> $results rows with pjsip_status enriched
 * @param string           $order   asc|desc
 * @return void
 */
function zts_phones_list_sort_by_pjsip(array &$results, $order)
{
	$desc = (is_string($order) && strtolower(trim($order)) === 'desc');
	usort($results, function ($a, $b) use ($desc) {
		$sa = (isset($a['pjsip_status']) && is_array($a['pjsip_status']) && isset($a['pjsip_status']['state']))
			? (string) $a['pjsip_status']['state']
			: '';
		$sb = (isset($b['pjsip_status']) && is_array($b['pjsip_status']) && isset($b['pjsip_status']['state']))
			? (string) $b['pjsip_status']['state']
			: '';
		$ra = zts_phones_list_pjsip_sort_rank($sa);
		$rb = zts_phones_list_pjsip_sort_rank($sb);
		if ($ra !== $rb)
		{
			return $desc ? ($rb - $ra) : ($ra - $rb);
		}
		$ma = strtolower(isset($a['mac']) ? (string) $a['mac'] : '');
		$mb = strtolower(isset($b['mac']) ? (string) $b['mac'] : '');

		return strcmp($ma, $mb);
	});
}

function zts_normalize_phones_list_sort($sort, $order)
{
	$map = zts_phones_list_sort_map();
	$sort = is_string($sort) ? strtolower(trim($sort)) : '';
	if (!isset($map[$sort]))
	{
		$sort = 'mac';
	}
	$order = (is_string($order) && strtolower(trim($order)) === 'desc') ? 'desc' : 'asc';
	return array($sort, $order);
}

/**
 * Flatten AMI Command response to plain text (FreePBX astman variants).
 *
 * @param mixed $response
 * @return string
 */
function zts_ami_command_response_to_text($response)
{
	if (is_string($response))
	{
		return $response;
	}
	if (!is_array($response))
	{
		return '';
	}
	if (isset($response['data']) && is_string($response['data']))
	{
		return $response['data'];
	}
	if (isset($response['Data']) && is_string($response['Data']))
	{
		return $response['Data'];
	}
	$parts = array();
	foreach ($response as $k => $v)
	{
		if (is_string($v) && $v !== '')
		{
			$parts[] = $v;
		}
	}
	return trim(implode("\n", $parts));
}

/**
 * @return bool
 */
function zts_pjsip_ami_available()
{
	global $astman;

	if (!is_object($astman) || !method_exists($astman, 'send_request'))
	{
		return false;
	}
	if (method_exists($astman, 'connected'))
	{
		return (bool) $astman->connected();
	}

	return true;
}

/**
 * SIP user parts and contact host IPs from `pjsip show contacts` lines whose status is Avail
 * (same notion as AsteriskInfo: Contact … Avail — not Unavailable).
 *
 * @return array{extensions:array<string,bool>,ips:array<string,bool>}
 */
function zts_pjsip_online_peer_map()
{
	static $cache = null;
	if ($cache !== null)
	{
		return $cache;
	}
	$cache = array(
		'extensions' => array(),
		'ips' => array(),
	);
	if (!zts_pjsip_ami_available())
	{
		return $cache;
	}
	global $astman;
	try
	{
		$res = $astman->send_request('Command', array('Command' => 'pjsip show contacts'));
	}
	catch (Exception $e)
	{
		return $cache;
	}
	$text = zts_ami_command_response_to_text($res);
	if ($text === '')
	{
		return $cache;
	}
	$text = str_replace(array("\r\n", "\r"), "\n", $text);
	foreach (explode("\n", $text) as $line)
	{
		if (stripos($line, 'Contact:') === false || stripos($line, 'sip:') === false)
		{
			continue;
		}
		if (!preg_match('/\bAvail\b/i', $line))
		{
			continue;
		}
		// Simple sip:user@host — avoid nested [] classes (older PCRE can mis-parse them).
		if (!preg_match_all('~sip:([^@;\s>]+)@([^;\s>/]+)~i', $line, $matches, PREG_SET_ORDER))
		{
			continue;
		}
		foreach ($matches as $m)
		{
			$user = trim((string) $m[1]);
			$hostRaw = trim((string) $m[2]);
			$host = $hostRaw;
			if ($host !== '')
			{
				$host = preg_split('/[;>]/', $host, 2);
				$host = trim((string) $host[0]);
				if ($host !== '' && $host[0] === '[' && preg_match('~^\[([^\]]+)\](?::\d+)?$~', $host, $hm))
				{
					$host = $hm[1];
				}
				elseif ($host !== '' && preg_match('~^(\d{1,3}(?:\.\d{1,3}){3})(?::\d+)?$~', $host, $hm))
				{
					$host = $hm[1];
				}
			}
			if ($user !== '')
			{
				$cache['extensions'][$user] = true;
				$t = ltrim($user, '+');
				if ($t !== $user && $t !== '')
				{
					$cache['extensions'][$t] = true;
				}
			}
			if ($host !== '' && filter_var($host, FILTER_VALIDATE_IP))
			{
				$cache['ips'][$host] = true;
			}
		}
	}
	return $cache;
}

/**
 * PJSIP registration-style status for one phone row (line extensions and/or last device IP vs Avail contacts).
 *
 * @param array $device zts_devices row including lines[], lastip
 * @param array $peer_map from zts_pjsip_online_peer_map()
 * @return array{state:string,label:string} state: online|offline|na|unknown
 */
function zts_device_pjsip_line_status($device, array $peer_map)
{
	$extMap = isset($peer_map['extensions']) && is_array($peer_map['extensions']) ? $peer_map['extensions'] : array();
	$ipMap = isset($peer_map['ips']) && is_array($peer_map['ips']) ? $peer_map['ips'] : array();

	$exts = array();
	if (isset($device['lines']) && is_array($device['lines']))
	{
		foreach ($device['lines'] as $line)
		{
			if (!empty($line['extension']))
			{
				$exts[] = (string) $line['extension'];
			}
		}
	}
	$exts = array_values(array_unique($exts));
	$lastip = trim((string) (isset($device['lastip']) ? $device['lastip'] : ''));
	$ipUp = ($lastip !== '' && !empty($ipMap[$lastip]));

	if (!zts_pjsip_ami_available())
	{
		return array(
			'state' => 'unknown',
			'label' => (string) _('PJSIP status unavailable (AMI not connected)'),
		);
	}

	if (count($exts) < 1)
	{
		if ($ipUp)
		{
			return array(
				'state' => 'online',
				'label' => (string) sprintf(_('PJSIP Avail contact at last device IP: %s'), $lastip),
			);
		}
		return array(
			'state' => 'na',
			'label' => (string) _('No extension on lines — PJSIP status N/A'),
		);
	}

	$up = array();
	foreach ($exts as $e)
	{
		if (!empty($extMap[$e]) || !empty($extMap[ltrim($e, '+')]))
		{
			$up[] = $e;
		}
	}
	if (count($up) > 0)
	{
		$miss = array_diff($exts, $up);
		$label = sprintf(_('PJSIP Avail: %s'), implode(', ', $up));
		if (count($miss) > 0)
		{
			$label .= sprintf(_(' — no Avail contact: %s'), implode(', ', $miss));
		}
		return array('state' => 'online', 'label' => $label);
	}
	if ($ipUp)
	{
		return array(
			'state' => 'online',
			'label' => (string) sprintf(
				_('PJSIP Avail at last device IP (%s), no Avail for extensions: %s'),
				$lastip,
				implode(', ', $exts)
			),
		);
	}
	return array(
		'state' => 'offline',
		'label' => (string) sprintf(_('No PJSIP Avail contact for: %s'), implode(', ', $exts)),
	);
}

/**
 * @param array<int,array> $results
 * @return void
 */
function zts_phones_list_enrich_pjsip_status(&$results)
{
	if (!is_array($results) || count($results) < 1)
	{
		return;
	}
	$peer_map = zts_pjsip_online_peer_map();
	foreach ($results as $k => $row)
	{
		$results[$k]['pjsip_status'] = zts_device_pjsip_line_status($row, $peer_map);
	}
}

function zts_get_phones_list($sort = 'mac', $order = 'asc')
{
	return Zts_PhonesListService::getList($sort, $order);
}

/**
 * HTTPS provisioning URL for admin UI.
 *
 * @param string $serverName
 * @return array{primary:string}
 */
function zts_provisioning_public_urls($serverName)
{
	return Zts_ProvisioningUrlService::publicUrls($serverName);
}

function zts_get_phones_edit($id)
{
	return Zts_DeviceRepository::findForEdit($id);
}

function zts_save_phones_edit($id, $device)
{
	return Zts_DeviceRepository::save($id, $device);
}

function zts_delete_phones_list($id)
{
	Zts_DeviceRepository::deleteById($id);
}

function zts_lookup_deviceid($id)
{
	return Zts_DeviceRepository::lookupMinPbxDeviceId($id);
}

// =============================================================================
// NETWORK MANAGEMENT FUNCTIONS
// =============================================================================

function zts_networks_list_sort_map()
{
	return Zts_NetworkRepository::sortColumnMap();
}

function zts_normalize_networks_list_sort($sort, $order)
{
	return Zts_NetworksListQueryValidator::normalize($sort, $order);
}

function zts_get_networks_list($sort = 'cidr', $order = 'asc')
{
	return Zts_NetworksListService::getList($sort, $order);
}

function zts_get_networks_edit($id)
{
	return Zts_NetworkRepository::findForEdit($id);
}

function zts_save_networks_edit($id, $network)
{
	return Zts_NetworkRepository::save($id, $network);
}

function zts_delete_networks_list($id)
{
	Zts_NetworkRepository::deleteById($id);
}

function zts_cidr_ip_check ($ip, $cidr)
{
	list ($net, $mask) = explode ("/", $cidr);
	$mask = (int)$mask;

	if ($mask < 0 || $mask > 32) {
		return false;
	}

	$ip_net = ip2long($net);
	$ip_ip = ip2long($ip);
	if ($ip_net === false || $ip_ip === false) {
		return false;
	}

	$ip_mask = $mask === 0 ? 0 : (~((1 << (32 - $mask)) - 1));
	$normalized_net = $ip_net & $ip_mask;
	$ip_ip_net = $ip_ip & $ip_mask;

	return ($ip_ip_net === $normalized_net);
}

/**
 * CIDR prefix length (0–32), or -1 if invalid.
 *
 * @param string $cidr
 * @return int
 */
function zts_cidr_prefix_length($cidr)
{
	$cidr = trim((string) $cidr);
	if (strpos($cidr, '/') === false)
	{
		return -1;
	}
	list(, $mask) = explode('/', $cidr, 2);

	return (int) $mask;
}

/**
 * Network row for client IP: longest-prefix match (narrowest mask, up to /32).
 *
 * @param string $ip
 * @return array|null zts_get_networks_edit()-shaped row
 */
function zts_get_networks_ip($ip)
{
	$results = sql('SELECT id, cidr FROM zts_networks', 'getAll', DB_FETCHMODE_ASSOC);
	if (!is_array($results))
	{
		return null;
	}

	$bestId = null;
	$bestMask = -1;
	foreach ($results as $result)
	{
		if (!zts_cidr_ip_check($ip, $result['cidr']))
		{
			continue;
		}
		$mask = zts_cidr_prefix_length($result['cidr']);
		if ($mask > $bestMask)
		{
			$bestMask = $mask;
			$bestId = $result['id'];
		}
	}

	if ($bestId === null)
	{
		return null;
	}

	return zts_get_networks_edit($bestId);
}

/**
 * Fanvil MMI web UI account block for VOIP cfg (&lt;MMI CONFIG MODULE&gt;, OEM colon format).
 *
 * @param array $network zts_get_networks_edit row
 * @return array<int,string>
 */
function zts_fanvil_mmi_config_lines($network)
{
	if (!is_array($network) || empty($network['settings']) || !is_array($network['settings']))
	{
		return array();
	}

	return Zts_NetworkMmiAccountService::fanvilConfigLines($network['settings']);
}

/**
 * Yealink web UI credentials for MAC .cfg (security.user_name.* / security.user_password).
 *
 * @param array      $network zts_get_networks_edit row
 * @param array|null $global  general settings (device_user_password, device_admin_password) fallback
 * @return array<int,string>
 */
function zts_yealink_web_ui_security_lines($network, $global = null)
{
	$settings = array();
	if (is_array($network) && !empty($network['settings']) && is_array($network['settings']))
	{
		$settings = $network['settings'];
	}
	$fallback = null;
	if (is_array($global))
	{
		$fallback = array(
			'device_user_password' => isset($global['device_user_password']) ? (string) $global['device_user_password'] : '',
			'device_admin_password' => isset($global['device_admin_password']) ? (string) $global['device_admin_password'] : '',
		);
	}

	return Zts_NetworkMmiAccountService::yealinkConfigLines($settings, $fallback);
}

function zts_check_network($network)
{
	if(empty($network))
	{
		zts_send_forbidden();
	}

	// Check if SSL is required
	if ($network['settings']['prov_protocol'] == 'HTTPS' && empty($_SERVER['HTTPS']))
	{
		zts_send_forbidden();
	}

	// Network has authentication disabled
	if(empty($network['settings']['prov_username']))
	{
		return;
	}

	if (!isset($_SERVER['PHP_AUTH_USER']))
	{
		zts_send_unauthorized();
	}

	if($_SERVER['PHP_AUTH_USER'] != $network['settings']['prov_username'] ||
	   $_SERVER['PHP_AUTH_PW'] != $network['settings']['prov_password'])
	{
		zts_send_unauthorized();
	}
}

function zts_send_unauthorized()
{
	header('WWW-Authenticate: Basic realm="Yealink Provisioning"');
	header('HTTP/1.0 401 Unauthorized');
	zts_send_error('401 Unauthorized', 'Authentication is required to view this page.');
}

/**
 * Fanvil VOIP config: first line must be exactly 64 characters before CRLF (vendor provisioning PDF).
 *
 * @param string $version_token value after "Version:" (e.g. "2.0004")
 * @return string exactly 64 bytes, no trailing CRLF
 */
function zts_fanvil_padded_voip_file_first_line($version_token)
{
	$prefix = '<<VOIP CONFIG FILE>>Version:';
	$token = trim((string) $version_token);
	$line = $prefix . $token;
	$len = strlen($line);
	if ($len > 64)
	{
		return substr($line, 0, 64);
	}

	return str_pad($line, 64, ' ', STR_PAD_RIGHT);
}

/**
 * OEM-style tail (MAINTENANCE + AUTOUPDATE) for Fanvil H2U-V2 class configs; shared with full device cfg.
 *
 * @param array<string,string>|null $general zts_settings (General Settings SIP PnP)
 * @return array<int,string>
 */
function zts_fanvil_voip_oem_autoupdate_tail_lines($general = null)
{
	if (!is_array($general) && function_exists('zts_get_general_edit'))
	{
		$general = zts_get_general_edit();
	}
	if (!is_array($general))
	{
		$general = array();
	}
	$pnp = class_exists('Zts_GeneralSipPnpService')
		? Zts_GeneralSipPnpService::fanvilConfigLines($general)
		: array(
			'--Sip Pnp List--   :',
			'PNP Enable         :1',
			'PNP IP             :224.0.1.75',
			'PNP Port           :5060',
			'PNP Transport      :0',
			'PNP Interval       :1',
		);

	return array_merge(array(
		"",
		"<MAINTENANCE CONFIG MODULE>",
		"Contact Update Mode:0",
		"Auto Server Digest :0",
		"",
		"<AUTOUPDATE CONFIG MODULE>",
		"Default Username   :",
		"###Default Password   :",
		"Input Cfg File Name:",
		"###Device Cfg File Key:",
		"###Common Cfg File Key:",
		"Download CommonConf:1",
		"Save Provision Info:0",
		"Check FailTimes    :1",
		"Flash Server IP    :",
		"Flash File Name    :",
		"Flash Protocol     :2",
		"Flash Mode         :0",
		"Flash Interval     :1",
		"update PB Interval :720",
		"AP Config Priority :0",
		"Local Config Type  :0",
		"Download Boot File :1",
		"DHCP Protocol Type       :2",
		"Enable User Config Upload:0",
		"User Config Upload Method:0",
		"User Config Provision Url:",
	), $pnp, array(
		"--Net Option--     :",
		"DHCP Option        :66",
		"DHCPv6 Option      :0",
		"Dhcp Option 120    :0",
		"Save DHCP Opion    :0",
		"Dhcp Renew Upgrade :1",
		"DHCP Option ACS    :0",
	));
}

/**
 * Minimal Fanvil VOIP body for placeholder MAC .boot, F000*.cfg (model common probe), and fanvil_common.php.
 * F00000000000 maps to model common class F0V2UV200000 (Fanvil H2U-V2 naming).
 *
 * @param string $vendor_class_raw modelcfg basename or probe MAC class id
 * @param array|null $network_full_or_null zts_get_networks_edit()-shaped row (optional settings)
 * @param array|null $general_or_null      zts_settings for SIP PnP block
 * @return string
 */
function zts_fanvil_common_probe_voip_body($vendor_class_raw, $network_full_or_null = null, $general_or_null = null)
{
	$defaults = array(
		'ntp_server1' => 'pool.ntp.org',
		'ntp_server2' => 'pool.ntp.org',
		'time_zone' => '3',
		'time_zone_fanvil' => '12',
		'time_zone_name' => '(UTC+3) East Africa Time,Baghdad,Moscow,Ankara,Istanbul',
		'daylight_saving_time' => '2',
		'daylight_saving_time_fanvil' => '1',
	);
	$s = $defaults;
	if (is_array($network_full_or_null) && !empty($network_full_or_null['settings']) && is_array($network_full_or_null['settings']))
	{
		$st = $network_full_or_null['settings'];
		foreach ($defaults as $k => $v)
		{
			if (isset($st[$k]) && (string) $st[$k] !== '')
			{
				$s[$k] = (string) $st[$k];
			}
		}
	}
	$ntp2 = (string) $s['ntp_server2'];
	if ($ntp2 === '')
	{
		$ntp2 = (string) $s['ntp_server1'];
	}

	$vc = strtoupper(trim((string) $vendor_class_raw));
	if ($vc === '' || $vc === 'F00000000000')
	{
		$vc = 'F0V2UV200000';
	}

	$lines = array();
	$lines[] = zts_fanvil_padded_voip_file_first_line(
		Zts_FanvilConfigVersionService::forNetwork($network_full_or_null)
	);
	$lines[] = '';
	$lines[] = '<GLOBAL CONFIG MODULE>';
	$lines[] = 'WAN Mode           :DHCP';
	$lines[] = 'Enable DHCP        :1';
	$lines[] = 'DHCP Auto DNS      :1';
	$lines[] = 'DHCP Auto Time     :1';
	$lines[] = 'SNTP Server        :' . $s['ntp_server1'];
	$lines[] = 'Second SNTP Server :' . $ntp2;
	$lines[] = 'Enable SNTP        :1';
	$fanvilTim = isset($s['time_zone_fanvil']) ? (string) $s['time_zone_fanvil'] : Zts_FanvilTimeZoneOptions::defaultValue();
	$lines[] = 'Time Zone          :' . Zts_FanvilTimeZoneOptions::cfgHoursFromTim($fanvilTim);
	$tzName = isset($s['time_zone_name']) ? trim((string) $s['time_zone_name']) : '';
	if ($tzName === '')
	{
		$tzName = Zts_FanvilTimeZoneOptions::labelForValue($fanvilTim);
	}
	$lines[] = 'Time Zone Name     :' . $tzName;
	$dst = isset($s['daylight_saving_time_fanvil']) ? trim((string) $s['daylight_saving_time_fanvil']) : '1';
	if (!in_array($dst, array('0', '1', '2'), true))
	{
		$dst = '1';
	}
	$lines[] = 'Enable DST         :' . $dst;
	$lines[] = 'Vendor Class ID    :' . $vc;
	$lines[] = '';

	$general = is_array($general_or_null) ? $general_or_null : array();
	if ($general === array() && function_exists('zts_get_general_edit'))
	{
		$general = zts_get_general_edit();
	}
	$lines = array_merge($lines, zts_fanvil_voip_oem_autoupdate_tail_lines($general));
	$lines[] = '<<END OF FILE>>';

	return implode("\r\n", $lines) . "\r\n";
}

/**
 * Minimal Fanvil VOIP file for placeholder MACs (e.g. F00000000000) — no DB autoreg.
 * Some Fanvil firmware aborts the whole provision cycle if this request returns HTTP 403/HTML.
 *
 * @param array|null $network_full_or_null optional network row for SNTP/time from ZeroTouchSIP → Networks
 */
function zts_provisioning_fanvil_placeholder_mac_response($network_full_or_null = null)
{
	header('Content-Type: text/plain; charset=UTF-8');
	http_response_code(200);
	echo zts_fanvil_common_probe_voip_body('F00000000000', $network_full_or_null);
	exit;
}

function zts_send_forbidden()
{
	header('HTTP/1.0 403 Forbidden');
	zts_send_error('403 Forbidden', 'Access is denied');
}

function zts_send_error($title, $message)
{
	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>' . $title . '</title>
</head><body>
<h1>' . $title . '</h1>
<p>' . $message . '</p>
</body></html>';
	exit;
}

// =============================================================================
// GENERAL SETTINGS FUNCTIONS
// =============================================================================

function zts_get_general_edit()
{
	return Zts_GeneralSettingsService::load();
}

function zts_save_general_edit($settings)
{
	Zts_SettingsRepository::saveAll($settings);
}

// =============================================================================
// SIP NOTIFY FUNCTIONS
// =============================================================================

/**
 * Inventory rows (for Fanvil HTTP notify). $id null = all phones; else list of zts_devices.id.
 *
 * @param string|string[]|null $id
 * @return array
 */
function zts_notify_inventory_rows($id = null)
{
	return Zts_NotifyInventoryRepository::fetchInventory($id);
}

/**
 * Format IP for URL host (bracket IPv6).
 *
 * @param string $ip_literal
 * @return string empty if invalid
 */
function zts_notify_host_for_url($ip_literal)
{
	return Zts_ProvisioningUrlService::notifyHostForUrl($ip_literal);
}

/**
 * Host part for provisioning URLs (FQDN or bracketed IPv6).
 *
 * @param string $host
 * @return string
 */
function zts_provisioning_url_host_literal($host)
{
	return Zts_ProvisioningUrlService::hostLiteral($host);
}

/**
 * Scheme for URLs returned to the phone (manifest / redirects).
 * Uses X-Forwarded-Proto / HTTPS when present; otherwise network prov_protocol.
 *
 * @param array $network zts_get_networks_edit row
 * @return string http|https
 */
function zts_fanvil_provisioning_response_scheme($network)
{
	return Zts_ProvisioningUrlService::responseScheme($network);
}

/**
 * HTTP Host for provisioning base (no path), from request or SIP/FQDN network field.
 *
 * @param array $network
 * @return string
 */
function zts_fanvil_provisioning_request_host($network)
{
	return Zts_ProvisioningUrlService::requestHost($network);
}

/**
 * Base URL for Fanvil manifests (/zerotouchsip).
 *
 * @param array $network
 * @return string e.g. https://pbx.example.com/zerotouchsip
 */
function zts_fanvil_provisioning_base_url($network)
{
	return Zts_ProvisioningUrlService::deviceBaseUrl($network);
}

/**
 * Fanvil common cfg filename for boot manifest (per Fanvil Auto Provision naming).
 *
 * @param string $model_upper effective model / name token
 * @return string basename or '' if unknown
 */
function zts_fanvil_common_cfg_basename_for_model($model_upper)
{
	$m = strtoupper(trim((string) $model_upper));
	if (strpos($m, 'H2U') !== false)
	{
		return 'F0V2UV200000.cfg';
	}
		if (strpos($m, 'H6W') !== false)
		{
			return 'F0V2UH6W00000.cfg';
		}
		if (strpos($m, 'W611') !== false)
		{
			return 'F0V611W00000.cfg';
		}
		if (strpos($m, 'H5') !== false)
	{
		return 'f0H5hw1.100.cfg';
	}

	return '';
}

/**
 * Fanvil H-series .boot file: INI-style manifest (CRLF). Points phone at HTTP(S) .cfg URLs.
 * Opt-in (touch provisioning/.fanvil_boot_manifest): default .boot is full VOIP via config.php.
 *
 * @param string $mac 12 hex uppercase
 * @param array $network
 * @param string $model_hint reserved for future (e.g. common cfg); manifest points at device MAC.cfg only
 * @return string
 */
function zts_fanvil_boot_manifest_body($mac, $network, $model_hint = '')
{
	$mac = strtoupper(trim((string) $mac));
	$base = zts_fanvil_provisioning_base_url($network);
	if ($base === '')
	{
		$base = 'http://127.0.0.1/'.Zts_ProvisioningPaths::primaryWebSegment();
	}
	$ver = '2.'.gmdate('ymdHi');
	$lines = array(
		'Version='.$ver,
		'Config='.$base.'/'.$mac.'.cfg',
	);

	return implode("\r\n", $lines)."\r\n";
}

/**
 * HTTP attempts for MAINTENANCE → Auto Provision (web “Autoprovision Now” equivalent).
 * H2U-V2 / H5 / H6W: ConfigManApp.com; some builds omit “.com”.
 * POST variants are listed first: a bare GET with key=Autoprovision often returns HTTP 200 as the menu HTML
 * without starting a download (same class of issue as other ConfigManApp actions that return 200 but do nothing).
 *
 * @return array<int,array{method:string,path:string,body:?string}>
 */
function zts_fanvil_http_autoprovision_attempts()
{
	return Zts_FanvilHttpNotifyService::autoprovisionAttempts();
}

/**
 * Fanvil: HTTP to built-in ConfigManApp (Autoprovision). Uses admin + password from General Settings.
 * Tries POST then GET candidates over http then https; follows redirects (phones often redirect to HTTPS).
 *
 * @param string $ip_literal
 * @param string $admin_password
 * @param string|int|bool $trust_all_certs zts_settings security_trust_certificates
 * @return string status token for logs. On success: ok_https_200|POST /cgi-bin/ConfigManApp.com (method + path after |).
 */
function zts_fanvil_http_autoprovision($ip_literal, $admin_password, $trust_all_certs)
{
	return Zts_FanvilHttpNotifyService::runAutoprovision($ip_literal, $admin_password, $trust_all_certs);
}

/**
 * Fanvil phones use different SIP NOTIFY semantics than Yealink; Yealink-style
 * reboot NOTIFY is often ignored or disruptive. Heuristic uses profile + model/name.
 *
 * @param string $model
 * @param string $name
 * @param string $prov_profile provisioning_profile setting or ''.
 * @return bool true => send check-sync only (no yealink-reboot NOTIFY).
 */
function zts_notify_extension_is_fanvil_heuristic($model, $name, $prov_profile)
{
	return Zts_NotifyVendorHeuristic::isFanvil($model, $name, $prov_profile);
}

/**
 * Edit Phone UI / save: treat device as Fanvil (H2U/H5/H6W, profile, name/model heuristics).
 *
 * @param array $device From zts_get_phones_edit()
 * @return bool
 */
function zts_phones_edit_ui_is_fanvil($device)
{
	if (!is_array($device))
	{
		return false;
	}
	$m = isset($device['model']) ? $device['model'] : '';
	$n = isset($device['name']) ? $device['name'] : '';
	$p = isset($device['settings']['provisioning_profile']) ? $device['settings']['provisioning_profile'] : 'auto';

	return zts_notify_extension_is_fanvil_heuristic($m, $n, $p);
}

/**
 * Whether POSTed phone save should apply Fanvil-only fields (lines 1–2, hotline).
 *
 * @param string $edit_id zts_devices.id or empty for new
 * @param array  $post    $_POST
 * @return bool
 */
function zts_phones_edit_post_is_fanvil($edit_id, $post)
{
	if (!is_array($post))
	{
		return false;
	}
	global $db;

	$prof = isset($post['provisioning_profile']) ? trim((string) $post['provisioning_profile']) : 'auto';
	if ($prof === 'fanvil')
	{
		return true;
	}
	if ($prof === 'yealink')
	{
		return false;
	}
	$model = '';
	if ($edit_id !== '' && $edit_id !== null)
	{
		$model = (string) sql("SELECT model FROM zts_devices WHERE id = \"".$db->escapeSimple($edit_id)."\"", 'getOne');
	}
	$name = isset($post['name']) ? trim((string) $post['name']) : '';

	return zts_notify_extension_is_fanvil_heuristic($model, $name, 'auto');
}

/**
 * Hotline/WarmLine per SIP line (Fanvil). Stored as fanvil_sip_hotline_{1|2}_{enable|delay|number}.
 * Legacy keys fanvil_sip_hotline_{enable,delay,number} map to line 1 when line 1 keys are absent.
 *
 * @param array $device From zts_get_phones_edit()
 * @param int   $lineNum 1 or 2
 * @return array enable '0'|'1', delay int 0..30, number string max 39
 */
function zts_fanvil_hotline_row_values($device, $lineNum)
{
	$lineNum = (int) $lineNum;
	if ($lineNum < 1 || $lineNum > 2)
	{
		$lineNum = 1;
	}
	$s = (is_array($device) && isset($device['settings']) && is_array($device['settings'])) ? $device['settings'] : array();

	$en = null;
	$delay = null;
	$num = null;

	if (array_key_exists('fanvil_sip_hotline_'.$lineNum.'_enable', $s))
	{
		$en = (string) $s['fanvil_sip_hotline_'.$lineNum.'_enable'];
	}
	if (array_key_exists('fanvil_sip_hotline_'.$lineNum.'_delay', $s))
	{
		$delay = (int) $s['fanvil_sip_hotline_'.$lineNum.'_delay'];
	}
	if (array_key_exists('fanvil_sip_hotline_'.$lineNum.'_number', $s))
	{
		$num = trim((string) $s['fanvil_sip_hotline_'.$lineNum.'_number']);
	}

	if ($lineNum === 1 && !array_key_exists('fanvil_sip_hotline_1_enable', $s) && isset($s['fanvil_sip_hotline_enable']))
	{
		$en = (string) $s['fanvil_sip_hotline_enable'];
	}
	if ($lineNum === 1 && !array_key_exists('fanvil_sip_hotline_1_delay', $s) && isset($s['fanvil_sip_hotline_delay']))
	{
		$delay = (int) $s['fanvil_sip_hotline_delay'];
	}
	if ($lineNum === 1 && !array_key_exists('fanvil_sip_hotline_1_number', $s) && isset($s['fanvil_sip_hotline_number']))
	{
		$num = trim((string) $s['fanvil_sip_hotline_number']);
	}

	if ($en === null)
	{
		$en = '0';
	}
	if ($delay === null)
	{
		$delay = 0;
	}
	if ($num === null)
	{
		$num = '';
	}
	if ($delay < 0)
	{
		$delay = 0;
	}
	if ($delay > 30)
	{
		$delay = 30;
	}
	$enable = ($en !== '' && $en !== '0') ? '1' : '0';
	if (strlen($num) > 39)
	{
		$num = substr($num, 0, 39);
	}

	return array(
		'enable' => $enable,
		'delay' => $delay,
		'number' => $num,
	);
}

/**
 * @param string $edit_id
 * @return void
 */
function zts_phones_edit_delete_fanvil_hotline_settings($edit_id)
{
	global $db;

	if ($edit_id === '' || $edit_id === null)
	{
		return;
	}
	$eid = $db->escapeSimple($edit_id);
	sql("DELETE FROM zts_device_settings WHERE id = '".$eid."' AND keyword IN (
		'fanvil_sip_hotline_enable','fanvil_sip_hotline_delay','fanvil_sip_hotline_number',
		'fanvil_sip_hotline_1_enable','fanvil_sip_hotline_1_delay','fanvil_sip_hotline_1_number',
		'fanvil_sip_hotline_2_enable','fanvil_sip_hotline_2_delay','fanvil_sip_hotline_2_number')");
}

/**
 * @param string $deviceid FreePBX devices.id / PJSIP endpoint
 * @param bool   $fanvil_light If true, only yealink-check-cfg (Event: check-sync; Fanvil autoprovision, no reboot).
 * @return bool true if AMI commands were sent
 */
function zts_notify_pjsip_lines($deviceid, $fanvil_light)
{
	return Zts_NotifyPjsipService::sendToEndpoint($deviceid, $fanvil_light);
}

/**
 * Notify phones: SIP NOTIFY + optional Fanvil HTTP autoprovision.
 *
 * @param string|string[]|null $id zts_devices.id or null for all
 * @param bool                 $soft If true, SIP check-sync only (no reboot, no Fanvil HTTP)
 * @return array<int,string>
 */
function zts_notify_checkconfig($id = null, $soft = false)
{
	return Zts_NotifyCheckConfigService::run($id, $soft);
}

/**
 * @param string $deviceid FreePBX line / PJSIP endpoint id
 * @param mixed  $device_edit_id Optional zts_devices.id (from phones edit)
 * @param bool   $withFanvilHttp  Pass false from Save form to avoid long HTTP probes
 */
function zts_notify_checkconfig_deviceid($deviceid, $device_edit_id = null, $withFanvilHttp = true)
{
	Zts_NotifyCheckConfigService::runForDeviceId($deviceid, $device_edit_id, $withFanvilHttp);
}

// =============================================================================
// DROPDOWN HELPERS
// =============================================================================

function zts_dropdown_lines($id)
{
	global $db;

	$dropdown = array('' => '');
	$lines = array();

	$results = sql("SELECT devices.id, devices.description, users.extension, users.name FROM devices
		LEFT OUTER JOIN users on devices.user = users.extension
		WHERE tech IN ('sip', 'pjsip') ORDER BY devices.id",'getAll',DB_FETCHMODE_ASSOC);

	foreach($results as $result)
		$lines[$result['id']]=$result['id'] .
			(!empty($result['extension']) ? ': '.$result['name'].' <'.$result['extension'].'>' :
			(!empty($result['description']) ? ': '.$result['description'] : ''));

	if(count($lines) > 0)
		$dropdown['FreePBX Lines'] = $lines;

	return $dropdown;
}

function zts_dropdown_linekey_types()
{
	return array(
		'0' => 'N/A (Disabled)',
		'15' => 'Line',
		'16' => 'BLF',
		'13' => 'Speed Dial',
		'11' => 'DTMF',
		'14' => 'Intercom',
		'10' => 'Call Park',
		'27' => 'Group Pickup',
	);
}

/**
 * Raw dial target from linekey (Value, else Extension).
 *
 * @param array $linekey Row from zts_device_linekeys.
 * @return string trimmed number/extension or ''
 */
function zts_fanvil_linekey_raw_dial_value($linekey)
{
	if (!is_array($linekey))
	{
		return '';
	}
	$v = '';
	if (isset($linekey['value']) && $linekey['value'] !== '' && $linekey['value'] !== null)
	{
		$v = trim((string)$linekey['value']);
	}
	if ($v === '' && isset($linekey['extension']) && $linekey['extension'] !== '' && $linekey['extension'] !== null)
	{
		$v = trim((string)$linekey['extension']);
	}
	return $v;
}

/**
 * Fanvil Memory Key / BLF / Speed Dial value: "ext@<SIPLine>/f" (phone web UI: Subtype /f, SIP line index 1..2).
 * Does not append if "@n/f" is already present (admin hand-entered full value).
 *
 * @param string $raw     Extension or number only (no @ suffix).
 * @param int    $sip_line SIP line index on handset (1 or 2 on H2U).
 * @return string
 */
function zts_fanvil_linekey_append_memory_suffix($raw, $sip_line = 1)
{
	$raw = trim((string)$raw);
	if ($raw === '')
	{
		return '';
	}
	$sip_line = (int)$sip_line;
	if ($sip_line < 1)
	{
		$sip_line = 1;
	}
	if ($sip_line > 10)
	{
		$sip_line = 10;
	}
	if (preg_match('#@\\d+/f$#i', $raw))
	{
		return $raw;
	}
	return $raw . '@' . $sip_line . '/f';
}

/**
 * Fanvil H5/H6W <PHONE CONFIG MODULE> BLF value: monitored extension as "1234@1/f".
 * Uses Value/Extension from Yealink Phones linekey row.
 *
 * @param array $linekey Row from zts_device_linekeys (value, extension, …).
 * @return string
 */
function zts_fanvil_linekey_blf_subscribe_value($linekey)
{
	return zts_fanvil_linekey_append_memory_suffix(zts_fanvil_linekey_raw_dial_value($linekey), 1);
}

/**
 * Fanvil Speed Dial (Memory Key, DB type 13): same Value layout as phone export, e.g. "2111@1/f".
 *
 * @param array $linekey  Row from zts_device_linekeys.
 * @param int   $sip_line SIP line index shown in web UI (1 = primary line when both exist).
 * @return string
 */
function zts_fanvil_linekey_speed_dial_value($linekey, $sip_line = 1)
{
	return zts_fanvil_linekey_append_memory_suffix(zts_fanvil_linekey_raw_dial_value($linekey), $sip_line);
}

/**
 * Fanvil DSS / SoftDss config column alignment (matches OEM spacing before ':').
 *
 * @param string $keyStem e.g. "Fkey1", "Fkey10", "exKey2"
 * @param string $field   Type|Value|Title|ICON
 * @param string $value
 * @return string
 */
function zts_fanvil_dss_column_line($keyStem, $field, $value)
{
	$left = $keyStem . ' ' . $field;
	return str_pad($left, 27, ' ', STR_PAD_RIGHT) . ':' . $value;
}

function zts_dropdown($id, $default = false, $defaultvalue = 'Default')
{
	$dropdowns['transport'] = array(
		'0' => 'UDP',
		'1' => 'TCP',
		'2' => 'TLS',
	);

	$dropdowns['protocol'] = array(
		'HTTP' => 'HTTP',
		'HTTPS' => 'HTTPS',
	);

	$dropdowns['enabled_disabled'] = array(
		'1' => _('Enabled'),
		'0' => _('Disabled'),
	);

	$merged = $dropdowns[$id];
	if ($default)
	{
		$merged = array('' => _($defaultvalue)) + $merged;
	}

	return $merged;
}

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

function zts_array_escape($values)
{
	global $db;

	if($values == null)
		return array();

	if(!is_array($values))
		$values = array($values);

	$escaped = array();
	foreach($values as $value)
		$escaped[] = $db->escapeSimple($value);

	return $escaped;
}

/**
 * MAC addresses that must never auto-create an inventory device (vendor probes / placeholders).
 *
 * @param string $mac 12 hex chars, any case
 * @return bool true = OK to auto-register when missing from DB
 */
function zts_provisioning_mac_allows_autoreg($mac)
{
	if (!is_string($mac) || strlen($mac) !== 12)
	{
		return false;
	}
	$mac = strtoupper($mac);
	static $denylist = array(
		'F00000000000', // Fanvil placeholder .boot probe (real MAC is still in User-Agent)
		'000000000000',
		'FFFFFFFFFFFF',
	);
	return !in_array($mac, $denylist, true);
}

/**
 * PJSIP secret for a FreePBX extension: try endpoint row, then auth object (keyword authentication).
 *
 * @param string $extension FreePBX extension / PJSIP endpoint id (string)
 * @return string empty if not found
 */
function zts_provisioning_pjsip_secret($extension)
{
	global $db;

	if ($extension === null || $extension === '')
	{
		return '';
	}
	$has_pjsip = sql("SHOW TABLES LIKE 'pjsip'", 'getOne');
	if (empty($has_pjsip))
	{
		return '';
	}
	$e = $db->escapeSimple((string) $extension);
	$s = sql("SELECT data FROM pjsip
		WHERE id = '".$e."'
		AND keyword IN ('password','secret')
		ORDER BY CASE keyword WHEN 'password' THEN 0 ELSE 1 END
		LIMIT 1", 'getOne');
	if (!empty($s))
	{
		return (string) $s;
	}
	$auth = sql("SELECT data FROM pjsip WHERE id = '".$e."' AND keyword = 'authentication'", 'getOne');
	if (empty($auth))
	{
		return '';
	}
	foreach (explode(',', (string) $auth) as $auth_id)
	{
		$auth_id = trim($auth_id);
		if ($auth_id === '')
		{
			continue;
		}
		$a = $db->escapeSimple($auth_id);
		$s = sql("SELECT data FROM pjsip
			WHERE id = '".$a."'
			AND keyword IN ('password','secret')
			ORDER BY CASE keyword WHEN 'password' THEN 0 ELSE 1 END
			LIMIT 1", 'getOne');
		if (!empty($s))
		{
			return (string) $s;
		}
	}
	return '';
}

function zts_lookup_mac($mac)
{
	global $db;

	return sql("SELECT id FROM zts_devices WHERE mac = '" . $db->escapeSimple($mac) . "'",'getOne');
}

/**
 * Leading token from device name "MODEL-12HEXMAC" (auto-provision / admin naming).
 *
 * @param string $name
 * @return string e.g. H2U-V2, T46, or ''
 */
function zts_model_from_device_name($name)
{
	if (!is_string($name) || $name === '')
	{
		return '';
	}
	$trimmed = trim($name);
	if (preg_match('/^\d+-([^-]+)-[0-9A-F]{4}$/i', $trimmed, $extModelMac4))
	{
		return trim($extModelMac4[1]);
	}
	if (!preg_match('/^(.+)-([0-9A-F]{12})$/i', $trimmed, $matches))
	{
		return '';
	}
	$prefix = trim($matches[1]);
	// {extension}-{model} or {extension}-{mac} inventory names must not be treated as model tokens.
	if ($prefix !== '' && preg_match('/^[0-9]+$/', $prefix))
	{
		return '';
	}

	return $prefix;
}

/**
 * DHCP hostname from device Name (phones_list) for provisioning templates.
 *
 * @param array $device zts_get_phones_edit row
 * @return string
 */
function zts_provisioning_dhcp_hostname($device)
{
	return Zts_DeviceNamingService::dhcpHostname(is_array($device) ? $device : array());
}

/**
 * Model string for vendor detection and Fanvil branches when DB `model` was overwritten (e.g. T00) by a generic User-Agent.
 *
 * @param array $device Row from zts_get_phones_edit()
 * @return string
 */
function zts_device_effective_model($device)
{
	if (!is_array($device))
	{
		return '';
	}
	$m = isset($device['model']) ? trim((string) $device['model']) : '';
	$from_name = zts_model_from_device_name(isset($device['name']) ? $device['name'] : '');

	if ($m !== '' && $m !== 'T00')
	{
		return $m;
	}
	if ($from_name !== '' && $from_name !== 'T00')
	{
		return $from_name;
	}

	return $m;
}

/**
 * Path to empty flag file: touch to enable verbose provisioning in Apache error.log when
 * General Setting "Provisioning log mode" is Off (legacy compatibility).
 * Verbose legacy: touch zerotouchsip/provisioning/.provision_verbose (or symlinked webroot; see DEPLOY.md).
 */
function zts_provisioning_verbose_flag_path()
{
	return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'provisioning' . DIRECTORY_SEPARATOR . '.provision_verbose';
}

function zts_provisioning_verbose_enabled()
{
	return is_file(zts_provisioning_verbose_flag_path());
}

/**
 * Resolve a safe absolute log path under /var/log, or empty string if invalid.
 *
 * @param string $path
 * @return string
 */
function zts_provisioning_log_file_safe_path($path)
{
	$path = trim((string) $path);
	if ($path === '' || strpos($path, '..') !== false || strpos($path, "\0") !== false) {
		return '';
	}
	if ($path[0] !== '/') {
		return '';
	}
	$base = basename($path);
	if ($base === '' || $base === '.' || $base === '..') {
		return '';
	}
	$dir = dirname($path);
	$rp_dir = @realpath($dir);
	$rp_varlog = @realpath('/var/log');
	if ($rp_dir === false || $rp_varlog === false) {
		return '';
	}
	$vl = strlen($rp_varlog);
	if ($rp_dir !== $rp_varlog && strncmp($rp_dir, $rp_varlog . DIRECTORY_SEPARATOR, $vl + 1) !== 0) {
		return '';
	}
	$full = $rp_dir . DIRECTORY_SEPARATOR . $base;
	if (strncmp($full, $rp_varlog, $vl) !== 0) {
		return '';
	}
	if (strlen($full) > $vl && $full[$vl] !== DIRECTORY_SEPARATOR) {
		return '';
	}
	return $full;
}

/**
 * @return array{apache:bool,file:string}
 */
function zts_provisioning_log_resolve_targets()
{
	static $resolved = null;
	static $warned_invalid_file = false;

	if ($resolved !== null) {
		return $resolved;
	}
	$resolved = array('apache' => false, 'file' => '');
	$mode = 'off';
	$file_setting = '';
	if (function_exists('zts_get_general_edit')) {
		$g = zts_get_general_edit();
		if (isset($g['provisioning_log_mode'])) {
			$mode = (string) $g['provisioning_log_mode'];
		}
		if (isset($g['provisioning_log_file'])) {
			$file_setting = trim((string) $g['provisioning_log_file']);
		}
	}
	if (!in_array($mode, array('off', 'apache', 'file'), true)) {
		$mode = 'off';
	}
	$legacy = zts_provisioning_verbose_enabled();
	if ($mode === 'apache') {
		$resolved['apache'] = true;
	} elseif ($mode === 'file') {
		$safe = zts_provisioning_log_file_safe_path($file_setting);
		if ($safe !== '') {
			$resolved['file'] = $safe;
		} elseif (!$warned_invalid_file) {
			$warned_invalid_file = true;
			error_log(Zts_ModuleBranding::logTag('Prov').' provisioning_log_file is not writable or not under /var/log; file logging skipped');
		}
	} elseif ($mode === 'off' && $legacy) {
		$resolved['apache'] = true;
	}
	return $resolved;
}

/**
 * @param string $event short label (e.g. config_vendor, fanvil_ok)
 * @param array $context extra fields (never log passwords)
 */
function zts_provisioning_log($event, $context = array())
{
	$r = zts_provisioning_log_resolve_targets();
	if (!$r['apache'] && $r['file'] === '') {
		return;
	}
	$ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	if (strlen($ua) > 240) {
		$ua = substr($ua, 0, 240) . '…';
	}
	$row = array_merge(array(
		'event' => $event,
		'ts' => date('c'),
		'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
		'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
		'ua' => $ua,
	), $context);
	$msg = Zts_ModuleBranding::logTag('Prov').' ' . json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	if ($r['file'] !== '') {
		@error_log($msg . "\n", 3, $r['file']);
	}
	if ($r['apache']) {
		error_log($msg);
	}
}

function zts_detect_model($user_agent)
{
	return Zts_VendorRegistry::detectModel($user_agent);
}

function zts_detect_vendor($user_agent, $model = '')
{
	return Zts_VendorRegistry::detectVendorId($user_agent, $model);
}

function zts_detect_firmware($user_agent, $vendor = 'yealink')
{
	return Zts_VendorRegistry::detectFirmware($user_agent, $vendor);
}

/**
 * Admin config.php URL for this module (neutral helper for views).
 *
 * @param string $form
 * @param array  $extra
 * @return string
 */
function zts_admin_url($form, array $extra = array())
{
	return Zts_ModuleIdentifiers::adminPageUrl($form, $extra);
}

?>
