<?php
// SPDX-License-Identifier: GPL-3.0-or-later

/**
 * Resolves vendor adapters (Fanvil checked before Yealink default).
 */
class Zts_VendorRegistry
{
	/** @var Zts_VendorAdapterInterface[]|null */
	private static $adapters = null;

	/**
	 * @return Zts_VendorAdapterInterface[]
	 */
	private static function adapters()
	{
		if (self::$adapters === null)
		{
			self::$adapters = array(
				new Zts_FanvilVendorAdapter(),
				new Zts_YealinkVendorAdapter(),
			);
		}

		return self::$adapters;
	}

	/**
	 * @param string $userAgent
	 * @param string $modelHint
	 * @return Zts_VendorAdapterInterface
	 */
	public static function resolveAdapter($userAgent, $modelHint = '')
	{
		foreach (self::adapters() as $adapter)
		{
			if ($adapter->getVendorId() === 'fanvil' && $adapter->matches($userAgent, $modelHint))
			{
				return $adapter;
			}
		}

		return new Zts_YealinkVendorAdapter();
	}

	/**
	 * @param string $userAgent
	 * @param string $modelHint
	 * @return string yealink|fanvil
	 */
	public static function detectVendorId($userAgent, $modelHint = '')
	{
		return self::resolveAdapter($userAgent, $modelHint)->getVendorId();
	}

	/**
	 * @param string $userAgent
	 * @param string $modelHint
	 * @return string
	 */
	public static function detectModel($userAgent, $modelHint = '')
	{
		$adapter = self::resolveAdapter($userAgent, $modelHint);
		$model = $adapter->detectModelFromUserAgent($userAgent);
		if ($model !== '')
		{
			return $model;
		}
		if ($adapter->getVendorId() === 'fanvil')
		{
			return '';
		}

		return '00';
	}

	/**
	 * @param string $userAgent
	 * @param string $vendorId
	 * @return string
	 */
	public static function detectFirmware($userAgent, $vendorId = '')
	{
		$userAgent = (string) $userAgent;
		$vendorId = strtolower(Zts_InputValidator::trimString($vendorId));
		if ($vendorId === '')
		{
			$vendorId = self::detectVendorId($userAgent);
		}
		foreach (self::adapters() as $adapter)
		{
			if ($adapter->getVendorId() === $vendorId)
			{
				$fw = $adapter->detectFirmwareFromUserAgent($userAgent);
				if ($fw !== '')
				{
					return $fw;
				}
				break;
			}
		}
		if (preg_match('/\b(\d+(?:\.\d+){1,5})\b/', $userAgent, $matches))
		{
			return $matches[1];
		}

		return '';
	}
}
