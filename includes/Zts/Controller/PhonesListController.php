<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_PhonesListController
{
	/**
	 * @return array{devices:array,phones_list_sort:string,phones_list_order:string,phones_list_redir:string,zts_sort_q:string,zts_phones_list_url:string,zts_phones_edit_url:string}
	 */
	public static function handle()
	{
		list($phones_list_sort, $phones_list_order) = Zts_PhonesListQueryValidator::normalize(
			isset($_GET['sort']) ? $_GET['sort'] : 'mac',
			isset($_GET['order']) ? $_GET['order'] : 'asc'
		);
		$phones_list_redir = Zts_ModuleIdentifiers::adminPageUrl('phones_list', array(
			'sort' => $phones_list_sort,
			'order' => $phones_list_order,
		));
		$sort_q = '&sort='.rawurlencode((string) $phones_list_sort).'&order='.rawurlencode((string) $phones_list_order);

		if ($_SERVER['REQUEST_METHOD'] === 'POST'
			&& isset($_POST['zts_phones_bulk']))
		{
			$bulkRaw = $_POST['zts_phones_bulk'];
			$bulk = Zts_PhoneBulkActionValidator::parseAction($bulkRaw);
			$phone_ids = Zts_PhoneBulkActionValidator::parsePhoneIds(
				isset($_POST['phone_ids']) ? $_POST['phone_ids'] : array()
			);
			if ($bulk === Zts_PhoneBulkActionValidator::ACTION_DELETE && count($phone_ids) > 0)
			{
				foreach ($phone_ids as $pid)
				{
					Zts_DeviceRepository::deleteById((string) $pid);
				}
				redirect($phones_list_redir);
			}
			if ($bulk === Zts_PhoneBulkActionValidator::ACTION_NOTIFY && count($phone_ids) > 0)
			{
				Zts_NotifySessionService::storeResults(zts_notify_checkconfig($phone_ids));
				redirect($phones_list_redir);
			}
			if ($bulk === Zts_PhoneBulkActionValidator::ACTION_NOTIFY_SOFT && count($phone_ids) > 0)
			{
				Zts_NotifySessionService::storeResults(zts_notify_checkconfig($phone_ids, true));
				redirect($phones_list_redir);
			}
			if ($bulk === Zts_PhoneBulkActionValidator::ACTION_APPLY_LINEKEY_TEMPLATE && count($phone_ids) > 0)
			{
				$templateId = isset($_POST['linekey_template_id']) ? (string) $_POST['linekey_template_id'] : '';
				Zts_NotifySessionService::storeResults(
					Zts_PhoneLinekeyTemplateBulkService::applyToPhones($phone_ids, $templateId)
				);
				redirect($phones_list_redir);
			}
		}

		$devices = Zts_PhonesListService::getList($phones_list_sort, $phones_list_order);
		$linekey_templates = Zts_LinekeyTemplateService::fromGeneral(Zts_GeneralSettingsService::load());

		return array(
			'devices' => $devices,
			'zts_linekey_templates' => $linekey_templates,
			'phones_list_sort' => $phones_list_sort,
			'phones_list_order' => $phones_list_order,
			'phones_list_redir' => $phones_list_redir,
			'zts_sort_q' => $sort_q,
			'zts_phones_list_url' => '?type=setup&display='.Zts_ModuleIdentifiers::RAWNAME.'&'.Zts_ModuleIdentifiers::FORM_PARAM.'=phones_list',
			'zts_phones_edit_url' => '?type=setup&display='.Zts_ModuleIdentifiers::RAWNAME.'&'.Zts_ModuleIdentifiers::FORM_PARAM.'=phones_edit',
		);
	}
}
