<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * System identifiers (rawname, URL params). DB tables use zts_* prefix (see Zts_DatabaseSchema).
 */
class Zts_ModuleIdentifiers
{
	const RAWNAME = 'zerotouchsip';
	const FORM_PARAM = 'zerotouchsip_form';

	const DISPLAY_QUERY = 'display';

	public static function adminPageUrl($form, array $extra = array())
	{
		$q = array_merge(array(
			'type' => 'setup',
			self::DISPLAY_QUERY => self::RAWNAME,
			self::FORM_PARAM => $form,
		), $extra);

		return 'config.php?'.http_build_query($q);
	}

	/**
	 * Resolve form id from GET (zerotouchsip_form only).
	 *
	 * @return string
	 */
	public static function resolveFormFromRequest()
	{
		if (isset($_GET[self::FORM_PARAM]))
		{
			return trim((string) $_GET[self::FORM_PARAM]);
		}

		return '';
	}
}
