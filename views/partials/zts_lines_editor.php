<?php
/**
 * Reusable SIP line assignment editor (phones_edit Line Configuration).
 *
 * Expected vars:
 *   $zts_ln_instance (string) DOM id prefix, e.g. zts-ln-phone
 *   $zts_ln_max (int) max lines (2 Fanvil, 16 Yealink)
 *   $zts_ln_visible (int) rows to show initially
 *   $zts_ln_dropdown (array) from zts_dropdown_lines()
 *   $zts_ln_lines (array<int,array>) device lines keyed by line id
 */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

if (!isset($zts_ln_instance) || (string) $zts_ln_instance === '')
{
	return;
}
$zts_ln_max = isset($zts_ln_max) ? (int) $zts_ln_max : Zts_DeviceEditService::LINE_MAX;
$zts_ln_lines = isset($zts_ln_lines) && is_array($zts_ln_lines) ? $zts_ln_lines : array();
$zts_ln_dropdown = isset($zts_ln_dropdown) && is_array($zts_ln_dropdown) ? $zts_ln_dropdown : array();
$zts_ln_visible = isset($zts_ln_visible)
	? (int) $zts_ln_visible
	: Zts_DeviceEditService::linesVisibleCount($zts_ln_lines, $zts_ln_max);
$zts_ln_default_visible = min(Zts_DeviceEditService::LINE_DEFAULT_VISIBLE, $zts_ln_max);

if (!function_exists('zts_lines_editor_render_dropdown'))
{
	/**
	 * @param array  $dropdown
	 * @param string $selected
	 * @return void
	 */
	function zts_lines_editor_render_dropdown(array $dropdown, $selected)
	{
		foreach ($dropdown as $group => $options)
		{
			if (is_array($options))
			{
				echo '<optgroup label="'.htmlspecialchars((string) $group, ENT_QUOTES, 'UTF-8').'">';
				foreach ($options as $key => $value)
				{
					$key = (string) $key;
					echo '<option value="'.htmlspecialchars($key, ENT_QUOTES, 'UTF-8').'"'
						.($selected === $key ? ' selected' : '').'>'
						.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'</option>';
				}
				echo '</optgroup>';
			}
			else
			{
				$group = (string) $group;
				echo '<option value="'.htmlspecialchars($group, ENT_QUOTES, 'UTF-8').'"'
					.($selected === $group ? ' selected' : '').'>'
					.htmlspecialchars((string) $options, ENT_QUOTES, 'UTF-8').'</option>';
			}
		}
	}
}
?>
<div id="<?php echo htmlspecialchars($zts_ln_instance, ENT_QUOTES, 'UTF-8'); ?>-container" class="bootstrap-table zts-bt-wrap zts-bt-wrap-edit zts-lines-editor-wrap">
	<div class="fixed-table-container" style="padding-bottom:0;">
		<div class="fixed-table-body">
			<div id="<?php echo htmlspecialchars($zts_ln_instance, ENT_QUOTES, 'UTF-8'); ?>-toolbar" class="zts-table-toolbar clearfix">
				<div class="pull-left zts-ln-toolbar-lines">
					<button type="button" class="btn btn-primary" id="<?php echo htmlspecialchars($zts_ln_instance, ENT_QUOTES, 'UTF-8'); ?>-btn-add"<?php echo ($zts_ln_visible >= $zts_ln_max ? ' disabled' : ''); ?>>
						<i class="fa fa-plus"></i> <?php echo _('Add Lines'); ?>
					</button>
					<button type="button" class="btn btn-primary" id="<?php echo htmlspecialchars($zts_ln_instance, ENT_QUOTES, 'UTF-8'); ?>-btn-edit" disabled="disabled">
						<i class="fa fa-pencil"></i> <span><?php echo _('Edit'); ?></span>
					</button>
					<button type="button" class="btn btn-danger btn-remove" id="<?php echo htmlspecialchars($zts_ln_instance, ENT_QUOTES, 'UTF-8'); ?>-btn-delete" disabled="disabled">
						<i class="fa fa-trash"></i> <span><?php echo _('Delete'); ?></span>
					</button>
				</div>
			</div>
			<div class="table-responsive zts-table-responsive">
				<table id="<?php echo htmlspecialchars($zts_ln_instance, ENT_QUOTES, 'UTF-8'); ?>-table" class="table table-striped table-bordered table-hover zts-lines-table">
					<thead>
						<tr>
							<th class="bs-checkbox" data-field="state" data-checkbox="true">
								<div class="th-inner">
									<input name="btSelectAll" type="checkbox" class="btSelectAll" title="<?php echo htmlspecialchars(_('Select all'), ENT_QUOTES, 'UTF-8'); ?>">
								</div>
								<div class="fht-cell"></div>
							</th>
							<th style="width:10%;"><div class="th-inner"><?php echo _('Line'); ?></div><div class="fht-cell"></div></th>
							<th style="width:50%;"><div class="th-inner"><?php echo _('FreePBX Device'); ?></div><div class="fht-cell"></div></th>
							<th style="width:40%;"><div class="th-inner"><?php echo _('Label'); ?></div><div class="fht-cell"></div></th>
							<th class="zts-actions-th">
								<div class="th-inner"><?php echo _('Actions'); ?></div>
								<div class="fht-cell"></div>
							</th>
						</tr>
					</thead>
					<tbody id="<?php echo htmlspecialchars($zts_ln_instance, ENT_QUOTES, 'UTF-8'); ?>-body">
						<?php for ($i = 1; $i <= $zts_ln_max; $i++):
							$line_value = isset($zts_ln_lines[$i]['line']) ? (string) $zts_ln_lines[$i]['line'] : '';
							$label_value = isset($zts_ln_lines[$i]['settings']['label'])
								? (string) $zts_ln_lines[$i]['settings']['label'] : '';
							$zts_ln_extra = ($i > $zts_ln_default_visible);
							$zts_ln_hidden = $zts_ln_extra && ($i > $zts_ln_visible);
							$zts_ln_row_class = 'zts-line-row'.($zts_ln_extra ? ' zts-line-extra' : '');
						?>
						<tr class="<?php echo htmlspecialchars($zts_ln_row_class, ENT_QUOTES, 'UTF-8'); ?>"
						    data-line-id="<?php echo (int) $i; ?>"
						    <?php echo $zts_ln_hidden ? ' style="display:none;"' : ''; ?>>
							<td class="bs-checkbox">
								<input type="checkbox" class="btSelectItem" value="<?php echo (int) $i; ?>"
									<?php echo $zts_ln_extra ? '' : ' data-zts-line-fixed="1"'; ?>>
							</td>
							<td class="zts-ln-col-line"><?php echo (int) $i; ?></td>
							<td>
								<select name="line[]" class="form-control input-sm">
									<?php zts_lines_editor_render_dropdown($zts_ln_dropdown, $line_value); ?>
								</select>
							</td>
							<td>
								<input type="text" name="label[]" class="form-control input-sm"
								       value="<?php echo htmlspecialchars($label_value, ENT_QUOTES, 'UTF-8'); ?>"
								       placeholder="<?php echo htmlspecialchars(_('Optional custom label'), ENT_QUOTES, 'UTF-8'); ?>">
							</td>
							<td class="zts-row-actions">
								<a href="#" class="zts-action-icon zts-line-edit" title="<?php echo htmlspecialchars(_('Edit'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-edit"></i></a>
								<?php if ($zts_ln_extra): ?>
								<a href="#" class="zts-action-icon zts-action-delete zts-line-remove" title="<?php echo htmlspecialchars(_('Delete'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-trash-o"></i></a>
								<?php endif; ?>
							</td>
						</tr>
						<?php endfor; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<?php
static $zts_lines_editor_script_loaded = false;
static $zts_lines_editor_css_loaded = false;
if (!$zts_lines_editor_css_loaded):
	$zts_lines_editor_css_loaded = true;
?>
<style>
.zts-lines-editor-wrap.zts-bt-wrap { margin-bottom: 24px; }
.zts-lines-editor-wrap.zts-bt-wrap .fixed-table-container {
	border: 1px solid #ddd; border-radius: 4px; background-color: #fff;
}
.zts-lines-editor-wrap .zts-table-toolbar {
	padding: 8px 15px; border-bottom: 1px solid #ddd; background-color: #f9f9f9; border-radius: 4px 4px 0 0;
}
.zts-lines-editor-wrap .zts-table-toolbar .pull-left .btn { margin-right: 6px; }
.zts-lines-editor-wrap .zts-table-responsive { border-radius: 0 0 4px 4px; }
.zts-lines-editor-wrap .zts-table-responsive > .table { margin-bottom: 0; }
.zts-lines-table thead th { vertical-align: top; border-bottom-width: 1px; }
.zts-lines-table thead th .fht-cell { height: 0; }
.zts-lines-table thead th .th-inner {
	padding: 8px; line-height: 1.42857143; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.zts-lines-table thead th.bs-checkbox,
.zts-lines-table tbody td.bs-checkbox { width: 36px; text-align: center; vertical-align: middle; }
.zts-lines-table .btSelectAll, .zts-lines-table .btSelectItem { margin: 0; cursor: pointer; }
.zts-lines-table thead th.zts-ln-col-line,
.zts-lines-table tbody td.zts-ln-col-line { width: 48px; text-align: center; vertical-align: middle; }
.zts-lines-table thead th.zts-actions-th,
.zts-lines-table tbody td.zts-row-actions {
	white-space: nowrap; vertical-align: middle; text-align: center;
}
.zts-lines-table tbody td.zts-row-actions { font-size: 14px; line-height: 1.2; }
.zts-lines-table .zts-row-actions .zts-action-icon {
	display: inline-block; padding: 2px 5px; margin: 0; color: #333; text-decoration: none;
	cursor: pointer; position: relative; z-index: 2; pointer-events: auto;
}
.zts-lines-table .zts-row-actions .zts-action-icon .fa { pointer-events: none; }
.zts-lines-table .zts-row-actions .zts-action-icon:hover,
.zts-lines-table .zts-row-actions .zts-action-icon:focus { color: #337ab7; text-decoration: none; }
.zts-lines-table .zts-row-actions .zts-action-delete:hover,
.zts-lines-table .zts-row-actions .zts-action-delete:focus { color: #c9302c; }
</style>
<?php endif; ?>
<?php
if (!$zts_lines_editor_script_loaded):
	$zts_lines_editor_script_loaded = true;
	$zts_ln_js = '/admin/assets/'.Zts_ModuleIdentifiers::RAWNAME.'/js/zts-lines-editor.js';
?>
<script src="<?php echo htmlspecialchars($zts_ln_js, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
<?php if (empty($zts_ln_skip_autoinit)): ?>
<script>
window.ZtsLinesWhenReady(function () {
	if (!window.ZtsLinesEditor) {
		return;
	}
	window.ZtsLinesEditor.ensure({
		instanceId: <?php echo json_encode($zts_ln_instance); ?>,
		maxLines: <?php echo (int) $zts_ln_max; ?>,
		defaultVisible: <?php echo (int) $zts_ln_default_visible; ?>
	});
});
</script>
<?php endif; ?>
