<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Human-readable vendor label for inventory rows (uses vendor adapters).
 */
class Zts_VendorDisplayService
{
	/**
	 * @param string $model device model from DB
	 * @return string e.g. Yealink, Fanvil
	 */
	public static function labelForModel($model)
	{
		$vendorId = Zts_VendorRegistry::detectVendorId('', (string) $model);
		if ($vendorId === 'fanvil')
		{
			return _('Fanvil');
		}

		return _('Yealink');
	}

	/**
	 * @param array<int,array> $devices list rows (modified in place)
	 * @return void
	 */
	public static function enrichListRows(array &$devices)
	{
		foreach ($devices as $k => $row)
		{
			$devices[$k]['vendor_label'] = self::labelForModel(isset($row['model']) ? $row['model'] : '');
		}
	}
}
