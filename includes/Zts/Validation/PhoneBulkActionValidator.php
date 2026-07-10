<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Bulk actions on phone list (delete / notify).
 */
class Zts_PhoneBulkActionValidator
{
	const ACTION_DELETE = 'delete';
	const ACTION_NOTIFY = 'notify';
	const ACTION_NOTIFY_SOFT = 'notify_soft';
	const ACTION_AUTOPROVISION = 'autoprovision';
	const ACTION_APPLY_LINEKEY_TEMPLATE = 'apply_linekey_template';

	/**
	 * @param mixed $bulk
	 * @return string delete|notify|notify_soft|autoprovision|apply_linekey_template|''
	 */
	public static function parseAction($bulk)
	{
		return Zts_InputValidator::whitelist(
			$bulk,
			array(
				self::ACTION_DELETE => true,
				self::ACTION_NOTIFY => true,
				self::ACTION_NOTIFY_SOFT => true,
				self::ACTION_AUTOPROVISION => true,
				self::ACTION_APPLY_LINEKEY_TEMPLATE => true,
			),
			''
		);
	}

	/**
	 * @param mixed $rawIds
	 * @return int[]
	 */
	public static function parsePhoneIds($rawIds)
	{
		return Zts_InputValidator::positiveIntList($rawIds);
	}

	/**
	 * @param mixed $singleId GET notify/delete id
	 * @return int 0 if invalid
	 */
	public static function parseSinglePhoneId($singleId)
	{
		return Zts_InputValidator::positiveInt($singleId);
	}
}
