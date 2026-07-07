<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_PhoneEditController
{
	/**
	 * @return array{device:array}
	 */
	public static function handle()
	{
		$editId = isset($_GET['edit']) ? Zts_InputValidator::trimString($_GET['edit']) : '';

		if (isset($_POST['action']) && $_POST['action'] === 'edit')
		{
			$previousPbxDeviceIds = array();
			if ($editId !== '')
			{
				$previousPbxDeviceIds = Zts_DeviceEditService::pbxDeviceIdsFromDevice(
					Zts_DeviceRepository::findForEdit($editId)
				);
			}

			$result = Zts_DeviceEditService::saveFromPost($editId, $_POST);
			if (!$result['ok'])
			{
				if (session_status() === PHP_SESSION_NONE)
				{
					@session_start();
				}
				$_SESSION['Zts_device_edit_errors'] = $result['errors'];
				redirect(Zts_ModuleIdentifiers::adminPageUrl('phones_edit', array('edit' => $editId)));
			}
			$savedId = $result['id'];
			$newPbxDeviceIds = Zts_DeviceEditService::pbxDeviceIdsFromDevice($result['device']);
			Zts_NotifyCheckConfigService::runAfterPhoneSave($savedId, $previousPbxDeviceIds, $newPbxDeviceIds);
			redirect(Zts_ModuleIdentifiers::adminPageUrl('phones_list'));
		}

		$device = Zts_DeviceEditService::loadForView($editId);

		$general = Zts_GeneralSettingsService::load();

		$wifiNetwork = Zts_DeviceWifiSettingsService::resolveNetworkForDevice($device);
		$wifiProfiles = Zts_NetworkWifiProfileService::fromSettings(
			isset($wifiNetwork['settings']) && is_array($wifiNetwork['settings']) ? $wifiNetwork['settings'] : array()
		);

		return array(
			'device' => $device,
			'edit_id' => $editId,
			'zts_linekey_templates' => Zts_LinekeyTemplateService::fromGeneral($general),
			'wifi_network' => $wifiNetwork,
			'wifi_profile_options' => Zts_DeviceWifiSettingsService::profileSelectOptions($wifiProfiles),
		);
	}
}
