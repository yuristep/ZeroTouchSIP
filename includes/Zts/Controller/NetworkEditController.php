<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_NetworkEditController
{
	/**
	 * @return array{network:array}
	 */
	public static function handle()
	{
		$editId = isset($_GET['edit']) ? Zts_InputValidator::trimString($_GET['edit']) : '';

		if (isset($_POST['action']) && $_POST['action'] === 'edit')
		{
			$result = Zts_NetworkEditService::saveFromPost($editId, $_POST);
			if (!$result['ok'])
			{
				$_SESSION['Zts_network_edit_errors'] = $result['errors'];
				redirect(Zts_ModuleIdentifiers::adminPageUrl('networks_edit', array('edit' => $editId)));
			}
			redirect(Zts_ModuleIdentifiers::adminPageUrl('networks_list'));
		}

		$network = Zts_NetworkRepository::findForEdit($editId);
		$mmi = Zts_NetworkMmiAccountService::fromSettings($network['settings']);
		if (count($mmi) < 1)
		{
			$mmi = Zts_NetworkMmiAccountService::defaultRowsForForm();
		}
		$wifiProfiles = Zts_NetworkWifiProfileService::fromSettings($network['settings']);
		if (count($wifiProfiles) < 1)
		{
			$wifiProfiles = Zts_NetworkWifiProfileService::defaultRowsForForm();
		}

		$errors = array();
		if (isset($_SESSION['Zts_network_edit_errors']) && is_array($_SESSION['Zts_network_edit_errors']))
		{
			$errors = $_SESSION['Zts_network_edit_errors'];
			unset($_SESSION['Zts_network_edit_errors']);
		}

		return array(
			'network' => $network,
			'mmi_accounts' => $mmi,
			'wifi_profiles' => $wifiProfiles,
			'network_edit_errors' => $errors,
			'codec_rows' => Zts_NetworkCodecMapper::editRows($network['settings']),
		);
	}
}
