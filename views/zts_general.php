<?php
/* General Settings View — FreePBX 17 edit layout */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$zts_security_rows = Zts_GeneralPhoneSecurityService::rowsFromGeneral($general);
$zts_linekey_templates = Zts_LinekeyTemplateService::fromGeneral($general);
$zts_profile_choices = Zts_GeneralPhoneSecurityService::profileChoices();
$zts_security_max = Zts_GeneralPhoneSecurityService::MAX_ROWS;
$zts_default_time = Zts_GeneralTimeSettingsMapper::timeSlice($general);
$zts_ui_tz = Zts_NetworkTimeSettingsMapper::uiTimeZoneSelected($zts_default_time);
$zts_ui_dst = Zts_NetworkTimeSettingsMapper::uiDaylightSavingSelected($zts_default_time);
$zts_prov_urls = zts_provisioning_public_urls(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
$zts_form_action = Zts_ModuleIdentifiers::adminPageUrl('general_edit');

/**
 * @param string $label
 * @param string $extraClass
 * @return void
 */
$zts_fpbx_th = function ($label, $extraClass = '') {
	$class = 'zts-fpbx-th';
	if ($extraClass !== '')
	{
		$class .= ' '.$extraClass;
	}
	echo '<th class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').'">';
	echo '<div class="th-inner">'.htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8').'</div>';
	echo '<div class="fht-cell"></div></th>';
};

/**
 * @param string      $name
 * @param string      $value
 * @param string      $placeholder
 * @param string|null $maxlength
 * @return void
 */
$zts_password_input = function ($name, $value, $placeholder = '', $maxlength = null) {
	$ph = $placeholder !== '' ? ' placeholder="'.htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8').'"' : '';
	$ml = $maxlength !== null ? ' maxlength="'.(int) $maxlength.'"' : '';
	echo '<div class="input-group zts-pw-group">';
	echo '<input type="password" name="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'" class="form-control zts-pw-field" value="'
		.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'" autocomplete="new-password"'.$ml.$ph.'>';
	echo '<span class="input-group-addon zts-pw-toggle-wrap">';
	echo '<button type="button" class="zts-pw-toggle" title="'.htmlspecialchars(_('Show password'), ENT_QUOTES, 'UTF-8').'" tabindex="-1">';
	echo '<i class="fa fa-eye"></i></button></span></div>';
};

/**
 * Horizontal header/value table (no section title — use inside panel or subsection).
 *
 * @param string $tableId
 * @param array  $columns
 * @param string $helpHtml
 * @return void
 */
$zts_horizontal_table = function ($tableId, array $columns, $helpHtml = '') use ($zts_fpbx_th) {
	echo '<div class="bootstrap-table zts-bt-wrap zts-bt-wrap-edit zts-param-table-wrap">';
	echo '<div class="fixed-table-container"><div class="fixed-table-body">';
	echo '<div class="table-responsive zts-table-responsive">';
	echo '<table id="'.htmlspecialchars($tableId, ENT_QUOTES, 'UTF-8').'" class="table table-striped table-bordered table-hover zts-fpbx-table zts-hvalues-table">';
	echo '<thead><tr>';
	foreach ($columns as $col)
	{
		$extra = isset($col['th_class']) ? (string) $col['th_class'] : '';
		$zts_fpbx_th((string) $col['label'], $extra);
	}
	echo '</tr></thead><tbody><tr class="zts-hvalues-row">';
	foreach ($columns as $col)
	{
		$tdClass = isset($col['th_class']) ? ' class="'.htmlspecialchars((string) $col['th_class'], ENT_QUOTES, 'UTF-8').'"' : '';
		echo '<td'.$tdClass.'>'.$col['cell'].'</td>';
	}
	echo '</tr></tbody></table></div></div></div></div>';
	if ($helpHtml !== '')
	{
		echo '<p class="help-block zts-table-help">'.$helpHtml.'</p>';
	}
};

/**
 * @param string   $title
 * @param string   $helpHtml
 * @param callable $body
 * @return void
 */
$zts_subsection = function ($title, $helpHtml, $body) {
	echo '<h4 class="zts-subsection-title">'.htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8').'</h4>';
	if ($helpHtml !== '')
	{
		echo '<p class="help-block zts-subsection-help">'.$helpHtml.'</p>';
	}
	$body();
};
?>

<div id="zts-general-edit-page" class="zts-fpbx-edit-page">

<h2 class="zts-page-title"><?php echo _("General Settings"); ?></h2>

<?php
if (session_status() === PHP_SESSION_NONE)
{
	@session_start();
}
if (!empty($_SESSION['Zts_general_edit_errors']) && is_array($_SESSION['Zts_general_edit_errors']))
{
	echo '<div class="alert alert-danger zts-general-edit-errors"><ul class="list-unstyled" style="margin:0;">';
	foreach ($_SESSION['Zts_general_edit_errors'] as $_zts_ge_err)
	{
		echo '<li>'.htmlspecialchars((string) $_zts_ge_err, ENT_QUOTES, 'UTF-8').'</li>';
	}
	echo '</ul></div>';
	unset($_SESSION['Zts_general_edit_errors']);
}
?>

<div class="well well-sm zts-prov-urls-well">
	<p class="zts-prov-urls-lead" style="margin-bottom:0;"><strong><?php echo _('Provisioning URL:'); ?></strong>
		<code class="zts-prov-url"><?php echo htmlspecialchars($zts_prov_urls['primary'], ENT_QUOTES, 'UTF-8'); ?></code></p>
	<p class="help-block zts-dhcp-help"><?php echo _('DHCP setup: publish this URL to phones as their provisioning server, usually with DHCP Option 66 (or the vendor-specific provisioning URL option used in your phone VLAN). Phones should receive this URL and then request their MAC-based config from ZeroTouchSIP.'); ?></p>
</div>

<form method="post" action="<?php echo htmlspecialchars($zts_form_action, ENT_QUOTES, 'UTF-8'); ?>" id="zts-general-edit-form" class="form-horizontal zts-edit-form" accept-charset="UTF-8">
	<input type="hidden" name="action" value="edit">

	<div class="panel panel-default zts-edit-panel">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _("Auto-Provisioning"); ?></h3>
		</div>
		<div class="panel-body">
			<?php
			ob_start();
			echo '<input type="number" name="auto_provision_repeat_minutes" class="form-control" value="'
				.htmlspecialchars($general['auto_provision_repeat_minutes'], ENT_QUOTES, 'UTF-8').'" required>';
			$autoProvCell = ob_get_clean();
			$zts_horizontal_table('zts-general-autoprov-table', array(
				array(
					'label' => _('Auto-Provision Repeat Interval (minutes)'),
					'th_class' => 'zts-col-medium',
					'cell' => $autoProvCell,
				),
			), htmlspecialchars(_('How often phones should check for config updates (default: 1440 = 24 hours)'), ENT_QUOTES, 'UTF-8'));
			?>
		</div>
	</div>

	<?php
	$zts_pnp = Zts_GeneralSipPnpService::installDefaults();
	foreach (Zts_GeneralSipPnpService::storageKeys() as $_zts_pk)
	{
		if (isset($general[$_zts_pk]) && (string) $general[$_zts_pk] !== '')
		{
			$zts_pnp[$_zts_pk] = (string) $general[$_zts_pk];
		}
	}
	?>
	<div class="panel panel-default zts-edit-panel">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _("SIP Plug and Play (PnP)"); ?></h3>
		</div>
		<div class="panel-body">
			<p class="help-block"><?php echo _('SIP PnP helps supported phones discover their provisioning URL automatically. Phones send a multicast SUBSCRIBE (RFC 6080 ua-profile), and the PBX replies with a NOTIFY that points the phone to its ZeroTouchSIP config URL. Fanvil phones also receive matching AUTOUPDATE PNP settings in generated configs.'); ?></p>
			<div class="form-group">
				<div class="col-sm-offset-3 col-sm-9">
					<label class="checkbox-inline">
						<input type="checkbox" name="sip_pnp_enable" value="1"<?php echo ($zts_pnp['sip_pnp_enable'] === '1' ? ' checked' : ''); ?>>
						<?php echo _('Fanvil: SIP PnP enabled in provisioning (AUTOUPDATE)'); ?>
					</label>
				</div>
			</div>
			<?php
			ob_start();
			echo '<input type="text" name="sip_pnp_multicast" class="form-control" value="'
				.htmlspecialchars($zts_pnp['sip_pnp_multicast'], ENT_QUOTES, 'UTF-8').'" placeholder="224.0.1.75">';
			$mcCell = ob_get_clean();
			ob_start();
			echo '<input type="number" name="sip_pnp_port" class="form-control" min="1" max="65535" value="'
				.htmlspecialchars($zts_pnp['sip_pnp_port'], ENT_QUOTES, 'UTF-8').'">';
			$portCell = ob_get_clean();
			ob_start();
			echo '<select name="sip_pnp_transport" class="form-control">';
			$trLabels = array('0' => _('UDP'), '1' => _('TCP'), '2' => _('TLS'));
			foreach ($trLabels as $tv => $tl)
			{
				$sel = ($zts_pnp['sip_pnp_transport'] === $tv) ? ' selected' : '';
				echo '<option value="'.htmlspecialchars($tv, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'.htmlspecialchars($tl, ENT_QUOTES, 'UTF-8').'</option>';
			}
			echo '</select>';
			$trCell = ob_get_clean();
			ob_start();
			echo '<input type="number" name="sip_pnp_interval" class="form-control" min="0" max="99" value="'
				.htmlspecialchars($zts_pnp['sip_pnp_interval'], ENT_QUOTES, 'UTF-8').'">';
			$intCell = ob_get_clean();
			$zts_horizontal_table('zts-general-sip-pnp-table', array(
				array('label' => _('Multicast address (server)'), 'th_class' => 'zts-col-medium', 'cell' => $mcCell),
				array('label' => _('Server port'), 'cell' => $portCell),
				array('label' => _('Transport protocol'), 'cell' => $trCell),
				array('label' => _('Refresh interval (hours, 0–99)'), 'cell' => $intCell),
			), htmlspecialchars(_('Fanvil defaults: 224.0.1.75, 5060, UDP, 1 hour.'), ENT_QUOTES, 'UTF-8'));
			?>
			<div class="form-group">
				<label class="col-sm-3 control-label" for="sip_pnp_cfg_base_url"><?php echo _('Profile URL base'); ?></label>
				<div class="col-sm-9">
					<input type="url" name="sip_pnp_cfg_base_url" id="sip_pnp_cfg_base_url" class="form-control"
						value="<?php echo htmlspecialchars($zts_pnp['sip_pnp_cfg_base_url'], ENT_QUOTES, 'UTF-8'); ?>"
						placeholder="<?php echo htmlspecialchars($zts_prov_urls['primary'], ENT_QUOTES, 'UTF-8'); ?>">
					<p class="help-block"><?php echo _('NOTIFY body: {base}/{MAC}.cfg. Leave empty to use this server provisioning URL.'); ?></p>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-3 col-sm-9">
					<label class="checkbox-inline">
						<input type="checkbox" name="sip_pnp_secure_urls" value="1"<?php echo (($zts_pnp['sip_pnp_secure_urls'] ?? '1') === '1' ? ' checked' : ''); ?>>
						<?php echo _('Secure one-time PnP URLs (?mac=...&hash=...)'); ?>
					</label>
					<p class="help-block"><?php echo _('NOTIFY contains pnp.php?mac=...&hash=... where hash is a one-time HMAC-SHA256 token. Invalid hash attempts can ban the client IP.'); ?></p>
				</div>
			</div>
			<?php
			ob_start();
			echo '<input type="number" name="sip_pnp_ban_max_failures" class="form-control" min="1" max="100" value="'
				.htmlspecialchars((string) ($zts_pnp['sip_pnp_ban_max_failures'] ?? '5'), ENT_QUOTES, 'UTF-8').'">';
			$banMaxCell = ob_get_clean();
			ob_start();
			echo '<input type="number" name="sip_pnp_ban_seconds" class="form-control" min="60" max="604800" value="'
				.htmlspecialchars((string) ($zts_pnp['sip_pnp_ban_seconds'] ?? '3600'), ENT_QUOTES, 'UTF-8').'">';
			$banSecCell = ob_get_clean();
			$zts_horizontal_table('zts-general-sip-pnp-ban-table', array(
				array('label' => _('Ban after invalid hash attempts'), 'cell' => $banMaxCell),
				array('label' => _('Ban duration (seconds)'), 'cell' => $banSecCell),
			));
			?>
			<div class="form-group">
				<div class="col-sm-offset-3 col-sm-9">
					<label class="checkbox-inline">
						<input type="checkbox" name="sip_pnp_listener_enable" value="1"<?php echo ($zts_pnp['sip_pnp_listener_enable'] === '1' ? ' checked' : ''); ?>>
						<?php echo _('Run PnP listener on PBX (zerotouchsip/bin/sip-pnp-listen.php, systemd)'); ?>
					</label>
					<p class="help-block"><?php echo _('Responds to SUBSCRIBE; only MACs registered in the phone list. Requires UDP 5060 multicast on LAN interfaces.'); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="panel panel-default zts-edit-panel">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _("Provisioning diagnostics"); ?></h3>
		</div>
		<div class="panel-body">
			<?php $plm = isset($general['provisioning_log_mode']) ? $general['provisioning_log_mode'] : 'off'; ?>
			<?php
			$plf = isset($general['provisioning_log_file']) ? trim((string) $general['provisioning_log_file']) : '';
			if ($plf === '')
			{
				$plf = Zts_ProvisioningLogConfig::defaultFilePath();
			}
			?>
			<div class="form-group">
				<label class="col-sm-3 control-label" for="zts-prov-log-mode"><?php echo _("Provisioning log mode"); ?></label>
				<div class="col-sm-9">
					<select name="provisioning_log_mode" id="zts-prov-log-mode" class="form-control">
						<option value="off"<?php echo ($plm === 'off' ? ' selected' : ''); ?>><?php echo _("Off (no structured provisioning trace)"); ?></option>
						<option value="apache"<?php echo ($plm === 'apache' ? ' selected' : ''); ?>><?php echo _("Apache PHP error log (JSON lines, tag [ZeroTouchSIP Prov])"); ?></option>
						<option value="file"<?php echo ($plm === 'file' ? ' selected' : ''); ?>><?php echo _("Dedicated log file (append, one JSON line per event)"); ?></option>
					</select>
					<span class="help-block"><?php echo _("Structured provisioning events only; passwords are never logged. If provisioning/.provision_verbose exists, Apache mode is used when this setting is Off."); ?></span>
				</div>
			</div>
			<div class="form-group" style="margin-bottom:0;">
				<label class="col-sm-3 control-label" for="zts-prov-log-file"><?php echo _("Provisioning log file path"); ?></label>
				<div class="col-sm-9">
					<input type="text" name="provisioning_log_file" id="zts-prov-log-file" class="form-control"
					       value="<?php echo htmlspecialchars($plf, ENT_QUOTES, 'UTF-8'); ?>">
					<span class="help-block"><?php echo htmlspecialchars(_("Used when mode is \"Dedicated log file\". Path must stay under /var/log/. On Sangoma FreePBX (rpm) use /var/log/httpd/; on Debian typically /var/log/apache2/. Logrotate: deployment/logrotate-zerotouchsip-provision -> /etc/logrotate.d/zerotouchsip-provision"), ENT_QUOTES, 'UTF-8'); ?></span>
				</div>
			</div>
		</div>
	</div>

	<div class="panel panel-default zts-edit-panel">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _("Default Phone Settings"); ?></h3>
		</div>
		<div class="panel-body">
			<p class="help-block zts-section-lead"><?php echo _("Defaults used when a new phone is discovered or when an inventory entry has no SIP line bindings yet. Saving these defaults does not overwrite already configured phones."); ?></p>

			<?php
			$zts_subsection(_('Phone'), htmlspecialchars(_('Default display and provisioning behavior for newly discovered phones. Trust certificates controls whether phones accept self-signed HTTPS certificates during provisioning.'), ENT_QUOTES, 'UTF-8'), function () use ($zts_horizontal_table, $general) {
				ob_start();
				echo '<input type="number" name="default_backlight_time" class="form-control" value="'
					.htmlspecialchars(isset($general['default_backlight_time']) ? $general['default_backlight_time'] : '60', ENT_QUOTES, 'UTF-8').'">';
				$backlightCell = ob_get_clean();

				ob_start();
				echo '<select name="default_lang" class="form-control">';
				$langs = array('English', 'Russian', 'Spanish', 'French', 'German', 'Italian', 'Portuguese');
				$curLang = isset($general['default_lang']) ? $general['default_lang'] : 'English';
				foreach ($langs as $lang)
				{
					$sel = ($curLang === $lang) ? ' selected' : '';
					echo '<option value="'.htmlspecialchars($lang, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'
						.htmlspecialchars($lang, ENT_QUOTES, 'UTF-8').'</option>';
				}
				echo '</select>';
				$langCell = ob_get_clean();

				ob_start();
				echo '<select name="security_trust_certificates" class="form-control">';
				$trust = isset($general['security_trust_certificates']) ? $general['security_trust_certificates'] : '0';
				echo '<option value="0"'.($trust === '0' ? ' selected' : '').'>'.htmlspecialchars(_('No (Validate certificates)'), ENT_QUOTES, 'UTF-8').'</option>';
				echo '<option value="1"'.($trust === '1' ? ' selected' : '').'>'.htmlspecialchars(_('Yes (Accept all)'), ENT_QUOTES, 'UTF-8').'</option>';
				echo '</select>';
				$trustCell = ob_get_clean();

				$zts_horizontal_table('zts-general-phone-table', array(
					array('label' => _('Default Backlight Timeout (seconds)'), 'th_class' => 'zts-col-medium', 'cell' => $backlightCell),
					array('label' => _('Default Language'), 'th_class' => 'zts-col-medium', 'cell' => $langCell),
					array('label' => _('Trust All Certificates'), 'th_class' => 'zts-col-medium', 'cell' => $trustCell),
				));
			});

			$zts_subsection(_('Phone Security'), htmlspecialchars(_('Default phone web interface users for newly discovered phones. Yealink uses security.user_name.* / security.user_password; Fanvil uses MMI Account settings unless a Network has custom users. The first Administrators row defines the default profile for auto-registered phones.'), ENT_QUOTES, 'UTF-8'), function () use ($zts_fpbx_th, $zts_password_input, $zts_security_rows, $zts_profile_choices, $zts_security_max) {
				?>
			<div class="bootstrap-table zts-bt-wrap zts-bt-wrap-edit zts-param-table-wrap">
				<div class="fixed-table-container">
					<div class="fixed-table-body">
						<div id="zts-general-security-toolbar" class="zts-table-toolbar clearfix">
							<div class="pull-left">
								<button type="button" class="btn btn-default btn-sm" id="zts-general-security-add-row"<?php echo (count($zts_security_rows) >= $zts_security_max ? ' disabled' : ''); ?>>
									<i class="fa fa-plus"></i> <?php echo _("Add User"); ?>
								</button>
							</div>
						</div>
						<div class="table-responsive zts-table-responsive">
							<table id="zts-general-security-table" class="table table-striped table-bordered table-hover zts-fpbx-table">
								<thead>
									<tr>
										<?php $zts_fpbx_th(_('Vendor'), 'zts-sec-col-vendor'); ?>
										<?php $zts_fpbx_th(_('User name'), 'zts-mmi-col-user'); ?>
										<?php $zts_fpbx_th(_('Web Authentication Password'), 'zts-mmi-col-pass'); ?>
										<?php $zts_fpbx_th(_('Privilege'), 'zts-mmi-col-priv'); ?>
										<?php $zts_fpbx_th(_('Actions'), 'zts-actions-th'); ?>
									</tr>
								</thead>
								<tbody id="zts-general-security-body">
									<?php foreach ($zts_security_rows as $secRow): ?>
									<tr class="zts-general-security-row">
										<td class="zts-sec-col-vendor">
											<select name="security_profile[]" class="form-control">
												<?php foreach ($zts_profile_choices as $pKey => $pLabel): ?>
												<option value="<?php echo htmlspecialchars($pKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($secRow['profile'] === $pKey ? ' selected' : ''); ?>>
													<?php echo htmlspecialchars($pLabel, ENT_QUOTES, 'UTF-8'); ?>
												</option>
												<?php endforeach; ?>
											</select>
										</td>
										<td class="zts-mmi-col-user">
											<input type="text" name="security_username[]" class="form-control" maxlength="32"
											       value="<?php echo htmlspecialchars($secRow['username'], ENT_QUOTES, 'UTF-8'); ?>"
											       placeholder="<?php echo htmlspecialchars($secRow['level'] === 'admin' ? _('admin') : _('user'), ENT_QUOTES, 'UTF-8'); ?>">
										</td>
										<td class="zts-pw-cell zts-mmi-col-pass">
											<?php $zts_password_input('security_password[]', $secRow['password']); ?>
										</td>
										<td class="zts-mmi-col-priv">
											<select name="security_level[]" class="form-control">
												<option value="admin"<?php echo ($secRow['level'] === 'admin' ? ' selected' : ''); ?>><?php echo _("Administrators"); ?></option>
												<option value="user"<?php echo ($secRow['level'] !== 'admin' ? ' selected' : ''); ?>><?php echo _("Users"); ?></option>
											</select>
										</td>
										<td class="zts-row-actions">
											<a href="#" class="zts-action-icon zts-action-delete zts-general-security-remove" title="<?php echo htmlspecialchars(_('Remove'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-trash-o"></i></a>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
				<?php
			});

			$zts_subsection(_('Default Time Settings'), htmlspecialchars(_('Default NTP, time zone, and daylight-saving values for new phones. Yealink and Fanvil provisioning values are mapped automatically when you save.'), ENT_QUOTES, 'UTF-8'), function () use ($zts_horizontal_table, $zts_default_time, $zts_ui_tz, $zts_ui_dst) {
				ob_start();
				echo '<select name="default_time_zone" class="form-control">';
				foreach (Zts_NetworkTimeSettingsMapper::uiTimeZoneOptions() as $tzVal => $tzLabel)
				{
					$sel = ((string) $tzVal === $zts_ui_tz) ? ' selected' : '';
					echo '<option value="'.htmlspecialchars((string) $tzVal, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'
						.htmlspecialchars((string) $tzLabel, ENT_QUOTES, 'UTF-8').'</option>';
				}
				echo '</select>';
				$timeZoneCell = ob_get_clean();

				ob_start();
				echo '<select name="default_daylight_saving_time" class="form-control">';
				foreach (Zts_NetworkTimeSettingsMapper::uiDaylightSavingOptions() as $dstVal => $dstLabel)
				{
					$sel = ((string) $dstVal === $zts_ui_dst) ? ' selected' : '';
					echo '<option value="'.htmlspecialchars((string) $dstVal, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'
						.htmlspecialchars((string) $dstLabel, ENT_QUOTES, 'UTF-8').'</option>';
				}
				echo '</select>';
				$dstCell = ob_get_clean();

				$zts_horizontal_table('zts-general-time-table', array(
					array(
						'label' => _('NTP Server 1'),
						'th_class' => 'zts-col-ntp',
						'cell' => '<input type="text" name="default_ntp_server1" class="form-control" value="'
							.htmlspecialchars(isset($zts_default_time['ntp_server1']) ? $zts_default_time['ntp_server1'] : '', ENT_QUOTES, 'UTF-8').'">',
					),
					array(
						'label' => _('NTP Server 2'),
						'th_class' => 'zts-col-ntp',
						'cell' => '<input type="text" name="default_ntp_server2" class="form-control" value="'
							.htmlspecialchars(isset($zts_default_time['ntp_server2']) ? $zts_default_time['ntp_server2'] : '', ENT_QUOTES, 'UTF-8').'">',
					),
					array('label' => _('Time Zone'), 'th_class' => 'zts-col-timezone', 'cell' => $timeZoneCell),
					array('label' => _('Daylight Saving Time'), 'th_class' => 'zts-col-dst', 'cell' => $dstCell),
				));
			});
			?>
		</div>
	</div>

	<div class="panel panel-default zts-edit-panel">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Line Key Templates (BLF / Speed Dial)'); ?></h3>
		</div>
		<div class="panel-body">
			<p class="help-block zts-section-lead"><?php echo _('Create named Line Key templates here for BLF and speed dial layouts. Each tab is a separate template: rename it, configure the keys, then save settings. On Edit Phone, select a template and click Apply to Line Keys to copy it into that phone; the phone is updated only after you save the phone form.'); ?></p>
			<?php require __DIR__.'/partials/zts_linekey_templates_panel.php'; ?>
		</div>
	</div>

	<div class="zts-form-actions clearfix">
		<button type="submit" class="btn btn-primary">
			<i class="fa fa-save"></i> <?php echo _("Save Settings"); ?>
		</button>
		<a href="<?php echo htmlspecialchars(Zts_ModuleIdentifiers::adminPageUrl('phones_list'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-default">
			<i class="fa fa-times"></i> <?php echo _("Cancel"); ?>
		</a>
	</div>
</form>

<?php require __DIR__.'/partials/zts_publisher_info.php'; ?>

</div>

<script>
(function () {
	var maxRows = <?php echo (int) $zts_security_max; ?>;
	var form = document.getElementById('zts-general-edit-form');
	var tbody = document.getElementById('zts-general-security-body');
	var addBtn = document.getElementById('zts-general-security-add-row');

	function rowCount() {
		return tbody ? tbody.querySelectorAll('.zts-general-security-row').length : 0;
	}

	function updateAddButton() {
		if (addBtn) {
			addBtn.disabled = rowCount() >= maxRows;
		}
	}

	if (!form) {
		return;
	}

	form.addEventListener('click', function (e) {
		var toggleBtn = e.target.closest ? e.target.closest('.zts-pw-toggle') : null;
		if (toggleBtn) {
			var group = toggleBtn.closest('.zts-pw-group');
			var input = group ? group.querySelector('.zts-pw-field') : null;
			if (input) {
				var show = input.type === 'password';
				input.type = show ? 'text' : 'password';
				var icon = toggleBtn.querySelector('i');
				if (icon) {
					icon.className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
				}
			}
			return;
		}
		var removeBtn = e.target.closest ? e.target.closest('.zts-general-security-remove') : null;
		if (removeBtn && tbody) {
			e.preventDefault();
			var row = removeBtn.closest('.zts-general-security-row');
			if (row && rowCount() > 1) {
				row.parentNode.removeChild(row);
				updateAddButton();
			}
		}
	});

	if (addBtn && tbody) {
		addBtn.addEventListener('click', function () {
			if (rowCount() >= maxRows) {
				return;
			}
			var first = tbody.querySelector('.zts-general-security-row');
			if (!first) {
				return;
			}
			var clone = first.cloneNode(true);
			var inputs = clone.querySelectorAll('input');
			for (var j = 0; j < inputs.length; j++) {
				if (inputs[j].name === 'security_username[]') {
					inputs[j].value = '';
					inputs[j].placeholder = '<?php echo htmlspecialchars(_('user'), ENT_QUOTES, 'UTF-8'); ?>';
				} else if (inputs[j].name === 'security_password[]') {
					inputs[j].value = '';
					inputs[j].type = 'password';
				}
			}
			var icon = clone.querySelector('.zts-pw-toggle i');
			if (icon) {
				icon.className = 'fa fa-eye';
			}
			var vendorSel = clone.querySelector('select[name="security_profile[]"]');
			if (vendorSel) {
				vendorSel.value = 'auto';
			}
			var levelSel = clone.querySelector('select[name="security_level[]"]');
			if (levelSel) {
				levelSel.value = 'user';
			}
			tbody.appendChild(clone);
			updateAddButton();
		});
	}

	updateAddButton();
})();
</script>
<script>
window._ztsLkTplPlaceholderName = <?php echo json_encode(Zts_LinekeyTemplateService::placeholderName()); ?>;
window._ztsLkTplMsgMinOne = <?php echo json_encode(_('At least one template is required.')); ?>;
window._ztsLkTplMsgMax = <?php echo json_encode(_('Maximum templates reached.')); ?>;
window._ztsLkTplMsgAdd = <?php echo json_encode(_('Add template')); ?>;
window._ztsLkTplMsgDelete = <?php echo json_encode(_('Delete this template?')); ?>;
window._ztsLkTplMsgRenameNew = <?php echo json_encode(sprintf(_('Rename the template "%s" before adding another.'), Zts_LinekeyTemplateService::placeholderName())); ?>;
window._ztsLkTplMsgDuplicate = <?php echo json_encode(_('Template names must be unique.')); ?>;
window._ztsLkTplMsgDupName = <?php echo json_encode(_('Duplicate template name: %s')); ?>;
</script>
