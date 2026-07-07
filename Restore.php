<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace FreePBX\modules\Zerotouchsip;

use FreePBX\modules\Backup as Base;

/**
 * FreePBX Backup module integration (zts_* tables).
 */
class Restore extends Base\RestoreBase
{
	public function runRestore()
	{
		$configs = $this->getConfigs();
		if (!empty($configs['defaultFallback']))
		{
			$this->importAll($configs);
		}
		elseif (!empty($configs['tables']) && is_array($configs['tables']))
		{
			$this->importTables($configs['tables']);
		}
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables)
	{
		$this->restoreLegacyDatabase($pdo);
	}
}
