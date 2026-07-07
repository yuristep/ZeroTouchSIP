<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Bulk Line Key template application from Phones list.
 */
class Zts_PhoneLinekeyTemplateBulkService
{
	/**
	 * @param int[]  $phoneIds
	 * @param string $templateId
	 * @return string[] UI result lines
	 */
	public static function applyToPhones(array $phoneIds, $templateId)
	{
		$phoneIds = Zts_InputValidator::positiveIntList($phoneIds);
		$templateId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $templateId);
		if (count($phoneIds) < 1)
		{
			return array(_('No phones selected.'));
		}
		if ($templateId === '')
		{
			return array(_('No Line Key template selected.'));
		}

		$general = Zts_GeneralSettingsService::load();
		$templates = Zts_LinekeyTemplateService::fromGeneral($general);
		$templateName = '';
		$keys = null;
		foreach ($templates as $tpl)
		{
			if ((string) $tpl['id'] === $templateId)
			{
				$templateName = (string) $tpl['name'];
				$keys = $tpl['keys'];
				break;
			}
		}
		if ($keys === null)
		{
			return array(_('Selected Line Key template was not found.'));
		}

		$updated = 0;
		foreach ($phoneIds as $pid)
		{
			if (Zts_DeviceRepository::replaceLinekeys($pid, $keys))
			{
				$updated++;
			}
		}

		return array(sprintf(
			_('Applied Line Key template "%s" to %d phone(s). Save/Notify is not triggered automatically; use Notify if you want phones to fetch the new config now.'),
			$templateName,
			$updated
		));
	}
}
