<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Fanvil vs Yealink NOTIFY behaviour (profile + model/name heuristics).
 */
class Zts_NotifyVendorHeuristic
{
	/**
	 * @param string $model
	 * @param string $name
	 * @param string $prov_profile provisioning_profile or ''
	 * @return bool true => Fanvil-style (check-sync only, no yealink-reboot NOTIFY)
	 */
	public static function isFanvil($model, $name, $prov_profile)
	{
		$prov_profile = trim((string) $prov_profile);
		if ($prov_profile === 'fanvil')
		{
			return true;
		}
		if ($prov_profile === 'yealink')
		{
			return false;
		}
		$eff = zts_device_effective_model(array(
			'model' => $model,
			'name' => $name,
		));
		$u = strtoupper((string) $eff);
		if (strpos($u, 'H2U') !== false || strpos($u, 'H5') !== false || strpos($u, 'H6W') !== false || strpos($u, 'W611') !== false)
		{
			return true;
		}
		$m = strtoupper(trim((string) $model));
		if (strpos($m, 'H2U') !== false || strpos($m, 'H5') !== false || strpos($m, 'H6W') !== false || strpos($m, 'W611') !== false)
		{
			return true;
		}
		$n = strtoupper(trim((string) $name));
		$bundle = $m.' '.$n.' '.$u;
		if (strpos($bundle, 'F0V2U') !== false || strpos($bundle, 'F0H5') !== false || stripos($bundle, 'FANVIL') !== false)
		{
			return true;
		}

		return false;
	}
}
