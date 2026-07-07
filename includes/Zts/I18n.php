<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * gettext domain for module zerotouchsip (FreePBX 17 standard layout).
 */
class Zts_I18n
{
	const DOMAIN = 'zerotouchsip';

	/** @var bool */
	private static $initialized = false;

	/** @var array<string,string>|null */
	private static $jsCache = null;

	/**
	 * Bind textdomain once per request. Locale is set by FreePBX core (AMPUSERLANG / system).
	 *
	 * @return void
	 */
	public static function init()
	{
		if (self::$initialized)
		{
			return;
		}
		$localeDir = dirname(dirname(__DIR__)) . '/i18n';
		if (!is_dir($localeDir))
		{
			return;
		}
		bindtextdomain(self::DOMAIN, $localeDir);
		bind_textdomain_codeset(self::DOMAIN, 'UTF-8');
		textdomain(self::DOMAIN);
		self::$initialized = true;
	}

	/**
	 * @return string
	 */
	public static function localeDir()
	{
		return dirname(dirname(__DIR__)) . '/i18n';
	}

	/**
	 * Translate a string (alias for consistency in services).
	 *
	 * @param string $msgid
	 * @return string
	 */
	public static function translate($msgid)
	{
		self::init();

		return _($msgid);
	}

	/**
	 * Keys for JS bridge (confirm dialogs, dynamic UI). Pass only strings needed in browser.
	 *
	 * @return array<string,string>
	 */
	public static function jsDictionary()
	{
		if (self::$jsCache !== null)
		{
			return self::$jsCache;
		}
		self::init();
		self::$jsCache = array(
			'confirmDeletePhones' => _('Are you sure you want to delete the selected phone(s)?'),
			'confirmApplyLinekeyTemplate' => _('Apply Line Key template "%template%" to %count% selected phone(s)? Existing Line Keys on those phones will be replaced.'),
			'confirmDeleteNetworks' => _('Are you sure you want to delete the selected network(s)?'),
			'showPassword' => _('Show password'),
			'clearSearch' => _('Clear search'),
			'paginationInfo' => _('Records %from% to %to% of %total%'),
			'rowsPerPage' => _('records per page'),
			'paginationPrev' => _('previous page'),
			'paginationNext' => _('next page'),
			'paginationGoto' => _('go to page'),
		);

		return self::$jsCache;
	}

	/**
	 * Emit &lt;script&gt; window.ZTS_I18N = {...} for admin pages.
	 *
	 * @return void
	 */
	public static function emitJsDictionary()
	{
		$dict = self::jsDictionary();
		echo '<script>window.ZTS_I18N=';
		echo json_encode($dict, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		echo ';</script>' . "\n";
	}

	/**
	 * @param string $key
	 * @param string $fallback
	 * @return string
	 */
	public static function js($key, $fallback = '')
	{
		$dict = self::jsDictionary();

		return isset($dict[$key]) ? $dict[$key] : $fallback;
	}

	/**
	 * Compile .po catalogs to .mo (requires gettext msgfmt on the server).
	 *
	 * @return bool true if all present locales compiled
	 */
	public static function compileCatalogs()
	{
		$msgfmt = trim((string) @shell_exec('command -v msgfmt 2>/dev/null'));
		if ($msgfmt === '')
		{
			return false;
		}
		$base = self::localeDir();
		$ok = true;
		foreach (array('en_US', 'ru_RU', 'zh_CN') as $loc)
		{
			$po = $base . '/' . $loc . '/LC_MESSAGES/zerotouchsip.po';
			$mo = $base . '/' . $loc . '/LC_MESSAGES/zerotouchsip.mo';
			if (!is_file($po))
			{
				continue;
			}
			$dir = dirname($mo);
			if (!is_dir($dir))
			{
				@mkdir($dir, 0755, true);
			}
			$cmd = escapeshellarg($msgfmt) . ' -o ' . escapeshellarg($mo) . ' ' . escapeshellarg($po);
			exec($cmd . ' 2>&1', $out, $code);
			if ($code !== 0)
			{
				$ok = false;
			}
		}

		return $ok;
	}
}
