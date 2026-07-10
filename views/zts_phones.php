<?php
/* Phone List View */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$zts_phones_list_url = '?type=setup&display=zerotouchsip&zerotouchsip_form=phones_list';
$zts_phones_edit_url = '?type=setup&display=zerotouchsip&zerotouchsip_form=phones_edit';

$zts_sort_q = '';
if (isset($phones_list_sort, $phones_list_order))
{
	$zts_sort_q = '&sort=' . rawurlencode((string) $phones_list_sort) . '&order=' . rawurlencode((string) $phones_list_order);
}

$phones_list_sort_cur = isset($phones_list_sort) ? (string) $phones_list_sort : 'mac';
$phones_list_order_cur = isset($phones_list_order) ? strtolower((string) $phones_list_order) : 'asc';
if ($phones_list_order_cur !== 'desc')
{
	$phones_list_order_cur = 'asc';
}

$zts_phones_form_action = 'config.php?type=setup&display=zerotouchsip&zerotouchsip_form=phones_list'
	. '&sort=' . rawurlencode($phones_list_sort_cur) . '&order=' . rawurlencode($phones_list_order_cur);

if (!isset($zts_linekey_templates) || !is_array($zts_linekey_templates))
{
	$zts_linekey_templates = Zts_LinekeyTemplateService::fromGeneral(Zts_GeneralSettingsService::load());
}

/**
 * @param string $col
 * @return string href for next sort (toggle when same column, else asc)
 */
$zts_sort_href = function ($col) use ($zts_phones_list_url, $phones_list_sort_cur, $phones_list_order_cur)
{
	$next = ($col === $phones_list_sort_cur)
		? (($phones_list_order_cur === 'asc') ? 'desc' : 'asc')
		: 'asc';

	return $zts_phones_list_url.'&sort='.rawurlencode($col).'&order='.rawurlencode($next);
};

/**
 * Bootstrap-table–style sort header (same DOM pattern as FreePBX CEL report).
 *
 * @param string $col
 * @param string $label
 * @param string $title optional link title (defaults to Sort)
 * @param string $thClass optional extra class on <th>
 * @return void echoes <th>…
 */
$zts_render_sort_th = function ($col, $label, $title = '', $thClass = '') use (
	$zts_sort_href,
	$phones_list_sort_cur,
	$phones_list_order_cur
) {
	$inner = 'th-inner sortable both';
	if ($col === $phones_list_sort_cur)
	{
		$inner .= ' '.$phones_list_order_cur;
	}
	$href = htmlspecialchars($zts_sort_href($col));
	$innerClass = htmlspecialchars($inner, ENT_QUOTES, 'UTF-8');
	$linkTitle = $title !== '' ? $title : (string) _('Sort');
	$thClassAttr = $thClass !== ''
		? ' class="'.htmlspecialchars($thClass, ENT_QUOTES, 'UTF-8').'"'
		: '';
	echo '<th'.$thClassAttr.'><a href="'.$href.'" class="zts-th-sort" title="'.htmlspecialchars($linkTitle, ENT_QUOTES, 'UTF-8').'">';
	echo '<div class="'.$innerClass.'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</div>';
	echo '</a><div class="fht-cell"></div></th>';
};
?>

<div id="zts-phones-list-page" class="zts-fpbx-list-page">

<?php require __DIR__.'/partials/zts_list_view_styles.php'; ?>

<h2 class="zts-page-title"><?php echo _('Phones'); ?></h2>

<?php
$nr = Zts_NotifySessionService::pullResults();
if (is_array($nr))
{
	echo '<div class="alert alert-success"><p><strong>'.htmlspecialchars(_('Result')).'</strong></p><ul class="list-unstyled" style="margin-bottom:0;">';
	foreach ($nr as $line)
	{
		echo '<li>'.htmlspecialchars((string) $line).'</li>';
	}
	echo '</ul></div>';
}
?>

<div class="display no-border">
<?php if(count($devices) == 0): ?>
	<div class="bootstrap-table bootstrap4 zts-phone-list-bt zts-bt-wrap-empty">
		<div class="fixed-table-toolbar">
			<div class="bs-bars float-left">
				<div id="zts-phones-list-toolbar">
					<a href="<?php echo htmlspecialchars($zts_phones_edit_url); ?>&edit=" class="btn btn-default">
						<i class="fa fa-plus"></i> <?php echo _("Add Phone"); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
	<p class="zts-list-empty-msg"><?php echo _("No phones configured yet."); ?></p>
<?php else: ?>
<form id="zts-phones-list-form" method="post" action="<?php echo htmlspecialchars($zts_phones_form_action); ?>">
	<input type="hidden" name="zts_phones_bulk" id="zts-phones-bulk" value="">
<div class="bootstrap-table bootstrap4 zts-phone-list-bt">
	<div class="fixed-table-toolbar">
		<div class="bs-bars float-left">
			<div id="zts-phones-list-toolbar">
				<a href="<?php echo htmlspecialchars($zts_phones_edit_url); ?>&edit=" class="btn btn-default">
					<i class="fa fa-plus"></i> <?php echo _("Add Phone"); ?>
				</a>
				<button type="button" id="zts-phones-btn-edit" class="btn btn-default" disabled="disabled" data-section="zts-phone-list">
					<i class="fa fa-pencil"></i> <span><?php echo _("Edit"); ?></span>
				</button>
				<button type="button" id="zts-phones-btn-notify" class="btn btn-default" disabled="disabled" data-section="zts-phone-list">
					<i class="fa fa-bell"></i> <span><?php echo _("Notify (fast)"); ?></span>
				</button>
				<button type="button" id="zts-phones-btn-autoprovision" class="btn btn-default" disabled="disabled" data-section="zts-phone-list">
					<i class="fa fa-refresh"></i> <span><?php echo _("Autoprovision"); ?></span>
				</button>
				<button type="button" id="zts-phones-btn-delete" class="btn btn-danger btn-remove" disabled="disabled" data-type="internal" data-section="zts-phone-list">
					<i class="fa fa-trash"></i> <span><?php echo _("Delete"); ?></span>
				</button>
				<span class="zts-phone-list-linekey-toolbar">
					<label class="zts-phone-list-linekey-label" for="zts-linekey-template-id"><?php echo htmlspecialchars(_('Line Key Template'), ENT_QUOTES, 'UTF-8'); ?></label>
					<select name="linekey_template_id" id="zts-linekey-template-id" class="form-control input-sm zts-linekey-template-select"<?php echo (count($zts_linekey_templates) < 1 ? ' disabled="disabled"' : ''); ?>
						title="<?php echo htmlspecialchars(count($zts_linekey_templates) > 0 ? _('Select phones, choose a template, then apply it to replace Line Keys on those phones.') : _('Create Line Key Templates in General Settings first.'), ENT_QUOTES, 'UTF-8'); ?>">
						<option value=""><?php echo htmlspecialchars(_('Select Line Key Template'), ENT_QUOTES, 'UTF-8'); ?></option>
						<?php foreach (Zts_LinekeyTemplateService::choicesForSelect($zts_linekey_templates) as $zts_lk_choice): ?>
						<option value="<?php echo htmlspecialchars((string) $zts_lk_choice['id'], ENT_QUOTES, 'UTF-8'); ?>">
							<?php echo htmlspecialchars((string) $zts_lk_choice['name'], ENT_QUOTES, 'UTF-8'); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<button type="button" id="zts-phones-btn-apply-linekeys" class="btn btn-default" disabled="disabled" data-section="zts-phone-list"
						title="<?php echo htmlspecialchars(_('Apply selected template to checked phones'), ENT_QUOTES, 'UTF-8'); ?>">
						<i class="fa fa-keyboard-o"></i> <span><?php echo _("Apply Line Keys"); ?></span>
					</button>
				</span>
			</div>
		</div>
		<div class="float-right search btn-group zts-phones-search-wrap">
			<div class="zts-list-search-group">
				<input type="search" class="form-control search-input zts-list-search zts-phone-search" id="zts-phone-search" placeholder="<?php echo htmlspecialchars(_('Search text or dial pattern (e.g. 6[123][01][0-9], 6XX)'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(_('Plain text, FreePBX patterns (X Z N .), or regexp: /pattern/ or re:pattern'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
				<button type="button" class="zts-list-search-clear" id="zts-phone-search-clear" title="<?php echo htmlspecialchars(_('Clear search'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(_('Clear search'), ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1">
					<i class="fa fa-times-circle"></i>
				</button>
			</div>
		</div>
	</div>
	<div class="fixed-table-container" style="padding-bottom:0;">
		<div class="fixed-table-body">
	<table id="zts-phone-list" class="table table-striped zts-phone-list-table zts-fpbx-list-table table-bordered table-hover">
		<thead>
			<tr>
				<th class="bs-checkbox" style="width:36px;" data-field="state" data-checkbox="true">
					<div class="th-inner">
						<label>
							<input name="btSelectAll" type="checkbox" class="btSelectAll" title="<?php echo htmlspecialchars(_('Select all'), ENT_QUOTES, 'UTF-8'); ?>">
							<span></span>
						</label>
					</div>
					<div class="fht-cell"></div>
				</th>
				<?php $zts_render_sort_th('name', _('Name')); ?>
				<?php $zts_render_sort_th('mac', _('MAC Address')); ?>
				<?php $zts_render_sort_th('vendor', _('Vendor')); ?>
				<?php $zts_render_sort_th('model', _('Model')); ?>
				<?php $zts_render_sort_th('firmware', _('Firmware')); ?>
				<?php $zts_render_sort_th('lines', _('Lines')); ?>
				<?php $zts_render_sort_th('lastconfig', _('Last Config')); ?>
				<?php $zts_render_sort_th('lastip', _('Last IP')); ?>
				<?php $zts_render_sort_th('pjsip', _('Actions'), _('Sort by PJSIP status (PJSIP / No PJSIP)'), 'zts-actions-th'); ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach($devices as $device): ?>
				<?php
				$vendor_cell = isset($device['vendor_label'])
					? (string) $device['vendor_label']
					: Zts_VendorDisplayService::labelForModel(isset($device['model']) ? $device['model'] : '');
				$line_labels = array();
				foreach ($device['lines'] as $line)
				{
					if (!empty($line['extension']))
					{
						$line_labels[] = $line['extension'] . ' (' . $line['name'] . ')';
					}
					elseif (!empty($line['description']))
					{
						$line_labels[] = $line['id'] . ' (' . $line['description'] . ')';
					}
				}
				$lines_txt = implode(' ', $line_labels);
				$search_extensions = array();
				foreach ($device['lines'] as $line)
				{
					if (!empty($line['extension']))
					{
						$search_extensions[] = trim((string) $line['extension']);
					}
				}
				$search_extensions = implode(' ', array_unique($search_extensions));
				$ps = (isset($device['pjsip_status']) && is_array($device['pjsip_status']))
					? $device['pjsip_status']
					: array('state' => 'unknown', 'label' => '');
				$search_blob = $device['name'].' '.$device['mac'].' '.$vendor_cell.' '.$device['model'].' '.$device['firmware_version'].' '.$lines_txt.' '.$device['lastconfig'].' '.$device['lastip'].' '.(isset($ps['state']) ? $ps['state'] : '').' '.(isset($ps['label']) ? $ps['label'] : '');
				$did = (int) $device['id'];
				$pst = isset($ps['state']) ? (string) $ps['state'] : 'unknown';
				$ptitle = isset($ps['label']) ? (string) $ps['label'] : '';
				$picon = 'fa fa-phone-square fa-2x';
				$pcolor = 'zts-pjsip-unavailable';
				if ($pst === 'online')
				{
					$pcolor = 'zts-pjsip-online';
				}
				$web_ui_url = isset($device['web_ui_url']) ? trim((string) $device['web_ui_url']) : '';
				$web_ui_label = isset($device['web_ui_url_label']) ? trim((string) $device['web_ui_url_label']) : '';
				$has_web_ui = $web_ui_url !== '';
				$web_ui_title = $has_web_ui
					? ($ptitle !== '' ? $ptitle.' — ' : '').sprintf(_('Open phone web UI (%s)'), $web_ui_label)
					: $ptitle;
				?>
				<tr class="zts-phone-row zts-list-row" data-zts-search="<?php echo htmlspecialchars($search_blob, ENT_QUOTES, 'UTF-8'); ?>" data-zts-extensions="<?php echo htmlspecialchars($search_extensions, ENT_QUOTES, 'UTF-8'); ?>">
					<td class="bs-checkbox" style="width:36px;">
						<label>
							<input type="checkbox" class="btSelectItem" name="phone_ids[]" value="<?php echo $did; ?>">
							<span></span>
						</label>
					</td>
					<td><?php echo htmlspecialchars($device['name']); ?></td>
					<td><?php echo htmlspecialchars($device['mac']); ?></td>
					<td><?php echo htmlspecialchars($vendor_cell); ?></td>
					<td><?php echo htmlspecialchars($device['model']); ?></td>
					<td><?php echo htmlspecialchars($device['firmware_version']); ?></td>
					<td><?php echo implode('<br>', $line_labels); ?></td>
					<td><?php echo htmlspecialchars($device['lastconfig']); ?></td>
					<td><?php echo htmlspecialchars($device['lastip']); ?></td>
					<td class="zts-row-actions">
						<?php if ($has_web_ui): ?>
						<a href="<?php echo htmlspecialchars($web_ui_url, ENT_QUOTES, 'UTF-8'); ?>"
							target="_blank" rel="noopener noreferrer"
							class="zts-action-icon zts-pjsip-status <?php echo htmlspecialchars($pcolor, ENT_QUOTES, 'UTF-8'); ?>"
							title="<?php echo htmlspecialchars($web_ui_title, ENT_QUOTES, 'UTF-8'); ?>"><i class="<?php echo htmlspecialchars($picon, ENT_QUOTES, 'UTF-8'); ?>"></i></a>
						<?php else: ?>
						<span class="zts-action-icon zts-pjsip-status <?php echo htmlspecialchars($pcolor, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($ptitle, ENT_QUOTES, 'UTF-8'); ?>"><i class="<?php echo htmlspecialchars($picon, ENT_QUOTES, 'UTF-8'); ?>"></i></span>
						<?php endif; ?>
						<a href="<?php echo htmlspecialchars($zts_phones_edit_url); ?>&edit=<?php echo $did; ?>" class="zts-action-icon" title="<?php echo htmlspecialchars(_('Edit'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-edit"></i></a>
						<a href="#" class="zts-action-icon zts-action-notify" data-phone-id="<?php echo $did; ?>" title="<?php echo htmlspecialchars(_('Notify (soft, no reboot)'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-refresh"></i></a>
						<a href="#" class="zts-action-icon zts-action-delete" data-phone-id="<?php echo $did; ?>" title="<?php echo htmlspecialchars(_('Delete'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-trash-o"></i></a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
		</div>
	</div>
	<?php
	$zts_pagination_id = 'zts-phones-list';
	$zts_default_page_size = 10;
	require __DIR__.'/partials/zts_list_pagination.php';
	?>
</div>
</form>
<script>
(function ($) {
	if (!$) {
		return;
	}
	$(function () {
	var form = $('#zts-phones-list-form');
	var table = $('#zts-phone-list');
	if (!form.length || !table.length) {
		return;
	}
	var editBase = <?php echo json_encode($zts_phones_edit_url . '&edit='); ?>;
	var btnEdit = $('#zts-phones-btn-edit');
	var btnNotify = $('#zts-phones-btn-notify');
	var btnAutoprovision = $('#zts-phones-btn-autoprovision');
	var btnDelete = $('#zts-phones-btn-delete');
	var btnApplyLinekeys = $('#zts-phones-btn-apply-linekeys');
	var linekeyTemplate = $('#zts-linekey-template-id');
	var searchInput = $('#zts-phone-search');
	var bulk = $('#zts-phones-bulk');
	var selectAll = table.find('thead .btSelectAll');
	var paginator = null;

	function $visibleRows() {
		return table.find('tbody tr.zts-phone-row:not(.zts-search-hidden):not(.zts-page-hidden)');
	}

	if (window.ZtsListPagination) {
		paginator = window.ZtsListPagination.bind({
			id: 'zts-phones-list',
			$table: table,
			rowSelector: 'tr.zts-list-row',
			onChange: function () {
				updateToolbar();
			}
		});
	}

	function selectedIds() {
		var ids = [];
		table.find('tbody .btSelectItem:checked').each(function () {
			var v = parseInt($(this).val(), 10);
			if (v > 0) {
				ids.push(v);
			}
		});
		return ids;
	}

	function updateToolbar() {
		var ids = selectedIds();
		var n = ids.length;
		btnNotify.prop('disabled', n < 1);
		btnAutoprovision.prop('disabled', n < 1);
		btnDelete.prop('disabled', n < 1);
		btnEdit.prop('disabled', n !== 1);
		btnApplyLinekeys.prop('disabled', n < 1 || linekeyTemplate.val() === '');
		var vis = $visibleRows().find('.btSelectItem').length;
		var checkedVis = $visibleRows().find('.btSelectItem:checked').length;
		selectAll.prop('checked', vis > 0 && checkedVis === vis);
	}

	selectAll.on('change', function () {
		var on = $(this).prop('checked');
		$visibleRows().find('.btSelectItem').prop('checked', on);
		updateToolbar();
	});

	table.on('change', 'tbody .btSelectItem', function () {
		updateToolbar();
	});

	form.on('submit', function (e) {
		// Search is client-side only; avoid accidental POST on Enter in the search field.
		if ($.trim(bulk.val()) === '') {
			e.preventDefault();
		}
	});

	searchInput.on('keydown', function (e) {
		if ((e.key && e.key === 'Enter') || e.which === 13) {
			e.preventDefault();
			if (paginator) {
				paginator.resetPage();
			} else {
				updateToolbar();
			}
		}
	});

	function submitSingleRowAction(id, action, confirmMessage) {
		var box = table.find('tbody .btSelectItem[value="' + String(id).replace(/"/g, '\\"') + '"]');
		if (!box.length) {
			return;
		}
		if (confirmMessage && !window.confirm(confirmMessage)) {
			return;
		}
		table.find('tbody .btSelectItem').prop('checked', false);
		box.prop('checked', true);
		bulk.val(action);
		form.trigger('submit');
	}

	btnEdit.on('click', function () {
		var ids = selectedIds();
		if (ids.length === 1) {
			window.location.href = editBase + ids[0];
		}
	});

	btnNotify.on('click', function () {
		if (selectedIds().length < 1) {
			return;
		}
		bulk.val('notify_soft');
		form.trigger('submit');
	});

	btnAutoprovision.on('click', function () {
		if (selectedIds().length < 1) {
			return;
		}
		bulk.val('autoprovision');
		form.trigger('submit');
	});

	btnDelete.on('click', function () {
		var ids = selectedIds();
		if (ids.length < 1) {
			return;
		}
		var msg = (window.ZTS_I18N && ZTS_I18N.confirmDeletePhones) ? ZTS_I18N.confirmDeletePhones : '';
		if (!window.confirm(msg)) {
			return;
		}
		bulk.val('delete');
		form.trigger('submit');
	});

	table.on('click', '.zts-action-notify', function (e) {
		e.preventDefault();
		submitSingleRowAction($(this).attr('data-phone-id'), 'notify_soft', '');
	});

	table.on('click', '.zts-action-delete', function (e) {
		e.preventDefault();
		var msg = (window.ZTS_I18N && ZTS_I18N.confirmDeletePhones) ? ZTS_I18N.confirmDeletePhones : '';
		submitSingleRowAction($(this).attr('data-phone-id'), 'delete', msg);
	});

	linekeyTemplate.on('change', function () {
		updateToolbar();
	});

	btnApplyLinekeys.on('click', function () {
		var ids = selectedIds();
		var tplText = $.trim(linekeyTemplate.find('option:selected').text());
		if (ids.length < 1 || linekeyTemplate.val() === '') {
			return;
		}
		var msg = (window.ZTS_I18N && ZTS_I18N.confirmApplyLinekeyTemplate)
			? ZTS_I18N.confirmApplyLinekeyTemplate
			: 'Apply selected Line Key template to selected phones?';
		msg = msg.replace('%template%', tplText).replace('%count%', String(ids.length));
		if (!window.confirm(msg)) {
			return;
		}
		bulk.val('apply_linekey_template');
		form.trigger('submit');
	});

	if (window.ZtsPhonesListSearch && window.ZtsPhonesListSearch.bind({
		$input: searchInput,
		$clear: $('#zts-phone-search-clear'),
		$table: table,
		onFilter: function () {
			selectAll.prop('checked', false);
			if (paginator) {
				paginator.resetPage();
			} else {
				updateToolbar();
			}
		}
	})) {
		/* bound */
	} else {
		var $search = searchInput;
		$search.on('input', function () {
			var q = $.trim($search.val());
			var matcher = window.ZtsListSearch && window.ZtsListSearch.rowMatches
				? window.ZtsListSearch.rowMatches
				: function (query, hay) {
					return query === '' || hay.indexOf(query.toLowerCase()) !== -1;
				};
			table.find('tbody tr.zts-phone-row').each(function () {
				var $row = $(this);
				var hay = ($row.attr('data-zts-search') || '').toLowerCase();
				var exts = $row.attr('data-zts-extensions') || '';
				var show = matcher(q, hay, exts);
				$row.toggleClass('zts-search-hidden', !show);
			});
			selectAll.prop('checked', false);
			if (paginator) {
				paginator.resetPage();
			} else {
				updateToolbar();
			}
		});
	}

	updateToolbar();
	});
})(window.jQuery || window.$);
</script>
<?php endif; ?>
</div>

</div>
