<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Placeholder map for Yealink baseline templates (provisioning/yealink/baseline/*.cfg).
 */
class Zts_YealinkBaselinePlaceholders
{
	/**
	 * @param array<string,mixed> $ctx device, network, global, mac, model, family
	 * @return array<string,string>
	 */
	public static function buildMap(array $ctx)
	{
		$device = isset($ctx['device']) && is_array($ctx['device']) ? $ctx['device'] : array();
		$network = isset($ctx['network']) && is_array($ctx['network']) ? $ctx['network'] : array();
		$global = isset($ctx['global']) && is_array($ctx['global']) ? $ctx['global'] : array();
		$family = isset($ctx['family']) ? (string) $ctx['family'] : Zts_YealinkDeviceConfigService::FAMILY_DESK;

		$settings = isset($network['settings']) && is_array($network['settings']) ? $network['settings'] : array();

		$provUrl = Zts_ProvisioningUrlService::yealinkConfigAutoProvisionUrl($network);
		if ($provUrl === '')
		{
			$provUrl = 'http://127.0.0.1/'.Zts_ProvisioningPaths::primaryWebSegment();
		}

		$ntp1 = isset($settings['ntp_server1']) ? (string) $settings['ntp_server1'] : 'pool.ntp.org';
		$ntp2 = isset($settings['ntp_server2']) && (string) $settings['ntp_server2'] !== ''
			? (string) $settings['ntp_server2'] : $ntp1;

		$yealinkTz = isset($settings['time_zone']) ? trim((string) $settings['time_zone']) : Zts_YealinkTimeZoneOptions::defaultValue();
		$yealinkTzName = isset($settings['time_zone_name']) ? trim((string) $settings['time_zone_name']) : '';
		if ($yealinkTzName === '')
		{
			$fanvilTim = isset($settings['time_zone_fanvil']) ? (string) $settings['time_zone_fanvil'] : '';
			$yealinkTzName = Zts_FanvilTimeZoneOptions::labelForValue($fanvilTim);
		}
		if ($yealinkTzName === '')
		{
			$yealinkTzName = 'None';
		}

		$yealinkDst = isset($settings['daylight_saving_time']) ? trim((string) $settings['daylight_saving_time']) : '2';
		if (!in_array($yealinkDst, array('0', '1', '2'), true))
		{
			$yealinkDst = '2';
		}

		$backlight = isset($device['settings']['phone_backlight_time']) && (string) $device['settings']['phone_backlight_time'] !== ''
			? (string) $device['settings']['phone_backlight_time']
			: (isset($global['default_backlight_time']) ? (string) $global['default_backlight_time'] : '60');

		$langWui = '';
		$phoneLang = isset($device['settings']['phone_lang']) ? trim((string) $device['settings']['phone_lang']) : '';
		if ($phoneLang === '' && isset($global['default_lang']))
		{
			$phoneLang = trim((string) $global['default_lang']);
		}
		if ($phoneLang !== '')
		{
			$langWui = $phoneLang;
		}

		$dateFormat = '';
		if (in_array($family, array(Zts_YealinkDeviceConfigService::FAMILY_T4_HD, Zts_YealinkDeviceConfigService::FAMILY_T5), true))
		{
			$dateFormat = '5';
		}

		$pushXml = ($family === Zts_YealinkDeviceConfigService::FAMILY_T4) ? '1' : '';
		$progKey17 = ($family === Zts_YealinkDeviceConfigService::FAMILY_T4) ? '7' : '';
		$enhancedDss = ($family === Zts_YealinkDeviceConfigService::FAMILY_T5) ? '1' : '';

		$map = array(
			'STATIC_PROV_URL' => $provUrl,
			'DHCP_HOSTNAME' => zts_provisioning_dhcp_hostname($device),
			'NTP_SERVER1' => $ntp1,
			'NTP_SERVER2' => $ntp2,
			'TIME_ZONE' => $yealinkTz,
			'TIME_ZONE_NAME' => $yealinkTzName,
			'SUMMER_TIME' => $yealinkDst,
			'DATE_FORMAT' => $dateFormat,
			'BACKLIGHT_TIME' => $backlight,
			'LANG_WUI' => $langWui,
			'PUSH_XML_SIP_NOTIFY' => $pushXml,
			'PROGRAMMABLE_KEY_17_TYPE' => $progKey17,
			'ENHANCED_DSS_KEYS_ENABLE' => $enhancedDss,
			'STATIC_GUEST_USER' => 'guest',
			'VOICE_HANDSET_SPK_VOL' => '',
			'VOICE_HANDFREE_SPK_VOL' => '',
			'VOICE_HANDFREE_TONE_VOL' => '',
			'VOICE_RING_VOL' => '',
		);

		$deviceSettings = isset($device['settings']) && is_array($device['settings']) ? $device['settings'] : array();
		foreach (array(
			'voice_handset_spk_vol' => 'VOICE_HANDSET_SPK_VOL',
			'voice_handfree_spk_vol' => 'VOICE_HANDFREE_SPK_VOL',
			'voice_handfree_tone_vol' => 'VOICE_HANDFREE_TONE_VOL',
			'voice_ring_vol' => 'VOICE_RING_VOL',
		) as $settingKey => $placeholder)
		{
			if (isset($deviceSettings[$settingKey]) && trim((string) $deviceSettings[$settingKey]) !== '')
			{
				$map[$placeholder] = trim((string) $deviceSettings[$settingKey]);
			}
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
		return Zts_FanvilBaselinePlaceholders::apply($lines, $map);
	}

	/**
	 * Drop optional lines left empty after placeholder substitution.
	 *
	 * @param array<int,string> $lines
	 * @return array<int,string>
	 */
	public static function filterEmptyValues(array $lines)
	{
		$out = array();
		foreach ($lines as $line)
		{
			if (strpos($line, ';; @') === 0)
			{
				continue;
			}
			if (preg_match('/^#/', $line) === 1)
			{
				$out[] = $line;
				continue;
			}
			if (preg_match('/^([A-Za-z0-9_.]+)\s*=\s*$/', $line) === 1)
			{
				continue;
			}

			$out[] = $line;
		}

		return $out;
	}
}
