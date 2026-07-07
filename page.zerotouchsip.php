<?php
/* $Id */
/*
 * ZeroTouchSIP module for FreePBX
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

require_once dirname(__FILE__) . '/includes/bootstrap.php';

$ztsForm = Zts_ModuleIdentifiers::resolveFormFromRequest();

Zts_I18n::emitJsDictionary();

if ($ztsForm === '')
{
	redirect(Zts_ModuleIdentifiers::adminPageUrl('phones_list'));
}

$zts_tab_phones_active = ($ztsForm === 'phones_list' || $ztsForm === 'phones_edit');
$zts_tab_networks_active = ($ztsForm === 'networks_list' || $ztsForm === 'networks_edit');
$zts_tab_general_active = ($ztsForm === 'general_edit');

$zts_tab_pane_id = 'phones_list';
if ($zts_tab_networks_active)
{
	$zts_tab_pane_id = 'networks_list';
}
elseif ($zts_tab_general_active)
{
	$zts_tab_pane_id = 'general_edit';
}

echo '<style>
.zts-module-header { margin-bottom: 0; }
.zts-module-header .well.well-sm { margin-bottom: 12px; }
.zts-module-tabbar { margin-bottom: 0; }
.zts-module-tabcontent { padding-top: 0; }
</style>
<div class="fpbx-container container-fluid">
<div class="row">
<div class="col-sm-12">
<div class="zts-module-header">
<h1>'.htmlspecialchars(Zts_ModuleBranding::displayName(), ENT_QUOTES, 'UTF-8').'</h1>';
require dirname(__FILE__) . '/views/zts_module_intro.php';
echo '</div>
<div class="zts-module-tabbar clearfix">';
include dirname(__FILE__) . '/views/zts_nav_tabs.php';
echo '</div>
<div class="tab-content display zts-module-tabcontent">
<div role="tabpanel" class="tab-pane active" id="'.htmlspecialchars($zts_tab_pane_id, ENT_QUOTES, 'UTF-8').'">';

switch ($ztsForm)
{
	case 'phones_list':
		extract(Zts_PhonesListController::handle(), EXTR_SKIP);
		require 'modules/zerotouchsip/views/zts_phones.php';
		break;

	case 'phones_edit':
		extract(Zts_PhoneEditController::handle(), EXTR_SKIP);
		require 'modules/zerotouchsip/views/zts_phones_edit.php';
		break;

	case 'networks_list':
		extract(Zts_NetworksListController::handle(), EXTR_SKIP);
		require 'modules/zerotouchsip/views/zts_networks.php';
		break;

	case 'networks_edit':
		extract(Zts_NetworkEditController::handle(), EXTR_SKIP);
		require 'modules/zerotouchsip/views/zts_networks_edit.php';
		break;

	case 'general_edit':
		extract(Zts_GeneralSettingsController::handle(), EXTR_SKIP);
		require 'modules/zerotouchsip/views/zts_general.php';
		break;

	default:
		break;
}

echo '</div>
</div>
</div>
</div>
</div>
</div>';

?>
