<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Orchestrates SIP NOTIFY + optional Fanvil HTTP autoprovision for selected phones.
 */
class Zts_NotifyCheckConfigService
{
	/**
	 * Fanvil HTTP + SIP NOTIFY credentials for a phone Last IP (network MMI admin, else General Settings).
	 *
	 * @param string $lastip
	 * @param array  $general zts_get_general_edit()
	 * @return array{username:string,password:string}
	 */
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

	/**
	 * @param string $lastip
	 * @param array  $general
	 * @param string|int|bool $trust_certs
	 * @return string
	 */
	private static function fanvilHttpAutoprovision($lastip, array $general, $trust_certs)
	{
		$creds = self::fanvilHttpCredentialsForLastIp($lastip, $general);
		if (trim($creds['password']) === '')
		{
			return 'skipped_no_credentials';
		}

		return Zts_FanvilHttpNotifyService::runAutoprovision(
			$lastip,
			$creds['password'],
			$trust_certs,
			$creds['username']
		);
	}

	/**
	 * @param string|string[]|null $id zts_devices.id or null for all
	 * @param bool                 $soft If true, SIP check-sync only (no reboot, no Fanvil HTTP)
	 * @return array<int,string>
	 */
	public static function run($id = null, $soft = false)
	{
		global $db;

		$ui = array();
		$softNotify = (bool) $soft;

		$general = zts_get_general_edit();
		$trust_certs = isset($general['security_trust_certificates']) ? $general['security_trust_certificates'] : '0';

		$ids_filter = null;
		if (!empty($id))
		{
			$id_array = is_array($id) ? $id : array($id);
			$escaped = array();
			foreach ($id_array as $single_id)
			{
				$escaped[] = $db->escapeSimple($single_id);
			}
			$ids_filter = implode("','", $escaped);
		}

		$inv = Zts_NotifyInventoryRepository::fetchInventory(!empty($id) ? $id : null);
		$fanvil_http_status = array();

		$results = Zts_NotifyInventoryRepository::fetchLineEndpoints($ids_filter);
		$amiWarned = false;

		if (!empty($id) && (count($results) < 1))
		{
			$one = is_array($id) ? (count($id) === 1 ? (string) $id[0] : '') : (string) $id;
			if ($one !== '')
			{
				$ui[] = sprintf(
					_('Phone inventory id %s: no extension line is assigned — open Edit Phone, bind a FreePBX extension, then Notify again.'),
					$one
				);
			}
		}

		$pjsip_has_sip_line = array();
		$fanvil_by_endpoint = array();
		foreach ($results as $row)
		{
			$d = $row['deviceid'];
			$fv = Zts_NotifyVendorHeuristic::isFanvil(
				isset($row['model']) ? $row['model'] : '',
				isset($row['name']) ? $row['name'] : '',
				isset($row['prov_profile']) ? $row['prov_profile'] : ''
			);
			if (!isset($pjsip_has_sip_line[$d]))
			{
				$pjsip_has_sip_line[$d] = false;
			}
			if (!$fv)
			{
				$pjsip_has_sip_line[$d] = true;
			}
			if (!isset($fanvil_by_endpoint[$d]))
			{
				$fanvil_by_endpoint[$d] = false;
			}
			$fanvil_by_endpoint[$d] = $fanvil_by_endpoint[$d] || $fv;
		}

		$sip_notify_sent = array();
		foreach ($fanvil_by_endpoint as $deviceid => $fanvil_light)
		{
			$endpoint = Zts_NotifyPjsipService::endpointNameForDevicesTableId($deviceid);
			$notifyLight = $softNotify;
			if (!empty($pjsip_has_sip_line[$deviceid]))
			{
				$err = Zts_NotifyPjsipService::notifyEndpoint($deviceid, $notifyLight);
				if ($err === '')
				{
					$sip_notify_sent[$deviceid] = true;
					if ($softNotify)
					{
						$ui[] = sprintf(_('Extension %s: sent SIP NOTIFY (check-sync only).'), $endpoint);
					}
					elseif ($fanvil_light)
					{
						$ui[] = sprintf(_('Extension %s (Fanvil): sent SIP NOTIFY (check-sync + reboot).'), $endpoint);
					}
					else
					{
						$ui[] = sprintf(_('Extension %s: sent SIP NOTIFY (reboot + check-sync).'), $endpoint);
					}
				}
				elseif ($err === 'no_endpoint')
				{
					$ui[] = sprintf(
						_('FreePBX device id %s: cannot resolve extension for SIP NOTIFY — check the extension exists and is linked to the line.'),
						$deviceid
					);
				}
				elseif ($err === 'ami' && !$amiWarned)
				{
					$amiWarned = true;
					$ui[] = _('Asterisk Manager (AMI) is not available to PHP — SIP NOTIFY was not sent. Check Manager access for this admin user.');
				}
			}
			elseif (!empty($fanvil_light))
			{
				$err = Zts_NotifyPjsipService::notifyEndpoint($deviceid, $notifyLight);
				if ($err === '')
				{
					$sip_notify_sent[$deviceid] = true;
					if ($softNotify)
					{
						$ui[] = sprintf(_('Extension %s (Fanvil): sent SIP NOTIFY (check-sync only).'), $endpoint);
					}
					else
					{
						$ui[] = sprintf(_('Extension %s (Fanvil): sent SIP NOTIFY (check-sync + reboot).'), $endpoint);
					}
				}
				elseif ($err === 'no_endpoint')
				{
					$ui[] = sprintf(
						_('FreePBX device id %s: cannot resolve extension (Fanvil SIP NOTIFY skipped).'),
						$deviceid
					);
				}
				elseif ($err === 'ami' && !$amiWarned)
				{
					$amiWarned = true;
					$ui[] = _('Asterisk Manager (AMI) is not available to PHP — SIP NOTIFY was not sent.');
				}
			}
		}

		if (!$softNotify)
		{
		foreach ($inv as $inv_row)
		{
			$prov = isset($inv_row['prov_profile']) ? $inv_row['prov_profile'] : '';
			if (!Zts_NotifyVendorHeuristic::isFanvil(
				isset($inv_row['model']) ? $inv_row['model'] : '',
				isset($inv_row['name']) ? $inv_row['name'] : '',
				$prov
			))
			{
				continue;
			}
			$tid = (string) $inv_row['id'];
			$lip = trim(isset($inv_row['lastip']) ? $inv_row['lastip'] : '');
			if ($lip === '')
			{
				$fanvil_http_status[$tid] = 'no_lastip';
				error_log(Zts_ModuleBranding::logTag('Notify').' Fanvil id='.$tid.' — no Last IP in inventory, HTTP skipped.');
				$ui[] = sprintf(_('Fanvil id %s: no Last IP — HTTP skipped'), $tid);
				continue;
			}
			$st = self::fanvilHttpAutoprovision($lip, $general, $trust_certs);
			$fanvil_http_status[$tid] = $st;
			error_log(Zts_ModuleBranding::logTag('Notify').' Fanvil HTTP Autoprovision id='.$tid.' ip='.$lip.' status='.$st);
			$ui[] = sprintf(_('Fanvil id %s (%s): HTTP Autoprovision %s'), $tid, $lip, $st);
		}

		$sip_fallback_sent = array();
		foreach ($inv as $inv_row)
		{
			$prov = isset($inv_row['prov_profile']) ? $inv_row['prov_profile'] : '';
			if (!Zts_NotifyVendorHeuristic::isFanvil(
				isset($inv_row['model']) ? $inv_row['model'] : '',
				isset($inv_row['name']) ? $inv_row['name'] : '',
				$prov
			))
			{
				continue;
			}
			$tid = (string) $inv_row['id'];
			$st = isset($fanvil_http_status[$tid]) ? $fanvil_http_status[$tid] : '';
			if (strpos($st, 'ok_') === 0)
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
				$pd = $lr['deviceid'];
				if (isset($sip_fallback_sent[$pd]) || isset($sip_notify_sent[$pd]))
				{
					continue;
				}
				$sip_fallback_sent[$pd] = true;
				$ep = Zts_NotifyPjsipService::endpointNameForDevicesTableId($pd);
				if (Zts_NotifyPjsipService::notifyEndpoint($pd, false) === '')
				{
					$ui[] = sprintf(_('Extension %s: extra SIP check-sync (HTTP was %s).'), $ep, $st !== '' ? $st : 'unknown');
				}
				elseif (!$amiWarned)
				{
					$amiWarned = true;
					$ui[] = _('Asterisk Manager (AMI) is not available — extra SIP NOTIFY was not sent.');
				}
				error_log(Zts_ModuleBranding::logTag('Notify').' Fanvil id='.$tid.' — HTTP supplement did not succeed ('.$st.'); yealink-check-cfg to extension '.$ep.' (devices.id '.$pd.').');
			}
		}
		}

		if (count($ui) === 0)
		{
			$ui[] = _('No notify actions were recorded (no lines / no AMI / nothing to do).');
		}

		return $ui;
	}

	/**
	 * After Edit Phone save: NOTIFY old and new line endpoints (phone may still register on the previous extension).
	 *
	 * @param string   $inventoryId         zts_devices.id
	 * @param string[] $previousPbxDeviceIds devices.id before save
	 * @param string[] $newPbxDeviceIds      devices.id after save
	 * @return void
	 */
	public static function runAfterPhoneSave($inventoryId, array $previousPbxDeviceIds, array $newPbxDeviceIds)
	{
		$toNotify = array();
		foreach (array_merge($previousPbxDeviceIds, $newPbxDeviceIds) as $pbxDeviceId)
		{
			$pbxDeviceId = Zts_InputValidator::trimString($pbxDeviceId);
			if ($pbxDeviceId !== '' && !in_array($pbxDeviceId, $toNotify, true))
			{
				$toNotify[] = $pbxDeviceId;
			}
		}
		foreach ($toNotify as $pbxDeviceId)
		{
			self::runForDeviceId($pbxDeviceId, $inventoryId, true);
		}
	}

	/**
	 * After single-device save from edit form.
	 *
	 * @param string      $deviceid        FreePBX devices.id for PJSIP endpoint
	 * @param string|null $device_edit_id zts_devices.id
	 * @param bool        $withFanvilHttp  If false, skip Fanvil HTTP autoprovision (avoids blocking Save for minutes)
	 * @return void
	 */
	public static function runForDeviceId($deviceid, $device_edit_id = null, $withFanvilHttp = true)
	{
		if ($deviceid === null || $deviceid === '' || $deviceid === false)
		{
			return;
		}

		$fanvil = false;
		$row = null;
		if ($device_edit_id !== null && $device_edit_id !== '' && $device_edit_id !== false)
		{
			$row = Zts_NotifyInventoryRepository::fetchDeviceRow($device_edit_id);
			if (is_array($row))
			{
				$fanvil = Zts_NotifyVendorHeuristic::isFanvil(
					isset($row['model']) ? $row['model'] : '',
					isset($row['name']) ? $row['name'] : '',
					isset($row['prov_profile']) ? $row['prov_profile'] : ''
				);
			}
		}
		$endpoint = Zts_NotifyPjsipService::endpointNameForDevicesTableId($deviceid);

		if ($fanvil && is_array($row))
		{
			$err = Zts_NotifyPjsipService::notifyEndpoint($deviceid, false);
			error_log(Zts_ModuleBranding::logTag('Notify').' (after save) Fanvil inventory='.$device_edit_id
				.' devices.id='.$deviceid.' endpoint='.$endpoint.' result='.($err === '' ? 'ok' : $err));
			if ($withFanvilHttp)
			{
				$lip = trim(isset($row['lastip']) ? $row['lastip'] : '');
				if ($lip !== '')
				{
					$general = zts_get_general_edit();
					$trust_certs = isset($general['security_trust_certificates']) ? $general['security_trust_certificates'] : '0';
					$st = self::fanvilHttpAutoprovision($lip, $general, $trust_certs);
					error_log(Zts_ModuleBranding::logTag('Notify').' (after save) Fanvil HTTP Autoprovision id='.$device_edit_id.' ip='.$lip.' status='.$st);
				}
			}
			else
			{
				error_log(Zts_ModuleBranding::logTag('Notify').' (after save) Fanvil id='.$device_edit_id.' — SIP NOTIFY only (HTTP skipped).');
			}

			return;
		}

		$err = Zts_NotifyPjsipService::notifyEndpoint($deviceid, false);
		error_log(Zts_ModuleBranding::logTag('Notify').' (after save) inventory='.$device_edit_id
			.' devices.id='.$deviceid.' endpoint='.$endpoint.' result='.($err === '' ? 'ok' : $err));
	}
}
