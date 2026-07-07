<?php
/**
 * Reusable Line Keys editor (phones_edit + general template tab).
 *
 * Expected vars:
 *   $zts_lk_instance (string) DOM id prefix, e.g. zts-lk-phone, zts-lk-tpl_tpl_1
 *   $zts_lk_field_base (string) '' for phone (linekey_type[]) or linekey_tpl[id]
 *   $zts_lk_keys (array<int,array>) keys 1..27
 *   $zts_lk_line_max (int)
 *   $zts_lk_types (array) linekey type dropdown
 *   $zts_lk_visible (int) optional, computed if omitted
 */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

if (!isset($zts_lk_instance) || (string) $zts_lk_instance === '')
{
	return;
}
$zts_lk_field_base = isset($zts_lk_field_base) ? (string) $zts_lk_field_base : '';
$zts_lk_keys = isset($zts_lk_keys) && is_array($zts_lk_keys) ? $zts_lk_keys : Zts_LinekeyTemplateService::emptyKeysMap();
$zts_lk_line_max = isset($zts_lk_line_max) ? (int) $zts_lk_line_max : 16;
$zts_lk_types = isset($zts_lk_types) && is_array($zts_lk_types) ? $zts_lk_types : zts_dropdown_linekey_types();
$zts_lk_max = Zts_DeviceEditService::LINEKEY_MAX;
$zts_lk_visible = isset($zts_lk_visible) ? (int) $zts_lk_visible : Zts_LinekeyTemplateService::visibleKeyCount($zts_lk_keys);
$zts_lk_tpl_toolbar = !empty($zts_lk_tpl_toolbar);
$zts_lk_tpl_id = isset($zts_lk_tpl_id) ? (string) $zts_lk_tpl_id : '';
$zts_lk_tpl_name = isset($zts_lk_tpl_name) ? (string) $zts_lk_tpl_name : '';
$zts_lk_form_disabled = !empty($zts_lk_form_disabled);
if (!isset($zts_lk_tpl_toolbar))
{
	$zts_lk_tpl_toolbar = false;
}

if (!function_exists('zts_linekeys_input_name'))
{
	/**
	 * @param string $base
	 * @param string $field type|line|value|label|extension|pickup
	 * @return string
	 */
	function zts_linekeys_input_name($base, $field)
	{
		if ($base === '' || $base === 'linekey')
		{
			return 'linekey_'.$field.'[]';
		}

		return $base.'['.$field.'][]';
	}
}
?>
<div id="<?php echo htmlspecialchars($zts_lk_instance, ENT_QUOTES, 'UTF-8'); ?>-container" class="bootstrap-table zts-bt-wrap zts-bt-wrap-edit zts-linekeys-editor-wrap">
	<?php if ($zts_lk_form_disabled): ?><fieldset disabled="disabled" class="zts-lk-editor-prototype-fieldset"><?php endif; ?>
	<div class="fixed-table-container" style="padding-bottom:0;">
		<div class="fixed-table-body">
			<div id="<?php echo htmlspecialchars($zts_lk_instance, ENT_QUOTES, 'UTF-8'); ?>-toolbar" class="zts-table-toolbar clearfix">
				<div class="pull-left zts-lk-toolbar-keys">
					<button type="button" class="btn btn-primary" id="<?php echo htmlspecialchars($zts_lk_instance, ENT_QUOTES, 'UTF-8'); ?>-btn-add"<?php echo ($zts_lk_visible >= $zts_lk_max ? ' disabled' : ''); ?>>
						<i class="fa fa-plus"></i> <?php echo _("Add Keys"); ?>
					</button>
					<button type="button" class="btn btn-primary" id="<?php echo htmlspecialchars($zts_lk_instance, ENT_QUOTES, 'UTF-8'); ?>-btn-edit" disabled="disabled">
						<i class="fa fa-pencil"></i> <span><?php echo _("Edit"); ?></span>
					</button>
					<button type="button" class="btn btn-danger btn-remove" id="<?php echo htmlspecialchars($zts_lk_instance, ENT_QUOTES, 'UTF-8'); ?>-btn-delete" disabled="disabled">
						<i class="fa fa-trash"></i> <span><?php echo _("Delete"); ?></span>
					</button>
				</div>
				<?php if ($zts_lk_tpl_toolbar && $zts_lk_tpl_id !== ''): ?>
				<div class="pull-left zts-lk-toolbar-tpl">
					<?php if (!empty($zts_lk_tpl_is_draft)): ?>
					<input type="hidden" class="zts-lk-tpl-is-draft" name="linekey_tpl[<?php echo htmlspecialchars($zts_lk_tpl_id, ENT_QUOTES, 'UTF-8'); ?>][is_draft]" value="1">
					<?php endif; ?>
					<label class="zts-lk-toolbar-tpl-label" for="<?php echo htmlspecialchars($zts_lk_instance, ENT_QUOTES, 'UTF-8'); ?>-tpl-name"><?php echo _('Template name'); ?></label>
					<input type="text" class="form-control input-sm zts-lk-tpl-name-input" id="<?php echo htmlspecialchars($zts_lk_instance, ENT_QUOTES, 'UTF-8'); ?>-tpl-name"
					       name="linekey_tpl[<?php echo htmlspecialchars($zts_lk_tpl_id, ENT_QUOTES, 'UTF-8'); ?>][name]"
					       value="<?php echo htmlspecialchars($zts_lk_tpl_name, ENT_QUOTES, 'UTF-8'); ?>"
					       maxlength="64" placeholder="<?php echo htmlspecialchars(_('Template name'), ENT_QUOTES, 'UTF-8'); ?>"
					       <?php echo $zts_lk_form_disabled ? ' disabled="disabled"' : ''; ?>>
					<button type="button" class="btn btn-default btn-sm zts-lk-tpl-rename" title="<?php echo htmlspecialchars(_('Apply name to tab'), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $zts_lk_form_disabled ? ' disabled="disabled"' : ''; ?>>
						<i class="fa fa-check"></i> <span><?php echo _('Apply'); ?></span>
					</button>
					<button type="button" class="btn btn-danger btn-sm zts-lk-tpl-delete-pane" title="<?php echo htmlspecialchars(_('Delete template'), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $zts_lk_form_disabled ? ' disabled="disabled"' : ''; ?>>
						<i class="fa fa-trash"></i> <span><?php echo _('Delete template'); ?></span>
					</button>
				</div>
				<?php endif; ?>
			</div>
			<div class="table-responsive zts-table-responsive">
				<table id="<?php echo htmlspecialchars($zts_lk_instance, ENT_QUOTES, 'UTF-8'); ?>-table" class="table table-striped table-bordered table-hover zts-linekeys-table">
					<thead>
						<tr>
							<th class="bs-checkbox" data-field="state" data-checkbox="true">
								<div class="th-inner">
									<input name="btSelectAll" type="checkbox" class="btSelectAll" title="<?php echo htmlspecialchars(_('Select all'), ENT_QUOTES, 'UTF-8'); ?>">
								</div>
								<div class="fht-cell"></div>
							</th>
							<th><div class="th-inner"><?php echo _("Key"); ?></div><div class="fht-cell"></div></th>
							<th><div class="th-inner"><?php echo _("Type"); ?></div><div class="fht-cell"></div></th>
							<th><div class="th-inner"><?php echo _("Line"); ?></div><div class="fht-cell"></div></th>
							<th><div class="th-inner"><?php echo _("Value/Extension"); ?></div><div class="fht-cell"></div></th>
							<th><div class="th-inner"><?php echo _("Label"); ?></div><div class="fht-cell"></div></th>
							<th><div class="th-inner"><?php echo _("Extension"); ?></div><div class="fht-cell"></div></th>
							<th><div class="th-inner"><?php echo _("Pickup"); ?></div><div class="fht-cell"></div></th>
							<th class="zts-actions-th">
								<div class="th-inner"><?php echo _("Actions"); ?></div>
								<div class="fht-cell"></div>
							</th>
						</tr>
					</thead>
					<tbody id="<?php echo htmlspecialchars($zts_lk_instance, ENT_QUOTES, 'UTF-8'); ?>-body">
						<?php for ($i = 1; $i <= $zts_lk_max; $i++):
							$linekey = isset($zts_lk_keys[$i]) && is_array($zts_lk_keys[$i])
								? Zts_LinekeyTemplateService::normalizeKeyRow($zts_lk_keys[$i])
								: Zts_LinekeyTemplateService::emptyKeyRow();
							$zts_lk_extra = ($i > Zts_DeviceEditService::LINEKEY_DEFAULT_VISIBLE);
							$zts_lk_hidden = $zts_lk_extra && ($i > $zts_lk_visible);
							$zts_lk_row_class = 'zts-linekey-row'.($zts_lk_extra ? ' zts-linekey-extra' : '');
						?>
						<tr class="<?php echo htmlspecialchars($zts_lk_row_class, ENT_QUOTES, 'UTF-8'); ?>"
						    data-linekey-id="<?php echo (int) $i; ?>"
						    <?php echo $zts_lk_hidden ? ' style="display:none;"' : ''; ?>>
							<td class="bs-checkbox">
								<input type="checkbox" class="btSelectItem" value="<?php echo (int) $i; ?>"
									<?php echo $zts_lk_extra ? '' : ' data-zts-linekey-fixed="1"'; ?>>
							</td>
							<td class="zts-lk-col-key"><?php echo (int) $i; ?></td>
							<td>
								<select name="<?php echo htmlspecialchars(zts_linekeys_input_name($zts_lk_field_base, 'type'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control input-sm">
									<?php foreach ($zts_lk_types as $type_id => $type_name): ?>
										<option value="<?php echo htmlspecialchars((string) $type_id, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ((string) $linekey['type'] === (string) $type_id ? ' selected' : ''); ?>>
											<?php echo htmlspecialchars((string) $type_name, ENT_QUOTES, 'UTF-8'); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<select name="<?php echo htmlspecialchars(zts_linekeys_input_name($zts_lk_field_base, 'line'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control input-sm">
									<?php for ($l = 1; $l <= $zts_lk_line_max; $l++): ?>
										<option value="<?php echo (int) $l; ?>"<?php echo ((string) $linekey['line'] === (string) $l ? ' selected' : ''); ?>><?php echo (int) $l; ?></option>
									<?php endfor; ?>
								</select>
							</td>
							<td>
								<input type="text" name="<?php echo htmlspecialchars(zts_linekeys_input_name($zts_lk_field_base, 'value'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control input-sm"
								       value="<?php echo htmlspecialchars((string) $linekey['value'], ENT_QUOTES, 'UTF-8'); ?>"
								       placeholder="<?php echo htmlspecialchars(_("Extension or number"), ENT_QUOTES, 'UTF-8'); ?>">
							</td>
							<td>
								<input type="text" name="<?php echo htmlspecialchars(zts_linekeys_input_name($zts_lk_field_base, 'label'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control input-sm"
								       value="<?php echo htmlspecialchars((string) $linekey['label'], ENT_QUOTES, 'UTF-8'); ?>"
								       placeholder="<?php echo htmlspecialchars(_("Display label"), ENT_QUOTES, 'UTF-8'); ?>">
							</td>
							<td>
								<input type="text" name="<?php echo htmlspecialchars(zts_linekeys_input_name($zts_lk_field_base, 'extension'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control input-sm"
								       value="<?php echo htmlspecialchars((string) $linekey['extension'], ENT_QUOTES, 'UTF-8'); ?>"
								       placeholder="<?php echo htmlspecialchars(_("Ext"), ENT_QUOTES, 'UTF-8'); ?>">
							</td>
							<td>
								<input type="text" name="<?php echo htmlspecialchars(zts_linekeys_input_name($zts_lk_field_base, 'pickup'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control input-sm"
								       value="<?php echo htmlspecialchars((string) $linekey['pickup_value'], ENT_QUOTES, 'UTF-8'); ?>"
								       placeholder="<?php echo htmlspecialchars(_('*8'), ENT_QUOTES, 'UTF-8'); ?>">
							</td>
							<td class="zts-row-actions">
								<a href="#" class="zts-action-icon zts-linekey-edit" title="<?php echo htmlspecialchars(_('Edit'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-edit"></i></a>
								<?php if ($zts_lk_extra): ?>
								<a href="#" class="zts-action-icon zts-action-delete zts-linekey-remove" title="<?php echo htmlspecialchars(_('Delete'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-trash-o"></i></a>
								<?php endif; ?>
							</td>
						</tr>
						<?php endfor; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php if ($zts_lk_form_disabled): ?></fieldset><?php endif; ?>
</div>
<?php
static $zts_linekeys_editor_script_loaded = false;
static $zts_linekeys_editor_css_loaded = false;
if (!$zts_linekeys_editor_css_loaded):
	$zts_linekeys_editor_css_loaded = true;
?>
<style>
.zts-linekeys-editor-wrap.zts-bt-wrap { margin-bottom: 24px; }
.zts-linekeys-editor-wrap.zts-bt-wrap .fixed-table-container {
	border: 1px solid #ddd; border-radius: 4px; background-color: #fff;
}
.zts-linekeys-editor-wrap .zts-table-toolbar {
	padding: 8px 15px; border-bottom: 1px solid #ddd; background-color: #f9f9f9; border-radius: 4px 4px 0 0;
}
.zts-linekeys-editor-wrap .zts-table-toolbar .pull-left .btn { margin-right: 6px; }
.zts-linekeys-editor-wrap .zts-lk-toolbar-tpl {
	margin-left: 14px;
	padding-left: 14px;
	border-left: 1px solid #ccc;
}
.zts-linekeys-editor-wrap .zts-lk-toolbar-tpl-label {
	font-weight: 600;
	margin: 0 8px 0 0;
	vertical-align: middle;
	line-height: 30px;
}
.zts-linekeys-editor-wrap .zts-lk-toolbar-tpl .zts-lk-tpl-name-input {
	display: inline-block;
	width: 200px;
	vertical-align: middle;
	margin-right: 6px;
}
.zts-linekeys-editor-wrap .zts-lk-toolbar-tpl .btn { margin-right: 6px; vertical-align: middle; }
.zts-linekeys-editor-wrap .zts-lk-tpl-name-input.zts-lk-tpl-name-dup {
	border-color: #a94442;
	background-color: #f2dede;
}
.zts-linekeys-editor-wrap .zts-lk-editor-prototype-fieldset {
	border: 0;
	margin: 0;
	padding: 0;
	min-width: 0;
}
.zts-linekeys-editor-wrap .zts-table-responsive { border-radius: 0 0 4px 4px; }
.zts-linekeys-editor-wrap .zts-table-responsive > .table { margin-bottom: 0; }
.zts-linekeys-table thead th { vertical-align: top; border-bottom-width: 1px; }
.zts-linekeys-table thead th .fht-cell { height: 0; }
.zts-linekeys-table thead th .th-inner {
	padding: 8px; line-height: 1.42857143; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.zts-linekeys-table thead th.bs-checkbox,
.zts-linekeys-table tbody td.bs-checkbox { width: 36px; text-align: center; vertical-align: middle; }
.zts-linekeys-table .btSelectAll, .zts-linekeys-table .btSelectItem { margin: 0; cursor: pointer; }
.zts-linekeys-table thead th.zts-lk-col-key,
.zts-linekeys-table tbody td.zts-lk-col-key { width: 48px; text-align: center; vertical-align: middle; }
.zts-linekeys-table thead th.zts-actions-th,
.zts-linekeys-table tbody td.zts-row-actions {
	white-space: nowrap; vertical-align: middle; text-align: center;
}
.zts-linekeys-table tbody td.zts-row-actions { font-size: 14px; line-height: 1.2; }
.zts-linekeys-table .zts-row-actions .zts-action-icon {
	display: inline-block; padding: 2px 5px; margin: 0; color: #333; text-decoration: none;
	cursor: pointer; position: relative; z-index: 2; pointer-events: auto;
}
.zts-linekeys-table .zts-row-actions .zts-action-icon .fa { pointer-events: none; }
.zts-linekeys-table .zts-row-actions .zts-action-icon:hover,
.zts-linekeys-table .zts-row-actions .zts-action-icon:focus { color: #337ab7; text-decoration: none; }
.zts-linekeys-table .zts-row-actions .zts-action-delete:hover,
.zts-linekeys-table .zts-row-actions .zts-action-delete:focus { color: #c9302c; }
</style>
<?php endif; ?>
<?php
if (!$zts_linekeys_editor_script_loaded):
	$zts_linekeys_editor_script_loaded = true;
	$zts_lk_js = '/admin/assets/'.Zts_ModuleIdentifiers::RAWNAME.'/js/zts-linekeys-editor.js';
?>
<script src="<?php echo htmlspecialchars($zts_lk_js, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
<?php if (empty($zts_lk_skip_autoinit)): ?>
<script>
window.ZtsLinekeysWhenReady(function () {
	if (!window.ZtsLinekeysEditor) {
		return;
	}
	window.ZtsLinekeysEditor.ensure({
		instanceId: <?php echo json_encode($zts_lk_instance); ?>,
		maxKeys: <?php echo (int) $zts_lk_max; ?>,
		defaultVisible: <?php echo (int) Zts_DeviceEditService::LINEKEY_DEFAULT_VISIBLE; ?>
	});
});
</script>
<?php endif; ?>
