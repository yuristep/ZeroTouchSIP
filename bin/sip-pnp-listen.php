#!/usr/bin/env php
<?php
/**
 * SIP Plug and Play multicast listener (224.0.0.0/224.0.1.75:5060).
 *
 * Usage:
 *   php sip-pnp-listen.php [--debug]
 *
 * Requires General Settings → SIP PnP → "Run PnP listener on PBX" or Fanvil PnP enabled.
 * Run under systemd (see deployment/zerotouchsip-sip-pnp.service).
 *
 * @license GPL-3.0-or-later
 */

if (php_sapi_name() !== 'cli')
{
	fwrite(STDERR, "CLI only.\n");
	exit(1);
}

$debug = in_array('--debug', $argv, true);

if (!@include_once getenv('FREEPBX_CONF') ?: '/etc/freepbx.conf')
{
	fwrite(STDERR, "FREEPBX_CONF not found.\n");
	exit(1);
}

require_once dirname(__DIR__).'/includes/bootstrap.php';

$general = Zts_SettingsRepository::fetchAll();
$opts = Zts_GeneralSipPnpService::listenerOptions($general);
if (!$opts['listener'])
{
	fwrite(STDERR, "sip_pnp_listener_enable is off in General Settings.\n");
	exit(2);
}

$svc = new Zts_SipPnpListenService($general, $debug);
$svc->listen();
