<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Phone create/edit: parse POST, validate, persist (Fanvil hotline via legacy helpers).
 */
class Zts_DeviceEditService
{
	const LINEKEY_MAX = 27;

	const LINEKEY_DEFAULT_VISIBLE = 6;

	const LINE_MAX = 16;

	const LINE_DEFAULT_VISIBLE = 2;

	/**
	 * @param array $line
	 * @return bool
	 */
	public static function isLineFilled(array $line)
	{
		if (isset($line['deviceid']) && $line['deviceid'] !== null && trim((string) $line['deviceid']) !== '')
		{
			return true;
		}
		if (isset($line['line']) && $line['line'] !== null && trim((string) $line['line']) !== '')
		{
			return true;
		}
		if (isset($line['settings']['label']) && trim((string) $line['settings']['label']) !== '')
		{
			return true;
		}

		return false;
	}

	/**
	 * How many SIP line rows to show (2 by default; up to highest filled line ≥3).
	 *
	 * @param array<int,array> $lines
	 * @param int|null         $max
	 * @return int
	 */
	public static function linesVisibleCount(array $lines, $max = null)
	{
		$cap = ($max !== null) ? (int) $max : self::LINE_MAX;
		$visible = self::LINE_DEFAULT_VISIBLE;
		for ($i = self::LINE_DEFAULT_VISIBLE + 1; $i <= $cap; $i++)
		{
			if (isset($lines[$i]) && is_array($lines[$i]) && self::isLineFilled($lines[$i]))
			{
				$visible = $i;
			}
		}

		return min($visible, $cap);
	}

	/**
	 * @param array $linekey
	 * @return bool
	 */
	public static function isLinekeyFilled(array $linekey)
	{
		if (isset($linekey['type']) && (string) $linekey['type'] !== '' && (string) $linekey['type'] !== '0')
		{
			return true;
		}
		foreach (array('value', 'label', 'extension', 'pickup_value') as $field)
		{
			if (isset($linekey[$field]) && trim((string) $linekey[$field]) !== '')
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * How many line key rows to show (6 by default; up to highest filled key ≥7).
	 *
	 * @param array<int,array> $linekeys
	 * @return int
	 */
	public static function linekeysVisibleCount(array $linekeys)
	{
		$max = self::LINEKEY_DEFAULT_VISIBLE;
		for ($i = self::LINEKEY_DEFAULT_VISIBLE + 1; $i <= self::LINEKEY_MAX; $i++)
		{
			if (isset($linekeys[$i]) && is_array($linekeys[$i]) && self::isLinekeyFilled($linekeys[$i]))
			{
				$max = $i;
			}
		}

		return $max;
	}

	/**
	 * @param string|int $editId
	 * @return array
	 */
	public static function loadForView($editId)
	{
		$editId = Zts_InputValidator::trimString($editId);
		if ($editId !== '')
		{
			Zts_GeneralPhoneDefaultsService::applyToDeviceIfEligible($editId);
		}
		$device = Zts_DeviceRepository::findForEdit($editId);
		if ($editId === '')
		{
			$device = Zts_GeneralPhoneDefaultsService::mergeIntoNewDeviceForm($device);
		}
		foreach ($device['lines'] as $key => $line)
		{
			if ($line['deviceid'] != null)
			{
				$device['lines'][$key]['line'] = $line['deviceid'];
			}
		}

		return $device;
	}

	/**
	 * FreePBX devices.id values from assigned inventory lines.
	 *
	 * @param array $device
	 * @return string[]
	 */
	public static function pbxDeviceIdsFromDevice(array $device)
	{
		$ids = array();
		if (!isset($device['lines']) || !is_array($device['lines']))
		{
			return $ids;
		}
		foreach ($device['lines'] as $line)
		{
			if (!is_array($line) || empty($line['deviceid']))
			{
				continue;
			}
			$pbxDeviceId = Zts_InputValidator::trimString($line['deviceid']);
			if ($pbxDeviceId !== '' && !in_array($pbxDeviceId, $ids, true))
			{
				$ids[] = $pbxDeviceId;
			}
		}

		return $ids;
	}

	/**
	 * @param string|int $editId
	 * @param array      $post $_POST
	 * @return array{ok:bool,errors:string[],device:array,id:string}
	 */
	public static function saveFromPost($editId, array $post)
	{
		$editId = Zts_InputValidator::trimString($editId);
		$isNew = ($editId === '');

		$device = array(
			'name' => isset($post['name']) ? (string) $post['name'] : '',
			'mac' => isset($post['mac']) ? strtoupper((string) $post['mac']) : '',
			'lines' => array(),
			'linekeys' => array(),
			'settings' => array(),
		);

		if (isset($post['line']) && is_array($post['line']))
		{
			foreach ($post['line'] as $key => $value)
			{
				$key++;
				$device['lines'][$key]['deviceid'] = !empty($value) ? $value : null;
			}
			if (isset($post['label']) && is_array($post['label']))
			{
				foreach ($post['label'] as $key => $value)
				{
					$key++;
					if (isset($device['lines'][$key]))
					{
						$device['lines'][$key]['settings']['label'] = $value;
					}
				}
			}
		}

		if (isset($post['linekey_type']) && is_array($post['linekey_type']))
		{
			foreach ($post['linekey_type'] as $key => $value)
			{
				$key++;
				$device['linekeys'][$key]['type'] = $value;
				$device['linekeys'][$key]['line'] = isset($post['linekey_line'][$key - 1]) ? $post['linekey_line'][$key - 1] : '1';
				$device['linekeys'][$key]['value'] = isset($post['linekey_value'][$key - 1]) ? $post['linekey_value'][$key - 1] : '';
				$device['linekeys'][$key]['label'] = isset($post['linekey_label'][$key - 1]) ? $post['linekey_label'][$key - 1] : '';
				$device['linekeys'][$key]['extension'] = isset($post['linekey_extension'][$key - 1]) ? $post['linekey_extension'][$key - 1] : '';
				$device['linekeys'][$key]['pickup_value'] = isset($post['linekey_pickup'][$key - 1]) ? $post['linekey_pickup'][$key - 1] : '';
			}
		}

		$device['settings']['provisioning_profile'] = isset($post['provisioning_profile']) ? $post['provisioning_profile'] : 'auto';
		$device['settings'] = array_merge($device['settings'], Zts_DeviceWifiSettingsService::parseFromPost($post));

		$existingSettings = array();
		$existingModel = '';
		$previousExtension = '';
		if ($editId !== '')
		{
			$existing = Zts_DeviceRepository::findForEdit($editId);
			$existingSettings = isset($existing['settings']) && is_array($existing['settings'])
				? $existing['settings'] : array();
			$existingModel = isset($existing['model']) ? (string) $existing['model'] : '';
			$previousExtension = Zts_DeviceNamingService::primaryExtensionFromDevice($existing);
			$device['settings'] = array_merge($existingSettings, $device['settings']);
		}

		$check = Zts_DeviceEditValidator::validate($device, $isNew);
		if (!$check['ok'])
		{
			return array('ok' => false, 'errors' => $check['errors'], 'device' => $device, 'id' => $editId);
		}
		$wifiNetwork = Zts_DeviceWifiSettingsService::resolveNetworkForDevice(
			$editId !== '' ? Zts_DeviceRepository::findForEdit($editId) : $device
		);
		$wifiCheck = Zts_DeviceWifiSettingsService::validate(
			$device['settings'],
			isset($wifiNetwork['settings']) && is_array($wifiNetwork['settings']) ? $wifiNetwork['settings'] : array()
		);
		if (!$wifiCheck['ok'])
		{
			return array('ok' => false, 'errors' => $wifiCheck['errors'], 'device' => $device, 'id' => $editId);
		}
		$device['mac'] = $check['mac'];
		Zts_DeviceNamingService::resolveNameOnSave($device, $check['name'], $existingSettings, $existingModel, $previousExtension);

		$saveFanvil = zts_phones_edit_post_is_fanvil($editId, $post);
		if (!$saveFanvil)
		{
			zts_phones_edit_delete_fanvil_hotline_settings($editId);
		}
		else
		{
			for ($hl = 1; $hl <= 2; $hl++)
			{
				$device['settings']['fanvil_sip_hotline_'.$hl.'_enable'] = !empty($post['hotline_enable'][$hl]) ? '1' : '0';
				$hl_delay = isset($post['hotline_delay'][$hl]) ? (int) $post['hotline_delay'][$hl] : 0;
				$hl_delay = max(0, min(30, $hl_delay));
				$device['settings']['fanvil_sip_hotline_'.$hl.'_delay'] = (string) $hl_delay;
				$hl_num = isset($post['hotline_number'][$hl]) ? Zts_InputValidator::trimString($post['hotline_number'][$hl]) : '';
				if (strlen($hl_num) > 39)
				{
					$hl_num = substr($hl_num, 0, 39);
				}
				$device['settings']['fanvil_sip_hotline_'.$hl.'_number'] = $hl_num;
			}
			if ($editId !== '')
			{
				global $db;
				sql("DELETE FROM zts_device_settings WHERE id = '".$db->escapeSimple($editId)."' AND keyword IN ('fanvil_sip_hotline_enable','fanvil_sip_hotline_delay','fanvil_sip_hotline_number')");
			}
		}

		$savedId = Zts_DeviceRepository::save($editId, $device);

		if ($editId !== '' && !isset($device['settings'][Zts_DeviceNamingService::SETTING_NAME_MANUAL]))
		{
			global $db;
			sql("DELETE FROM zts_device_settings WHERE id = '".$db->escapeSimple($savedId)."'
				AND keyword = '".$db->escapeSimple(Zts_DeviceNamingService::SETTING_NAME_MANUAL)."'");
		}

		return array('ok' => true, 'errors' => array(), 'device' => $device, 'id' => $savedId);
	}
}
