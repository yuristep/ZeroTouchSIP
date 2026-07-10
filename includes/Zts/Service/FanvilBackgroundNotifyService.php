<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Queues and executes Fanvil HTTP autoprovision in background CLI jobs.
 */
class Zts_FanvilBackgroundNotifyService
{
	private static function logFile()
	{
		$candidates = array(
			'/var/log/asterisk/zerotouchsip-notify.log',
			'/var/spool/asterisk/zerotouchsip/notify.log',
			rtrim(self::queueDir(), '/\\').'/notify.log',
		);
		foreach ($candidates as $path)
		{
			$dir = dirname($path);
			if (!is_dir($dir))
			{
				@mkdir($dir, 0775, true);
			}
			if (is_dir($dir) && (is_writable($dir) || (!file_exists($path) && is_writable($dir)) || is_writable($path)))
			{
				return $path;
			}
		}

		return rtrim(sys_get_temp_dir(), '/\\').'/zerotouchsip-notify.log';
	}

	public static function appendLog($message)
	{
		$line = '['.date('Y-m-d H:i:s').'] '.$message."\n";
		@file_put_contents(self::logFile(), $line, FILE_APPEND);
	}

	private static function queueDir()
	{
		$dir = '/var/spool/asterisk/zerotouchsip/notify-jobs';
		if (!is_dir($dir))
		{
			@mkdir($dir, 0775, true);
		}
		if (!is_dir($dir) || !is_writable($dir))
		{
			$dir = rtrim(sys_get_temp_dir(), '/\\').'/zerotouchsip-notify-jobs';
			if (!is_dir($dir))
			{
				@mkdir($dir, 0775, true);
			}
		}

		return $dir;
	}

	private static function phpCliBinary()
	{
		foreach (array('/usr/bin/php', '/usr/local/bin/php', PHP_BINARY) as $bin)
		{
			$bin = trim((string) $bin);
			if ($bin !== '' && @is_file($bin) && @is_executable($bin))
			{
				return $bin;
			}
		}

		return 'php';
	}

	private static function runnerScript()
	{
		return dirname(__DIR__, 3).'/bin/fanvil-http-notify.php';
	}

	private static function fanvilHttpCredentialsForLastIp($lastip, array $general)
	{
		$network = zts_get_networks_ip($lastip);
		if (is_array($network))
		{
			$creds = Zts_NetworkMmiAccountService::webAdminCredentials($network, $general);
			if (!empty($creds['password']))
			{
				return array(
					'username' => !empty($creds['username']) ? (string) $creds['username'] : 'admin',
					'password' => (string) $creds['password'],
				);
			}
		}
		$fallback = Zts_GeneralPhoneSecurityService::adminWebCredentials($general, 'fanvil');

		return array(
			'username' => !empty($fallback['username']) ? (string) $fallback['username'] : 'admin',
			'password' => !empty($fallback['password']) ? (string) $fallback['password'] : '',
		);
	}

	public static function queueInventoryRows(array $inventoryRows, array $general, $trust_certs)
	{
		$rows = array();
		foreach ($inventoryRows as $row)
		{
			if (!is_array($row))
			{
				continue;
			}
			$lastip = isset($row['lastip']) ? trim((string) $row['lastip']) : '';
			if ($lastip === '')
			{
				continue;
			}
			$rows[] = array(
				'id' => isset($row['id']) ? (string) $row['id'] : '',
				'lastip' => $lastip,
			);
		}
		if (count($rows) === 0)
		{
			return '';
		}

		$jobId = 'fanvil-http-'.date('YmdHis').'-'.substr(sha1(uniqid('', true)), 0, 10);
		$jobFile = rtrim(self::queueDir(), '/\\').'/'.$jobId.'.json';
		$payload = array(
			'job_id' => $jobId,
			'trust_certs' => $trust_certs,
			'general' => $general,
			'rows' => $rows,
		);
		file_put_contents($jobFile, json_encode($payload));

		$cmd = 'nohup '
			.escapeshellarg(self::phpCliBinary()).' '
			.escapeshellarg(self::runnerScript()).' '
			.escapeshellarg($jobFile)
			.' >/dev/null 2>&1 &';
		@shell_exec($cmd);
		self::appendLog('queued job='.$jobId.' count='.count($rows).' file='.$jobFile);

		return $jobId;
	}

	public static function runInventoryRows(array $inventoryRows, array $general, $trust_certs, $jobId = '')
	{
		foreach ($inventoryRows as $row)
		{
			if (!is_array($row))
			{
				continue;
			}
			$tid = isset($row['id']) ? (string) $row['id'] : '';
			$lip = isset($row['lastip']) ? trim((string) $row['lastip']) : '';
			if ($tid === '' || $lip === '')
			{
				continue;
			}
			$creds = self::fanvilHttpCredentialsForLastIp($lip, $general);
			$status = 'skipped_no_credentials';
			if (trim((string) $creds['password']) !== '')
			{
				$status = Zts_FanvilHttpNotifyService::runAutoprovision(
					$lip,
					$creds['password'],
					$trust_certs,
					$creds['username']
				);
			}
			error_log(
				Zts_ModuleBranding::logTag('Notify')
				.' Fanvil HTTP background'
				.($jobId !== '' ? ' job='.$jobId : '')
				.' id='.$tid.' ip='.$lip.' status='.$status
			);
			self::appendLog(
				'item'
				.($jobId !== '' ? ' job='.$jobId : '')
				.' id='.$tid.' ip='.$lip.' status='.$status
			);

			if (strpos($status, 'ok_') === 0)
			{
				continue;
			}

			$ldrows = Zts_NotifyInventoryRepository::fetchLineDeviceIds($tid);
			if (!is_array($ldrows))
			{
				continue;
			}
			foreach ($ldrows as $lr)
			{
				$pd = isset($lr['deviceid']) ? (string) $lr['deviceid'] : '';
				if ($pd === '')
				{
					continue;
				}
				$ep = Zts_NotifyPjsipService::endpointNameForDevicesTableId($pd);
				$res = Zts_NotifyPjsipService::notifyEndpoint($pd, false);
				error_log(
					Zts_ModuleBranding::logTag('Notify')
					.' Fanvil HTTP background fallback'
					.($jobId !== '' ? ' job='.$jobId : '')
					.' id='.$tid.' endpoint='.$ep.' status='.($res === '' ? 'ok' : $res)
				);
				self::appendLog(
					'fallback'
					.($jobId !== '' ? ' job='.$jobId : '')
					.' id='.$tid.' endpoint='.$ep.' status='.($res === '' ? 'ok' : $res)
				);
			}
		}
	}
}
