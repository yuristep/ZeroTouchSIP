<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Data access for phone inventory list (no PJSIP enrichment, no HTML).
 */
class Zts_PhonesListRepository
{
	/**
	 * @param string $sort  normalized column key
	 * @param string $order asc|desc
	 * @return array<int,array>
	 */
	public static function fetchAllSorted($sort, $order)
	{
		global $db;

		$map = zts_phones_list_sort_map();
		if (!isset($map[$sort]))
		{
			$sort = 'mac';
		}
		$orderExpr = $map[$sort];
		$sqlOrder = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

		$linesCountSql = zts_phones_list_assigned_lines_count_sql();
		$results = sql("SELECT zts_devices.id, zts_devices.name, zts_devices.mac, zts_devices.model,
			zts_devices.firmware_version, zts_devices.lastconfig, zts_devices.lastip,
			".$linesCountSql." AS line_count
			FROM zts_devices
			ORDER BY ".$orderExpr." ".$sqlOrder.", zts_devices.mac ASC", 'getAll', DB_FETCHMODE_ASSOC);

		if (!is_array($results))
		{
			return array();
		}

		foreach ($results as $key => $result)
		{
			$results[$key]['lines'] = sql("SELECT zts_device_lines.lineid, zts_device_lines.deviceid,
					devices.id, devices.description, users.extension, users.name
				FROM zts_device_lines
				LEFT OUTER JOIN devices ON devices.id = zts_device_lines.deviceid
				LEFT OUTER JOIN users ON devices.user = users.extension
				WHERE zts_device_lines.id = \"".$db->escapeSimple($result['id'])."\"
				ORDER BY zts_device_lines.lineid", 'getAll', DB_FETCHMODE_ASSOC);
			if (!is_array($results[$key]['lines']))
			{
				$results[$key]['lines'] = array();
			}
		}

		return $results;
	}
}
