<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Central input trim, type checks, and whitelist helpers.
 */
class Zts_InputValidator
{
	/**
	 * @param mixed $value
	 * @return string
	 */
	public static function trimString($value)
	{
		return trim((string) $value);
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function isNonEmptyString($value)
	{
		return self::trimString($value) !== '';
	}

	/**
	 * @param mixed $value
	 * @return int positive int or 0
	 */
	public static function positiveInt($value)
	{
		return max(0, (int) $value);
	}

	/**
	 * @param mixed $raw
	 * @return int[]
	 */
	public static function positiveIntList($raw)
	{
		if (!is_array($raw))
		{
			return array();
		}
		$out = array();
		foreach ($raw as $v)
		{
			$n = self::positiveInt($v);
			if ($n > 0)
			{
				$out[] = $n;
			}
		}

		return array_values(array_unique($out));
	}

	/**
	 * @param mixed  $value
	 * @param string $default
	 * @param array  $allowed whitelist map keys or list of allowed values
	 * @return string
	 */
	public static function whitelist($value, array $allowed, $default)
	{
		$key = self::trimString($value);
		if ($key === '')
		{
			return $default;
		}
		if (isset($allowed[$key]))
		{
			return $key;
		}
		if (in_array($key, $allowed, true))
		{
			return $key;
		}

		return $default;
	}

	/**
	 * @param mixed $value
	 * @return string asc|desc
	 */
	public static function sortOrder($value)
	{
		return strtolower(self::trimString($value)) === 'desc' ? 'desc' : 'asc';
	}

	/**
	 * @param mixed $mac
	 * @return string uppercase 12-hex or ''
	 */
	public static function normalizeMac($mac)
	{
		$m = strtoupper(preg_replace('/[^0-9A-F]/', '', (string) $mac));
		if (strlen($m) !== 12)
		{
			return '';
		}

		return $m;
	}
}
