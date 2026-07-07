<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Phone list orchestration: validate query, load rows, enrich PJSIP status.
 */
class Zts_PhonesListService
{
	/**
	 * @param mixed $sort
	 * @param mixed $order
	 * @return array<int,array>
	 */
	public static function getList($sort = 'mac', $order = 'asc')
	{
		list($sort, $order) = Zts_PhonesListQueryValidator::normalize($sort, $order);
		$results = Zts_PhonesListRepository::fetchAllSorted($sort, $order);
		Zts_VendorDisplayService::enrichListRows($results);
		zts_phones_list_enrich_pjsip_status($results);
		if ($sort === 'pjsip')
		{
			zts_phones_list_sort_by_pjsip($results, $order);
		}
		Zts_PhoneWebUiAccessService::enrichListWebUiUrls($results);

		return $results;
	}
}
