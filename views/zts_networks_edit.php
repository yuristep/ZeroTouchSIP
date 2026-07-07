<?php
/* Network Edit View */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

if (!class_exists('Zts_FanvilConfigVersionService', false))
{
	require_once dirname(__DIR__).'/includes/Zts/Service/FanvilConfigVersionService.php';
}

$isNew = !isset($_GET['edit']) || (string) $_GET['edit'] === '';

$zts_network_edit_form_action = Zts_ModuleIdentifiers::adminPageUrl('networks_edit', array(
	'edit' => isset($_GET['edit']) ? (string) $_GET['edit'] : '',
));

if (!isset($mmi_accounts) || !is_array($mmi_accounts) || count($mmi_accounts) < 1)
{
	$mmi_accounts = Zts_NetworkMmiAccountService::defaultRowsForForm();
}
if (!isset($network_edit_errors) || !is_array($network_edit_errors))
{
	$network_edit_errors = array();
}
$mmi_max = Zts_NetworkMmiAccountService::MAX_ACCOUNTS;
if (!isset($wifi_profiles) || !is_array($wifi_profiles) || count($wifi_profiles) < 1)
{
	$wifi_profiles = Zts_NetworkWifiProfileService::defaultRowsForForm();
}
$wifi_max = Zts_NetworkWifiProfileService::MAX_PROFILES;
if (!isset($codec_rows) || !is_array($codec_rows))
{
	$codec_rows = Zts_NetworkCodecMapper::editRows($network['settings']);
}

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
 * @param bool        $hideValueInField leave value in data-zts-stored only (MMI keep-password rows)
 * @return void
 */
$zts_password_input = function ($name, $value, $placeholder = '', $maxlength = null, $hideValueInField = false) {
	$ph = $placeholder !== '' ? ' placeholder="'.htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8').'"' : '';
	$ml = $maxlength !== null ? ' maxlength="'.(int) $maxlength.'"' : '';
	$stored = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	$fieldValue = $hideValueInField ? '' : $stored;
	$dataStored = ($hideValueInField && $value !== '') ? ' data-zts-stored="'.$stored.'"' : '';
	echo '<div class="input-group zts-pw-group">';
	echo '<input type="password" name="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'" class="form-control zts-pw-field" value="'
		.$fieldValue.'" autocomplete="new-password"'.$ml.$ph.$dataStored.'>';
	echo '<span class="input-group-addon zts-pw-toggle-wrap">';
	echo '<button type="button" class="zts-pw-toggle" title="'.htmlspecialchars(_('Show password'), ENT_QUOTES, 'UTF-8').'" tabindex="-1">';
	echo '<i class="fa fa-eye"></i></button></span></div>';
};

/**
 * Header row + single values row (FreePBX 17 horizontal parameter tables).
 *
 * @param string $title
 * @param string $tableId
 * @param array  $columns array<int,array{label:string,cell:string,th_class?:string}>
 * @param string $helpHtml optional HTML below table
 * @return void
 */
$zts_horizontal_table = function ($title, $tableId, array $columns, $helpHtml = '') use ($zts_fpbx_th) {
	echo '<h3>'.htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8').'</h3>';
	echo '<div class="bootstrap-table zts-bt-wrap zts-bt-wrap-edit">';
	echo '<div class="fixed-table-container" style="padding-bottom:0;"><div class="fixed-table-body">';
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
		echo '<p class="help-block">'.$helpHtml.'</p>';
	}
};

$zts_ui_tz = Zts_NetworkTimeSettingsMapper::uiTimeZoneSelected($network['settings']);
$zts_ui_dst = Zts_NetworkTimeSettingsMapper::uiDaylightSavingSelected($network['settings']);
?>

<h2><?php echo $isNew ? _("Add Network") : _("Edit Network"); ?></h2>

<?php if (count($network_edit_errors) > 0): ?>
<div class="alert alert-danger">
	<ul class="list-unstyled" style="margin:0;">
		<?php foreach ($network_edit_errors as $err): ?>
		<li><?php echo htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8'); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($zts_network_edit_form_action, ENT_QUOTES, 'UTF-8'); ?>" id="zts-network-edit-form">
	<input type="hidden" name="action" value="edit">

	<?php
	$zts_horizontal_table(_('Network Information'), 'zts-net-info-table', array(
		array(
			'label' => _('Network Name'),
			'th_class' => 'zts-col-medium',
			'cell' => '<input type="text" name="name" class="form-control" value="'
				.htmlspecialchars($network['name'], ENT_QUOTES, 'UTF-8').'" required>',
		),
		array(
			'label' => _('CIDR Range'),
			'th_class' => 'zts-col-medium',
			'cell' => '<input type="text" name="cidr" class="form-control" value="'
				.htmlspecialchars($network['cidr'], ENT_QUOTES, 'UTF-8').'" placeholder="'
				.htmlspecialchars(_('192.168.1.0/24'), ENT_QUOTES, 'UTF-8').'" required>',
		),
	), htmlspecialchars(_('IP range in CIDR notation (e.g., 192.168.1.0/24)'), ENT_QUOTES, 'UTF-8'));

	ob_start();
	echo '<select name="prov_protocol" class="form-control">';
	foreach (zts_dropdown('protocol') as $key => $value)
	{
		$sel = ($network['settings']['prov_protocol'] == $key) ? ' selected' : '';
		echo '<option value="'.htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'
			.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'</option>';
	}
	echo '</select>';
	$provProtocolCell = ob_get_clean();

	ob_start();
	$cfgVer = isset($network['settings']['fanvil_config_version'])
		? (string) $network['settings']['fanvil_config_version'] : Zts_FanvilConfigVersionService::DEFAULT_VERSION;
	echo '<input type="text" name="fanvil_config_version" class="form-control" value="'
		.htmlspecialchars($cfgVer, ENT_QUOTES, 'UTF-8').'" maxlength="16" placeholder="'
		.htmlspecialchars(Zts_FanvilConfigVersionService::DEFAULT_VERSION, ENT_QUOTES, 'UTF-8').'">';
	$provCfgVerCell = ob_get_clean();

	ob_start();
	echo '<input type="text" name="prov_username" class="form-control" value="'
		.htmlspecialchars($network['settings']['prov_username'], ENT_QUOTES, 'UTF-8').'">';
	$provUserCell = ob_get_clean();

	ob_start();
	$zts_password_input('prov_password', $network['settings']['prov_password']);
	$provPassCell = ob_get_clean();

	$zts_horizontal_table(_('Provisioning Settings'), 'zts-net-prov-table', array(
		array('label' => _('Protocol'), 'th_class' => 'zts-col-narrow', 'cell' => $provProtocolCell),
		array('label' => _('Config Version'), 'th_class' => 'zts-col-version', 'cell' => $provCfgVerCell),
		array('label' => _('Username'), 'th_class' => 'zts-col-medium', 'cell' => $provUserCell),
		array('label' => _('Password'), 'th_class' => 'zts-col-wide zts-pw-cell', 'cell' => $provPassCell),
	), htmlspecialchars(_('Fanvil: first line of MAC .cfg (Current Config Version on phone). Increase after template changes so handsets re-apply config. HTTP(S) provisioning: leave Username/Password blank to disable authentication.'), ENT_QUOTES, 'UTF-8'));
	?>

	<h3><?php echo _("Phone Web Interface Users"); ?></h3>
	<p class="help-block"><?php echo _("Pushed in provisioning for phones in this network: Fanvil &lt;MMI CONFIG MODULE&gt; / AccountN Name|Password|Level; Yealink security.user_name.* and security.user_password (login:password). Administrators map to admin, Users to user. If several networks match, the narrowest CIDR (/32) wins. Empty username skips a row; if no rows are saved, Yealink uses General Settings passwords."); ?></p>

	<div class="bootstrap-table zts-bt-wrap zts-bt-wrap-edit">
		<div class="fixed-table-container" style="padding-bottom:0;">
			<div class="fixed-table-body">
				<div id="zts-mmi-toolbar" class="zts-table-toolbar clearfix">
					<div class="pull-left">
						<button type="button" class="btn btn-default" id="zts-mmi-add-row"<?php echo (count($mmi_accounts) >= $mmi_max ? ' disabled' : ''); ?>>
							<i class="fa fa-plus"></i> <?php echo _("Add User"); ?>
						</button>
					</div>
				</div>
				<div class="table-responsive zts-table-responsive">
					<table id="zts-mmi-accounts-table" class="table table-striped table-bordered table-hover zts-fpbx-table">
						<thead>
							<tr>
								<?php $zts_fpbx_th(_('Username'), 'zts-mmi-col-user'); ?>
								<?php $zts_fpbx_th(_('Web Authentication Password'), 'zts-mmi-col-pass'); ?>
								<?php $zts_fpbx_th(_('Privilege'), 'zts-mmi-col-priv'); ?>
								<?php $zts_fpbx_th(_('Actions'), 'zts-actions-th'); ?>
							</tr>
						</thead>
						<tbody id="zts-mmi-accounts-body">
							<?php foreach ($mmi_accounts as $mmi_row): ?>
							<tr class="zts-mmi-row">
								<td class="zts-mmi-col-user">
									<input type="text" name="mmi_name[]" class="form-control" maxlength="32"
									       value="<?php echo htmlspecialchars($mmi_row['name'], ENT_QUOTES, 'UTF-8'); ?>"
									       placeholder="<?php echo htmlspecialchars(_('admin'), ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td class="zts-pw-cell zts-mmi-col-pass">
									<?php
									$zts_password_input(
										'mmi_password[]',
										$mmi_row['password'],
										$mmi_row['password'] !== '' ? _('leave blank to keep') : '',
										'32',
										true
									);
									?>
								</td>
								<td class="zts-mmi-col-priv">
									<select name="mmi_level[]" class="form-control">
										<option value="10"<?php echo ((string) $mmi_row['level'] === '10' ? ' selected' : ''); ?>><?php echo _("Administrators"); ?></option>
										<option value="5"<?php echo ((string) $mmi_row['level'] !== '10' ? ' selected' : ''); ?>><?php echo _("Users"); ?></option>
									</select>
								</td>
								<td class="zts-row-actions">
									<a href="#" class="zts-action-icon zts-action-delete zts-mmi-remove" title="<?php echo htmlspecialchars(_('Remove'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-trash-o"></i></a>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<h3><?php echo _("Wi-Fi Profiles"); ?></h3>
	<p class="help-block"><?php echo _("Define up to five Wi-Fi profiles (Networks Edit). Phone Edit selects which SSID to push. Fanvil W611/W611W: Secure Mode 0=None, 1=WPA/WPA2-PSK, 2=802.1x, 3=FT-PSK; Encryption 1=TKIP, 2=AES (CCMP), 3=TKIP+AES."); ?></p>

	<div class="bootstrap-table zts-bt-wrap zts-bt-wrap-edit">
		<div class="fixed-table-container" style="padding-bottom:0;">
			<div class="fixed-table-body">
				<div id="zts-wifi-toolbar" class="zts-table-toolbar clearfix">
					<div class="pull-left">
						<button type="button" class="btn btn-default" id="zts-wifi-add-row"<?php echo (count($wifi_profiles) >= $wifi_max ? ' disabled' : ''); ?>>
							<i class="fa fa-plus"></i> <?php echo _("Add Profile"); ?>
						</button>
					</div>
				</div>
				<div class="table-responsive zts-table-responsive">
					<table id="zts-wifi-profiles-table" class="table table-striped table-bordered table-hover zts-fpbx-table">
						<thead>
							<tr>
								<?php $zts_fpbx_th(_('Label'), 'zts-wifi-col-label'); ?>
								<?php $zts_fpbx_th(_('SSID'), 'zts-wifi-col-ssid'); ?>
								<?php $zts_fpbx_th(_('Secure Mode'), 'zts-wifi-col-mode'); ?>
								<?php $zts_fpbx_th(_('Encryption'), 'zts-wifi-col-enc'); ?>
								<?php $zts_fpbx_th(_('Username'), 'zts-wifi-col-user'); ?>
								<?php $zts_fpbx_th(_('Password'), 'zts-wifi-col-pass'); ?>
								<?php $zts_fpbx_th(_('Priority'), 'zts-wifi-col-pri'); ?>
								<?php $zts_fpbx_th(_('Actions'), 'zts-actions-th'); ?>
							</tr>
						</thead>
						<tbody id="zts-wifi-profiles-body">
							<?php foreach ($wifi_profiles as $wifi_row): ?>
							<tr class="zts-wifi-row">
								<td class="zts-wifi-col-label">
									<input type="text" name="wifi_label[]" class="form-control" maxlength="32"
									       value="<?php echo htmlspecialchars($wifi_row['label'], ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td class="zts-wifi-col-ssid">
									<input type="text" name="wifi_ssid[]" class="form-control" maxlength="32"
									       value="<?php echo htmlspecialchars($wifi_row['ssid'], ENT_QUOTES, 'UTF-8'); ?>"
									       placeholder="<?php echo htmlspecialchars(_('SSID'), ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td class="zts-wifi-col-mode">
									<select name="wifi_secure_mode[]" class="form-control zts-wifi-secure-mode">
										<?php foreach (Zts_NetworkWifiProfileService::secureModeOptions() as $modeKey => $modeLabel): ?>
										<option value="<?php echo htmlspecialchars($modeKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ((string) $wifi_row['secure_mode'] === $modeKey ? ' selected' : ''); ?>><?php echo htmlspecialchars($modeLabel, ENT_QUOTES, 'UTF-8'); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="zts-wifi-col-enc">
									<select name="wifi_encryption[]" class="form-control zts-wifi-encryption"<?php echo ((string) $wifi_row['secure_mode'] === Zts_NetworkWifiProfileService::SECURE_MODE_NONE ? ' disabled' : ''); ?>>
										<?php foreach (Zts_NetworkWifiProfileService::encryptionOptions() as $encKey => $encLabel): ?>
										<option value="<?php echo htmlspecialchars($encKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ((string) $wifi_row['encryption'] === $encKey ? ' selected' : ''); ?>><?php echo htmlspecialchars($encLabel, ENT_QUOTES, 'UTF-8'); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="zts-wifi-col-user">
									<input type="text" name="wifi_username[]" class="form-control" maxlength="64"
									       value="<?php echo htmlspecialchars($wifi_row['username'], ENT_QUOTES, 'UTF-8'); ?>"
									       placeholder="<?php echo htmlspecialchars(_('802.1X only'), ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td class="zts-pw-cell zts-wifi-col-pass">
									<?php
									$zts_password_input(
										'wifi_password[]',
										$wifi_row['password'],
										$wifi_row['password'] !== '' ? _('leave blank to keep') : '',
										'63',
										true
									);
									?>
								</td>
								<td class="zts-wifi-col-pri">
									<select name="wifi_priority[]" class="form-control">
										<?php for ($pri = 5; $pri >= 1; $pri--): ?>
										<option value="<?php echo $pri; ?>"<?php echo ((string) $wifi_row['priority'] === (string) $pri ? ' selected' : ''); ?>><?php echo $pri; ?></option>
										<?php endfor; ?>
									</select>
								</td>
								<td class="zts-row-actions">
									<a href="#" class="zts-action-icon zts-action-delete zts-wifi-remove" title="<?php echo htmlspecialchars(_('Remove'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-trash-o"></i></a>
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
	ob_start();
	echo '<select name="sip_server_transport" class="form-control">';
	foreach (zts_dropdown('transport') as $key => $value)
	{
		$sel = ($network['settings']['sip_server_transport'] == $key) ? ' selected' : '';
		echo '<option value="'.htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'
			.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'</option>';
	}
	echo '</select>';
	$sipTransportCell = ob_get_clean();

	$zts_horizontal_table(_('SIP Server Settings'), 'zts-net-sip-table', array(
		array(
			'label' => _('SIP Server Address'),
			'th_class' => 'zts-col-host',
			'cell' => '<input type="text" name="sip_server_address" class="form-control" value="'
				.htmlspecialchars($network['settings']['sip_server_address'], ENT_QUOTES, 'UTF-8').'" required>',
		),
		array(
			'label' => _('SIP Server Port'),
			'th_class' => 'zts-col-compact',
			'cell' => '<input type="number" name="sip_server_port" class="form-control" value="'
				.htmlspecialchars($network['settings']['sip_server_port'], ENT_QUOTES, 'UTF-8').'" required>',
		),
		array('label' => _('Transport'), 'th_class' => 'zts-col-narrow', 'cell' => $sipTransportCell),
		array(
			'label' => _('Registration Expires (seconds)'),
			'th_class' => 'zts-col-reg',
			'cell' => '<input type="number" name="sip_server_expires" class="form-control" value="'
				.htmlspecialchars($network['settings']['sip_server_expires'], ENT_QUOTES, 'UTF-8').'">',
		),
		array(
			'label' => _('NAT Keepalive Interval (seconds)'),
			'th_class' => 'zts-col-reg',
			'cell' => '<input type="number" name="nat_keepalive_interval" class="form-control" value="'
				.htmlspecialchars($network['settings']['nat_keepalive_interval'], ENT_QUOTES, 'UTF-8').'">',
		),
	), htmlspecialchars(_('NAT keepalive: 0 = disabled'), ENT_QUOTES, 'UTF-8'));

	ob_start();
	echo '<select name="time_zone" class="form-control">';
	foreach (Zts_NetworkTimeSettingsMapper::uiTimeZoneOptions() as $tzVal => $tzLabel)
	{
		$sel = ((string) $tzVal === $zts_ui_tz) ? ' selected' : '';
		echo '<option value="'.htmlspecialchars((string) $tzVal, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'
			.htmlspecialchars((string) $tzLabel, ENT_QUOTES, 'UTF-8').'</option>';
	}
	echo '</select>';
	$timeZoneCell = ob_get_clean();

	ob_start();
	echo '<select name="daylight_saving_time" class="form-control">';
	foreach (Zts_NetworkTimeSettingsMapper::uiDaylightSavingOptions() as $dstVal => $dstLabel)
	{
		$sel = ((string) $dstVal === $zts_ui_dst) ? ' selected' : '';
		echo '<option value="'.htmlspecialchars((string) $dstVal, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'
			.htmlspecialchars((string) $dstLabel, ENT_QUOTES, 'UTF-8').'</option>';
	}
	echo '</select>';
	$dstCell = ob_get_clean();

	$zts_horizontal_table(_('Time Settings'), 'zts-net-time-table', array(
		array(
			'label' => _('NTP Server 1'),
			'th_class' => 'zts-col-ntp',
			'cell' => '<input type="text" name="ntp_server1" class="form-control" value="'
				.htmlspecialchars($network['settings']['ntp_server1'], ENT_QUOTES, 'UTF-8').'">',
		),
		array(
			'label' => _('NTP Server 2'),
			'th_class' => 'zts-col-ntp',
			'cell' => '<input type="text" name="ntp_server2" class="form-control" value="'
				.htmlspecialchars($network['settings']['ntp_server2'], ENT_QUOTES, 'UTF-8').'">',
		),
		array(
			'label' => _('Time Zone'),
			'th_class' => 'zts-col-timezone',
			'cell' => $timeZoneCell,
		),
		array(
			'label' => _('Daylight Saving Time'),
			'th_class' => 'zts-col-dst',
			'cell' => $dstCell,
		),
	));
	?>

	<h3><?php echo _("Codec Settings"); ?></h3>
	<p class="help-block"><?php echo _("Codecs enabled in FreePBX General SIP Settings (Settings → SIP Settings → Codecs). Set phone priority per codec (0 = disabled on handset, 1–16 = lower number is higher priority). Yealink and Fanvil values are applied during provisioning."); ?></p>

	<div class="bootstrap-table zts-bt-wrap zts-bt-wrap-edit">
		<div class="fixed-table-container" style="padding-bottom:0;">
			<div class="fixed-table-body">
				<div class="table-responsive zts-table-responsive">
					<table id="zts-codec-table" class="table table-striped table-bordered table-hover zts-fpbx-table">
						<thead>
							<tr>
								<?php $zts_fpbx_th(_('Codec')); ?>
								<?php $zts_fpbx_th(_('Priority')); ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($codec_rows as $codec_row): ?>
							<tr>
								<td><?php echo htmlspecialchars($codec_row['label'], ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<input type="number" name="<?php echo htmlspecialchars($codec_row['priority_key'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control" min="0" max="16"
									       value="<?php echo (int) $codec_row['priority']; ?>">
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="form-group" style="margin-top:15px;">
		<button type="submit" class="btn btn-primary">
			<i class="fa fa-save"></i> <?php echo _("Save"); ?>
		</button>
		<a href="<?php echo htmlspecialchars(Zts_ModuleIdentifiers::adminPageUrl('networks_list'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-default">
			<i class="fa fa-times"></i> <?php echo _("Cancel"); ?>
		</a>
	</div>
</form>

<script>
(function () {
	var maxRows = <?php echo (int) $mmi_max; ?>;
	var form = document.getElementById('zts-network-edit-form');
	var tbody = document.getElementById('zts-mmi-accounts-body');
	var addBtn = document.getElementById('zts-mmi-add-row');

	function rowCount() {
		return tbody ? tbody.querySelectorAll('.zts-mmi-row').length : 0;
	}

	function updateAddButton() {
		if (addBtn) {
			addBtn.disabled = rowCount() >= maxRows;
		}
	}

	if (form) {
		form.addEventListener('click', function (e) {
			var toggleBtn = e.target.closest ? e.target.closest('.zts-pw-toggle') : null;
			if (toggleBtn) {
				var group = toggleBtn.closest('.zts-pw-group');
				var input = group ? group.querySelector('.zts-pw-field') : null;
				if (input) {
					var show = input.type === 'password';
					if (show && input.value === '' && input.getAttribute('data-zts-stored')) {
						input.value = input.getAttribute('data-zts-stored');
					}
					input.type = show ? 'text' : 'password';
					var icon = toggleBtn.querySelector('i');
					if (icon) {
						icon.className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
					}
				}
				return;
			}
			var removeBtn = e.target.closest ? e.target.closest('.zts-mmi-remove') : null;
			if (removeBtn && tbody) {
				e.preventDefault();
				var row = removeBtn.closest('.zts-mmi-row');
				if (row && rowCount() > 1) {
					row.parentNode.removeChild(row);
					updateAddButton();
				}
			}
		});
	}

	if (addBtn && tbody) {
		addBtn.addEventListener('click', function () {
			if (rowCount() >= maxRows) {
				return;
			}
			var first = tbody.querySelector('.zts-mmi-row');
			if (!first) {
				return;
			}
			var clone = first.cloneNode(true);
			var inputs = clone.querySelectorAll('input');
			for (var j = 0; j < inputs.length; j++) {
				if (inputs[j].name === 'mmi_name[]') {
					inputs[j].value = '';
				} else if (inputs[j].name === 'mmi_password[]') {
					inputs[j].value = '';
					inputs[j].removeAttribute('data-zts-stored');
					inputs[j].placeholder = '';
					inputs[j].type = 'password';
				}
			}
			var icon = clone.querySelector('.zts-pw-toggle i');
			if (icon) {
				icon.className = 'fa fa-eye';
			}
			var sel = clone.querySelector('select[name="mmi_level[]"]');
			if (sel) {
				sel.value = '5';
			}
			tbody.appendChild(clone);
			updateAddButton();
		});
	}

	updateAddButton();
})();
</script>

<script>
(function () {
	var maxRows = <?php echo (int) $wifi_max; ?>;
	var form = document.getElementById('zts-network-edit-form');
	var tbody = document.getElementById('zts-wifi-profiles-body');
	var addBtn = document.getElementById('zts-wifi-add-row');
	var secureNone = '<?php echo Zts_NetworkWifiProfileService::SECURE_MODE_NONE; ?>';
	var encAes = '<?php echo Zts_NetworkWifiProfileService::ENCRYPTION_AES; ?>';

	function syncWifiEncryptionRow(row) {
		if (!row) {
			return;
		}
		var modeSel = row.querySelector('.zts-wifi-secure-mode');
		var encSel = row.querySelector('.zts-wifi-encryption');
		if (!modeSel || !encSel) {
			return;
		}
		if (modeSel.value === secureNone) {
			encSel.value = '0';
			encSel.disabled = true;
		} else {
			encSel.disabled = false;
			if (encSel.value === '0') {
				encSel.value = encAes;
			}
		}
	}

	function rowCount() {
		return tbody ? tbody.querySelectorAll('.zts-wifi-row').length : 0;
	}

	function updateAddButton() {
		if (addBtn) {
			addBtn.disabled = rowCount() >= maxRows;
		}
	}

	if (form) {
		form.addEventListener('submit', function () {
			if (!tbody) {
				return;
			}
			var encSels = tbody.querySelectorAll('.zts-wifi-encryption');
			for (var k = 0; k < encSels.length; k++) {
				encSels[k].disabled = false;
			}
		});
		form.addEventListener('change', function (e) {
			if (e.target && e.target.classList && e.target.classList.contains('zts-wifi-secure-mode')) {
				syncWifiEncryptionRow(e.target.closest('.zts-wifi-row'));
			}
		});
		form.addEventListener('click', function (e) {
			var removeBtn = e.target.closest ? e.target.closest('.zts-wifi-remove') : null;
			if (removeBtn && tbody) {
				e.preventDefault();
				var row = removeBtn.closest('.zts-wifi-row');
				if (row && rowCount() > 1) {
					row.parentNode.removeChild(row);
					updateAddButton();
				}
			}
		});
	}

	if (addBtn && tbody) {
		addBtn.addEventListener('click', function () {
			if (rowCount() >= maxRows) {
				return;
			}
			var first = tbody.querySelector('.zts-wifi-row');
			if (!first) {
				return;
			}
			var clone = first.cloneNode(true);
			var inputs = clone.querySelectorAll('input');
			for (var j = 0; j < inputs.length; j++) {
				if (inputs[j].name === 'wifi_label[]' || inputs[j].name === 'wifi_ssid[]' || inputs[j].name === 'wifi_username[]') {
					inputs[j].value = '';
				} else if (inputs[j].name === 'wifi_password[]') {
					inputs[j].value = '';
					inputs[j].removeAttribute('data-zts-stored');
					inputs[j].placeholder = '';
					inputs[j].type = 'password';
				}
			}
			var icon = clone.querySelector('.zts-pw-toggle i');
			if (icon) {
				icon.className = 'fa fa-eye';
			}
			var modeSel = clone.querySelector('.zts-wifi-secure-mode');
			if (modeSel) {
				modeSel.value = '<?php echo Zts_NetworkWifiProfileService::SECURE_MODE_WPA_PSK; ?>';
			}
			var encSel = clone.querySelector('.zts-wifi-encryption');
			if (encSel) {
				encSel.value = encAes;
				encSel.disabled = false;
			}
			var priSel = clone.querySelector('select[name="wifi_priority[]"]');
			if (priSel) {
				priSel.value = '5';
			}
			syncWifiEncryptionRow(clone);
			tbody.appendChild(clone);
			updateAddButton();
		});
	}

	if (tbody) {
		var rows = tbody.querySelectorAll('.zts-wifi-row');
		for (var i = 0; i < rows.length; i++) {
			syncWifiEncryptionRow(rows[i]);
		}
	}

	updateAddButton();
})();
</script>
