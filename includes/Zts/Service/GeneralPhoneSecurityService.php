<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Default Phone Security rows (admin / user) for General Settings.
 */
class Zts_GeneralPhoneSecurityService
{
	const SETTING_JSON = 'default_phone_security_json';
	const MAX_ROWS = 8;

	/** @return array<int,array{profile:string,username:string,password:string,level:string}> */
	public static function defaultRows()
	{
		return array(
			array('profile' => 'auto', 'username' => 'admin', 'password' => 'admin', 'level' => 'admin'),
			array('profile' => 'auto', 'username' => 'user', 'password' => 'user', 'level' => 'user'),
		);
	}

	/** @return array<string,string> */
	public static function profileChoices()
	{
		return array(
			'auto' => _('Auto'),
			'fanvil' => _('Fanvil'),
			'yealink' => _('Yealink'),
		);
	}

	/**
	 * @param array<string,string> $general
	 * @return array<int,array{profile:string,username:string,password:string,level:string}>
	 */
	public static function rowsFromGeneral(array $general)
	{
		if (isset($general[self::SETTING_JSON]) && (string) $general[self::SETTING_JSON] !== '')
		{
			$decoded = json_decode((string) $general[self::SETTING_JSON], true);
			if (is_array($decoded) && count($decoded) >= 1)
			{
				$rows = array();
				foreach ($decoded as $row)
				{
					if (!is_array($row))
					{
						continue;
					}
					$rows[] = self::normalizeRow($row);
				}
				if (count($rows) >= 1)
				{
					return $rows;
				}
			}
		}

		return array(
			array(
				'profile' => 'auto',
				'username' => isset($general['device_admin_username']) ? (string) $general['device_admin_username'] : 'admin',
				'password' => isset($general['device_admin_password']) ? (string) $general['device_admin_password'] : 'admin',
				'level' => 'admin',
			),
			array(
				'profile' => 'auto',
				'username' => isset($general['device_user_username']) ? (string) $general['device_user_username'] : 'user',
				'password' => isset($general['device_user_password']) ? (string) $general['device_user_password'] : 'user',
				'level' => 'user',
			),
		);
	}

	/**
	 * @param array $row
	 * @return array{profile:string,username:string,password:string,level:string}
	 */
	private static function normalizeRow(array $row)
	{
		$profile = isset($row['profile']) ? strtolower(trim((string) $row['profile'])) : 'auto';
		if (!in_array($profile, array('auto', 'fanvil', 'yealink'), true))
		{
			$profile = 'auto';
		}
		$level = isset($row['level']) ? strtolower(trim((string) $row['level'])) : 'user';
		if ($level === '10')
		{
			$level = 'admin';
		}
		elseif ($level === '5')
		{
			$level = 'user';
		}
		if ($level !== 'admin')
		{
			$level = 'user';
		}

		return array(
			'profile' => $profile,
			'username' => isset($row['username']) ? Zts_InputValidator::trimString($row['username']) : '',
			'password' => isset($row['password']) ? (string) $row['password'] : '',
			'level' => $level,
		);
	}

	/**
	 * @param array<string,string> $general
	 * @param array              $post
	 * @return array<string,string>
	 */
	public static function applyFromPost(array $general, array $post)
	{
		$profiles = isset($post['security_profile']) && is_array($post['security_profile']) ? $post['security_profile'] : array();
		$names = isset($post['security_username']) && is_array($post['security_username']) ? $post['security_username'] : array();
		$passwords = isset($post['security_password']) && is_array($post['security_password']) ? $post['security_password'] : array();
		$levels = isset($post['security_level']) && is_array($post['security_level']) ? $post['security_level'] : array('admin', 'user');

		$rows = array();
		$count = max(count($profiles), count($names), count($passwords), count($levels));
		for ($i = 0; $i < $count; $i++)
		{
			$rows[] = self::normalizeRow(array(
				'profile' => isset($profiles[$i]) ? $profiles[$i] : 'auto',
				'username' => isset($names[$i]) ? $names[$i] : '',
				'password' => isset($passwords[$i]) ? $passwords[$i] : '',
				'level' => isset($levels[$i]) ? $levels[$i] : 'user',
			));
		}
		if (count($rows) < 1)
		{
			$rows = self::defaultRows();
		}
		if (count($rows) > self::MAX_ROWS)
		{
			$rows = array_slice($rows, 0, self::MAX_ROWS);
		}

		$general[self::SETTING_JSON] = json_encode($rows);
		$adminSynced = false;
		$userSynced = false;
		foreach ($rows as $row)
		{
			if ($row['level'] === 'admin')
			{
				if (!$adminSynced)
				{
					$general['device_admin_username'] = $row['username'];
					$general['device_admin_password'] = $row['password'];
					$general['default_provisioning_profile'] = $row['profile'];
					$adminSynced = true;
				}
			}
			elseif (!$userSynced)
			{
				$general['device_user_username'] = $row['username'];
				$general['device_user_password'] = $row['password'];
				$userSynced = true;
			}
		}

		return $general;
	}

	/**
	 * Admin credentials for an exact Phone Security profile (fanvil|yealink|auto), no fallback.
	 *
	 * @param array<string,string> $general
	 * @param string               $profile auto|fanvil|yealink
	 * @return array{username:string,password:string}
	 */
	public static function adminWebCredentialsByProfile(array $general, $profile)
	{
		$profile = strtolower(trim((string) $profile));
		if (!in_array($profile, array('auto', 'fanvil', 'yealink'), true))
		{
			return array('username' => '', 'password' => '');
		}
		foreach (self::rowsFromGeneral($general) as $row)
		{
			if ($row['level'] !== 'admin' || $row['profile'] !== $profile)
			{
				continue;
			}
			if ($row['username'] === '' || $row['password'] === '')
			{
				continue;
			}

			return array(
				'username' => $row['username'],
				'password' => $row['password'],
			);
		}

		return array('username' => '', 'password' => '');
	}

	public static function adminWebCredentials(array $general, $vendorId = '')
	{
		$vendorId = trim((string) $vendorId);
		$rows = self::rowsFromGeneral($general);
		$fallbackAuto = null;
		$fallbackAny = null;
		foreach ($rows as $row)
		{
			if ($row['level'] !== 'admin')
			{
				continue;
			}
			if ($row['username'] === '' || $row['password'] === '')
			{
				continue;
			}
			if ($vendorId !== '' && $row['profile'] === $vendorId)
			{
				return array(
					'username' => $row['username'],
					'password' => $row['password'],
				);
			}
			if ($row['profile'] === 'auto' && $fallbackAuto === null)
			{
				$fallbackAuto = $row;
			}
			if ($fallbackAny === null)
			{
				$fallbackAny = $row;
			}
		}
		if ($fallbackAuto !== null)
		{
			return array(
				'username' => $fallbackAuto['username'],
				'password' => $fallbackAuto['password'],
			);
		}
		if ($fallbackAny !== null)
		{
			return array(
				'username' => $fallbackAny['username'],
				'password' => $fallbackAny['password'],
			);
		}

		$legacy = self::legacyPasswordFallback($general);

		return array(
			'username' => isset($general['device_admin_username']) && (string) $general['device_admin_username'] !== ''
				? (string) $general['device_admin_username']
				: 'admin',
			'password' => (string) $legacy['device_admin_password'],
		);
	}

	public static function legacyPasswordFallback(array $general)
	{
		$rows = self::rowsFromGeneral($general);
		$adminPass = '';
		$userPass = '';
		foreach ($rows as $row)
		{
			if ($row['level'] === 'admin' && $adminPass === '')
			{
				$adminPass = $row['password'];
			}
			elseif ($row['level'] === 'user' && $userPass === '')
			{
				$userPass = $row['password'];
			}
		}
		if ($adminPass === '' && isset($rows[0]))
		{
			$adminPass = $rows[0]['password'];
		}
		if ($userPass === '' && isset($rows[1]))
		{
			$userPass = $rows[1]['password'];
		}

		return array(
			'device_user_password' => $userPass,
			'device_admin_password' => $adminPass,
		);
	}
}
