<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Vendor-specific detection and provisioning identifiers.
 */
interface Zts_VendorAdapterInterface
{
	/**
	 * @return string yealink|fanvil
	 */
	public function getVendorId();

	/**
	 * @param string $userAgent
	 * @param string $modelHint
	 * @return bool
	 */
	public function matches($userAgent, $modelHint = '');

	/**
	 * @param string $userAgent
	 * @return string model token for provisioning
	 */
	public function detectModelFromUserAgent($userAgent);

	/**
	 * @param string $userAgent
	 * @return string firmware version or ''
	 */
	public function detectFirmwareFromUserAgent($userAgent);
}
