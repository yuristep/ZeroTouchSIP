<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Named Line Key (BLF / Speed Dial) templates stored in General Settings (JSON).
 */
class Zts_LinekeyTemplateService
{
	const SETTING_JSON = 'linekey_templates_json';

	const MAX_TEMPLATES = 20;

	/** Placeholder tab title until the user assigns a unique name. */
	const PLACEHOLDER_NAME = 'New';

	/**
	 * @return string
	 */
	public static function placeholderName()
	{
		return _(self::PLACEHOLDER_NAME);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public static function isPlaceholderName($name)
	{
		$name = trim((string) $name);
		if ($name === '')
		{
			return true;
		}

		return strcasecmp($name, self::PLACEHOLDER_NAME) === 0
			|| strcasecmp($name, self::placeholderName()) === 0;
	}

	/**
	 * @param array $post linekey_tpl from $_POST
	 * @return array{ok:bool,errors:array<int,string>}
	 */
	public static function validateTemplatesPost(array $post)
	{
		$errors = array();
		$seen = array();
		$valid = 0;

		foreach ($post as $tplId => $tplData)
		{
			if (!is_array($tplData))
			{
				continue;
			}
			$tplId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $tplId);
			if ($tplId === '' || strpos($tplId, '__') === 0)
			{
				continue;
			}
			$name = isset($tplData['name']) ? trim((string) $tplData['name']) : '';
			$isDraft = isset($tplData['is_draft']) && (string) $tplData['is_draft'] === '1';

			if ($name === '' || self::isPlaceholderName($name) || $isDraft)
			{
				$errors[] = sprintf(
					_('Rename the template "%s" to a unique name before saving or adding another template.'),
					self::placeholderName()
				);
				continue;
			}
			$key = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
			if (isset($seen[$key]))
			{
				$errors[] = sprintf(_('Duplicate template name: %s'), $name);
				continue;
			}
			$seen[$key] = true;
			$valid++;
		}

		if ($valid < 1)
		{
			$errors[] = _('At least one named Line Key template is required.');
		}

		return array(
			'ok' => count($errors) === 0,
			'errors' => array_values(array_unique($errors)),
		);
	}

	/**
	 * @return array<int,array{id:string,name:string,keys:array<int,array>}>
	 */
	public static function defaultTemplates()
	{
		return array(
			array(
				'id' => 'tpl_1',
				'name' => _('Default'),
				'keys' => self::emptyKeysMap(),
			),
		);
	}

	/**
	 * @return array<int,array{type:string,line:string,value:string,label:string,extension:string,pickup_value:string}>
	 */
	public static function emptyKeysMap()
	{
		$out = array();
		for ($i = 1; $i <= Zts_DeviceEditService::LINEKEY_MAX; $i++)
		{
			$out[$i] = self::emptyKeyRow();
		}

		return $out;
	}

	/**
	 * @return array{type:string,line:string,value:string,label:string,extension:string,pickup_value:string}
	 */
	public static function emptyKeyRow()
	{
		return array(
			'type' => '0',
			'line' => '1',
			'value' => '',
			'label' => '',
			'extension' => '',
			'pickup_value' => '',
		);
	}

	/**
	 * @param array<string,string> $general
	 * @return array<int,array{id:string,name:string,keys:array<int,array>}>
	 */
	public static function fromGeneral(array $general)
	{
		$raw = isset($general[self::SETTING_JSON]) ? trim((string) $general[self::SETTING_JSON]) : '';
		if ($raw === '')
		{
			return self::defaultTemplates();
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded))
		{
			return self::defaultTemplates();
		}

		return self::normalizeTemplateList($decoded);
	}

	/**
	 * @param array $list
	 * @return array<int,array{id:string,name:string,keys:array<int,array>}>
	 */
	public static function normalizeTemplateList(array $list)
	{
		$out = array();
		$n = 0;
		$seenNames = array();
		foreach ($list as $row)
		{
			if (!is_array($row))
			{
				continue;
			}
			$name = isset($row['name']) ? trim((string) $row['name']) : '';
			if ($name === '' || self::isPlaceholderName($name))
			{
				continue;
			}
			$nameKey = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
			if (isset($seenNames[$nameKey]))
			{
				continue;
			}
			$seenNames[$nameKey] = true;
			$id = isset($row['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $row['id']) : '';
			if ($id === '' || strpos($id, '__') === 0)
			{
				$id = 'tpl_'.($n + 1);
			}
			$keys = isset($row['keys']) && is_array($row['keys']) ? $row['keys'] : array();
			$out[] = array(
				'id' => $id,
				'name' => $name,
				'keys' => self::normalizeKeysMap($keys),
			);
			$n++;
			if ($n >= self::MAX_TEMPLATES)
			{
				break;
			}
		}
		if (count($out) < 1)
		{
			return self::defaultTemplates();
		}

		return $out;
	}

	/**
	 * @param array $keys keyed by linekey id (1..27) or 0-based list
	 * @return array<int,array{type:string,line:string,value:string,label:string,extension:string,pickup_value:string}>
	 */
	public static function normalizeKeysMap(array $keys)
	{
		$out = self::emptyKeysMap();
		$isList = array_keys($keys) === range(0, count($keys) - 1);
		foreach ($keys as $k => $row)
		{
			if (!is_array($row))
			{
				continue;
			}
			$idx = $isList ? ((int) $k + 1) : (int) $k;
			if ($idx < 1 || $idx > Zts_DeviceEditService::LINEKEY_MAX)
			{
				continue;
			}
			$out[$idx] = self::normalizeKeyRow($row);
		}

		return $out;
	}

	/**
	 * @param array $row
	 * @return array{type:string,line:string,value:string,label:string,extension:string,pickup_value:string}
	 */
	public static function normalizeKeyRow(array $row)
	{
		$base = self::emptyKeyRow();
		foreach (array_keys($base) as $field)
		{
			if (isset($row[$field]))
			{
				$base[$field] = trim((string) $row[$field]);
			}
		}
		if ($base['line'] === '')
		{
			$base['line'] = '1';
		}

		return $base;
	}

	/**
	 * @param array<int,array> $keys
	 * @return int
	 */
	public static function visibleKeyCount(array $keys)
	{
		return Zts_DeviceEditService::linekeysVisibleCount($keys);
	}

	/**
	 * @param array $post linekey_tpl from $_POST
	 * @return array<int,array{id:string,name:string,keys:array<int,array>}>
	 */
	public static function parseFromPost(array $post)
	{
		$list = array();
		foreach ($post as $tplId => $tplData)
		{
			if (!is_array($tplData))
			{
				continue;
			}
			$tplId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $tplId);
			if ($tplId === '' || strpos($tplId, '__') === 0)
			{
				continue;
			}
			$name = isset($tplData['name']) ? trim((string) $tplData['name']) : '';
			if ($name === '' || self::isPlaceholderName($name))
			{
				continue;
			}
			if (isset($tplData['is_draft']) && (string) $tplData['is_draft'] === '1')
			{
				continue;
			}
			$nameKey = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
			$dup = false;
			foreach ($list as $existing)
			{
				$exKey = function_exists('mb_strtolower') ? mb_strtolower($existing['name'], 'UTF-8') : strtolower($existing['name']);
				if ($exKey === $nameKey)
				{
					$dup = true;
					break;
				}
			}
			if ($dup)
			{
				continue;
			}
			$keys = self::emptyKeysMap();
			if (isset($tplData['type']) && is_array($tplData['type']))
			{
				foreach ($tplData['type'] as $idx => $type)
				{
					$keyNum = (int) $idx + 1;
					if ($keyNum < 1 || $keyNum > Zts_DeviceEditService::LINEKEY_MAX)
					{
						continue;
					}
					$keys[$keyNum] = array(
						'type' => (string) $type,
						'line' => isset($tplData['line'][$idx]) ? (string) $tplData['line'][$idx] : '1',
						'value' => isset($tplData['value'][$idx]) ? (string) $tplData['value'][$idx] : '',
						'label' => isset($tplData['label'][$idx]) ? (string) $tplData['label'][$idx] : '',
						'extension' => isset($tplData['extension'][$idx]) ? (string) $tplData['extension'][$idx] : '',
						'pickup_value' => isset($tplData['pickup'][$idx]) ? (string) $tplData['pickup'][$idx] : '',
					);
					$keys[$keyNum] = self::normalizeKeyRow($keys[$keyNum]);
				}
			}
			$list[] = array(
				'id' => $tplId,
				'name' => $name,
				'keys' => $keys,
			);
			if (count($list) >= self::MAX_TEMPLATES)
			{
				break;
			}
		}

		return self::normalizeTemplateList($list);
	}

	/**
	 * @param array<int,array{id:string,name:string,keys:array<int,array>}> $templates
	 * @return string
	 */
	public static function toJson(array $templates)
	{
		return json_encode(array_values($templates), JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param array<int,array{id:string,name:string,keys:array<int,array>}> $templates
	 * @return array<int,array{id:string,name:string}>
	 */
	public static function choicesForSelect(array $templates)
	{
		$out = array();
		foreach ($templates as $tpl)
		{
			$out[] = array(
				'id' => (string) $tpl['id'],
				'name' => (string) $tpl['name'],
			);
		}

		return $out;
	}

	/**
	 * @param array<int,array{id:string,name:string,keys:array<int,array>}> $templates
	 * @param string $id
	 * @return array<int,array>|null
	 */
	public static function keysByTemplateId(array $templates, $id)
	{
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $id);
		foreach ($templates as $tpl)
		{
			if ((string) $tpl['id'] === $id)
			{
				return $tpl['keys'];
			}
		}

		return null;
	}

	/**
	 * JSON for phone edit apply (id, name, keys only).
	 *
	 * @param array<int,array{id:string,name:string,keys:array<int,array>}> $templates
	 * @return string
	 */
	public static function toJsonForClient(array $templates)
	{
		$payload = array();
		foreach ($templates as $tpl)
		{
			$payload[] = array(
				'id' => $tpl['id'],
				'name' => $tpl['name'],
				'keys' => $tpl['keys'],
			);
		}

		return json_encode($payload, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param array<string,string> $general
	 * @param array $post
	 * @return array<string,string>
	 */
	public static function applyFromPost(array $general, array $post)
	{
		$tplPost = isset($post['linekey_tpl']) && is_array($post['linekey_tpl']) ? $post['linekey_tpl'] : array();
		$templates = self::parseFromPost($tplPost);
		$general[self::SETTING_JSON] = self::toJson($templates);

		return $general;
	}
}
