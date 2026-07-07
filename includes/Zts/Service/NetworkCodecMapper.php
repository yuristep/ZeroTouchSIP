<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Network codec UI rows, save, and vendor provisioning lines.
 */
class Zts_NetworkCodecMapper
{
	/** Yealink account.codec.*.priority valid range. */
	const YEALINK_PRIORITY_MAX = 16;

	/**
	 * Rows for networks_edit (only codecs enabled in FreePBX SIP Settings).
	 *
	 * @param array<string,string> $settings
	 * @return array<int,array{id:string,label:string,priority_key:string,priority:int,pbx_order:int}>
	 */
	public static function editRows(array $settings)
	{
		$rows = array();
		$pbxOrder = 0;
		foreach (Zts_FreepbxSipCodecService::orderedEnabledKeywords() as $fbKeyword)
		{
			$resolved = Zts_NetworkCodecRegistry::resolveFromFreepbxKeyword($fbKeyword);
			$id = $resolved['id'];
			$pbxOrder++;
			$pKey = Zts_NetworkCodecRegistry::prioritySettingKey($id);
			$prio = isset($settings[$pKey]) ? (int) $settings[$pKey] : 0;
			if ($prio < 1)
			{
				$prio = $pbxOrder;
			}
			$rows[] = array(
				'id' => $id,
				'label' => $resolved['label'],
				'priority_key' => $pKey,
				'priority' => $prio,
				'pbx_order' => $pbxOrder,
				'freepbx_keyword' => $fbKeyword,
			);
		}

		if (count($rows) < 1)
		{
			foreach (Zts_NetworkCodecRegistry::defaultEnabledCodecIds() as $i => $fallbackId)
			{
				$pKey = Zts_NetworkCodecRegistry::prioritySettingKey($fallbackId);
				$prio = isset($settings[$pKey]) ? (int) $settings[$pKey] : ($i + 1);
				$rows[] = array(
					'id' => $fallbackId,
					'label' => Zts_NetworkCodecRegistry::label($fallbackId),
					'priority_key' => $pKey,
					'priority' => $prio > 0 ? $prio : ($i + 1),
					'pbx_order' => $i + 1,
				);
			}
		}

		return $rows;
	}

	/**
	 * @param array<string,string> $settings
	 * @param array              $post
	 * @return array<string,string>
	 */
	public static function applyFromPost(array $settings, array $post)
	{
		$rows = self::editRows($settings);
		foreach ($rows as $row)
		{
			$id = $row['id'];
			$pKey = $row['priority_key'];
			$eKey = Zts_NetworkCodecRegistry::enableSettingKey($id);
			$prio = isset($post[$pKey]) ? (int) $post[$pKey] : 0;
			if ($prio < 0)
			{
				$prio = 0;
			}
			if ($prio > self::YEALINK_PRIORITY_MAX)
			{
				$prio = self::YEALINK_PRIORITY_MAX;
			}
			$settings[$pKey] = (string) $prio;
			$settings[$eKey] = ($prio > 0) ? '1' : '0';
		}

		return $settings;
	}

	/**
	 * Enabled codecs sorted by phone priority (ascending).
	 *
	 * @param array<string,string> $settings
	 * @return array<int,array{id:string,priority:int}>
	 */
	public static function enabledForProvisioning(array $settings)
	{
		$list = array();
		foreach (self::editRows($settings) as $row)
		{
			$prio = isset($settings[$row['priority_key']]) ? (int) $settings[$row['priority_key']] : 0;
			if ($prio < 1)
			{
				continue;
			}
			$list[] = array('id' => $row['id'], 'priority' => $prio);
		}
		usort($list, function ($a, $b) {
			if ($a['priority'] === $b['priority'])
			{
				return 0;
			}

			return ($a['priority'] < $b['priority']) ? -1 : 1;
		});

		return $list;
	}

	/**
	 * @param int                  $lineId
	 * @param array<string,string> $settings
	 * @return string[]
	 */
	public static function yealinkConfigLines($lineId, array $settings)
	{
		$lines = array();
		$lineId = (int) $lineId;
		foreach (self::enabledForProvisioning($settings) as $entry)
		{
			$payload = Zts_NetworkCodecRegistry::yealinkPayload($entry['id']);
			if ($payload === '')
			{
				continue;
			}
			$lines[] = 'account.'.$lineId.'.codec.'.$payload.'.enable = 1';
			$lines[] = 'account.'.$lineId.'.codec.'.$payload.'.priority = '.$entry['priority'];
		}

		return $lines;
	}

	/**
	 * Fanvil SIP line VoiceCodecMap (comma-separated).
	 *
	 * @param array<string,string> $settings
	 * @return string
	 */
	public static function fanvilVoiceCodecMap(array $settings)
	{
		$tokens = array();
		foreach (self::enabledForProvisioning($settings) as $entry)
		{
			$token = Zts_NetworkCodecRegistry::fanvilToken($entry['id']);
			if ($token !== '' && !in_array($token, $tokens, true))
			{
				$tokens[] = $token;
			}
		}
		if (count($tokens) < 1)
		{
			return 'opus,G722,G711U,G711A';
		}

		return implode(',', $tokens);
	}
}
