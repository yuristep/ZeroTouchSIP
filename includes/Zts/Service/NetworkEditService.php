<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_NetworkEditService
{
	/** @return string[] */
	public static function settingFieldNames()
	{
		return array_merge(array(
			'prov_protocol', 'fanvil_config_version', 'prov_username', 'prov_password', 'sip_server_address', 'sip_server_port',
			'sip_server_transport', 'sip_server_expires', 'nat_keepalive_interval', 'ntp_server1', 'ntp_server2',
			'time_zone', 'time_zone_fanvil', 'time_zone_name', 'daylight_saving_time', 'daylight_saving_time_fanvil',
		), array_keys(Zts_NetworkWifiProfileService::defaultScalarSettings()));
	}

	/**
	 * @param string|int $editId
	 * @param array      $post
	 * @return array{ok:bool,errors:string[],id:string}
	 */
	public static function saveFromPost($editId, array $post)
	{
		$network = array(
			'name' => isset($post['name']) ? (string) $post['name'] : '',
			'cidr' => isset($post['cidr']) ? (string) $post['cidr'] : '',
			'settings' => array(),
		);
		foreach (self::settingFieldNames() as $field)
		{
			$network['settings'][$field] = isset($post[$field]) ? (string) $post[$field] : '';
		}
		$network['settings']['fanvil_config_version'] = Zts_FanvilConfigVersionService::normalize(
			$network['settings']['fanvil_config_version']
		);
		$network['settings'] = Zts_NetworkTimeSettingsMapper::applyFromPost($network['settings'], $post);
		$network['settings'] = Zts_NetworkCodecMapper::applyFromPost($network['settings'], $post);

		$existingMmi = array();
		$existingWifi = array();
		$editIdTrim = Zts_InputValidator::trimString($editId);
		if ($editIdTrim !== '')
		{
			$prev = Zts_NetworkRepository::findForEdit($editIdTrim);
			$existingMmi = Zts_NetworkMmiAccountService::fromSettings($prev['settings']);
			$existingWifi = Zts_NetworkWifiProfileService::fromSettings($prev['settings']);
		}
		$mmiParsed = Zts_NetworkMmiAccountService::parseFromPost($post);
		$mmiParsed = Zts_NetworkMmiAccountService::mergePasswordsFromExisting($mmiParsed, $existingMmi);
		$network['settings'][Zts_NetworkMmiAccountService::SETTING_KEY] = Zts_NetworkMmiAccountService::toJson($mmiParsed);

		$wifiParsed = Zts_NetworkWifiProfileService::parseFromPost($post);
		$wifiParsed = Zts_NetworkWifiProfileService::mergePasswordsFromExisting($wifiParsed, $existingWifi);
		$network['settings'][Zts_NetworkWifiProfileService::SETTING_KEY] = Zts_NetworkWifiProfileService::toJson($wifiParsed);

		$check = Zts_NetworkEditValidator::validate($network);
		if (!$check['ok'])
		{
			return array('ok' => false, 'errors' => $check['errors'], 'id' => Zts_InputValidator::trimString($editId));
		}
		$network['name'] = $check['name'];
		$network['cidr'] = $check['cidr'];

		$id = Zts_NetworkRepository::save(Zts_InputValidator::trimString($editId), $network);

		return array('ok' => true, 'errors' => array(), 'id' => $id);
	}
}
