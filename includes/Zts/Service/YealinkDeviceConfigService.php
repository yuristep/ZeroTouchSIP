<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Yealink MAC .cfg from baseline template + generated account/linekey sections.
 */
class Zts_YealinkDeviceConfigService
{
	const FAMILY_DESK = 'desk';
	const FAMILY_T4 = 't4';
	const FAMILY_T4_HD = 't4_hd';
	const FAMILY_T5 = 't5';

	const MARKER_ACCOUNTS = ';; @YEALINK_ACCOUNTS@';
	const MARKER_LINEKEYS = ';; @YEALINK_LINEKEYS@';
	const MARKER_EXPANSION = ';; @YEALINK_EXPANSION@';
	const MARKER_WIFI = ';; @YEALINK_WIFI@';
	const MARKER_SECURITY = ';; @YEALINK_SECURITY@';

	/** @var array<int,string>|null */
	private static $rawBaselineLines = null;

	/**
	 * @param string $modelUpper
	 * @return string desk|t4|t4_hd|t5
	 */
	public static function resolveFamily($modelUpper)
	{
		$m = strtoupper(trim((string) $modelUpper));
		if (preg_match('/^T5/', $m) === 1)
		{
			return self::FAMILY_T5;
		}
		if (preg_match('/^T4[6-9]/', $m) === 1 || preg_match('/^T48/', $m) === 1)
		{
			return self::FAMILY_T4_HD;
		}
		if (preg_match('/^T4/', $m) === 1)
		{
			return self::FAMILY_T4;
		}

		return self::FAMILY_DESK;
	}

	/**
	 * @param array<string,mixed> $ctx device, network, global, mac, device_id, model
	 * @return array{ok:bool,lines:array<int,string>,message:string,meta:array<string,mixed>}
	 */
	public static function build(array $ctx)
	{
		global $db;

		$device = isset($ctx['device']) && is_array($ctx['device']) ? $ctx['device'] : array();
		$network = isset($ctx['network']) && is_array($ctx['network']) ? $ctx['network'] : array();
		$global = isset($ctx['global']) && is_array($ctx['global']) ? $ctx['global'] : array();
		$mac = isset($ctx['mac']) ? (string) $ctx['mac'] : '';
		$deviceId = isset($ctx['device_id']) ? (int) $ctx['device_id'] : 0;
		$model = isset($ctx['model']) ? (string) $ctx['model'] : '';
		$family = self::resolveFamily($model);

		$settings = isset($network['settings']) && is_array($network['settings']) ? $network['settings'] : array();
		$sipHost = isset($settings['sip_server_address']) ? trim((string) $settings['sip_server_address']) : '';
		if ($sipHost === '')
		{
			$sipHost = self::fallbackSipHost();
		}

		$accountResult = self::buildAccountLines($device, $network, $family, $mac, $deviceId);
		if ($accountResult === null)
		{
			return array(
				'ok' => false,
				'lines' => array(),
				'message' => "Provisioning incomplete: assign FreePBX Device to SIP line(s) for this phone in the ZeroTouchSIP module, or fix PJSIP secret for the extension.\n",
				'meta' => array('sip_lines_built' => 0),
			);
		}
		$accountLines = $accountResult['lines'];
		$sipBuilt = $accountResult['count'];

		$placeholderCtx = array(
			'device' => $device,
			'network' => $network,
			'global' => $global,
			'mac' => $mac,
			'model' => $model,
			'family' => $family,
		);
		$map = Zts_YealinkBaselinePlaceholders::buildMap($placeholderCtx);

		$sections = array(
			self::MARKER_ACCOUNTS => $accountLines,
			self::MARKER_LINEKEYS => self::buildLinekeyLines($device),
			self::MARKER_EXPANSION => self::buildExpansionLines($device, $family),
			self::MARKER_WIFI => Zts_NetworkWifiProfileService::yealinkConfigLines(
				$settings,
				isset($device['settings']) && is_array($device['settings']) ? $device['settings'] : array()
			),
			self::MARKER_SECURITY => array_merge(
				zts_yealink_web_ui_security_lines($network, $global),
				array('security.trust_certificates = '.(isset($global['security_trust_certificates']) ? $global['security_trust_certificates'] : '0'))
			),
		);

		$out = array();
		foreach (self::loadRawBaselineLines() as $line)
		{
			if (isset($sections[$line]))
			{
				foreach ($sections[$line] as $sectionLine)
				{
					$out[] = $sectionLine;
				}
				if (count($sections[$line]) > 0)
				{
					$out[] = '';
				}
				continue;
			}

			$out[] = Zts_FanvilBaselinePlaceholders::applyLine($line, $map);
		}

		$out = Zts_YealinkBaselinePlaceholders::filterEmptyValues($out);

		return array(
			'ok' => true,
			'lines' => $out,
			'message' => '',
			'meta' => array(
				'family' => $family,
				'sip_lines_built' => $sipBuilt,
			),
		);
	}

	/**
	 * @param array $device
	 * @param array $network
	 * @param string $family
	 * @param string $mac
	 * @param int $deviceId
	 * @return array{lines:array<int,string>,count:int}|null
	 */
	private static function buildAccountLines(array $device, array $network, $family, $mac, $deviceId)
	{
		global $db;

		$settings = isset($network['settings']) && is_array($network['settings']) ? $network['settings'] : array();
		$sipHost = isset($settings['sip_server_address']) ? trim((string) $settings['sip_server_address']) : '';
		if ($sipHost === '')
		{
			$sipHost = self::fallbackSipHost();
		}

		$useBackupServer = in_array($family, array(self::FAMILY_T4_HD, self::FAMILY_T5), true);
		$lines = array();
		$built = 0;

		for ($lineid = 1; $lineid <= 16; $lineid++)
		{
			$line = isset($device['lines'][$lineid]) ? $device['lines'][$lineid] : null;
			if (!is_array($line) || empty($line['deviceid']))
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
			if (!is_array($freepbxDevice))
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
				zts_provisioning_log('yealink_line_skipped_no_password', array(
					'mac' => $mac,
					'lineid' => $lineid,
					'extension' => $extension,
				));
				continue;
			}

			$display = !empty($freepbxDevice['name']) ? $freepbxDevice['name'] : $extension;
			$lines[] = '###account.'.$lineid.'.password =';
			$lines[] = 'account.'.$lineid.'.auth_name = '.$extension;
			$lines[] = 'account.'.$lineid.'.display_name = '.$display;
			$lines[] = 'account.'.$lineid.'.dtmf.type = 2';
			$lines[] = 'account.'.$lineid.'.enable = 1';
			$lines[] = 'account.'.$lineid.'.label = '.$display;
			$lines[] = 'account.'.$lineid.'.nat.rport = 1';
			$lines[] = 'account.'.$lineid.'.sip_server.1.address = '.$sipHost;
			if (!empty($settings['sip_server_port']))
			{
				$lines[] = 'account.'.$lineid.'.sip_server.1.port = '.$settings['sip_server_port'];
			}
			if (isset($settings['sip_server_transport']) && (string) $settings['sip_server_transport'] !== '')
			{
				$lines[] = 'account.'.$lineid.'.sip_server.1.transport = '.$settings['sip_server_transport'];
			}
			if (!empty($settings['sip_server_expires']))
			{
				$lines[] = 'account.'.$lineid.'.sip_server.1.expires = '.$settings['sip_server_expires'];
			}
			if ($useBackupServer && $sipHost !== '')
			{
				$lines[] = 'account.'.$lineid.'.sip_server.2.address = '.$sipHost;
				$lines[] = 'account.'.$lineid.'.sip_server.2.transport_type = 1';
			}
			$lines[] = 'account.'.$lineid.'.subscribe_mwi = 1';
			$lines[] = 'account.'.$lineid.'.subscribe_mwi_to_vm = 1';
			$lines[] = 'account.'.$lineid.'.user_name = '.$extension;
			$lines[] = 'account.'.$lineid.'.password = '.$secret;
			foreach (Zts_NetworkCodecMapper::yealinkConfigLines($lineid, $settings) as $codecLine)
			{
				$lines[] = $codecLine;
			}
			$lines[] = 'voice_mail.number.'.$lineid.' = *97';
			$lines[] = '';
			$built++;
		}

		if ($built < 1)
		{
			return null;
		}

		return array(
			'lines' => $lines,
			'count' => $built,
		);
	}

	/**
	 * @param array $device
	 * @return array<int,string>
	 */
	private static function buildLinekeyLines(array $device)
	{
		$lines = array();
		$linekeys = isset($device['linekeys']) && is_array($device['linekeys']) ? $device['linekeys'] : array();

		for ($i = 1; $i <= Zts_DeviceEditService::LINEKEY_MAX; $i++)
		{
			$linekey = isset($linekeys[$i]) ? $linekeys[$i] : null;
			if (!is_array($linekey) || !isset($linekey['type']) || (string) $linekey['type'] === '' || (string) $linekey['type'] === '0')
			{
				$lines[] = 'linekey.'.$i.'.type = 0';
				continue;
			}

			$lines[] = 'linekey.'.$i.'.type = '.$linekey['type'];
			$lines[] = 'linekey.'.$i.'.line = '.(isset($linekey['line']) && (string) $linekey['line'] !== '' ? $linekey['line'] : '1');
			if (!empty($linekey['value']))
			{
				$lines[] = 'linekey.'.$i.'.value = '.$linekey['value'];
			}
			if (!empty($linekey['label']))
			{
				$lines[] = 'linekey.'.$i.'.label = '.$linekey['label'];
			}
			if (!empty($linekey['extension']))
			{
				$lines[] = 'linekey.'.$i.'.extension = '.$linekey['extension'];
			}
			if (!empty($linekey['pickup_value']))
			{
				$lines[] = 'linekey.'.$i.'.pickup_value = '.$linekey['pickup_value'];
			}
		}

		return $lines;
	}

	/**
	 * EXP50 keys: linekeys 28–40 map to expansion_module.1.key.N (T5x only).
	 *
	 * @param array $device
	 * @param string $family
	 * @return array<int,string>
	 */
	private static function buildExpansionLines(array $device, $family)
	{
		if ($family !== self::FAMILY_T5)
		{
			return array();
		}

		$lines = array();
		$linekeys = isset($device['linekeys']) && is_array($device['linekeys']) ? $device['linekeys'] : array();

		for ($expSlot = 1; $expSlot <= 40; $expSlot++)
		{
			$linekeyIndex = 27 + $expSlot;
			$linekey = isset($linekeys[$linekeyIndex]) ? $linekeys[$linekeyIndex] : null;
			if (!is_array($linekey) || !isset($linekey['type']) || (string) $linekey['type'] === '' || (string) $linekey['type'] === '0')
			{
				continue;
			}

			$lines[] = 'expansion_module.1.key.'.$expSlot.'.type = '.$linekey['type'];
			$lines[] = 'expansion_module.1.key.'.$expSlot.'.line = '.(isset($linekey['line']) && (string) $linekey['line'] !== '' ? $linekey['line'] : '1');
			if (!empty($linekey['value']))
			{
				$lines[] = 'expansion_module.1.key.'.$expSlot.'.value = '.$linekey['value'];
			}
			if (!empty($linekey['label']))
			{
				$lines[] = 'expansion_module.1.key.'.$expSlot.'.label = '.$linekey['label'];
			}
		}

		return $lines;
	}

	/**
	 * @return string
	 */
	private static function fallbackSipHost()
	{
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
	 * @return array<int,string>
	 */
	private static function loadRawBaselineLines()
	{
		if (self::$rawBaselineLines !== null)
		{
			return self::$rawBaselineLines;
		}

		$path = dirname(__DIR__, 3).'/provisioning/yealink/baseline/common_default.cfg';
		$lines = array();
		if (!is_readable($path))
		{
			self::$rawBaselineLines = $lines;

			return $lines;
		}

		$content = file_get_contents($path);
		foreach (preg_split('/\r\n|\n|\r/', (string) $content) as $line)
		{
			$lines[] = rtrim($line, "\r\n");
		}

		self::$rawBaselineLines = $lines;

		return $lines;
	}
}
