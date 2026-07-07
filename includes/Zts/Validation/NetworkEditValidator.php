<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_NetworkEditValidator
{
	/**
	 * @param array $network
	 * @return array{ok:bool,errors:string[],name:string,cidr:string}
	 */
	public static function validate(array $network)
	{
		$errors = array();
		$name = Zts_InputValidator::trimString(isset($network['name']) ? $network['name'] : '');
		$cidr = Zts_InputValidator::trimString(isset($network['cidr']) ? $network['cidr'] : '');

		if ($name === '')
		{
			$errors[] = _('Network name is required.');
		}
		if ($cidr === '' || strpos($cidr, '/') === false)
		{
			$errors[] = _('CIDR must include network and prefix (e.g. 192.168.1.0/24).');
		}
		else
		{
			list($net, $mask) = explode('/', $cidr, 2);
			$mask = (int) $mask;
			if ($mask < 0 || $mask > 32 || filter_var($net, FILTER_VALIDATE_IP) === false)
			{
				$errors[] = _('Invalid CIDR.');
			}
		}

		if (isset($network['settings']) && is_array($network['settings']))
		{
			$cfgVer = isset($network['settings'][Zts_FanvilConfigVersionService::SETTING_KEY])
				? trim((string) $network['settings'][Zts_FanvilConfigVersionService::SETTING_KEY]) : '';
			if ($cfgVer !== '' && !Zts_FanvilConfigVersionService::isValid($cfgVer))
			{
				$errors[] = _('Config Version must look like 2.0004 (major.minor, Fanvil Current Config Version).');
			}
			$mmiCheck = Zts_NetworkMmiAccountService::validate(
				Zts_NetworkMmiAccountService::fromSettings($network['settings'])
			);
			if (!$mmiCheck['ok'])
			{
				$errors = array_merge($errors, $mmiCheck['errors']);
			}
			$wifiCheck = Zts_NetworkWifiProfileService::validateSettings($network['settings']);
			if (!$wifiCheck['ok'])
			{
				$errors = array_merge($errors, $wifiCheck['errors']);
			}
		}

		return array('ok' => count($errors) < 1, 'errors' => $errors, 'name' => $name, 'cidr' => $cidr);
	}
}
