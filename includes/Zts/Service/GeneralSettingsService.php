<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_GeneralSettingsService
{
	/**
	 * @return array<string,string>
	 */
	public static function load()
	{
		return Zts_SettingsRepository::fetchAll();
	}

	/**
	 * @param array $post $_POST
	 * @return void
	 */
	public static function saveFromPost(array $post)
	{
		$settings = self::load();
		$base = Zts_GeneralSettingsValidator::normalizeFromPost($post);
		foreach ($base as $key => $val)
		{
			$settings[$key] = $val;
		}
		$settings = Zts_GeneralTimeSettingsMapper::applyFromPost($settings, $post);
		$settings = Zts_GeneralPhoneSecurityService::applyFromPost($settings, $post);
		$settings = Zts_LinekeyTemplateService::applyFromPost($settings, $post);
		Zts_SettingsRepository::saveAll($settings);
	}
}
