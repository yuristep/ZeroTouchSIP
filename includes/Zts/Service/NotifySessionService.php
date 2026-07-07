<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Stores notify UI feedback in session (legacy key for backward compatibility).
 */
class Zts_NotifySessionService
{
	/**
	 * @param string[]|array $lines
	 * @return void
	 */
	public static function storeResults($lines)
	{
		if (session_status() === PHP_SESSION_NONE)
		{
			@session_start();
		}
		$_SESSION[Zts_ModuleBranding::LEGACY_NOTIFY_SESSION_KEY] = is_array($lines) ? $lines : array();
	}

	/**
	 * @return array|null
	 */
	public static function pullResults()
	{
		if (!isset($_SESSION[Zts_ModuleBranding::LEGACY_NOTIFY_SESSION_KEY]))
		{
			return null;
		}
		$nr = $_SESSION[Zts_ModuleBranding::LEGACY_NOTIFY_SESSION_KEY];
		unset($_SESSION[Zts_ModuleBranding::LEGACY_NOTIFY_SESSION_KEY]);
		if (!is_array($nr))
		{
			return null;
		}

		return $nr;
	}
}
