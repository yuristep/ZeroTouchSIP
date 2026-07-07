<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_DeviceEditValidator
{
	/**
	 * @param array $device
	 * @param bool  $isNew
	 * @return array{ok:bool,errors:string[]}
	 */
	public static function validate(array $device, $isNew = false)
	{
		$errors = array();
		$name = Zts_InputValidator::trimString(isset($device['name']) ? $device['name'] : '');
		$mac = Zts_InputValidator::normalizeMac(isset($device['mac']) ? $device['mac'] : '');

		if ($name === '')
		{
			$errors[] = _('Device name is required.');
		}
		if ($mac === '')
		{
			$errors[] = _('MAC address must be 12 hexadecimal digits.');
		}

		if (isset($device['settings']['provisioning_profile']))
		{
			$profile = Zts_InputValidator::whitelist(
				$device['settings']['provisioning_profile'],
				array('auto' => true, 'yealink' => true, 'fanvil' => true),
				'auto'
			);
			if ($profile !== (string) $device['settings']['provisioning_profile'])
			{
				$errors[] = _('Invalid provisioning profile.');
			}
		}

		return array('ok' => count($errors) < 1, 'errors' => $errors, 'mac' => $mac, 'name' => $name);
	}
}
