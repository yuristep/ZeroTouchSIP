<?php
// SPDX-License-Identifier: GPL-3.0-or-later

namespace FreePBX\modules;

use BMO;
use FreePBX_Helpers;

/**
 * ZeroTouchSIP BMO entry point (FreePBX 17).
 *
 * Legacy procedural helpers remain in functions.inc.php; hooks live here.
 */
class Zerotouchsip extends FreePBX_Helpers implements BMO
{
	public function __construct($freepbx = null)
	{
		if ($freepbx === null)
		{
			throw new \RuntimeException('Not given a FreePBX Object');
		}
		$this->FreePBX = $freepbx;
		require_once __DIR__ . '/includes/bootstrap.php';
	}

	public function install()
	{
	}

	public function uninstall()
	{
	}

	/**
	 * Legacy backup hook (modern pipeline uses Backup.php / Restore.php).
	 */
	public function backup()
	{
	}

	/**
	 * Legacy restore hook (modern pipeline uses Backup.php / Restore.php).
	 */
	public function restore($backup)
	{
	}

	/**
	 * @param string $page FreePBX config page name (e.g. devices, zerotouchsip)
	 */
	public function doConfigPageInit($page)
	{
		if ($page === 'devices')
		{
			$this->initDevicesExtensionPage();
		}
	}

	/**
	 * Hide default FreePBX submit/reset; module forms provide their own actions.
	 *
	 * @param array $request
	 * @return array<string,array<string,string>>
	 */
	public function getActionBar($request)
	{
		if (!isset($request['display']) || $request['display'] !== Zts_ModuleIdentifiers::RAWNAME)
		{
			return array();
		}

		return array(
			'reset' => array(
				'name' => 'reset',
				'id' => 'Reset',
				'class' => 'hidden',
				'value' => _('Reset'),
			),
			'submit' => array(
				'name' => 'submit',
				'class' => 'hidden',
				'id' => 'Submit',
				'value' => _('Submit'),
			),
		);
	}

	/**
	 * Link assigned ZeroTouchSIP phones from Extensions (devices) edit screen.
	 */
	private function initDevicesExtensionPage()
	{
		global $currentcomponent;
		global $db;

		if (!isset($_REQUEST['extdisplay']) || $_REQUEST['extdisplay'] === '' || $_REQUEST['extdisplay'] === false)
		{
			return;
		}

		$extId = Zts_InputValidator::positiveInt($_REQUEST['extdisplay']);
		if ($extId < 1)
		{
			return;
		}

		$phones = sql(
			"SELECT zts_devices.id, zts_devices.name, zts_devices.mac FROM zts_devices
			INNER JOIN zts_device_lines ON zts_devices.id = zts_device_lines.id
			WHERE zts_device_lines.deviceid = '".$db->escapeSimple((string) $extId)."'",
			'getAll',
			DB_FETCHMODE_ASSOC
		);

		if (!is_array($phones))
		{
			return;
		}

		foreach ($phones as $phone)
		{
			$editURL = $_SERVER['PHP_SELF'].'?'.http_build_query(array(
				'display' => Zts_ModuleIdentifiers::RAWNAME,
				Zts_ModuleIdentifiers::FORM_PARAM => 'phones_edit',
				'edit' => $phone['id'],
			));
			$tlabel = sprintf(_('Edit in %s: %s (%s)'), Zts_ModuleBranding::displayName(), $phone['name'], $phone['mac']);
			$label = '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/telephone_edit.png"/>&nbsp;'.$tlabel.'</span>';
			$currentcomponent->addguielem('_top', new \gui_link('zts_edit_phone', $label, $editURL, true, false), 0);
		}
	}
}
