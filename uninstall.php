<?php
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

global $db;

require_once dirname(__FILE__) . '/includes/bootstrap.php';

$sql = array();
foreach (Zts_DatabaseSchema::allCurrentTables() as $table)
{
	$sql[] = 'DROP TABLE IF EXISTS `'.$table.'`;';
}

foreach ($sql as $statement)
{
	$check = $db->query($statement);
	if (DB::IsError($check))
	{
		out('Error executing: '.$statement.' - '.$check->getMessage());
	}
}

out('ZeroTouchSIP module uninstalled');
