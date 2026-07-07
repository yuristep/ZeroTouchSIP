<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_NetworksListController
{
	/**
	 * @return array{networks:array,networks_list_sort:string,networks_list_order:string}
	 */
	public static function handle()
	{
		list($networks_list_sort, $networks_list_order) = Zts_NetworksListQueryValidator::normalize(
			isset($_GET['sort']) ? $_GET['sort'] : 'cidr',
			isset($_GET['order']) ? $_GET['order'] : 'asc'
		);
		$redir = Zts_ModuleIdentifiers::adminPageUrl('networks_list', array(
			'sort' => $networks_list_sort,
			'order' => $networks_list_order,
		));

		if ($_SERVER['REQUEST_METHOD'] === 'POST'
			&& isset($_POST['zts_networks_bulk']))
		{
			$bulkRaw = $_POST['zts_networks_bulk'];
			$bulk = Zts_NetworkBulkActionValidator::parseAction($bulkRaw);
			$ids = Zts_NetworkBulkActionValidator::parseNetworkIds(
				isset($_POST['network_ids']) ? $_POST['network_ids'] : array()
			);
			if ($bulk === Zts_NetworkBulkActionValidator::ACTION_DELETE && count($ids) > 0)
			{
				foreach ($ids as $nid)
				{
					Zts_NetworkRepository::deleteById((string) $nid);
				}
				redirect($redir);
			}
		}

		return array(
			'networks' => Zts_NetworksListService::getList($networks_list_sort, $networks_list_order),
			'networks_list_sort' => $networks_list_sort,
			'networks_list_order' => $networks_list_order,
		);
	}
}
