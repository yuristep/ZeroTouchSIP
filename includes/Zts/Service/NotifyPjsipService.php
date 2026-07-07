<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * SIP NOTIFY via Asterisk Manager (yealink-check-cfg / yealink-reboot templates).
 */
class Zts_NotifyPjsipService
{
	/** @var string last error from notifyEndpoint (for logs) */
	private static $lastNotifyError = '';

	/**
	 * FreePBX devices.id → PJSIP/chan_sip peer name (usually numeric extension).
	 *
	 * @param string|int $devicesTableId
	 * @return string
	 */
	public static function endpointNameForDevicesTableId($devicesTableId)
	{
		global $db;

		$id = Zts_InputValidator::trimString($devicesTableId);
		if ($id === '')
		{
			return '';
		}

		$row = sql("SELECT users.extension, devices.dial
			FROM devices
			LEFT JOIN users ON devices.user = users.extension
			WHERE devices.id = '".$db->escapeSimple($id)."'", 'getRow', DB_FETCHMODE_ASSOC);
		if (!is_array($row))
		{
			return '';
		}
		if (isset($row['extension']) && trim((string) $row['extension']) !== '')
		{
			return trim((string) $row['extension']);
		}
		if (isset($row['dial']) && trim((string) $row['dial']) !== '')
		{
			return trim((string) $row['dial']);
		}

		return '';
	}

	/**
	 * Send NOTIFY templates to the endpoint.
	 *
	 * @param string|int $deviceid FreePBX devices.id (inventory line), not extension
	 * @param bool       $fanvilLight If true, only yealink-check-cfg (no reboot NOTIFY)
	 * @return string empty on success; else short reason: ami, no_endpoint
	 */
	public static function notifyEndpoint($deviceid, $fanvilLight)
	{
		global $astman;

		self::$lastNotifyError = '';
		$endpoint = self::endpointNameForDevicesTableId($deviceid);
		if ($endpoint === '')
		{
			self::$lastNotifyError = 'no_endpoint';
			error_log(Zts_ModuleBranding::logTag('Notify').' devices.id='.(string) $deviceid.' has no extension/dial — cannot resolve PJSIP endpoint name.');

			return 'no_endpoint';
		}
		if (!preg_match('/^[0-9A-Za-z_.+:-]+$/', $endpoint))
		{
			self::$lastNotifyError = 'invalid_endpoint';
			error_log(Zts_ModuleBranding::logTag('Notify').' invalid endpoint name for NOTIFY: '.$endpoint.' (devices.id='.(string) $deviceid.')');

			return 'invalid_endpoint';
		}

		if (!is_object($astman) || !method_exists($astman, 'send_request'))
		{
			self::$lastNotifyError = 'ami';
			error_log(Zts_ModuleBranding::logTag('Notify').' AMI not available; cannot send SIP NOTIFY to endpoint '.$endpoint.' (devices.id='.(string) $deviceid.')');

			return 'ami';
		}

		if (!$fanvilLight)
		{
			$astman->send_request('Command', array('Command' => 'pjsip send notify yealink-reboot endpoint '.$endpoint));
		}

		$astman->send_request('Command', array('Command' => 'pjsip send notify yealink-check-cfg endpoint '.$endpoint));

		return '';
	}

	/**
	 * @param string|int $deviceid FreePBX devices.id
	 * @param bool       $fanvilLight
	 * @return bool
	 */
	public static function sendToEndpoint($deviceid, $fanvilLight)
	{
		return self::notifyEndpoint($deviceid, $fanvilLight) === '';
	}

	/**
	 * @return string
	 */
	public static function lastError()
	{
		return self::$lastNotifyError;
	}
}
