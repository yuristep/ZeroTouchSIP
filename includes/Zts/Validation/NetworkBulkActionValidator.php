<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_NetworkBulkActionValidator
{
	const ACTION_DELETE = 'delete';

	/**
	 * @param mixed $bulk
	 * @return string
	 */
	public static function parseAction($bulk)
	{
		return Zts_InputValidator::whitelist($bulk, array(self::ACTION_DELETE => true), '');
	}

	/**
	 * @param mixed $rawIds
	 * @return int[]
	 */
	public static function parseNetworkIds($rawIds)
	{
		return Zts_InputValidator::positiveIntList($rawIds);
	}
}
