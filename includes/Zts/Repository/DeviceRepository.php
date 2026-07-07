<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * zts_devices + lines, linekeys, device_settings (data access only).
 */
class Zts_DeviceRepository
{
	/**
	 * @param string|int $id
	 * @return array
	 */
	public static function findForEdit($id)
	{
		global $db;

		$id = Zts_InputValidator::trimString($id);
		$device = array(
			'name' => '',
			'mac' => '',
			'model' => '',
			'firmware_version' => '',
			'lastconfig' => '',
			'lastip' => '',
			'settings' => array(),
			'lines' => array(),
			'linekeys' => array(),
		);

		if ($id === '')
		{
			return $device;
		}

		$row = sql("SELECT name, mac, model, firmware_version, lastconfig, lastip
			FROM zts_devices WHERE id = \"".$db->escapeSimple($id)."\"", 'getRow', DB_FETCHMODE_ASSOC);
		if (is_array($row))
		{
			$device = array_merge($device, $row);
		}

		$lines = sql("SELECT lineid, deviceid FROM zts_device_lines
			WHERE id = \"".$db->escapeSimple($id)."\" ORDER BY lineid", 'getAll', DB_FETCHMODE_ASSOC);
		if (is_array($lines))
		{
			foreach ($lines as $line)
			{
				$device['lines'][$line['lineid']] = $line;
			}
		}

		foreach ($device['lines'] as $key => $line)
		{
			$settings = sql("SELECT keyword, value FROM zts_device_line_settings
				WHERE id = \"".$db->escapeSimple($id)."\" AND lineid = \"".$db->escapeSimple($key)."\"",
				'getAll', DB_FETCHMODE_ASSOC);
			if (is_array($settings))
			{
				foreach ($settings as $setting)
				{
					$device['lines'][$key]['settings'][$setting['keyword']] = $setting['value'];
				}
			}
		}

		$linekeys = sql("SELECT linekeyid, type, line, value, label, extension, pickup_value
			FROM zts_device_linekeys WHERE id = \"".$db->escapeSimple($id)."\" ORDER BY linekeyid",
			'getAll', DB_FETCHMODE_ASSOC);
		if (is_array($linekeys))
		{
			foreach ($linekeys as $linekey)
			{
				$device['linekeys'][$linekey['linekeyid']] = $linekey;
			}
		}

		$settings = sql("SELECT keyword, value FROM zts_device_settings
			WHERE id = \"".$db->escapeSimple($id)."\"", 'getAll', DB_FETCHMODE_ASSOC);
		if (is_array($settings))
		{
			foreach ($settings as $setting)
			{
				$device['settings'][$setting['keyword']] = $setting['value'];
			}
		}

		return $device;
	}

	/**
	 * @param string|int $id empty for create
	 * @param array      $device
	 * @return string saved device id
	 */
	public static function save($id, array $device)
	{
		global $db;

		$id = Zts_InputValidator::trimString($id);
		$name = isset($device['name']) ? (string) $device['name'] : '';
		$mac = isset($device['mac']) ? (string) $device['mac'] : '';

		if ($id === '')
		{
			sql("INSERT INTO zts_devices (name, mac, model, firmware_version, lastconfig, lastip)
				VALUES ('".$db->escapeSimple($name)."','".$db->escapeSimple($mac)."','','',now(),'')");
			$id = (string) sql("SELECT LAST_INSERT_ID()", 'getOne');
		}
		else
		{
			sql("UPDATE zts_devices SET name = '".$db->escapeSimple($name)."',
				mac = '".$db->escapeSimple($mac)."' WHERE id = '".$db->escapeSimple($id)."'");
		}

		sql("DELETE FROM zts_device_lines WHERE id = '".$db->escapeSimple($id)."'");
		sql("DELETE FROM zts_device_line_settings WHERE id = '".$db->escapeSimple($id)."'");

		if (isset($device['lines']) && is_array($device['lines']))
		{
			foreach ($device['lines'] as $lineid => $line)
			{
				$devId = (isset($line['deviceid']) && $line['deviceid'] !== null && $line['deviceid'] !== '')
					? "'".$db->escapeSimple($line['deviceid'])."'" : 'NULL';
				sql("INSERT INTO zts_device_lines (id, lineid, deviceid)
					VALUES ('".$db->escapeSimple($id)."','".$db->escapeSimple($lineid)."',".$devId.")");

				if (isset($line['settings']) && is_array($line['settings']))
				{
					$entries = array();
					foreach ($line['settings'] as $key => $val)
					{
						$entries[] = '\''.$db->escapeSimple($id).'\',\''.$db->escapeSimple($lineid).'\',\''.
							$db->escapeSimple($key).'\',\''.$db->escapeSimple($val).'\'';
					}
					if (count($entries) > 0)
					{
						sql("INSERT INTO zts_device_line_settings (id, lineid, keyword, value)
							VALUES (".implode('),(', $entries).")");
					}
				}
			}
		}

		sql("DELETE FROM zts_device_linekeys WHERE id = '".$db->escapeSimple($id)."'");
		if (isset($device['linekeys']) && is_array($device['linekeys']))
		{
			foreach ($device['linekeys'] as $linekeyid => $linekey)
			{
				sql("INSERT INTO zts_device_linekeys (id, linekeyid, type, line, value, label, extension, pickup_value)
					VALUES ('".$db->escapeSimple($id)."','".$db->escapeSimple($linekeyid)."','".
					$db->escapeSimple($linekey['type'])."','".$db->escapeSimple($linekey['line'])."','".
					$db->escapeSimple($linekey['value'])."','".$db->escapeSimple($linekey['label'])."','".
					$db->escapeSimple($linekey['extension'])."','".$db->escapeSimple($linekey['pickup_value'])."')");
			}
		}

		if (isset($device['settings']) && is_array($device['settings']))
		{
			$entries = array();
			foreach ($device['settings'] as $key => $val)
			{
				$entries[] = '\''.$db->escapeSimple($id).'\',\''.$db->escapeSimple($key).'\',\''.$db->escapeSimple($val).'\'';
			}
			if (count($entries) > 0)
			{
				sql("REPLACE INTO zts_device_settings (id, keyword, value)
					VALUES (".implode('),(', $entries).")");
			}
		}

		return $id;
	}

	/**
	 * @param string|int $id
	 * @return void
	 */
	public static function deleteById($id)
	{
		global $db, $amp_conf;

		$id = Zts_InputValidator::trimString($id);
		if ($id === '')
		{
			return;
		}

		$mac = sql("SELECT mac FROM zts_devices WHERE id = '".$db->escapeSimple($id)."'", 'getOne');

		sql("DELETE FROM zts_devices WHERE id = '".$db->escapeSimple($id)."'");
		sql("DELETE FROM zts_device_settings WHERE id = '".$db->escapeSimple($id)."'");
		sql("DELETE FROM zts_device_lines WHERE id = '".$db->escapeSimple($id)."'");
		sql("DELETE FROM zts_device_line_settings WHERE id = '".$db->escapeSimple($id)."'");
		sql("DELETE FROM zts_device_linekeys WHERE id = '".$db->escapeSimple($id)."'");

		if (!empty($mac))
		{
			$path = $amp_conf['AMPWEBROOT'].'/admin/modules/'.Zts_ProvisioningPaths::SHARED_PROVISIONING_MODULE_SUBDIR.'/';
			foreach (array('logs', 'configs', 'contacts') as $folder)
			{
				foreach (glob($path.$folder.'/'.$mac.'*') as $filename)
				{
					@unlink($filename);
				}
			}
		}
	}

	/**
	 * Replace only programmable Line Keys for an existing phone.
	 *
	 * @param string|int      $id zts_devices.id
	 * @param array<int,array> $linekeys
	 * @return bool
	 */
	public static function replaceLinekeys($id, array $linekeys)
	{
		global $db;

		$id = Zts_InputValidator::trimString($id);
		if ($id === '')
		{
			return false;
		}
		$exists = sql("SELECT id FROM zts_devices WHERE id = '".$db->escapeSimple($id)."'", 'getOne');
		if ($exists === '' || $exists === false)
		{
			return false;
		}

		sql("DELETE FROM zts_device_linekeys WHERE id = '".$db->escapeSimple($id)."'");
		$linekeys = Zts_LinekeyTemplateService::normalizeKeysMap($linekeys);
		foreach ($linekeys as $linekeyid => $linekey)
		{
			sql("INSERT INTO zts_device_linekeys (id, linekeyid, type, line, value, label, extension, pickup_value)
				VALUES ('".$db->escapeSimple($id)."','".$db->escapeSimple($linekeyid)."','".
				$db->escapeSimple($linekey['type'])."','".$db->escapeSimple($linekey['line'])."','".
				$db->escapeSimple($linekey['value'])."','".$db->escapeSimple($linekey['label'])."','".
				$db->escapeSimple($linekey['extension'])."','".$db->escapeSimple($linekey['pickup_value'])."')");
		}

		return true;
	}

	/**
	 * @param string|int $id zts_devices.id
	 * @return mixed
	 */
	public static function lookupMinPbxDeviceId($id)
	{
		global $db;

		return sql("SELECT MIN(deviceid) AS deviceid FROM zts_device_lines
			WHERE deviceid IS NOT NULL AND id = '".$db->escapeSimple($id)."' GROUP BY id", 'getOne');
	}
}
