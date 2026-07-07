<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Placeholder map for Fanvil baseline templates (provisioning/fanvil/baseline/*.txt).
 *
 * Syntax in templates: {NAME} — replaced at provision time; unknown keys become empty.
 */
class Zts_FanvilBaselinePlaceholders
{
	/**
	 * @param array<string,mixed> $ctx device, network, global, mac, device_id, model, family, speed_dial_sip_line
	 * @return array<string,string>
	 */
	public static function buildMap(array $ctx)
	{
		$device = isset($ctx['device']) && is_array($ctx['device']) ? $ctx['device'] : array();
		$network = isset($ctx['network']) && is_array($ctx['network']) ? $ctx['network'] : array();
		$global = isset($ctx['global']) && is_array($ctx['global']) ? $ctx['global'] : array();
		$mac = isset($ctx['mac']) ? (string) $ctx['mac'] : '';
		$model = isset($ctx['model']) ? (string) $ctx['model'] : '';
		$family = isset($ctx['family']) ? (string) $ctx['family'] : Zts_FanvilDeviceConfigService::FAMILY_H2;
		$speedDialLine = isset($ctx['speed_dial_sip_line']) ? (int) $ctx['speed_dial_sip_line'] : 1;

		$settings = isset($network['settings']) && is_array($network['settings']) ? $network['settings'] : array();
		$isH2 = ($family === Zts_FanvilDeviceConfigService::FAMILY_H2);
		$isH5 = ($family === Zts_FanvilDeviceConfigService::FAMILY_H5);

		$phoneLang = isset($device['settings']['phone_lang']) ? (string) $device['settings']['phone_lang'] : '';
		$globalLang = isset($global['default_lang']) ? (string) $global['default_lang'] : 'English';
		$fanvilLang = Zts_FanvilLanguageOptions::cfgValue($family, $phoneLang, $globalLang);
		$fanvilBacklight = $isH2 ? '0' : '30';
		if (isset($device['settings']['phone_backlight_time']) && (string) $device['settings']['phone_backlight_time'] !== '')
		{
			$fanvilBacklight = (string) $device['settings']['phone_backlight_time'];
		}

		$sipHost = Zts_FanvilDeviceConfigService::resolveSipHostPublic($network);
		$sipPort = isset($settings['sip_server_port']) ? trim((string) $settings['sip_server_port']) : '5060';
		$sipTransport = isset($settings['sip_server_transport']) ? trim((string) $settings['sip_server_transport']) : '0';
		$sipExpires = isset($settings['sip_server_expires']) ? trim((string) $settings['sip_server_expires']) : '3600';
		$codecMap = Zts_NetworkCodecMapper::fanvilVoiceCodecMap($settings);

		$fanvilTim = isset($settings['time_zone_fanvil']) ? (string) $settings['time_zone_fanvil'] : Zts_FanvilTimeZoneOptions::defaultValue();
		$tzName = isset($settings['time_zone_name']) ? trim((string) $settings['time_zone_name']) : '';
		if ($tzName === '')
		{
			$tzName = Zts_FanvilTimeZoneOptions::labelForValue($fanvilTim);
		}
		$dst = isset($settings['daylight_saving_time_fanvil']) ? trim((string) $settings['daylight_saving_time_fanvil']) : '1';
		if (!in_array($dst, array('0', '1', '2'), true))
		{
			$dst = '1';
		}
		$ntp1 = isset($settings['ntp_server1']) ? (string) $settings['ntp_server1'] : 'pool.ntp.org';
		$ntp2 = isset($settings['ntp_server2']) && (string) $settings['ntp_server2'] !== ''
			? (string) $settings['ntp_server2'] : $ntp1;

		$sipLocalDomain = self::sipLocalDomain($sipHost);

		$map = array(
			'DEVICE_NAME' => isset($device['name']) ? (string) $device['name'] : '',
			'DEVICE_MAC' => $mac,
			'DEVICE_MODEL' => $model,
			'DHCP_HOSTNAME' => zts_provisioning_dhcp_hostname($device),
			'FANVIL_LANG' => $fanvilLang,
			'BACKLIGHT_TIME' => $fanvilBacklight,
			'NTP_SERVER1' => $ntp1,
			'NTP_SERVER2' => $ntp2,
			'TIME_ZONE' => Zts_FanvilTimeZoneOptions::cfgHoursFromTim($fanvilTim),
			'TIME_ZONE_NAME' => $tzName,
			'DST_ENABLE' => $dst,
			'AUDIO_CODEC_SETS' => $codecMap,
			'SIP_VOICE_CODEC_MAP' => $codecMap,
			'SIP_SERVER_HOST' => $sipHost,
			'SIP_SERVER_PORT' => $sipPort,
			'SIP_TRANSPORT' => $sipTransport,
			'SIP_REGISTER_TTL' => $sipExpires,
			'SIP_LOCAL_DOMAIN' => $sipLocalDomain,
		);

		$sipLines = self::resolveSipLines($device, $network, $sipHost, $sipPort, $sipTransport, $sipExpires, $codecMap, $sipLocalDomain);
		foreach ($sipLines as $key => $val)
		{
			$map[$key] = $val;
		}

		for ($n = 1; $n <= 15; $n++)
		{
			$tuple = self::linekeyTuple(
				isset($device['linekeys'][$n]) ? $device['linekeys'][$n] : null,
				$speedDialLine
			);
			$map['LINEKEY_'.$n.'_TYPE'] = $tuple['type'];
			$map['LINEKEY_'.$n.'_VALUE'] = $tuple['value'];
			$map['LINEKEY_'.$n.'_TITLE'] = $tuple['title'];
			$map['FKEY'.$n.'_TYPE'] = $tuple['type'];
			$map['FKEY'.$n.'_VALUE'] = $tuple['value'];
			$map['FKEY'.$n.'_TITLE'] = $tuple['title'];
		}

		for ($n = 1; $n <= 10; $n++)
		{
			$map['SOFTFKEY'.$n.'_TYPE'] = '0';
			$map['SOFTFKEY'.$n.'_VALUE'] = '';
			$map['SOFTFKEY'.$n.'_TITLE'] = '';
		}

		for ($n = 1; $n <= 10; $n++)
		{
			$map['SOFTDSS_'.$n.'_TYPE'] = '0';
			$map['SOFTDSS_'.$n.'_VALUE'] = '';
			$map['SOFTDSS_'.$n.'_TITLE'] = '';
		}

		for ($n = 1; $n <= 5; $n++)
		{
			$map['SIDEKEY_'.$n.'_TYPE'] = '0';
			$map['SIDEKEY_'.$n.'_VALUE'] = '';
			$map['SIDEKEY_'.$n.'_TITLE'] = '';
		}
		$sideTuple = self::linekeyTuple(
			isset($device['linekeys'][7]) ? $device['linekeys'][7] : null,
			$speedDialLine
		);
		$map['SIDEKEY_2_TYPE'] = $sideTuple['type'];
		$map['SIDEKEY_2_VALUE'] = $sideTuple['value'];
		$map['SIDEKEY_2_TITLE'] = $sideTuple['title'];

		foreach (self::mmiPlaceholderMap($network) as $key => $val)
		{
			$map[$key] = $val;
		}
		foreach (self::mmiAccountPlaceholderMap($network) as $key => $val)
		{
			$map[$key] = $val;
		}
		foreach (self::pnpPlaceholderMap($global) as $key => $val)
		{
			$map[$key] = $val;
		}

		$deviceSettings = isset($device['settings']) && is_array($device['settings']) ? $device['settings'] : array();
		foreach (Zts_NetworkWifiProfileService::fanvilWifiPlaceholderMap($settings, $deviceSettings, $family) as $key => $val)
		{
			$map[$key] = $val;
		}

		return $map;
	}

	/**
	 * @param array<int,string> $lines
	 * @param array<string,string> $map
	 * @return array<int,string>
	 */
	public static function apply(array $lines, array $map)
	{
		$out = array();
		foreach ($lines as $line)
		{
			$out[] = self::applyLine($line, $map);
		}

		return $out;
	}

	/**
	 * @param string $line
	 * @param array<string,string> $map
	 * @return string
	 */
	public static function applyLine($line, array $map)
	{
		return preg_replace_callback('/\{([A-Z0-9_]+)\}/', function (array $m) use ($map) {
			$key = $m[1];

			return array_key_exists($key, $map) ? (string) $map[$key] : '';
		}, $line);
	}

	/**
	 * @param string $sipHost
	 * @return string
	 */
	private static function sipLocalDomain($sipHost)
	{
		if ($sipHost === '' || filter_var($sipHost, FILTER_VALIDATE_IP) !== false)
		{
			return '';
		}
		$parts = explode('.', $sipHost);
		if (count($parts) >= 3)
		{
			return implode('.', array_slice($parts, -2));
		}
		if (count($parts) === 2)
		{
			return $sipHost;
		}

		return '';
	}

	/**
	 * @param array $device
	 * @param array $network
	 * @param string $sipHost
	 * @param string $sipPort
	 * @param string $sipTransport
	 * @param string $sipExpires
	 * @param string $codecMap
	 * @param string $sipLocalDomain
	 * @return array<string,string>
	 */
	private static function resolveSipLines(array $device, array $network, $sipHost, $sipPort, $sipTransport, $sipExpires, $codecMap, $sipLocalDomain)
	{
		global $db;

		$out = array();
		for ($lineid = 1; $lineid <= 2; $lineid++)
		{
			$p = 'SIP'.$lineid.'_';
			$empty = self::emptySipLinePlaceholders($lineid);
			$built = false;
			$line = isset($device['lines'][$lineid]) ? $device['lines'][$lineid] : null;
			if (is_array($line) && !empty($line['deviceid']))
			{
				$freepbxDevice = sql(
					"SELECT devices.id, devices.description, devices.dial, users.extension, users.name
					FROM devices LEFT OUTER JOIN users ON devices.user = users.extension
					WHERE devices.id = '".$db->escapeSimple($line['deviceid'])."'",
					'getRow',
					DB_FETCHMODE_ASSOC
				);
				if (is_array($freepbxDevice))
				{
					$extension = $freepbxDevice['extension'] ? $freepbxDevice['extension'] : $freepbxDevice['dial'];
					$secret = sql("SELECT data FROM sip WHERE id = '".$db->escapeSimple($extension)."' AND keyword = 'secret'", 'getOne');
					if (empty($secret))
					{
						$secret = zts_provisioning_pjsip_secret($extension);
					}
					if (!empty($secret))
					{
						$display = !empty($freepbxDevice['name']) ? $freepbxDevice['name'] : $extension;
						$hlrow = zts_fanvil_hotline_row_values($device, $lineid);
						$out[$p.'PHONE_NUMBER'] = (string) $extension;
						$out[$p.'DISPLAY_NAME'] = (string) $display;
						$out[$p.'SIP_NAME'] = $sipHost;
						$out[$p.'REGISTER_ADDR'] = $sipHost;
						$out[$p.'REGISTER_PORT'] = $sipPort;
						$out[$p.'REGISTER_USER'] = (string) $extension;
						$out[$p.'REGISTER_PSWD'] = (string) $secret;
						$out[$p.'REGISTER_TTL'] = $sipExpires;
						$out[$p.'BACKUP_ADDR'] = $sipHost;
						$out[$p.'BACKUP_PORT'] = $sipPort;
						$out[$p.'BACKUP_TRANSPORT'] = $sipTransport;
						$out[$p.'BACKUP_TTL'] = $sipExpires;
						$out[$p.'ENABLE_REG'] = '1';
						$out[$p.'VOICE_CODEC_MAP'] = $codecMap;
						$out[$p.'TRANSPORT'] = $sipTransport;
						$out[$p.'LOCAL_DOMAIN'] = $sipLocalDomain;
						$out[$p.'HOTLINE_NUM'] = $hlrow['number'];
						$out[$p.'HOTLINE_ENABLE'] = $hlrow['enable'];
						$out[$p.'WARMLINE_TIME'] = $hlrow['delay'];
						$built = true;
					}
				}
			}
			if (!$built)
			{
				$out = array_merge($out, $empty);
			}
		}

		return $out;
	}

	/**
	 * @param int $lineid
	 * @return array<string,string>
	 */
	private static function emptySipLinePlaceholders($lineid)
	{
		$p = 'SIP'.$lineid.'_';

		return array(
			$p.'PHONE_NUMBER' => '',
			$p.'DISPLAY_NAME' => '',
			$p.'SIP_NAME' => '',
			$p.'REGISTER_ADDR' => '',
			$p.'REGISTER_PORT' => '',
			$p.'REGISTER_USER' => '',
			$p.'REGISTER_PSWD' => '',
			$p.'REGISTER_TTL' => '',
			$p.'BACKUP_ADDR' => '',
			$p.'BACKUP_PORT' => '',
			$p.'BACKUP_TRANSPORT' => '',
			$p.'BACKUP_TTL' => '',
			$p.'ENABLE_REG' => '0',
			$p.'VOICE_CODEC_MAP' => '',
			$p.'TRANSPORT' => '',
			$p.'LOCAL_DOMAIN' => '',
			$p.'HOTLINE_NUM' => '',
			$p.'HOTLINE_ENABLE' => '0',
			$p.'WARMLINE_TIME' => '0',
		);
	}

	/**
	 * @param mixed $linekey
	 * @param int $speedDialLine
	 * @return array{type:string,value:string,title:string}
	 */
	private static function linekeyTuple($linekey, $speedDialLine)
	{
		$tuple = array('type' => '0', 'value' => '', 'title' => '');
		if (!is_array($linekey))
		{
			return $tuple;
		}
		$t = isset($linekey['type']) ? (string) $linekey['type'] : '';
		if ($t === '13')
		{
			$value = zts_fanvil_linekey_speed_dial_value($linekey, $speedDialLine);
			if ($value !== '')
			{
				$tuple['type'] = '1';
				$tuple['value'] = $value;
				$tuple['title'] = !empty($linekey['label']) ? $linekey['label'] : zts_fanvil_linekey_raw_dial_value($linekey);
			}
		}
		elseif ($t === '16')
		{
			$value = zts_fanvil_linekey_blf_subscribe_value($linekey);
			if ($value !== '')
			{
				$tuple['type'] = '1';
				$tuple['value'] = $value;
				$tuple['title'] = !empty($linekey['label']) ? $linekey['label'] : zts_fanvil_linekey_raw_dial_value($linekey);
			}
		}

		return $tuple;
	}

	/**
	 * @param array $network
	 * @return array<string,string>
	 */
	private static function mmiAccountPlaceholderMap(array $network)
	{
		$map = array();
		if (!class_exists('Zts_NetworkMmiAccountService'))
		{
			return $map;
		}
		$settings = isset($network['settings']) && is_array($network['settings']) ? $network['settings'] : array();
		$accounts = Zts_NetworkMmiAccountService::fromSettings($settings);
		for ($i = 1; $i <= 8; $i++)
		{
			$map['MMI_ACCOUNT'.$i.'_NAME'] = '';
			$map['MMI_ACCOUNT'.$i.'_PASSWORD'] = '';
			$map['MMI_ACCOUNT'.$i.'_LEVEL'] = '';
		}
		$idx = 0;
		foreach ($accounts as $acc)
		{
			$idx++;
			if ($idx > 8)
			{
				break;
			}
			$map['MMI_ACCOUNT'.$idx.'_NAME'] = isset($acc['name']) ? (string) $acc['name'] : '';
			$map['MMI_ACCOUNT'.$idx.'_PASSWORD'] = isset($acc['password']) ? (string) $acc['password'] : '';
			$map['MMI_ACCOUNT'.$idx.'_LEVEL'] = isset($acc['level']) ? (string) $acc['level'] : '';
		}

		return $map;
	}

	/**
	 * @param array $network
	 * @return array<string,string>
	 */
	private static function mmiPlaceholderMap(array $network)
	{
		$lines = zts_fanvil_mmi_config_lines($network);
		$map = array(
			'MMI_ADMIN_USER' => '',
			'MMI_ADMIN_PASSWORD' => '',
			'MMI_USER_USER' => '',
			'MMI_USER_PASSWORD' => '',
		);
		foreach ($lines as $line)
		{
			if (preg_match('/^Web Auth Admin User\s+:(.*)$/', $line, $m) === 1)
			{
				$map['MMI_ADMIN_USER'] = trim($m[1]);
			}
			elseif (preg_match('/^Web Auth Admin Password\s+:(.*)$/', $line, $m) === 1)
			{
				$map['MMI_ADMIN_PASSWORD'] = trim($m[1]);
			}
			elseif (preg_match('/^Web Auth User User\s+:(.*)$/', $line, $m) === 1)
			{
				$map['MMI_USER_USER'] = trim($m[1]);
			}
			elseif (preg_match('/^Web Auth User Password\s+:(.*)$/', $line, $m) === 1)
			{
				$map['MMI_USER_PASSWORD'] = trim($m[1]);
			}
		}

		return $map;
	}

	/**
	 * @param array $global
	 * @return array<string,string>
	 */
	private static function pnpPlaceholderMap(array $global)
	{
		$map = array(
			'PNP_ENABLE' => '1',
			'PNP_IP' => '224.0.1.75',
			'PNP_PORT' => '5060',
			'PNP_TRANSPORT' => '0',
			'PNP_INTERVAL' => '1',
		);
		if (!class_exists('Zts_GeneralSipPnpService'))
		{
			return $map;
		}
		foreach (Zts_GeneralSipPnpService::fanvilConfigLines($global) as $line)
		{
			if (preg_match('/^PNP Enable\s+:(.*)$/', $line, $m) === 1)
			{
				$map['PNP_ENABLE'] = trim($m[1]);
			}
			elseif (preg_match('/^PNP IP\s+:(.*)$/', $line, $m) === 1)
			{
				$map['PNP_IP'] = trim($m[1]);
			}
			elseif (preg_match('/^PNP Port\s+:(.*)$/', $line, $m) === 1)
			{
				$map['PNP_PORT'] = trim($m[1]);
			}
			elseif (preg_match('/^PNP Transport\s+:(.*)$/', $line, $m) === 1)
			{
				$map['PNP_TRANSPORT'] = trim($m[1]);
			}
			elseif (preg_match('/^PNP Interval\s+:(.*)$/', $line, $m) === 1)
			{
				$map['PNP_INTERVAL'] = trim($m[1]);
			}
		}

		return $map;
	}
}
