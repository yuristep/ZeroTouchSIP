<?php
/* Phone Edit View */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$isNew = empty($_GET['edit']);
$edit_fanvil = !$isNew && (zts_phones_edit_ui_is_fanvil($device)
	|| (isset($device['settings']['provisioning_profile']) && $device['settings']['provisioning_profile'] === 'fanvil'));
$line_config_max = $edit_fanvil ? 2 : 16;
$linekey_line_max = $edit_fanvil ? 2 : 16;

$zts_phones_edit_form_action = Zts_ModuleIdentifiers::adminPageUrl('phones_edit', array(
	'edit' => isset($_GET['edit']) ? (string) $_GET['edit'] : '',
));
$zts_phones_list_url_full = 'config.php?type=setup&display=zerotouchsip&zerotouchsip_form=phones_list';
?>

<h2><?php echo $isNew ? _("Add Phone") : _("Edit Phone"); ?></h2>

<form method="post" action="<?php echo htmlspecialchars($zts_phones_edit_form_action, ENT_QUOTES, 'UTF-8'); ?>">
	<input type="hidden" name="action" value="edit">

	<!-- General Information -->
	<h3><?php echo _("General Information"); ?></h3>
	<div class="form-group">
		<label><?php echo _("Phone Name"); ?></label>
		<input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($device['name']); ?>" required>
		<span class="help-block"><?php echo _("Descriptive name; after a line is assigned, saved as {extension}-{Model}-{last 4 MAC chars} (e.g. 6101-T33-A1B2) unless you change it manually. Used as DHCP Hostname in provisioning."); ?></span>
	</div>

	<div class="form-group">
		<label><?php echo _("MAC Address"); ?></label>
		<input type="text" name="mac" class="form-control" value="<?php echo htmlspecialchars($device['mac']); ?>"
		       pattern="[0-9A-Fa-f]{12}" maxlength="12" required
		       placeholder="<?php echo _("12 hex digits, no separators (e.g., 805EC0123456)"); ?>">
		<span class="help-block"><?php echo _("MAC address without colons or dashes"); ?></span>
	</div>

	<div class="form-group">
		<label><?php echo _("Provisioning Profile"); ?></label>
		<?php $profile = isset($device['settings']['provisioning_profile']) ? $device['settings']['provisioning_profile'] : 'auto'; ?>
		<select name="provisioning_profile" class="form-control">
			<option value="auto"<?php echo ($profile === 'auto' ? ' selected' : ''); ?>><?php echo _("Auto Detect"); ?></option>
			<option value="yealink"<?php echo ($profile === 'yealink' ? ' selected' : ''); ?>><?php echo _("Yealink"); ?></option>
			<option value="fanvil"<?php echo ($profile === 'fanvil' ? ' selected' : ''); ?>><?php echo _("Fanvil"); ?></option>
		</select>
		<span class="help-block"><?php echo _("Auto or Fanvil for Fanvil H2U/H5/H6W/W611W. Do not choose Yealink for those models - the server would send Yealink text format and the phone will ignore it; Yealink is overridden to Fanvil for H-series models only when this mistake is made."); ?></span>
	</div>

	<?php if(!$isNew): ?>
	<div class="well well-sm">
		<strong><?php echo _("Model:"); ?></strong> <?php echo htmlspecialchars($device['model']); ?><br>
		<strong><?php echo _("Firmware:"); ?></strong> <?php echo htmlspecialchars($device['firmware_version']); ?><br>
		<strong><?php echo _("Last Config:"); ?></strong> <?php echo htmlspecialchars($device['lastconfig']); ?><br>
		<strong><?php echo _("Last IP:"); ?></strong> <?php echo htmlspecialchars($device['lastip']); ?>
	</div>
	<?php endif; ?>

	<?php
	if (!isset($wifi_profile_options) || !is_array($wifi_profile_options))
	{
		$wifi_profile_options = array();
	}
	if (!isset($wifi_network) || !is_array($wifi_network))
	{
		$wifi_network = array();
	}
	$zts_show_wifi = Zts_DeviceWifiSettingsService::showWifiSection($device, $isNew);
	$zts_wifi_enable = isset($device['settings'][Zts_DeviceWifiSettingsService::KEY_ENABLE])
		&& (string) $device['settings'][Zts_DeviceWifiSettingsService::KEY_ENABLE] === '1';
	$zts_wifi_push = isset($device['settings'][Zts_DeviceWifiSettingsService::KEY_PUSH])
		&& (string) $device['settings'][Zts_DeviceWifiSettingsService::KEY_PUSH] === '1';
	$zts_wifi_profile_ssid = isset($device['settings'][Zts_DeviceWifiSettingsService::KEY_PROFILE_SSID])
		? (string) $device['settings'][Zts_DeviceWifiSettingsService::KEY_PROFILE_SSID] : '';
	$zts_wifi_network_label = isset($wifi_network['name']) && (string) $wifi_network['name'] !== ''
		? (string) $wifi_network['name'] : _('Default Network');
	?>
	<?php if ($zts_show_wifi): ?>
	<h3><?php echo _("Wi-Fi"); ?></h3>
	<p class="help-block"><?php echo sprintf(
		_("Provisioning network: %s (matched by last IP). Profiles are defined under Networks Edit. When Push is off, Fanvil W611 keeps locally configured Wi-Fi."),
		htmlspecialchars($zts_wifi_network_label, ENT_QUOTES, 'UTF-8')
	); ?></p>
	<table class="table table-bordered zts-phone-wifi-table">
		<thead>
			<tr>
				<th class="zts-wifi-col-enable"><?php echo _("Enable Wi-Fi"); ?></th>
				<th class="zts-wifi-col-push"><?php echo _("Push profile to phone"); ?></th>
				<th class="zts-wifi-col-profile"><?php echo _("Wi-Fi profile"); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="zts-wifi-col-enable text-center">
					<input type="checkbox" name="<?php echo Zts_DeviceWifiSettingsService::KEY_ENABLE; ?>" value="1"<?php echo ($zts_wifi_enable ? ' checked' : ''); ?>>
				</td>
				<td class="zts-wifi-col-push text-center">
					<input type="checkbox" name="<?php echo Zts_DeviceWifiSettingsService::KEY_PUSH; ?>" value="1"<?php echo ($zts_wifi_push ? ' checked' : ''); ?>>
				</td>
				<td class="zts-wifi-col-profile">
					<select name="<?php echo Zts_DeviceWifiSettingsService::KEY_PROFILE_SSID; ?>" class="form-control">
						<option value=""><?php echo htmlspecialchars(_('— select SSID —'), ENT_QUOTES, 'UTF-8'); ?></option>
						<?php foreach ($wifi_profile_options as $wifi_opt): ?>
						<option value="<?php echo htmlspecialchars($wifi_opt['value'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($zts_wifi_profile_ssid === $wifi_opt['value'] ? ' selected' : ''); ?>>
							<?php echo htmlspecialchars($wifi_opt['label'], ENT_QUOTES, 'UTF-8'); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<?php if (count($wifi_profile_options) < 1): ?>
					<p class="help-block" style="margin:8px 0 0;"><?php echo _("No Wi-Fi profiles on this network. Add profiles in Networks Edit first."); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
	<?php endif; ?>

	<!-- Line Configuration -->
	<h3><?php echo _("Line Configuration"); ?></h3>
	<p class="help-block"><?php echo $edit_fanvil
		? _("Assign FreePBX devices to phone lines (Fanvil H2U/H5/H6W/W611W: up to 2 SIP lines).")
		: _("Assign FreePBX devices to phone lines (up to 16 lines supported)"); ?></p>

	<?php
	$zts_ln_instance = 'zts-ln-phone';
	$zts_ln_max = $line_config_max;
	$zts_ln_lines = isset($device['lines']) && is_array($device['lines']) ? $device['lines'] : array();
	$zts_ln_dropdown = zts_dropdown_lines(isset($_GET['edit']) ? $_GET['edit'] : '');
	$zts_ln_visible = Zts_DeviceEditService::linesVisibleCount($zts_ln_lines, $zts_ln_max);
	require __DIR__.'/partials/zts_lines_editor.php';
	?>

	<?php if ($edit_fanvil): ?>
	<h3><?php echo _("Hotline"); ?></h3>
	<p class="help-block"><?php echo _("Per-SIP-line hotline / warm line (Fanvil). Shown in config as SIP1/SIP2 Hotline Num, Enable Hotline, WarmLine Time."); ?></p>
	<table class="table table-bordered">
		<thead>
			<tr>
				<th style="width: 10%;"><?php echo _("Line"); ?></th>
				<th style="width: 18%;"><?php echo _("Enable Hotline"); ?></th>
				<th style="width: 18%;"><?php echo _("Hotline Delay"); ?></th>
				<th style="width: 54%;"><?php echo _("Hotline Number"); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php for ($hl = 1; $hl <= 2; $hl++):
				$_hlv = zts_fanvil_hotline_row_values($device, $hl);
			?>
				<tr>
					<td><?php echo (int) $hl; ?></td>
					<td class="text-center">
						<input type="checkbox" name="hotline_enable[<?php echo $hl; ?>]" value="1"<?php echo ($_hlv['enable'] === '1' ? ' checked="checked"' : ''); ?>>
					</td>
					<td>
						<input type="text" class="form-control shortinput" name="hotline_delay[<?php echo $hl; ?>]" maxlength="2"
						       value="<?php echo htmlspecialchars((string) $_hlv['delay']); ?>"
						       placeholder="<?php echo htmlspecialchars(_('0'), ENT_QUOTES, 'UTF-8'); ?>">
					</td>
					<td>
						<input type="text" name="hotline_number[<?php echo $hl; ?>]" class="form-control text" maxlength="39"
						       value="<?php echo htmlspecialchars($_hlv['number']); ?>"
						       placeholder="<?php echo _("e.g. 2100"); ?>">
					</td>
				</tr>
			<?php endfor; ?>
		</tbody>
	</table>
	<p class="help-block"><?php echo _("Hotline delay: 0-30 seconds (WarmLine time)."); ?></p>
	<?php endif; ?>

	<!-- Line Keys (BLF/Speed Dial) -->
	<h3><?php echo _("Line Keys (BLF / Speed Dial)"); ?></h3>
	<p class="text-muted" style="margin-bottom:16px;"><?php echo _("Configure programmable keys for BLF monitoring, speed dial, etc."); ?></p>

	<?php
	if (!isset($zts_linekey_templates) || !is_array($zts_linekey_templates))
	{
		$zts_linekey_templates = Zts_LinekeyTemplateService::fromGeneral(Zts_GeneralSettingsService::load());
	}
	$zts_lk_phone_keys = isset($device['linekeys']) && is_array($device['linekeys']) ? $device['linekeys'] : array();
	$zts_lk_instance = 'zts-lk-phone';
	$zts_lk_field_base = 'linekey';
	$zts_lk_keys = Zts_LinekeyTemplateService::normalizeKeysMap($zts_lk_phone_keys);
	$zts_lk_line_max = $linekey_line_max;
	$zts_lk_types = zts_dropdown_linekey_types();
	$zts_lk_visible = Zts_DeviceEditService::linekeysVisibleCount($zts_lk_keys);
	?>

	<?php if (count($zts_linekey_templates) > 0): ?>
	<div class="form-inline zts-lk-apply-bar" id="zts-lk-apply-bar" style="margin-bottom:16px;">
		<label for="zts-lk-template-select" class="control-label" style="margin-right:8px;"><?php echo _('Apply template'); ?></label>
		<select id="zts-lk-template-select" class="form-control" style="min-width:220px; margin-right:8px;">
			<option value=""><?php echo htmlspecialchars(_('Select template'), ENT_QUOTES, 'UTF-8'); ?></option>
			<?php foreach (Zts_LinekeyTemplateService::choicesForSelect($zts_linekey_templates) as $zts_lk_choice): ?>
			<option value="<?php echo htmlspecialchars((string) $zts_lk_choice['id'], ENT_QUOTES, 'UTF-8'); ?>">
				<?php echo htmlspecialchars((string) $zts_lk_choice['name'], ENT_QUOTES, 'UTF-8'); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<button type="button" class="btn btn-default" id="zts-lk-template-apply">
			<i class="fa fa-magic"></i> <?php echo _('Apply to Line Keys'); ?>
		</button>
		<span class="help-block" style="margin:8px 0 0;"><?php echo _('Templates are configured under General Settings - Line Key Templates. Applying replaces current Line Keys on this form (save the phone to persist).'); ?></span>
	</div>
	<?php endif; ?>

	<?php require __DIR__.'/partials/zts_linekeys_editor.php'; ?>

	<!-- Form Actions -->
	<div class="form-group zts-phone-edit-form-actions">
		<button type="submit" class="btn btn-primary">
			<i class="fa fa-save"></i> <?php echo _("Save"); ?>
		</button>
		<a href="<?php echo htmlspecialchars($zts_phones_list_url_full, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-default">
			<i class="fa fa-times"></i> <?php echo _("Cancel"); ?>
		</a>
	</div>
</form>

<style>
.zts-phone-edit-form-actions {
	margin-top: 8px;
	margin-bottom: 0;
}
.zts-phone-wifi-table {
	table-layout: fixed;
	width: 100%;
	max-width: 960px;
}
.zts-phone-wifi-table thead th,
.zts-phone-wifi-table tbody td {
	vertical-align: middle;
}
.zts-phone-wifi-table .zts-wifi-col-enable,
.zts-phone-wifi-table .zts-wifi-col-push {
	width: 18%;
	min-width: 120px;
	text-align: center;
}
.zts-phone-wifi-table .zts-wifi-col-profile {
	width: 64%;
	min-width: 220px;
}
</style>
<?php if (count($zts_linekey_templates) > 0): ?>
<script>
window.ZTS_LINEKEY_TEMPLATES = <?php echo Zts_LinekeyTemplateService::toJsonForClient($zts_linekey_templates); ?>;
window.ZTS_LK_PHONE_EDITOR_OPTS = {
	instanceId: <?php echo json_encode($zts_lk_instance); ?>,
	maxKeys: <?php echo (int) Zts_DeviceEditService::LINEKEY_MAX; ?>,
	defaultVisible: <?php echo (int) Zts_DeviceEditService::LINEKEY_DEFAULT_VISIBLE; ?>
};
window.ZtsLinekeysWhenReady(function () {
	if (!window.ZtsLinekeysEditor || !window.ZTS_LINEKEY_TEMPLATES) {
		return;
	}
	var opts = window.ZTS_LK_PHONE_EDITOR_OPTS;
	window.ZtsLinekeysEditor.ensure(opts);

	$('#zts-lk-template-apply').off('click.ztsLkApply').on('click.ztsLkApply', function () {
		var id = $('#zts-lk-template-select').val();
		if (!id) {
			return;
		}
		var tpl = null;
		for (var i = 0; i < window.ZTS_LINEKEY_TEMPLATES.length; i++) {
			if (String(window.ZTS_LINEKEY_TEMPLATES[i].id) === String(id)) {
				tpl = window.ZTS_LINEKEY_TEMPLATES[i];
				break;
			}
		}
		if (!tpl || !tpl.keys) {
			return;
		}
		var ed = window.ZtsLinekeysEditor.ensure(opts);
		if (ed) {
			ed.applyKeysMap(tpl.keys);
		}
	});
});
</script>
<?php endif; ?>
