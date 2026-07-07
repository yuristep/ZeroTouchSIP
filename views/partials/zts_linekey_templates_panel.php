<?php
/**
 * General Settings: named Line Key templates as nav-tabs (Contact Manager Group style).
 *
 * Vars: $zts_linekey_templates (array from Zts_LinekeyTemplateService::fromGeneral)
 */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

if (!isset($zts_linekey_templates) || !is_array($zts_linekey_templates))
{
	$zts_linekey_templates = Zts_LinekeyTemplateService::defaultTemplates();
}
$zts_lk_tpl_line_max = 16;
$zts_lk_tpl_types = zts_dropdown_linekey_types();
?>
<div class="zts-lk-templates-panel" id="zts-lk-templates-panel">
	<ul class="nav nav-tabs" id="zts-lk-tpl-tabs" role="tablist">
		<?php foreach ($zts_linekey_templates as $idx => $tpl): ?>
		<li role="presentation"<?php echo ($idx === 0 ? ' class="active"' : ''); ?>>
			<a href="#zts-lk-tpl-pane-<?php echo htmlspecialchars((string) $tpl['id'], ENT_QUOTES, 'UTF-8'); ?>"
			   data-toggle="tab" role="tab"
			   aria-controls="zts-lk-tpl-pane-<?php echo htmlspecialchars((string) $tpl['id'], ENT_QUOTES, 'UTF-8'); ?>"
			   class="zts-lk-tpl-tab-link">
				<span class="zts-lk-tpl-tab-label"><?php echo htmlspecialchars((string) $tpl['name'], ENT_QUOTES, 'UTF-8'); ?></span>
			</a>
		</li>
		<?php endforeach; ?>
		<li role="presentation" class="zts-lk-tpl-tab-add">
			<button type="button" class="btn btn-link zts-lk-tpl-add-btn" id="zts-lk-tpl-add-tab"
			        title="<?php echo htmlspecialchars(_('Add template'), ENT_QUOTES, 'UTF-8'); ?>">
				<i class="fa fa-plus"></i>
			</button>
		</li>
	</ul>
	<div class="tab-content zts-lk-tpl-tab-content" id="zts-lk-tpl-tab-content">
		<?php foreach ($zts_linekey_templates as $idx => $tpl):
			$instance = 'zts-lk-tpl-'.preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $tpl['id']);
			$fieldBase = 'linekey_tpl['.(string) $tpl['id'].']';
			$visible = Zts_LinekeyTemplateService::visibleKeyCount($tpl['keys']);
		?>
		<div role="tabpanel" class="tab-pane<?php echo ($idx === 0 ? ' active' : ''); ?>"
		     id="zts-lk-tpl-pane-<?php echo htmlspecialchars((string) $tpl['id'], ENT_QUOTES, 'UTF-8'); ?>"
		     data-tpl-id="<?php echo htmlspecialchars((string) $tpl['id'], ENT_QUOTES, 'UTF-8'); ?>">
			<?php
			$zts_lk_instance = $instance;
			$zts_lk_field_base = $fieldBase;
			$zts_lk_keys = $tpl['keys'];
			$zts_lk_line_max = $zts_lk_tpl_line_max;
			$zts_lk_types = $zts_lk_tpl_types;
			$zts_lk_visible = $visible;
			$zts_lk_tpl_toolbar = true;
			$zts_lk_tpl_id = (string) $tpl['id'];
			$zts_lk_tpl_name = (string) $tpl['name'];
			$zts_lk_tpl_is_draft = Zts_LinekeyTemplateService::isPlaceholderName($zts_lk_tpl_name);
			require __DIR__.'/zts_linekeys_editor.php';
			unset($zts_lk_tpl_toolbar, $zts_lk_tpl_id, $zts_lk_tpl_name, $zts_lk_tpl_is_draft);
			?>
		</div>
		<?php endforeach; ?>
	</div>
</div>

<div id="zts-lk-tpl-clone-source" style="display:none;" aria-hidden="true">
	<div role="tabpanel" class="tab-pane" data-tpl-id="__TPLID__">
		<?php
		$zts_lk_instance = 'zts-lk-tpl-__TPLID__';
		$zts_lk_field_base = 'linekey_tpl[__TPLID__]';
		$zts_lk_keys = Zts_LinekeyTemplateService::emptyKeysMap();
		$zts_lk_line_max = $zts_lk_tpl_line_max;
		$zts_lk_types = $zts_lk_tpl_types;
		$zts_lk_visible = Zts_DeviceEditService::LINEKEY_DEFAULT_VISIBLE;
		$zts_lk_tpl_toolbar = true;
		$zts_lk_tpl_id = '__TPLID__';
		$zts_lk_tpl_name = Zts_LinekeyTemplateService::placeholderName();
		$zts_lk_tpl_is_draft = true;
		$zts_lk_form_disabled = true;
		$zts_lk_skip_autoinit = true;
		require __DIR__.'/zts_linekeys_editor.php';
		unset($zts_lk_tpl_toolbar, $zts_lk_tpl_id, $zts_lk_tpl_name, $zts_lk_tpl_is_draft, $zts_lk_form_disabled, $zts_lk_skip_autoinit);
		?>
	</div>
</div>
