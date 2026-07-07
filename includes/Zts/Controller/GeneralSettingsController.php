<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_GeneralSettingsController
{
	/**
	 * @return array{general:array<string,string>}
	 */
	public static function handle()
	{
		if (isset($_POST['action']) && $_POST['action'] === 'edit')
		{
			$tplPost = isset($_POST['linekey_tpl']) && is_array($_POST['linekey_tpl']) ? $_POST['linekey_tpl'] : array();
			$tplCheck = Zts_LinekeyTemplateService::validateTemplatesPost($tplPost);
			if (!$tplCheck['ok'])
			{
				if (session_status() === PHP_SESSION_NONE)
				{
					@session_start();
				}
				$_SESSION['Zts_general_edit_errors'] = $tplCheck['errors'];
				redirect(Zts_ModuleIdentifiers::adminPageUrl('general_edit'));
			}
			Zts_GeneralSettingsService::saveFromPost($_POST);
			redirect(Zts_ModuleIdentifiers::adminPageUrl('general_edit'));
		}

		return array('general' => Zts_GeneralSettingsService::load());
	}
}
