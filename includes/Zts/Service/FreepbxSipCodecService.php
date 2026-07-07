<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Reads enabled audio codecs from FreePBX SIP Settings (voicecodecs checkboxes).
 */
class Zts_FreepbxSipCodecService
{
	/**
	 * Active codecs only (checked on Settings → SIP Settings → Codecs), PBX order.
	 *
	 * @return array<int,string> ordered Asterisk keywords (ulaw, alaw, gsm, …)
	 */
	public static function orderedEnabledKeywords()
	{
		$raw = self::voiceCodecsConfig();
		if (!is_array($raw) || count($raw) < 1)
		{
			return array('opus', 'g722', 'ulaw', 'alaw');
		}

		$ordered = array();
		foreach ($raw as $keyword => $order)
		{
			if (!self::isActiveEntry($keyword, $order))
			{
				continue;
			}
			$ordered[(int) $order] = strtolower((string) $keyword);
		}
		ksort($ordered, SORT_NUMERIC);

		return array_values($ordered);
	}

	/**
	 * Matches sipsettings checkbox: present in voicecodecs[] POST / stored JSON with numeric order.
	 *
	 * @param string|int $keyword
	 * @param mixed      $order
	 * @return bool
	 */
	public static function isActiveEntry($keyword, $order)
	{
		$key = strtolower(trim((string) $keyword));
		if ($key === '' || $key === 'none')
		{
			return false;
		}

		return $order !== '' && $order !== null && is_numeric($order);
	}

	/**
	 * @return array<string,int|string>|null keyword => PBX priority order
	 */
	public static function voiceCodecsConfig()
	{
		$fromModule = self::voiceCodecsFromSipsettingsModule();
		if (is_array($fromModule) && count($fromModule) > 0)
		{
			return $fromModule;
		}

		return self::voiceCodecsFromKvstore();
	}

	/**
	 * @return array<string,int|string>|null
	 */
	private static function voiceCodecsFromSipsettingsModule()
	{
		if (!class_exists('FreePBX', false))
		{
			return null;
		}
		try
		{
			$fbx = \FreePBX::create();
			if (!isset($fbx->Sipsettings) || !is_object($fbx->Sipsettings))
			{
				return null;
			}
			$vc = $fbx->Sipsettings->getConfig('voicecodecs');
			if (!is_array($vc))
			{
				return null;
			}

			return self::normalizeVoiceCodecsArray($vc);
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	/**
	 * Fallback when FreePBX module object is unavailable in admin view bootstrap.
	 *
	 * @return array<string,int|string>|null
	 */
	private static function voiceCodecsFromKvstore()
	{
		if (!function_exists('sql'))
		{
			return null;
		}
		$row = sql(
			"SELECT val FROM kvstore_Sipsettings WHERE `key`='voicecodecs' LIMIT 1",
			'getRow',
			DB_FETCHMODE_ASSOC
		);
		if (!is_array($row) || !isset($row['val']) || (string) $row['val'] === '')
		{
			return null;
		}
		$decoded = json_decode((string) $row['val'], true);
		if (!is_array($decoded))
		{
			return null;
		}

		return self::normalizeVoiceCodecsArray($decoded);
	}

	/**
	 * @param array $vc
	 * @return array<string,int|string>
	 */
	private static function normalizeVoiceCodecsArray(array $vc)
	{
		$out = array();
		foreach ($vc as $keyword => $order)
		{
			if (!self::isActiveEntry($keyword, $order))
			{
				continue;
			}
			$out[strtolower((string) $keyword)] = $order;
		}

		return $out;
	}
}
