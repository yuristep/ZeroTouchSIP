<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Codec registry: FreePBX Asterisk keyword ↔ internal id ↔ Yealink / Fanvil provisioning.
 */
class Zts_NetworkCodecRegistry
{
	/**
	 * @return array<string,array{label:string,freepbx:array<int,string>,yealink:string,fanvil:string}>
	 */
	public static function definitions()
	{
		return array(
			'pcmu' => array(
				'label' => 'G.711 μ-law (PCMU)',
				'freepbx' => array('ulaw'),
				'yealink' => 'pcmu',
				'fanvil' => 'G711U',
			),
			'pcma' => array(
				'label' => 'G.711 A-law (PCMA)',
				'freepbx' => array('alaw'),
				'yealink' => 'pcma',
				'fanvil' => 'G711A',
			),
			'gsm' => array(
				'label' => 'GSM',
				'freepbx' => array('gsm'),
				'yealink' => 'gsm',
				'fanvil' => '',
			),
			'g726' => array(
				'label' => 'G.726',
				'freepbx' => array('g726', 'g726aal2'),
				'yealink' => 'g726_32',
				'fanvil' => 'G726-32',
			),
			'g722' => array(
				'label' => 'G.722',
				'freepbx' => array('g722'),
				'yealink' => 'g722',
				'fanvil' => 'G722',
			),
			'g729' => array(
				'label' => 'G.729',
				'freepbx' => array('g729'),
				'yealink' => 'g729',
				'fanvil' => 'G729',
			),
			'g723' => array(
				'label' => 'G.723.1',
				'freepbx' => array('g723'),
				'yealink' => 'g723_53',
				'fanvil' => 'G723',
			),
			'opus' => array(
				'label' => 'Opus',
				'freepbx' => array('opus'),
				'yealink' => 'opus',
				'fanvil' => 'opus',
			),
			'ilbc' => array(
				'label' => 'iLBC',
				'freepbx' => array('ilbc'),
				'yealink' => 'ilbc',
				'fanvil' => 'iLBC',
			),
			'speex' => array(
				'label' => 'Speex',
				'freepbx' => array('speex'),
				'yealink' => 'speex',
				'fanvil' => '',
			),
			'speex16' => array(
				'label' => 'Speex 16 kHz',
				'freepbx' => array('speex16'),
				'yealink' => 'speex16',
				'fanvil' => '',
			),
			'speex32' => array(
				'label' => 'Speex 32 kHz',
				'freepbx' => array('speex32'),
				'yealink' => 'speex32',
				'fanvil' => '',
			),
			'lpc10' => array(
				'label' => 'LPC10',
				'freepbx' => array('lpc10'),
				'yealink' => '',
				'fanvil' => '',
			),
			'adpcm' => array(
				'label' => 'ADPCM',
				'freepbx' => array('adpcm'),
				'yealink' => '',
				'fanvil' => '',
			),
			'siren7' => array(
				'label' => 'Siren7',
				'freepbx' => array('siren7'),
				'yealink' => '',
				'fanvil' => '',
			),
			'siren14' => array(
				'label' => 'Siren14',
				'freepbx' => array('siren14'),
				'yealink' => '',
				'fanvil' => '',
			),
			'g719' => array(
				'label' => 'G.719',
				'freepbx' => array('g719'),
				'yealink' => '',
				'fanvil' => '',
			),
			'codec2' => array(
				'label' => 'Codec2',
				'freepbx' => array('codec2'),
				'yealink' => '',
				'fanvil' => '',
			),
			'silk8' => array(
				'label' => 'SILK 8 kHz',
				'freepbx' => array('silk8'),
				'yealink' => '',
				'fanvil' => '',
			),
			'silk12' => array(
				'label' => 'SILK 12 kHz',
				'freepbx' => array('silk12'),
				'yealink' => '',
				'fanvil' => '',
			),
			'silk16' => array(
				'label' => 'SILK 16 kHz',
				'freepbx' => array('silk16'),
				'yealink' => '',
				'fanvil' => '',
			),
			'silk24' => array(
				'label' => 'SILK 24 kHz',
				'freepbx' => array('silk24'),
				'yealink' => '',
				'fanvil' => '',
			),
			'slin' => array(
				'label' => 'SLIN',
				'freepbx' => array('slin'),
				'yealink' => '',
				'fanvil' => '',
			),
			'slin12' => array(
				'label' => 'SLIN 12 kHz',
				'freepbx' => array('slin12'),
				'yealink' => '',
				'fanvil' => '',
			),
			'slin16' => array(
				'label' => 'SLIN 16 kHz',
				'freepbx' => array('slin16'),
				'yealink' => '',
				'fanvil' => '',
			),
			'slin24' => array(
				'label' => 'SLIN 24 kHz',
				'freepbx' => array('slin24'),
				'yealink' => '',
				'fanvil' => '',
			),
			'slin32' => array(
				'label' => 'SLIN 32 kHz',
				'freepbx' => array('slin32'),
				'yealink' => '',
				'fanvil' => '',
			),
			'slin44' => array(
				'label' => 'SLIN 44 kHz',
				'freepbx' => array('slin44'),
				'yealink' => '',
				'fanvil' => '',
			),
			'slin48' => array(
				'label' => 'SLIN 48 kHz',
				'freepbx' => array('slin48'),
				'yealink' => '',
				'fanvil' => '',
			),
			'slin96' => array(
				'label' => 'SLIN 96 kHz',
				'freepbx' => array('slin96'),
				'yealink' => '',
				'fanvil' => '',
			),
			'slin192' => array(
				'label' => 'SLIN 192 kHz',
				'freepbx' => array('slin192'),
				'yealink' => '',
				'fanvil' => '',
			),
		);
	}

	/**
	 * @param string $freepbxKeyword Asterisk / sipsettings id (ulaw, g726, …)
	 * @return array{id:string,label:string,yealink:string,fanvil:string}
	 */
	public static function resolveFromFreepbxKeyword($freepbxKeyword)
	{
		$key = strtolower(trim((string) $freepbxKeyword));
		foreach (self::definitions() as $id => $def)
		{
			foreach ($def['freepbx'] as $fb)
			{
				if (strtolower($fb) === $key)
				{
					return array(
						'id' => $id,
						'label' => $def['label'],
						'yealink' => $def['yealink'],
						'fanvil' => $def['fanvil'],
					);
				}
			}
		}

		return array(
			'id' => 'fb_'.$key,
			'label' => self::fallbackLabel($key),
			'yealink' => '',
			'fanvil' => '',
		);
	}

	/**
	 * @param string $freepbxKeyword
	 * @return string
	 */
	public static function fallbackLabel($freepbxKeyword)
	{
		$labels = array(
			'ulaw' => 'G.711 μ-law (ulaw)',
			'alaw' => 'G.711 A-law (alaw)',
			'gsm' => 'GSM',
			'g726' => 'G.726',
			'g726aal2' => 'G.726 AAL2',
			'g722' => 'G.722',
			'g729' => 'G.729',
			'g723' => 'G.723.1',
			'opus' => 'Opus',
			'ilbc' => 'iLBC',
		);
		$key = strtolower($freepbxKeyword);

		return isset($labels[$key]) ? $labels[$key] : strtoupper($key);
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function label($id)
	{
		$defs = self::definitions();

		return isset($defs[$id]) ? $defs[$id]['label'] : $id;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function yealinkPayload($id)
	{
		$defs = self::definitions();

		return isset($defs[$id]) ? $defs[$id]['yealink'] : '';
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function fanvilToken($id)
	{
		$defs = self::definitions();

		return isset($defs[$id]) ? $defs[$id]['fanvil'] : '';
	}

	/**
	 * Default enabled codecs for new networks (phone priority order).
	 *
	 * @return array<int,string>
	 */
	public static function defaultEnabledCodecIds()
	{
		return array('opus', 'g722', 'pcmu', 'pcma');
	}

	/**
	 * @return array<string,string>
	 */
	public static function defaultCodecSettings()
	{
		$out = array();
		foreach (self::defaultEnabledCodecIds() as $i => $id)
		{
			$prio = $i + 1;
			$out[self::enableSettingKey($id)] = '1';
			$out[self::prioritySettingKey($id)] = (string) $prio;
		}
		$out[self::enableSettingKey('g729')] = '0';
		$out[self::prioritySettingKey('g729')] = '0';

		return $out;
	}

	const SETTING_CODEC_DEFAULTS_MIGRATED = 'codec_defaults_opus_g722_migrated';

	/**
	 * Migrate networks still on legacy codec defaults (PCMU/PCMA/G722, opus off).
	 *
	 * @return int networks updated
	 */
	public static function migrateLegacyDefaultCodecPrioritiesIfNeeded()
	{
		global $db;
		if (!function_exists('sql'))
		{
			return 0;
		}

		$flag = self::SETTING_CODEC_DEFAULTS_MIGRATED;
		$done = sql("SELECT value FROM zts_settings WHERE keyword='".$db->escapeSimple($flag)."'", 'getOne');
		if ($done === '1')
		{
			return 0;
		}

		$legacy = array(
			'codec_pcmu_priority' => '1',
			'codec_pcma_priority' => '2',
			'codec_g722_priority' => '3',
			'codec_opus_priority' => '0',
			'codec_g729_priority' => '0',
		);
		$new = self::defaultCodecSettings();
		$networkRows = sql('SELECT id FROM zts_networks', 'getAll', DB_FETCHMODE_ASSOC);
		if (!is_array($networkRows) || count($networkRows) < 1)
		{
			return 0;
		}

		$updated = 0;
		foreach ($networkRows as $networkRow)
		{
			$networkId = isset($networkRow['id']) ? (string) $networkRow['id'] : '';
			if ($networkId === '')
			{
				continue;
			}
			$rows = sql(
				"SELECT keyword, value FROM zts_network_settings WHERE id='".$db->escapeSimple($networkId)."'",
				'getAll',
				DB_FETCHMODE_ASSOC
			);
			$settings = array();
			if (is_array($rows))
			{
				foreach ($rows as $row)
				{
					if (!isset($row['keyword']))
					{
						continue;
					}
					$settings[(string) $row['keyword']] = isset($row['value']) ? (string) $row['value'] : '';
				}
			}
			$matchesLegacy = true;
			foreach ($legacy as $key => $val)
			{
				if (!isset($settings[$key]) || $settings[$key] !== $val)
				{
					$matchesLegacy = false;
					break;
				}
			}
			if (!$matchesLegacy)
			{
				continue;
			}
			foreach ($new as $key => $val)
			{
				sql(
					"REPLACE INTO zts_network_settings (id, keyword, value) VALUES ('"
					.$db->escapeSimple($networkId)."', '".$db->escapeSimple($key)."', '".$db->escapeSimple($val)."')"
				);
			}
			$updated++;
		}

		sql("REPLACE INTO zts_settings (keyword, value) VALUES ('".$db->escapeSimple($flag)."', '1')");

		return $updated;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function prioritySettingKey($id)
	{
		return 'codec_'.$id.'_priority';
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function enableSettingKey($id)
	{
		return 'codec_'.$id.'_enable';
	}
}
