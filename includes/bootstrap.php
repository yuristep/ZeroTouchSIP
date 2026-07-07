<?php
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH') && !defined('FREEPBX_BOOTSTRAP_SKIP_AUTH'))
{
	die('No direct script access allowed');
}

$ztsInc = dirname(__FILE__) . '/Zts';

require_once $ztsInc . '/I18n.php';
Zts_I18n::init();

require_once $ztsInc . '/DatabaseSchema.php';
require_once $ztsInc . '/ModuleIdentifiers.php';
require_once $ztsInc . '/ModuleBranding.php';
require_once $ztsInc . '/ProvisioningPaths.php';
require_once $ztsInc . '/ProvisioningLogConfig.php';
require_once $ztsInc . '/Validation/InputValidator.php';
require_once $ztsInc . '/Validation/PhonesListQueryValidator.php';
require_once $ztsInc . '/Validation/PhoneBulkActionValidator.php';
require_once $ztsInc . '/Validation/DeviceEditValidator.php';
require_once $ztsInc . '/Validation/NetworksListQueryValidator.php';
require_once $ztsInc . '/Validation/NetworkBulkActionValidator.php';
require_once $ztsInc . '/Validation/NetworkEditValidator.php';
require_once $ztsInc . '/Validation/GeneralSettingsValidator.php';
require_once $ztsInc . '/Service/FanvilConfigVersionService.php';
require_once $ztsInc . '/Service/FanvilBaselinePlaceholders.php';
require_once $ztsInc . '/Service/YealinkBaselinePlaceholders.php';
require_once $ztsInc . '/Service/FanvilDeviceConfigService.php';
require_once $ztsInc . '/Service/YealinkDeviceConfigService.php';
require_once $ztsInc . '/Repository/PhonesListRepository.php';
require_once $ztsInc . '/Repository/DeviceRepository.php';
require_once $ztsInc . '/Repository/NetworkRepository.php';
require_once $ztsInc . '/Repository/SettingsRepository.php';
require_once $ztsInc . '/Service/PhonesListService.php';
require_once $ztsInc . '/Service/VendorDisplayService.php';
require_once $ztsInc . '/Service/ProvisioningUrlService.php';
require_once $ztsInc . '/Service/NotifyVendorHeuristic.php';
require_once $ztsInc . '/Service/NotifyPjsipService.php';
require_once $ztsInc . '/Repository/NotifyInventoryRepository.php';
require_once $ztsInc . '/Service/FanvilHttpNotifyService.php';
require_once $ztsInc . '/Service/PhoneWebUiAccessService.php';
require_once $ztsInc . '/Service/NotifyCheckConfigService.php';
require_once $ztsInc . '/Service/NotifySessionService.php';
require_once $ztsInc . '/Service/DeviceNamingService.php';
require_once $ztsInc . '/Service/DeviceEditService.php';
require_once $ztsInc . '/Support/FanvilTimeZoneOptions.php';
require_once $ztsInc . '/Support/FanvilLanguageOptions.php';
require_once $ztsInc . '/Support/YealinkTimeZoneOptions.php';
require_once $ztsInc . '/Support/NetworkCodecRegistry.php';
require_once $ztsInc . '/Service/FreepbxSipCodecService.php';
require_once $ztsInc . '/Service/NetworkCodecMapper.php';
require_once $ztsInc . '/Service/NetworkTimeSettingsMapper.php';
require_once $ztsInc . '/Service/NetworkMmiAccountService.php';
require_once $ztsInc . '/Service/NetworkWifiProfileService.php';
require_once $ztsInc . '/Service/DeviceWifiSettingsService.php';
require_once $ztsInc . '/Service/NetworkEditService.php';
require_once $ztsInc . '/Service/NetworksListService.php';
require_once $ztsInc . '/Service/SipPnpSecureUrlService.php';
require_once $ztsInc . '/Service/GeneralSipPnpService.php';
require_once $ztsInc . '/Service/SipPnpListenService.php';
require_once $ztsInc . '/Service/GeneralPhoneSecurityService.php';
require_once $ztsInc . '/Service/GeneralTimeSettingsMapper.php';
require_once $ztsInc . '/Service/GeneralPhoneDefaultsService.php';
require_once $ztsInc . '/Service/LinekeyTemplateService.php';
require_once $ztsInc . '/Service/GeneralSettingsService.php';
require_once $ztsInc . '/Service/PhoneLinekeyTemplateBulkService.php';
require_once $ztsInc . '/Controller/PhonesListController.php';
require_once $ztsInc . '/Controller/PhoneEditController.php';
require_once $ztsInc . '/Controller/NetworksListController.php';
require_once $ztsInc . '/Controller/NetworkEditController.php';
require_once $ztsInc . '/Controller/GeneralSettingsController.php';
require_once $ztsInc . '/Vendor/VendorAdapterInterface.php';
require_once $ztsInc . '/Vendor/YealinkVendorAdapter.php';
require_once $ztsInc . '/Vendor/FanvilVendorAdapter.php';
require_once $ztsInc . '/Vendor/VendorRegistry.php';
