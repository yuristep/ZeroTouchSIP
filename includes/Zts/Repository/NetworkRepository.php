<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_NetworkRepository
{
	/** @return array<string,string> */
	public static function sortColumnMap()
	{
		return array(
			'name' => 'zts_networks.name',
			'cidr' => 'zts_networks.cidr',
		);
	}

	/**
	 * @param string $sort
	 * @param string $order asc|desc
	 * @return array<int,array>
	 */
	public static function fetchAllSorted($sort, $order)
	{
		$map = self::sortColumnMap();
		if (!isset($map[$sort]))
		{
			$sort = 'cidr';
		}
		$orderExpr = $map[$sort];
		$sqlOrder = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

		$results = sql("SELECT id, name, cidr FROM zts_networks ORDER BY ".$orderExpr." ".$sqlOrder.", zts_networks.id ASC",
			'getAll', DB_FETCHMODE_ASSOC);

		return is_array($results) ? $results : array();
	}

	/**
	 * @param string|int $id
	 * @return array
	 */
	public static function findForEdit($id)
	{
		global $db;

		$id = Zts_InputValidator::trimString($id);
		$network = array(
			'name' => '',
			'cidr' => '',
			'settings' => self::defaultSettings(),
		);

		if ($id === '')
		{
			return $network;
		}

		$row = sql("SELECT name, cidr FROM zts_networks WHERE id = \"".$db->escapeSimple($id)."\"",
			'getRow', DB_FETCHMODE_ASSOC);
		if (is_array($row))
		{
			$network['name'] = $row['name'];
			$network['cidr'] = $row['cidr'];
		}

		$settings = sql("SELECT keyword, value FROM zts_network_settings WHERE id = \"".$db->escapeSimple($id)."\"",
			'getAll', DB_FETCHMODE_ASSOC);
		if (is_array($settings))
		{
			foreach ($settings as $setting)
			{
				$network['settings'][$setting['keyword']] = $setting['value'];
			}
		}

		return $network;
	}

	/**
	 * @return array<string,string>
	 */
	public static function defaultSettings()
	{
		return array_merge(array(
			'prov_protocol' => 'HTTPS',
			'fanvil_config_version' => '2.0004',
			'prov_username' => 'yealink',
			'prov_password' => 'yealink',
			'sip_server_address' => '',
			'sip_server_port' => '5060',
			'sip_server_transport' => '0',
			'sip_server_expires' => '3600',
			'nat_keepalive_interval' => '30',
			'ntp_server1' => 'pool.ntp.org',
			'ntp_server2' => '',
			'time_zone' => '3',
			'time_zone_fanvil' => '12',
			'time_zone_name' => '(UTC+3) East Africa Time,Baghdad,Moscow,Ankara,Istanbul',
			'daylight_saving_time' => '2',
			'daylight_saving_time_fanvil' => '1',
		), Zts_NetworkCodecRegistry::defaultCodecSettings(), Zts_NetworkWifiProfileService::defaultScalarSettings());
	}

	/**
	 * @param string|int $id
	 * @param array      $network
	 * @return string
	 */
	public static function save($id, array $network)
	{
		global $db;

		$id = Zts_InputValidator::trimString($id);
		$name = isset($network['name']) ? (string) $network['name'] : '';
		$cidr = isset($network['cidr']) ? (string) $network['cidr'] : '';

		if ($id === '')
		{
			sql("INSERT INTO zts_networks (name, cidr) VALUES ('".$db->escapeSimple($name)."', '".$db->escapeSimple($cidr)."')");
			$results = sql("SELECT LAST_INSERT_ID()", 'getAll', DB_FETCHMODE_ASSOC);
			if (is_array($results) && count($results) > 0)
			{
				$id = (string) $results[0]['LAST_INSERT_ID()'];
			}
			else
			{
				die_freepbx('Unable to determine SQL insert id');
			}
		}
		else
		{
			sql("UPDATE zts_networks SET name = '".$db->escapeSimple($name)."',
				cidr = '".$db->escapeSimple($cidr)."' WHERE id = '".$db->escapeSimple($id)."'");
		}

		if (isset($network['settings']) && is_array($network['settings']))
		{
			$entries = array();
			foreach ($network['settings'] as $key => $val)
			{
				$entries[] = '\''.$db->escapeSimple($id).'\',\''.$db->escapeSimple($key).'\',\''.$db->escapeSimple($val).'\'';
			}
			if (count($entries) > 0)
			{
				sql("REPLACE INTO zts_network_settings (id, keyword, value) VALUES (".implode('),(', $entries).")");
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
		global $db;

		$id = Zts_InputValidator::trimString($id);
		if ($id === '' || $id === '-1')
		{
			return;
		}

		sql("DELETE FROM zts_networks WHERE id = '".$db->escapeSimple($id)."'");
		sql("DELETE FROM zts_network_settings WHERE id = '".$db->escapeSimple($id)."'");
	}
}
