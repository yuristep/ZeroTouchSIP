<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_FanvilVendorAdapter implements Zts_VendorAdapterInterface
{
	public function getVendorId()
	{
		return 'fanvil';
	}

	public function matches($userAgent, $modelHint = '')
	{
		$model = strtoupper(Zts_InputValidator::trimString($modelHint));
		if (strpos($model, 'FANVIL') !== false || preg_match('/^H[0-9]/', $model) || preg_match('/^W611/', $model))
		{
			return true;
		}

		return stripos((string) $userAgent, 'Fanvil') !== false;
	}

	public function detectModelFromUserAgent($userAgent)
	{
		if (preg_match('/Fanvil\s+([A-Za-z0-9\-]+)/i', (string) $userAgent, $matches))
		{
			return strtoupper($matches[1]);
		}

		return '';
	}

	public function detectFirmwareFromUserAgent($userAgent)
	{
		if (preg_match('/Fanvil\s+[A-Za-z0-9\-]+\s+([\d\.]+)/i', (string) $userAgent, $matches))
		{
			return $matches[1];
		}

		return '';
	}
}
