<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_GeneralSettingsValidator
{
	/** @return string[] */
	public static function allowedKeywords()
	{
		return array_merge(
			Zts_GeneralSipPnpService::storageKeys(),
			array(
				'auto_provision_repeat_minutes',
				'device_user_password',
				'device_admin_password',
				'device_user_username',
				'device_admin_username',
				'default_provisioning_profile',
				'default_backlight_time',
				'default_lang',
				'security_trust_certificates',
				'provisioning_log_mode',
				'provisioning_log_file',
			),
			Zts_GeneralTimeSettingsMapper::storageKeys(),
			array(
				Zts_GeneralPhoneSecurityService::SETTING_JSON,
				Zts_LinekeyTemplateService::SETTING_JSON,
			)
		);
	}

	/**
	 * @param array $post raw POST
	 * @return array<string,string>
	 */
	public static function normalizeFromPost(array $post)
	{
		$out = array();
		foreach (self::allowedKeywords() as $field)
		{
			$out[$field] = isset($post[$field]) ? Zts_InputValidator::trimString($post[$field]) : '';
		}
		$out['provisioning_log_mode'] = Zts_InputValidator::whitelist(
			$out['provisioning_log_mode'],
			array('off' => true, 'apache' => true, 'file' => true),
			'off'
		);
		if ($out['provisioning_log_file'] !== '' && function_exists('zts_provisioning_log_file_safe_path'))
		{
			$safe = zts_provisioning_log_file_safe_path($out['provisioning_log_file']);
			if ($safe === '')
			{
				$out['provisioning_log_file'] = Zts_ProvisioningLogConfig::defaultFilePath();
			}
			else
			{
				$out['provisioning_log_file'] = $safe;
			}
		}
		$out = array_merge($out, Zts_GeneralSipPnpService::normalizeFromPost($post, $out));

		return $out;
	}
}
