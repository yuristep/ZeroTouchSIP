<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Fanvil H2 / H5 / H6 / W611 device .cfg from baseline templates + {PLACEHOLDER} substitution.
 */
class Zts_FanvilDeviceConfigService
{
	const FAMILY_H2 = 'h2';
	const FAMILY_H5 = 'h5';
	const FAMILY_H6 = 'h6';
	const FAMILY_W611 = 'w611';

	/** @var array<string,array<int,array{name:string,lines:array<int,string>}>> */
	private static $parsedBaselines = array();

	/** @var array<string,array<int,string>> */
	private static $rawBaselineLines = array();

	/**
	 * @param string $modelUpper
	 * @return string
	 */
	public static function resolveFamily($modelUpper)
	{
		$m = strtoupper(trim((string) $modelUpper));
		if (strpos($m, 'H2') !== false)
		{
			return self::FAMILY_H2;
		}
		if (strpos($m, 'H6') !== false)
		{
			return self::FAMILY_H6;
		}
		if (strpos($m, 'W611') !== false)
		{
			return self::FAMILY_W611;
		}
		if (strpos($m, 'H5') !== false)
		{
			return self::FAMILY_H5;
		}

		return self::FAMILY_H2;
	}

	/**
	 * @param string $family
	 * @return array<string,mixed>
	 */
	public static function profile($family)
	{
		switch ($family)
		{
			case self::FAMILY_H5:
				return array(
					'programmable_dss' => 6,
					'function_key_file_slots' => 15,
					'soft_dss_slots' => 10,
					'uses_dsskey_module' => false,
				);
			case self::FAMILY_H6:
				return array(
					'programmable_dss' => 5,
					'softdss_slots' => 5,
					'sidekey_slots' => 5,
					'dsskey_config_slots' => 10,
					'uses_dsskey_module' => true,
				);
			case self::FAMILY_W611:
				return array(
					'programmable_dss' => 6,
					'sidekey_slots' => 2,
					'dsskey_config_slots' => 8,
					'dsskey_config_pages' => 2,
					'softdss_slots' => 10,
					'uses_dsskey_module' => true,
				);
			default:
				return array(
					'programmable_dss' => 10,
					'softdss_slots' => 10,
					'dsskey_config_slots' => 1,
					'uses_dsskey_module' => true,
				);
		}
	}

	/**
	 * @param array $network
	 * @return string
	 */
	public static function resolveSipHostPublic(array $network)
	{
		return self::resolveSipHost($network);
	}

	/**
	 * @param array<string,mixed> $ctx device, network, global, mac, device_id, model
	 * @return array{ok:bool,lines:array<int,string>,message:string,meta:array<string,mixed>}
	 */
	public static function build(array $ctx)
	{
		$device = isset($ctx['device']) && is_array($ctx['device']) ? $ctx['device'] : array();
		$network = isset($ctx['network']) && is_array($ctx['network']) ? $ctx['network'] : array();
		$global = isset($ctx['global']) && is_array($ctx['global']) ? $ctx['global'] : array();
		$mac = isset($ctx['mac']) ? (string) $ctx['mac'] : '';
		$deviceId = isset($ctx['device_id']) ? (int) $ctx['device_id'] : 0;
		$model = isset($ctx['model']) ? (string) $ctx['model'] : '';

		$family = self::resolveFamily($model);
		$isH2 = ($family === self::FAMILY_H2);
		$isH5 = ($family === self::FAMILY_H5);
		$isH6 = ($family === self::FAMILY_H6);
		$isW611 = ($family === self::FAMILY_W611);

		$sipCheck = self::validateSipAssignable($device, $network, $mac, $deviceId);
		if (!$sipCheck['ok'])
		{
			return $sipCheck;
		}

		$placeholderCtx = array(
			'device' => $device,
			'network' => $network,
			'global' => $global,
			'mac' => $mac,
			'device_id' => $deviceId,
			'model' => $model,
			'family' => $family,
			'speed_dial_sip_line' => $sipCheck['meta']['speed_dial_sip_line'],
		);
		$map = Zts_FanvilBaselinePlaceholders::buildMap($placeholderCtx);
		$map['CONFIG_VERSION'] = Zts_FanvilConfigVersionService::forNetwork($network);
		$map['NOTIFY_REBOOT'] = '1';

		$out = array();
		$out[] = zts_fanvil_padded_voip_file_first_line($map['CONFIG_VERSION']);
		$out[] = '';

		foreach (self::parseBaselineModules($family) as $module)
		{
			if ($family === self::FAMILY_W611 && $module['name'] === 'AP DEFINED MODULE')
			{
				continue;
			}

			if ($module['name'] === 'MMI CONFIG MODULE')
			{
				$mmi = zts_fanvil_mmi_config_lines($network);
				if (count($mmi) > 0)
				{
					$out = array_merge($out, $mmi);
					$out[] = '';
					continue;
				}
			}

			$lines = Zts_FanvilBaselinePlaceholders::apply($module['lines'], $map);
			if ($module['name'] === 'NET CONFIG MODULE')
			{
				$deviceSettings = isset($device['settings']) && is_array($device['settings']) ? $device['settings'] : array();
				$lines = Zts_NetworkWifiProfileService::applyFanvilNetModuleLines(
					$lines,
					$family,
					isset($network['settings']) && is_array($network['settings']) ? $network['settings'] : array(),
					$deviceSettings
				);
			}
			if ($module['name'] === 'DSSKEY CONFIG MODULE')
			{
				$lines = self::applyH6SidekeyDefaults($lines, $family);
			}
			$out = array_merge($out, $lines);
			$out[] = '';
		}

		$out[] = '<<END OF FILE>>';

		return array(
			'ok' => true,
			'lines' => $out,
			'message' => '',
			'meta' => array_merge($sipCheck['meta'], array(
				'family' => $family,
				'is_h2' => $isH2 ? 1 : 0,
				'is_h5' => $isH5 ? 1 : 0,
				'is_h6' => $isH6 ? 1 : 0,
				'is_w611' => $isW611 ? 1 : 0,
			)),
		);
	}

	/**
	 * H6 Sidekey block stays disabled; Dsskey/SoftDss use LINEKEY_* from template.
	 *
	 * @param array<int,string> $lines
	 * @param string $family
	 * @return array<int,string>
	 */
	private static function applyH6SidekeyDefaults(array $lines, $family)
	{
		if ($family !== self::FAMILY_H6)
		{
			return $lines;
		}
		$inSidekey = false;
		$out = array();
		foreach ($lines as $line)
		{
			if (strpos($line, '--Sidekey Config') === 0)
			{
				$inSidekey = true;
				$out[] = $line;
				continue;
			}
			if ($inSidekey)
			{
				if (strpos($line, '--') === 0)
				{
					$inSidekey = false;
					$out[] = $line;
					continue;
				}
				if (preg_match('/^Fkey(\d+) Type\s+:/', $line) === 1)
				{
					$out[] = preg_replace('/:.*$/', ':0', $line);
					continue;
				}
				if (preg_match('/^Fkey(\d+) (Value|Title)\s+:/', $line) === 1)
				{
					$out[] = preg_replace('/:.*$/', ':', $line);
					continue;
				}
			}
			$out[] = $line;
		}

		return $out;
	}

	/**
	 * @param array $device
	 * @param array $network
	 * @param string $mac
	 * @param int $deviceId
	 * @return array{ok:bool,lines:array<int,string>,message:string,meta:array<string,mixed>}
	 */
	private static function validateSipAssignable(array $device, array $network, $mac, $deviceId)
	{
		global $db;

		$sipHost = self::resolveSipHost($network);
		$fanvilWantsSip = false;
		foreach ($device['lines'] as $lid => $ln)
		{
			if (!is_array($ln) || (int) $lid > 2)
			{
				continue;
			}
			if (!empty($ln['deviceid']))
			{
				$fanvilWantsSip = true;
				break;
			}
		}
		if ($fanvilWantsSip && $sipHost === '')
		{
			zts_provisioning_log('fanvil_503_no_sip_host', array('mac' => $mac, 'device_id' => $deviceId));
			error_log(Zts_ModuleBranding::displayName().' Fanvil: SIP server address empty for device id '.$deviceId.' mac '.$mac);
			return array(
				'ok' => false,
				'lines' => array(),
				'message' => "Provisioning incomplete: SIP Server Address is empty for this provisioning network. Set it to your PBX FQDN (e.g. pbx.example.com) or rely on the provisioning HTTP Host.\n",
				'meta' => array(),
			);
		}

		$configured = 0;
		$lineBuilt = array(1 => false, 2 => false);
		foreach ($device['lines'] as $lineid => $line)
		{
			if ((int) $lineid > 2 || !is_array($line) || empty($line['deviceid']))
			{
				continue;
			}
			$freepbxDevice = sql(
				"SELECT devices.id, devices.description, devices.dial, users.extension, users.name
				FROM devices LEFT OUTER JOIN users ON devices.user = users.extension
				WHERE devices.id = '".$db->escapeSimple($line['deviceid'])."'",
				'getRow',
				DB_FETCHMODE_ASSOC
			);
			if (!$freepbxDevice)
			{
				continue;
			}
			$extension = $freepbxDevice['extension'] ? $freepbxDevice['extension'] : $freepbxDevice['dial'];
			$secret = sql("SELECT data FROM sip WHERE id = '".$db->escapeSimple($extension)."' AND keyword = 'secret'", 'getOne');
			if (empty($secret))
			{
				$secret = zts_provisioning_pjsip_secret($extension);
			}
			if (empty($secret))
			{
				continue;
			}
			$configured++;
			$lineBuilt[$lineid] = true;
		}

		if ($configured === 0)
		{
			return array(
				'ok' => false,
				'lines' => array(),
				'message' => "Provisioning incomplete: assign FreePBX Device to SIP line(s) for this phone in the ZeroTouchSIP module, or fix PJSIP secret (endpoint vs authentication object in pjsip table) for the extension.\n",
				'meta' => array('sip_lines_built' => 0),
			);
		}

		$speedDialLine = 1;
		if (empty($lineBuilt[1]) && !empty($lineBuilt[2]))
		{
			$speedDialLine = 2;
		}

		return array(
			'ok' => true,
			'lines' => array(),
			'message' => '',
			'meta' => array(
				'sip_lines_built' => $configured,
				'speed_dial_sip_line' => $speedDialLine,
			),
		);
	}

	/**
	 * @param array $network
	 * @return string
	 */
	private static function resolveSipHost(array $network)
	{
		$sipHost = isset($network['settings']['sip_server_address']) ? trim((string) $network['settings']['sip_server_address']) : '';
		if ($sipHost !== '')
		{
			return rtrim($sipHost, '/');
		}
		$ph = !empty($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';
		$ph = preg_replace('#^https?://#i', '', $ph);
		$ph = preg_replace('#/.*$#', '', $ph);
		if (strpos($ph, ':') !== false && preg_match('/^\[.+\]$/', $ph) !== 1)
		{
			$ph = preg_replace('#:\d+$#', '', $ph);
		}
		if ($ph === '' && !empty($_SERVER['SERVER_NAME']))
		{
			$ph = trim((string) $_SERVER['SERVER_NAME']);
		}

		return rtrim($ph, '/');
	}

	/**
	 * @param string $family
	 * @return array<int,array{name:string,lines:array<int,string>}>
	 */
	private static function parseBaselineModules($family)
	{
		if (isset(self::$parsedBaselines[$family]))
		{
			return self::$parsedBaselines[$family];
		}

		$rawLines = self::loadRawBaselineLines($family);
		$modules = array();
		$current = null;
		foreach ($rawLines as $line)
		{
			if (preg_match('/^<(.+)>$/', $line, $m) === 1)
			{
				if ($current !== null)
				{
					$modules[] = $current;
				}
				$current = array('name' => $m[1], 'lines' => array($line));
				continue;
			}
			if ($current !== null)
			{
				$current['lines'][] = $line;
			}
		}
		if ($current !== null)
		{
			$modules[] = $current;
		}

		self::$parsedBaselines[$family] = $modules;
		return $modules;
	}

	/**
	 * @param string $family
	 * @return array<int,string>
	 */
	private static function loadRawBaselineLines($family)
	{
		if (isset(self::$rawBaselineLines[$family]))
		{
			return self::$rawBaselineLines[$family];
		}

		$path = dirname(__DIR__, 3).'/provisioning/fanvil/baseline/'.self::baselineFilename($family);
		$lines = array();
		if (!is_readable($path))
		{
			self::$rawBaselineLines[$family] = $lines;
			return $lines;
		}

		$content = file_get_contents($path);
		foreach (preg_split('/\r\n|\n|\r/', (string) $content) as $line)
		{
			$line = rtrim($line, "\r\n");
			if (preg_match('/^<<VOIP CONFIG FILE>>/', $line) === 1)
			{
				continue;
			}
			if ($line === '<<END OF FILE>>')
			{
				break;
			}
			$lines[] = $line;
		}

		self::$rawBaselineLines[$family] = $lines;
		return $lines;
	}

	/**
	 * @param string $family
	 * @return string
	 */
	private static function baselineFilename($family)
	{
		switch ($family)
		{
			case self::FAMILY_H5:
				return 'h5_default_user_config.txt';
			case self::FAMILY_H6:
				return 'h6_default_user_config.txt';
			case self::FAMILY_W611:
				return 'w611_default_user_config.txt';
			default:
				return 'h2_default_user_config.txt';
		}
	}
}
