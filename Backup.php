<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace FreePBX\modules\Zerotouchsip;

use FreePBX\modules\Backup as Base;

/**
 * FreePBX Backup module integration (zts_* tables via module.xml database section).
 */
class Backup extends Base\BackupBase
{
	public function runBackup($id, $transaction)
	{
		$this->addDependency('core');
		$this->addConfigs(array_merge($this->dumpAll(), array('defaultFallback' => true)));
	}
}
