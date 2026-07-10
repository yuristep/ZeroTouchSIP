<?php
// SPDX-License-Identifier: GPL-3.0-or-later

if (PHP_SAPI !== 'cli')
{
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

if ($argc < 2)
{
	fwrite(STDERR, "Usage: fanvil-http-notify.php <job-file>\n");
	exit(1);
}

$jobFile = (string) $argv[1];
if ($jobFile === '' || !is_file($jobFile))
{
	fwrite(STDERR, "Job file not found\n");
	exit(1);
}

define('FREEPBX_BOOTSTRAP_SKIP_AUTH', true);
require_once '/etc/freepbx.conf';
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$json = file_get_contents($jobFile);
$job = json_decode((string) $json, true);
if (!is_array($job))
{
	error_log('[ZeroTouchSIP Notify] Fanvil HTTP background invalid job file: '.$jobFile);
	@unlink($jobFile);
	exit(1);
}

$rows = isset($job['rows']) && is_array($job['rows']) ? $job['rows'] : array();
$general = isset($job['general']) && is_array($job['general']) ? $job['general'] : array();
$trust = isset($job['trust_certs']) ? $job['trust_certs'] : '0';
$jobId = isset($job['job_id']) ? (string) $job['job_id'] : '';

error_log('[ZeroTouchSIP Notify] Fanvil HTTP background start job='.$jobId.' count='.count($rows));
Zts_FanvilBackgroundNotifyService::appendLog('start job='.$jobId.' count='.count($rows));
Zts_FanvilBackgroundNotifyService::runInventoryRows($rows, $general, $trust, $jobId);
error_log('[ZeroTouchSIP Notify] Fanvil HTTP background done job='.$jobId);
Zts_FanvilBackgroundNotifyService::appendLog('done job='.$jobId.' count='.count($rows));
@unlink($jobFile);
exit(0);
