<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Inventory / line rows for SIP NOTIFY and Fanvil HTTP notify.
 */
class Zts_NotifyInventoryRepository
{
	/**
	 * @param string|string[]|null $id zts_devices.id or null for all
	 * @return array
	 */
	public static function fetchInventory($id = null)
	{
		global $db;

		$sql = "SELECT yd.id, yd.name, yd.model, yd.lastip, IFNULL(yds.value, '') AS prov_profile
			FROM zts_devices yd
			LEFT JOIN zts_device_settings yds ON yds.id = yd.id AND yds.keyword = 'provisioning_profile'";
		if ($id !== null && $id !== '' && $id !== false)
		{
			$id_array = is_array($id) ? $id : array($id);
			$escaped = array();
			foreach ($id_array as $single_id)
			{
				$escaped[] = "'".$db->escapeSimple($single_id)."'";
			}
			$sql .= ' WHERE yd.id IN ('.implode(',', $escaped).')';
		}

		return sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
	}

	/**
	 * Distinct PJSIP endpoint ids (deviceid) for selected zts_devices.id values.
	 *
	 * @param string|null $idsFilter SQL fragment without quotes, e.g. id1','id2
	 * @return array
	 */
	public static function fetchLineEndpoints($idsFilter = null)
	{
		$sql = "SELECT DISTINCT ydl.deviceid, yd.model, yd.name, IFNULL(yds.value, '') AS prov_profile
			FROM zts_device_lines ydl
			INNER JOIN zts_devices yd ON yd.id = ydl.id
			LEFT JOIN zts_device_settings yds ON yds.id = yd.id AND yds.keyword = 'provisioning_profile'
			WHERE ydl.deviceid IS NOT NULL";
		if ($idsFilter !== null)
		{
			$sql .= " AND ydl.id IN ('".$idsFilter."')";
		}

		return sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
	}

	/**
	 * @param string $yealinkDeviceId
	 * @return array|null
	 */
	public static function fetchDeviceRow($yealinkDeviceId)
	{
		global $db;

		return sql("SELECT yd.model, yd.name, yd.lastip, IFNULL(yds.value, '') AS prov_profile
			FROM zts_devices yd
			LEFT JOIN zts_device_settings yds ON yds.id = yd.id AND yds.keyword = 'provisioning_profile'
			WHERE yd.id = '".$db->escapeSimple($yealinkDeviceId)."'", 'getRow', DB_FETCHMODE_ASSOC);
	}

	/**
	 * @param string $yealinkDeviceId
	 * @return array
	 */
	public static function fetchLineDeviceIds($yealinkDeviceId)
	{
		global $db;

		return sql("SELECT DISTINCT deviceid FROM zts_device_lines
			WHERE id = '".$db->escapeSimple($yealinkDeviceId)."' AND deviceid IS NOT NULL", 'getAll', DB_FETCHMODE_ASSOC);
	}
}
