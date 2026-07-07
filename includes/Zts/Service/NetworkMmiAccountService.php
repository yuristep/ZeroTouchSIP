<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Phone web UI accounts per provisioning network (Fanvil MMI_Account, Yealink security.user_*).
 */
class Zts_NetworkMmiAccountService
{
	const SETTING_KEY = 'mmi_accounts_json';
	const MAX_ACCOUNTS = 8;
	/** Yealink security.user_name.* max length (Administrator's Guide). */
	const YEALINK_LOGIN_MAX = 32;

	/** Fanvil VOIP cfg: label padded to this width before ':' (OEM H2U/H5). */
	const FANVIL_COLON_PAD = 27;

	/**
	 * @return array<int,array{id:string,name:string,password:string,level:string}>
	 */
	public static function defaultRowsForForm()
	{
		return array(
			array('id' => 'Account1', 'name' => '', 'password' => '', 'level' => '10'),
			array('id' => 'Account2', 'name' => '', 'password' => '', 'level' => '5'),
		);
	}

	/**
	 * @param array<string,string> $settings
	 * @return array<int,array{id:string,name:string,password:string,level:string}>
	 */
	public static function fromSettings(array $settings)
	{
		$raw = isset($settings[self::SETTING_KEY]) ? trim((string) $settings[self::SETTING_KEY]) : '';
		if ($raw === '')
		{
			return array();
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded))
		{
			return array();
		}

		return self::normalizeList($decoded);
	}

	/**
	 * @param array $list
	 * @return array<int,array{id:string,name:string,password:string,level:string}>
	 */
	public static function normalizeList(array $list)
	{
		$out = array();
		$idx = 0;
		foreach ($list as $row)
		{
			if (!is_array($row))
			{
				continue;
			}
			$name = isset($row['name']) ? trim((string) $row['name']) : '';
			if ($name === '')
			{
				continue;
			}
			$idx++;
			$id = isset($row['id']) ? trim((string) $row['id']) : '';
			if ($id === '')
			{
				$id = 'Account'.$idx;
			}
			$level = isset($row['level']) ? (int) $row['level'] : 5;
			if ($level !== 10)
			{
				$level = 5;
			}
			$out[] = array(
				'id' => $id,
				'name' => $name,
				'password' => isset($row['password']) ? (string) $row['password'] : '',
				'level' => (string) $level,
			);
			if (count($out) >= self::MAX_ACCOUNTS)
			{
				break;
			}
		}

		return $out;
	}

	/**
	 * @param array $post
	 * @return array<int,array{id:string,name:string,password:string,level:string}>
	 */
	public static function parseFromPost(array $post)
	{
		$names = isset($post['mmi_name']) && is_array($post['mmi_name']) ? $post['mmi_name'] : array();
		$passwords = isset($post['mmi_password']) && is_array($post['mmi_password']) ? $post['mmi_password'] : array();
		$levels = isset($post['mmi_level']) && is_array($post['mmi_level']) ? $post['mmi_level'] : array();

		$rowCount = max(count($names), count($passwords), count($levels));
		$out = array();
		for ($i = 0; $i < $rowCount && count($out) < self::MAX_ACCOUNTS; $i++)
		{
			$name = isset($names[$i]) ? trim((string) $names[$i]) : '';
			if ($name === '')
			{
				continue;
			}
			$password = isset($passwords[$i]) ? (string) $passwords[$i] : '';
			$level = isset($levels[$i]) ? (int) $levels[$i] : 5;
			if ($level !== 10)
			{
				$level = 5;
			}
			$out[] = array(
				'id' => 'Account'.(count($out) + 1),
				'name' => $name,
				'password' => $password,
				'level' => (string) $level,
			);
		}

		// Re-assign sequential IDs
		foreach ($out as $k => $row)
		{
			$out[$k]['id'] = 'Account'.($k + 1);
		}

		return $out;
	}

	/**
	 * Keep passwords when POST left field empty: map existing by account name.
	 *
	 * @param array<int,array{id:string,name:string,password:string,level:string}> $parsed
	 * @param array<int,array{id:string,name:string,password:string,level:string}> $existing
	 * @return array<int,array{id:string,name:string,password:string,level:string}>
	 */
	public static function mergePasswordsFromExisting(array $parsed, array $existing)
	{
		$byName = array();
		foreach ($existing as $row)
		{
			$byName[$row['name']] = $row['password'];
		}
		foreach ($parsed as $k => $row)
		{
			if ($row['password'] === '' && isset($byName[$row['name']]))
			{
				$parsed[$k]['password'] = $byName[$row['name']];
			}
		}

		return $parsed;
	}

	/**
	 * @param array<int,array{id:string,name:string,password:string,level:string}> $accounts
	 * @return array{ok:bool,errors:string[]}
	 */
	public static function validate(array $accounts)
	{
		$errors = array();
		foreach ($accounts as $row)
		{
			$name = $row['name'];
			if ($name === '')
			{
				continue;
			}
			if (strlen($name) > self::YEALINK_LOGIN_MAX)
			{
				$errors[] = sprintf(_('Web UI username "%s" is too long (max %d).'), $name, self::YEALINK_LOGIN_MAX);
			}
			if ($row['password'] === '')
			{
				$errors[] = sprintf(_('Web UI password is required for user "%s".'), $name);
			}
			elseif (strlen($row['password']) > self::YEALINK_LOGIN_MAX)
			{
				$errors[] = sprintf(_('Web UI password for "%s" is too long (max %d for Yealink provisioning).'), $name, self::YEALINK_LOGIN_MAX);
			}
		}

		return array('ok' => count($errors) < 1, 'errors' => $errors);
	}

	/**
	 * @param array<int,array{id:string,name:string,password:string,level:string}> $accounts
	 * @return string
	 */
	public static function toJson(array $accounts)
	{
		return json_encode(array_values($accounts), JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Fanvil VOIP cfg lines: &lt;MMI CONFIG MODULE&gt; with --MMI Account-- (OEM colon format).
	 *
	 * @see data/0c383e822324_default.cfg
	 * @param array<string,string> $settings network settings
	 * @return array<int,string>
	 */
	public static function fanvilConfigLines(array $settings)
	{
		$accounts = self::fromSettings($settings);
		if (count($accounts) < 1)
		{
			return array();
		}

		$lines = array(
			'<MMI CONFIG MODULE>',
			self::fanvilColonLine('Web Server Type', '0'),
			self::fanvilColonLine('Web Port', '80'),
			self::fanvilColonLine('Https Web Port', '443'),
			self::fanvilColonLine('TLS Version', '2'),
			self::fanvilColonLine('Remote Control', '1'),
			self::fanvilColonLine('Enable MMI Filter', '0'),
			self::fanvilColonLine('Web Authentication', '0'),
			self::fanvilColonLine('Telnet Port', '23'),
			self::fanvilColonLine('Telnet Prompt', ''),
			self::fanvilColonLine('Logon Timeout', '15'),
			'--MMI Account--    :',
		);
		$idx = 0;
		foreach ($accounts as $acc)
		{
			$idx++;
			$lines[] = self::fanvilAccountLine($idx, 'Name', $acc['name']);
			$lines[] = self::fanvilAccountLine($idx, 'Password', $acc['password']);
			$lines[] = self::fanvilAccountLine($idx, 'Level', $acc['level']);
		}
		$lines[] = '';

		return $lines;
	}

	/**
	 * @param string $label
	 * @param string $value
	 * @return string
	 */
	public static function fanvilColonLine($label, $value)
	{
		return str_pad((string) $label, self::FANVIL_COLON_PAD, ' ', STR_PAD_RIGHT).':'.(string) $value;
	}

	/**
	 * @param int    $accountNum 1..8
	 * @param string $field      Name|Password|Level
	 * @param string $value
	 * @return string
	 */
	public static function fanvilAccountLine($accountNum, $field, $value)
	{
		$left = 'Account'.(int) $accountNum.' '.$field;

		return str_pad($left, self::FANVIL_COLON_PAD, ' ', STR_PAD_RIGHT).':'.(string) $value;
	}

	/**
	 * Yealink role for UI privilege (Administrators → admin, Users → user).
	 *
	 * @param string|int $level
	 * @return string admin|user
	 */
	public static function yealinkRoleForLevel($level)
	{
		return ((int) $level === 10) ? 'admin' : 'user';
	}

	/**
	 * Web UI administrator login from network MMI table (Networks edit), with General fallback.
	 *
	 * @param array<string,mixed>  $network zts_get_networks_edit row or empty
	 * @param array<string,string> $general zts_settings
	 * @return array{username:string,password:string}
	 */
	/**
	 * Networks MMI administrator only (no General Settings fallback).
	 *
	 * @param array<string,mixed> $network
	 * @return array{username:string,password:string}
	 */
	public static function networkWebAdminCredentialsOnly(array $network)
	{
		$settings = array();
		if (!empty($network['settings']) && is_array($network['settings']))
		{
			$settings = $network['settings'];
		}
		$accounts = self::fromSettings($settings);
		if (count($accounts) < 1)
		{
			return array('username' => '', 'password' => '');
		}
		foreach ($accounts as $acc)
		{
			if ((int) $acc['level'] === 10 && $acc['name'] !== '' && $acc['password'] !== '')
			{
				return array(
					'username' => $acc['name'],
					'password' => $acc['password'],
				);
			}
		}
		foreach ($accounts as $acc)
		{
			if ($acc['name'] !== '' && $acc['password'] !== '')
			{
				return array(
					'username' => $acc['name'],
					'password' => $acc['password'],
				);
			}
		}

		return array('username' => '', 'password' => '');
	}

	public static function webAdminCredentials(array $network, array $general)
	{
		$settings = array();
		if (!empty($network['settings']) && is_array($network['settings']))
		{
			$settings = $network['settings'];
		}
		$accounts = self::fromSettings($settings);
		if (count($accounts) < 1)
		{
			return Zts_GeneralPhoneSecurityService::adminWebCredentials($general, '');
		}
		foreach ($accounts as $acc)
		{
			if ((int) $acc['level'] === 10 && $acc['name'] !== '' && $acc['password'] !== '')
			{
				return array(
					'username' => $acc['name'],
					'password' => $acc['password'],
				);
			}
		}
		foreach ($accounts as $acc)
		{
			if ($acc['name'] !== '' && $acc['password'] !== '')
			{
				return array(
					'username' => $acc['name'],
					'password' => $acc['password'],
				);
			}
		}

		return Zts_GeneralPhoneSecurityService::adminWebCredentials($general, '');
	}

	/**
	 * Yealink auto-provision security lines (security.user_name.* / security.user_password).
	 * See Yealink Administrator's Guide: password format is login:password; rename via security.user_name.{role}.
	 *
	 * @param array<string,string>     $settings
	 * @param array<string,string>|null $globalFallback device_user_password, device_admin_password when no network rows
	 * @return array<int,string>
	 */
	public static function yealinkConfigLines(array $settings, $globalFallback = null)
	{
		$accounts = self::fromSettings($settings);
		if (count($accounts) < 1)
		{
			if (!is_array($globalFallback))
			{
				return array();
			}
			$userPass = isset($globalFallback['device_user_password']) ? (string) $globalFallback['device_user_password'] : '';
			$adminPass = isset($globalFallback['device_admin_password']) ? (string) $globalFallback['device_admin_password'] : '';
			$lines = array();
			if ($userPass !== '')
			{
				$lines[] = 'security.user_password = user:'.$userPass;
			}
			if ($adminPass !== '')
			{
				$lines[] = 'security.user_password = admin:'.$adminPass;
			}

			return $lines;
		}

		$byRole = array();
		foreach ($accounts as $acc)
		{
			$role = self::yealinkRoleForLevel($acc['level']);
			$byRole[$role] = $acc;
		}

		$lines = array();
		foreach (array('user', 'admin') as $role)
		{
			if (!isset($byRole[$role]))
			{
				continue;
			}
			$login = trim((string) $byRole[$role]['name']);
			$pass = (string) $byRole[$role]['password'];
			if ($login === '' || $pass === '')
			{
				continue;
			}
			if ($login !== $role)
			{
				$lines[] = 'security.user_name.'.$role.' = '.$login;
			}
			$lines[] = 'security.user_password = '.$login.':'.$pass;
		}

		return $lines;
	}
}
