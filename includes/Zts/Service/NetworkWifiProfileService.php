<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Wi-Fi profiles per provisioning network (Fanvil W611/H6W, Yealink Wi-Fi models).
 */
class Zts_NetworkWifiProfileService
{
	const SETTING_KEY = 'wifi_profiles_json';
	const MAX_PROFILES = 5;
	const FANVIL_COLON_PAD = 27;
	/** W611 ItemN field lines align colon at column 26 (label width 25). */
	const FANVIL_WIFI_ITEM_COLON_PAD = 25;

	const SECURE_MODE_NONE = '0';
	const SECURE_MODE_WPA_PSK = '1';
	const SECURE_MODE_8021X = '2';
	const SECURE_MODE_FT_PSK = '3';

	const ENCRYPTION_NONE = '0';
	const ENCRYPTION_TKIP = '1';
	const ENCRYPTION_AES = '2';
	const ENCRYPTION_TKIP_AES = '3';

	/** @deprecated legacy JSON key */
	const SECURITY_WPA2PSK = 'wpa2psk';
	/** @deprecated legacy JSON key */
	const SECURITY_OPEN = 'open';
	/** @deprecated legacy JSON key */
	const SECURITY_WPA_EAP = 'wpa_eap';

	/**
	 * @return array<string,string>
	 */
	public static function defaultScalarSettings()
	{
		return array();
	}

	/**
	 * @return array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}>
	 */
	public static function defaultRowsForForm()
	{
		return array(
			array(
				'label' => '',
				'ssid' => '',
				'secure_mode' => self::SECURE_MODE_WPA_PSK,
				'encryption' => self::ENCRYPTION_AES,
				'username' => '',
				'password' => '',
				'priority' => '5',
			),
		);
	}

	/**
	 * Fanvil W611/W611W Secure Mode values (web UI).
	 *
	 * @return array<string,string>
	 */
	public static function secureModeOptions()
	{
		return array(
			self::SECURE_MODE_NONE => _('None'),
			self::SECURE_MODE_WPA_PSK => _('WPA/WPA2-PSK'),
			self::SECURE_MODE_8021X => _('802.1x'),
			self::SECURE_MODE_FT_PSK => _('FT-PSK'),
		);
	}

	/**
	 * Fanvil W611/W611W Encryption values (web UI).
	 *
	 * @return array<string,string>
	 */
	public static function encryptionOptions()
	{
		return array(
			self::ENCRYPTION_TKIP => _('TKIP'),
			self::ENCRYPTION_AES => _('AES (CCMP)'),
			self::ENCRYPTION_TKIP_AES => _('TKIP+AES'),
		);
	}

	/**
	 * @param array<string,string> $settings
	 * @return array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}>
	 */
	public static function fromSettings(array $settings)
	{
		$raw = isset($settings[self::SETTING_KEY]) ? trim((string) $settings[self::SETTING_KEY]) : '';
		if ($raw === '')
		{
			return array();
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded))
		{
			return array();
		}

		return self::normalizeList($decoded);
	}

	/**
	 * @param array $row
	 * @return array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}
	 */
	public static function normalizeProfileRow(array $row)
	{
		$ssid = isset($row['ssid']) ? trim((string) $row['ssid']) : '';
		$priority = isset($row['priority']) ? trim((string) $row['priority']) : '5';
		if ($priority === '' || (int) $priority < 1 || (int) $priority > self::MAX_PROFILES)
		{
			$priority = '5';
		}

		if (isset($row['secure_mode']))
		{
			$secureMode = (string) (int) $row['secure_mode'];
			$encryption = isset($row['encryption']) ? (string) (int) $row['encryption'] : self::ENCRYPTION_NONE;
		}
		else
		{
			$legacy = isset($row['security']) ? trim((string) $row['security']) : self::SECURITY_WPA2PSK;
			if ($legacy === self::SECURITY_OPEN)
			{
				$secureMode = self::SECURE_MODE_NONE;
				$encryption = self::ENCRYPTION_NONE;
			}
			elseif ($legacy === self::SECURITY_WPA_EAP)
			{
				$secureMode = self::SECURE_MODE_8021X;
				$encryption = self::ENCRYPTION_AES;
			}
			else
			{
				$secureMode = self::SECURE_MODE_WPA_PSK;
				$encryption = self::ENCRYPTION_AES;
			}
		}

		if (!array_key_exists($secureMode, self::secureModeOptions()))
		{
			$secureMode = self::SECURE_MODE_WPA_PSK;
		}
		if ($secureMode === self::SECURE_MODE_NONE)
		{
			$encryption = self::ENCRYPTION_NONE;
		}
		elseif (!array_key_exists($encryption, self::encryptionOptions()))
		{
			$encryption = self::ENCRYPTION_AES;
		}

		return array(
			'label' => isset($row['label']) ? trim((string) $row['label']) : '',
			'ssid' => $ssid,
			'secure_mode' => $secureMode,
			'encryption' => $encryption,
			'username' => isset($row['username']) ? trim((string) $row['username']) : '',
			'password' => isset($row['password']) ? (string) $row['password'] : '',
			'priority' => $priority,
		);
	}

	/**
	 * @param array $list
	 * @return array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}>
	 */
	public static function normalizeList(array $list)
	{
		$out = array();
		foreach ($list as $row)
		{
			if (!is_array($row))
			{
				continue;
			}
			$normalized = self::normalizeProfileRow($row);
			if ($normalized['ssid'] === '')
			{
				continue;
			}
			$out[] = $normalized;
			if (count($out) >= self::MAX_PROFILES)
			{
				break;
			}
		}

		return $out;
	}

	/**
	 * @param array<string,string> $networkSettings
	 * @param array<string,string> $deviceSettings
	 * @return bool
	 */
	public static function shouldPushProvisioning(array $networkSettings, array $deviceSettings)
	{
		return Zts_DeviceWifiSettingsService::shouldPush($deviceSettings);
	}

	/**
	 * @param array<string,string> $networkSettings
	 * @param array<string,string> $deviceSettings
	 * @return array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}>
	 */
	public static function profilesForDevice(array $networkSettings, array $deviceSettings)
	{
		if (!self::shouldPushProvisioning($networkSettings, $deviceSettings))
		{
			return array();
		}
		if (!Zts_DeviceWifiSettingsService::isEnabled($deviceSettings))
		{
			return array();
		}

		$all = self::fromSettings($networkSettings);
		$ssid = Zts_DeviceWifiSettingsService::selectedSsid($deviceSettings);
		if ($ssid === '')
		{
			return array();
		}
		foreach ($all as $profile)
		{
			if ($profile['ssid'] === $ssid)
			{
				return array($profile);
			}
		}

		return array();
	}

	/**
	 * All network Wi-Fi profiles for Fanvil W611 --WIFI List-- (Item1..Item5 by priority).
	 * Phone Edit must have push + enable + a valid selected SSID; list content comes from Networks Edit.
	 *
	 * @param array<string,string> $networkSettings
	 * @param array<string,string> $deviceSettings
	 * @return array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}>
	 */
	public static function profilesForFanvilWifiList(array $networkSettings, array $deviceSettings)
	{
		if (!self::shouldPushProvisioning($networkSettings, $deviceSettings))
		{
			return array();
		}
		if (!Zts_DeviceWifiSettingsService::isEnabled($deviceSettings))
		{
			return array();
		}

		$all = self::fromSettings($networkSettings);
		if (count($all) < 1)
		{
			return array();
		}

		$selectedSsid = Zts_DeviceWifiSettingsService::selectedSsid($deviceSettings);
		if ($selectedSsid === '')
		{
			return array();
		}
		foreach ($all as $profile)
		{
			if ($profile['ssid'] === $selectedSsid)
			{
				return $all;
			}
		}

		return array();
	}

	/**
	 * @param array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}> $profiles
	 * @return array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}>
	 */
	private static function fanvilProfilesByItem(array $profiles)
	{
		$byItem = array();
		for ($n = 0; $n < self::MAX_PROFILES; $n++)
		{
			$byItem[$n] = self::emptyFanvilWifiSlot();
		}

		$nextFree = 0;
		foreach ($profiles as $profile)
		{
			$pri = (int) $profile['priority'];
			$slot = ($pri >= 1 && $pri <= self::MAX_PROFILES && $byItem[$pri - 1]['ssid'] === '')
				? $pri - 1
				: null;
			if ($slot === null)
			{
				while ($nextFree < self::MAX_PROFILES && $byItem[$nextFree]['ssid'] !== '')
				{
					$nextFree++;
				}
				if ($nextFree >= self::MAX_PROFILES)
				{
					break;
				}
				$slot = $nextFree;
				$nextFree++;
			}
			$byItem[$slot] = $profile;
		}

		return $byItem;
	}

	/**
	 * Placeholders for W611 baseline --WIFI Config-- / --WIFI List-- ({WIFI_ITEMn_*}).
	 *
	 * @param array<string,string> $networkSettings
	 * @param array<string,string> $deviceSettings
	 * @param string $family
	 * @return array<string,string>
	 */
	public static function fanvilWifiPlaceholderMap(array $networkSettings, array $deviceSettings, $family)
	{
		$map = array(
			'WIFI_ENABLE' => '0',
			'WIFI_COUNTRY_CODE' => 'RU',
		);
		for ($n = 1; $n <= self::MAX_PROFILES; $n++)
		{
			$map['WIFI_ITEM'.$n.'_NAME'] = '';
			$map['WIFI_ITEM'.$n.'_SSID'] = '';
			$map['WIFI_ITEM'.$n.'_SECURE_MODE'] = self::SECURE_MODE_NONE;
			$map['WIFI_ITEM'.$n.'_ENCRYPTION'] = self::ENCRYPTION_NONE;
			$map['WIFI_ITEM'.$n.'_USERNAME'] = '';
			$map['WIFI_ITEM'.$n.'_PASSWORD'] = '';
		}

		if ($family !== Zts_FanvilDeviceConfigService::FAMILY_W611)
		{
			return $map;
		}
		if (!self::shouldPushProvisioning($networkSettings, $deviceSettings)
			|| !Zts_DeviceWifiSettingsService::isEnabled($deviceSettings))
		{
			return $map;
		}

		$profiles = self::profilesForFanvilWifiList($networkSettings, $deviceSettings);
		if (count($profiles) < 1)
		{
			return $map;
		}

		$map['WIFI_ENABLE'] = '1';
		$byItem = self::fanvilProfilesByItem($profiles);
		for ($n = 1; $n <= self::MAX_PROFILES; $n++)
		{
			$row = $byItem[$n - 1];
			$wifiName = trim((string) $row['label']) !== '' ? $row['label'] : $row['ssid'];
			$map['WIFI_ITEM'.$n.'_NAME'] = $wifiName;
			$map['WIFI_ITEM'.$n.'_SSID'] = $row['ssid'];
			$map['WIFI_ITEM'.$n.'_SECURE_MODE'] = $row['secure_mode'];
			$map['WIFI_ITEM'.$n.'_ENCRYPTION'] = $row['encryption'];
			$map['WIFI_ITEM'.$n.'_USERNAME'] = $row['username'];
			$map['WIFI_ITEM'.$n.'_PASSWORD'] = $row['password'];
		}

		return $map;
	}

	/**
	 * @param array<string,string> $networkSettings
	 * @return bool
	 */
	public static function hasProfiles(array $networkSettings)
	{
		return count(self::fromSettings($networkSettings)) > 0;
	}

	/**
	 * @param array $post
	 * @return array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}>
	 */
	public static function parseFromPost(array $post)
	{
		$labels = isset($post['wifi_label']) && is_array($post['wifi_label']) ? $post['wifi_label'] : array();
		$ssids = isset($post['wifi_ssid']) && is_array($post['wifi_ssid']) ? $post['wifi_ssid'] : array();
		$secureModes = isset($post['wifi_secure_mode']) && is_array($post['wifi_secure_mode']) ? $post['wifi_secure_mode'] : array();
		$encryptions = isset($post['wifi_encryption']) && is_array($post['wifi_encryption']) ? $post['wifi_encryption'] : array();
		$usernames = isset($post['wifi_username']) && is_array($post['wifi_username']) ? $post['wifi_username'] : array();
		$passwords = isset($post['wifi_password']) && is_array($post['wifi_password']) ? $post['wifi_password'] : array();
		$priorities = isset($post['wifi_priority']) && is_array($post['wifi_priority']) ? $post['wifi_priority'] : array();

		$rowCount = max(count($labels), count($ssids), count($secureModes), count($encryptions), count($usernames), count($passwords), count($priorities));
		$out = array();
		for ($i = 0; $i < $rowCount && count($out) < self::MAX_PROFILES; $i++)
		{
			$ssid = isset($ssids[$i]) ? trim((string) $ssids[$i]) : '';
			if ($ssid === '')
			{
				continue;
			}
			$out[] = self::normalizeProfileRow(array(
				'label' => isset($labels[$i]) ? (string) $labels[$i] : '',
				'ssid' => $ssid,
				'secure_mode' => isset($secureModes[$i]) ? (string) $secureModes[$i] : self::SECURE_MODE_WPA_PSK,
				'encryption' => isset($encryptions[$i]) ? (string) $encryptions[$i] : self::ENCRYPTION_AES,
				'username' => isset($usernames[$i]) ? (string) $usernames[$i] : '',
				'password' => isset($passwords[$i]) ? (string) $passwords[$i] : '',
				'priority' => isset($priorities[$i]) ? (string) $priorities[$i] : '5',
			));
		}

		return $out;
	}

	/**
	 * @param array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}> $parsed
	 * @param array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}> $existing
	 * @return array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}>
	 */
	public static function mergePasswordsFromExisting(array $parsed, array $existing)
	{
		$bySsid = array();
		foreach ($existing as $row)
		{
			$bySsid[$row['ssid']] = $row['password'];
		}
		foreach ($parsed as $k => $row)
		{
			if ($row['password'] === '' && isset($bySsid[$row['ssid']]))
			{
				$parsed[$k]['password'] = $bySsid[$row['ssid']];
			}
		}

		return $parsed;
	}

	/**
	 * @param array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}> $profiles
	 * @return array{ok:bool,errors:string[]}
	 */
	public static function validateProfiles(array $profiles)
	{
		$errors = array();
		foreach ($profiles as $row)
		{
			if (strlen($row['ssid']) > 32)
			{
				$errors[] = sprintf(_('Wi-Fi SSID "%s" is too long (max 32).'), $row['ssid']);
			}
			if ($row['secure_mode'] === self::SECURE_MODE_8021X && $row['username'] === '')
			{
				$errors[] = sprintf(_('Wi-Fi username is required for 802.1x profile "%s".'), $row['ssid']);
			}
			if ($row['secure_mode'] !== self::SECURE_MODE_NONE && $row['password'] === '')
			{
				$errors[] = sprintf(_('Wi-Fi password is required for secured profile "%s".'), $row['ssid']);
			}
			elseif (strlen($row['password']) > 63)
			{
				$errors[] = sprintf(_('Wi-Fi password for "%s" is too long (max 63).'), $row['ssid']);
			}
		}

		return array('ok' => count($errors) < 1, 'errors' => $errors);
	}

	/**
	 * @param array<string,string> $settings network settings
	 * @return array{ok:bool,errors:string[]}
	 */
	public static function validateSettings(array $settings)
	{
		$errors = array();
		$profiles = self::fromSettings($settings);
		if (count($profiles) > 0)
		{
			$profileCheck = self::validateProfiles($profiles);
			if (!$profileCheck['ok'])
			{
				$errors = array_merge($errors, $profileCheck['errors']);
			}
		}

		return array('ok' => count($errors) < 1, 'errors' => $errors);
	}

	/**
	 * @param array<int,string> $lines baseline NET CONFIG lines (placeholders applied)
	 * @return string two-letter country code or empty
	 */
	public static function extractWifiCountryFromLines(array $lines)
	{
		foreach ($lines as $line)
		{
			if (preg_match('/^WIFI Country Code\s+:(.*)$/', $line, $matches) === 1)
			{
				return strtoupper(trim($matches[1]));
			}
		}

		return '';
	}

	/**
	 * @param array<int,array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}> $profiles
	 * @return string
	 */
	public static function toJson(array $profiles)
	{
		return json_encode(array_values($profiles), JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param string $label
	 * @param string $value
	 * @return string
	 */
	private static function fanvilColonLine($label, $value)
	{
		return str_pad((string) $label, self::FANVIL_COLON_PAD, ' ', STR_PAD_RIGHT).':'.(string) $value;
	}

	/**
	 * @param int $itemNum
	 * @param string $field
	 * @param string $value
	 * @return string
	 */
	private static function fanvilWifiItemLine($itemNum, $field, $value)
	{
		$label = 'Item'.(int) $itemNum.' '.$field;

		return str_pad($label, self::FANVIL_WIFI_ITEM_COLON_PAD, ' ', STR_PAD_RIGHT).':'.(string) $value;
	}

	/**
	 * @return array{label:string,ssid:string,secure_mode:string,encryption:string,username:string,password:string,priority:string}
	 */
	private static function emptyFanvilWifiSlot()
	{
		return array(
			'label' => '',
			'ssid' => '',
			'secure_mode' => self::SECURE_MODE_NONE,
			'encryption' => self::ENCRYPTION_NONE,
			'username' => '',
			'password' => '',
			'priority' => '1',
		);
	}

	/**
	 * @param array<string,string> $networkSettings
	 * @param array<string,string> $deviceSettings
	 * @param string $family Fanvil family constant from FanvilDeviceConfigService
	 * @param string $dhcpHostname
	 * @param string $wifiCountryCode from family baseline (W611)
	 * @return array<int,string>
	 */
	public static function fanvilWifiBlockLines(array $networkSettings, array $deviceSettings, $family, $dhcpHostname = '', $wifiCountryCode = '')
	{
		$profiles = ($family === Zts_FanvilDeviceConfigService::FAMILY_W611)
			? self::profilesForFanvilWifiList($networkSettings, $deviceSettings)
			: self::profilesForDevice($networkSettings, $deviceSettings);
		$country = strtoupper(trim((string) $wifiCountryCode));
		$enable = Zts_DeviceWifiSettingsService::isEnabled($deviceSettings) ? '1' : '0';

		$lines = array('--WIFI Config--    :');
		$lines[] = self::fanvilColonLine('WIFI Enable', $enable);
		$lines[] = self::fanvilColonLine('WIFI Log Enable', '0');
		if ($family === Zts_FanvilDeviceConfigService::FAMILY_W611 && $country !== '')
		{
			$lines[] = self::fanvilColonLine('WIFI Country Code', $country);
		}
		$lines[] = self::fanvilColonLine('Enable DHCP', '1');
		$lines[] = self::fanvilColonLine('DHCP Hostname', $dhcpHostname);

		if ($family === Zts_FanvilDeviceConfigService::FAMILY_W611)
		{
			$lines[] = '--Net Global--     :';
			$lines[] = self::fanvilColonLine('Net Priority', '1');
			$lines[] = '--WIFI List--      :';

			$byItem = self::fanvilProfilesByItem($profiles);

			for ($n = 1; $n <= self::MAX_PROFILES; $n++)
			{
				$row = $byItem[$n - 1];
				$wifiName = trim((string) $row['label']) !== '' ? $row['label'] : $row['ssid'];
				$lines[] = self::fanvilWifiItemLine($n, 'WIFI Name', $wifiName);
				$lines[] = self::fanvilWifiItemLine($n, 'WIFI SSID', $row['ssid']);
				$lines[] = self::fanvilWifiItemLine($n, 'Secure Mode', $row['secure_mode']);
				$lines[] = self::fanvilWifiItemLine($n, 'WIFI Encryption', $row['encryption']);
				$lines[] = self::fanvilWifiItemLine($n, 'WIFI User Name', $row['username']);
				$lines[] = self::fanvilWifiItemLine($n, 'WIFI Password', $row['password']);
			}

			$lines[] = '--WIFI Sharing-- :';
			$lines[] = self::fanvilColonLine('Enable', '0');
			$lines[] = self::fanvilColonLine('SSID', '');
			$lines[] = self::fanvilColonLine('Secure Mode', '0');
			$lines[] = self::fanvilColonLine('Password', '');
			$lines[] = self::fanvilColonLine('Username', '');
		}

		return $lines;
	}

	/**
	 * @param array<int,string> $lines
	 * @param string $family
	 * @param array<string,string> $networkSettings
	 * @param array<string,string> $deviceSettings
	 * @return array<int,string>
	 */
	public static function applyFanvilNetModuleLines(array $lines, $family, array $networkSettings, array $deviceSettings = array())
	{
		$isW611 = ($family === Zts_FanvilDeviceConfigService::FAMILY_W611);
		$isH6 = ($family === Zts_FanvilDeviceConfigService::FAMILY_H6);
		if (!$isW611 && !$isH6)
		{
			return $lines;
		}

		if (self::shouldPushProvisioning($networkSettings, $deviceSettings))
		{
			if ($isW611)
			{
				$profiles = self::profilesForFanvilWifiList($networkSettings, $deviceSettings);
				if (count($profiles) < 1)
				{
					return self::stripFanvilWifiSections($lines);
				}

				return $lines;
			}

			$profiles = self::profilesForDevice($networkSettings, $deviceSettings);
			if ($isH6)
			{
				$dhcpHostname = self::extractDhcpHostnameFromLines($lines);
				$wifiCountry = self::extractWifiCountryFromLines($lines);

				return self::replaceFanvilWifiSections(
					$lines,
					self::fanvilWifiBlockLines($networkSettings, $deviceSettings, $family, $dhcpHostname, $wifiCountry)
				);
			}
		}

		if ($isW611)
		{
			return self::stripFanvilWifiSections($lines);
		}

		return $lines;
	}

	/**
	 * @param array<int,string> $lines
	 * @return string
	 */
	private static function extractDhcpHostnameFromLines(array $lines)
	{
		foreach ($lines as $line)
		{
			if (preg_match('/^DHCP Hostname\s+:(.*)$/', $line, $matches) === 1)
			{
				return trim($matches[1]);
			}
		}

		return '';
	}

	/**
	 * @param array<int,string> $lines
	 * @return array<int,string>
	 */
	private static function stripFanvilWifiSections(array $lines)
	{
		$out = array();
		$skip = false;
		foreach ($lines as $line)
		{
			if (preg_match('/^--WIFI Config--/', $line) === 1)
			{
				$skip = true;
				continue;
			}
			if ($skip)
			{
				if (preg_match('/^</', $line) === 1)
				{
					$skip = false;
					$out[] = $line;
				}
				continue;
			}
			$out[] = $line;
		}

		return $out;
	}

	/**
	 * @param array<int,string> $lines
	 * @param array<int,string> $wifiBlock
	 * @return array<int,string>
	 */
	private static function replaceFanvilWifiSections(array $lines, array $wifiBlock)
	{
		$out = array();
		$replacing = false;
		$inserted = false;
		foreach ($lines as $line)
		{
			if (preg_match('/^--WIFI Config--/', $line) === 1)
			{
				if (!$inserted)
				{
					$out = array_merge($out, $wifiBlock);
					$inserted = true;
				}
				$replacing = true;
				continue;
			}
			if ($replacing)
			{
				if (preg_match('/^</', $line) === 1)
				{
					$replacing = false;
					$out[] = $line;
				}
				continue;
			}
			$out[] = $line;
		}

		return $out;
	}

	/**
	 * @param array{secure_mode:string,encryption:string} $profile
	 * @return array{security_mode:string,cipher_type:string,needs_username:bool}
	 */
	private static function yealinkWifiSecurityFromProfile(array $profile)
	{
		if ($profile['secure_mode'] === self::SECURE_MODE_NONE)
		{
			return array('security_mode' => 'Disabled', 'cipher_type' => 'CCMP', 'needs_username' => false);
		}
		if ($profile['secure_mode'] === self::SECURE_MODE_8021X)
		{
			return array('security_mode' => '802.1X-EAP', 'cipher_type' => 'CCMP', 'needs_username' => true);
		}
		$cipher = 'CCMP';
		if ($profile['encryption'] === self::ENCRYPTION_TKIP)
		{
			$cipher = 'TKIP';
		}
		elseif ($profile['encryption'] === self::ENCRYPTION_TKIP_AES)
		{
			$cipher = 'AUTO';
		}

		return array('security_mode' => 'WPA2-PSK', 'cipher_type' => $cipher, 'needs_username' => false);
	}

	/**
	 * Yealink static.wifi.* lines (Wi-Fi handsets only; ignored on wired models).
	 *
	 * @param array<string,string> $networkSettings
	 * @param array<string,string> $deviceSettings
	 * @return array<int,string>
	 */
	public static function yealinkConfigLines(array $networkSettings, array $deviceSettings = array())
	{
		$profiles = self::profilesForDevice($networkSettings, $deviceSettings);
		if (count($profiles) < 1)
		{
			return array();
		}
		$lines = array('static.wifi.enable = 1');
		$idx = 0;
		foreach ($profiles as $profile)
		{
			$idx++;
			$label = trim((string) $profile['label']) !== '' ? $profile['label'] : $profile['ssid'];
			$yealinkSec = self::yealinkWifiSecurityFromProfile($profile);
			$lines[] = 'static.wifi.'.$idx.'.label = '.$label;
			$lines[] = 'static.wifi.'.$idx.'.ssid = '.$profile['ssid'];
			$lines[] = 'static.wifi.'.$idx.'.password = '.$profile['password'];
			$lines[] = 'static.wifi.'.$idx.'.security_mode = '.$yealinkSec['security_mode'];
			if ($yealinkSec['security_mode'] !== 'Disabled')
			{
				$lines[] = 'static.wifi.'.$idx.'.cipher_type = '.$yealinkSec['cipher_type'];
			}
			if ($yealinkSec['needs_username'])
			{
				$lines[] = 'static.wifi.'.$idx.'.eap_user_name = '.$profile['username'];
			}
			$lines[] = 'static.wifi.'.$idx.'.priority = '.$profile['priority'];
		}

		return $lines;
	}
}
