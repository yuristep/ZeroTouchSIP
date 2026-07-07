<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_NetworksListService
{
	/**
	 * @param mixed $sort
	 * @param mixed $order
	 * @return array<int,array>
	 */
	public static function getList($sort, $order)
	{
		list($sort, $order) = Zts_NetworksListQueryValidator::normalize($sort, $order);

		return Zts_NetworkRepository::fetchAllSorted($sort, $order);
	}
}
