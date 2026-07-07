<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_NetworksListQueryValidator
{
	/**
	 * @param mixed $sort
	 * @param mixed $order
	 * @return array{0:string,1:string}
	 */
	public static function normalize($sort, $order)
	{
		$map = Zts_NetworkRepository::sortColumnMap();
		$sort = Zts_InputValidator::whitelist($sort, $map, 'cidr');
		$order = Zts_InputValidator::sortOrder($order);

		return array($sort, $order);
	}
}
