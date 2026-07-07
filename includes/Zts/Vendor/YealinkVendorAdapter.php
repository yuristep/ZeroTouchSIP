<?php
// SPDX-License-Identifier: GPL-3.0-or-later

class Zts_YealinkVendorAdapter implements Zts_VendorAdapterInterface
{
	public function getVendorId()
	{
		return 'yealink';
	}

	public function matches($userAgent, $modelHint = '')
	{
		return ! (new Zts_FanvilVendorAdapter())->matches($userAgent, $modelHint);
	}

	public function detectModelFromUserAgent($userAgent)
	{
		if (preg_match('/SIP-T(\d+)[A-Z]?\s/', (string) $userAgent, $matches))
		{
			return $matches[1];
		}

		return '00';
	}

	public function detectFirmwareFromUserAgent($userAgent)
	{
		if (preg_match('/SIP-T\d+[A-Z]?\s+([\d\.]+)\s/i', (string) $userAgent, $matches))
		{
			return $matches[1];
		}

		return '';
	}
}
