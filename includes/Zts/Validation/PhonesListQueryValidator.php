<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Phone list sort/order query validation (delegates column map to legacy helpers).
 */
class Zts_PhonesListQueryValidator
{
	/**
	 * @param mixed $sort
	 * @param mixed $order
	 * @return array{0:string,1:string} sort column key, asc|desc
	 */
	public static function normalize($sort, $order)
	{
		if (!function_exists('zts_normalize_phones_list_sort'))
		{
			$sort = Zts_InputValidator::trimString($sort);
			$order = Zts_InputValidator::sortOrder($order);
			if ($sort === '')
			{
				$sort = 'mac';
			}

			return array($sort, $order);
		}

		return zts_normalize_phones_list_sort($sort, $order);
	}
}
