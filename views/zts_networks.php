<?php
/* Network List View — FreePBX 17 list layout */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$zts_net_common = array(
	'type' => 'setup',
	'display' => Zts_ModuleIdentifiers::RAWNAME,
);

$zts_net_config_url = function (array $query) use ($zts_net_common) {
	return 'config.php?' . http_build_query(array_merge($zts_net_common, $query), '', '&');
};

$networks_list_sort_cur = isset($networks_list_sort) ? (string) $networks_list_sort : 'cidr';
$networks_list_order_cur = isset($networks_list_order) ? strtolower((string) $networks_list_order) : 'asc';
if ($networks_list_order_cur !== 'desc')
{
	$networks_list_order_cur = 'asc';
}

$zts_networks_form_action = $zts_net_config_url(array(
	'zerotouchsip_form' => 'networks_list',
	'sort' => $networks_list_sort_cur,
	'order' => $networks_list_order_cur,
));

$zts_networks_edit_js_base = $zts_net_config_url(array(
	'zerotouchsip_form' => 'networks_edit',
)) . '&edit=';

/**
 * @param string $col
 * @return string href for next sort (toggle when same column, else asc)
 */
$zts_sort_href = function ($col) use ($zts_net_config_url, $networks_list_sort_cur, $networks_list_order_cur)
{
	$next = ($col === $networks_list_sort_cur)
		? (($networks_list_order_cur === 'asc') ? 'desc' : 'asc')
		: 'asc';

	return $zts_net_config_url(array(
		'zerotouchsip_form' => 'networks_list',
		'sort' => $col,
		'order' => $next,
	));
};

/**
 * @param string $col
 * @param string $label
 * @return void echoes <th>…
 */
$zts_render_sort_th = function ($col, $label) use (
	$zts_sort_href,
	$networks_list_sort_cur,
	$networks_list_order_cur
) {
	$inner = 'th-inner sortable both';
	if ($col === $networks_list_sort_cur)
	{
		$inner .= ' '.$networks_list_order_cur;
	}
	$href = htmlspecialchars($zts_sort_href($col));
	$innerClass = htmlspecialchars($inner, ENT_QUOTES, 'UTF-8');
	echo '<th><a href="'.$href.'" class="zts-th-sort" title="'.htmlspecialchars(_('Sort'), ENT_QUOTES, 'UTF-8').'">';
	echo '<div class="'.$innerClass.'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</div>';
	echo '</a><div class="fht-cell"></div></th>';
};

$zts_networks_edit_href = function ($editId) use ($zts_net_config_url) {
	return $zts_net_config_url(array(
		'zerotouchsip_form' => 'networks_edit',
		'edit' => (string) $editId,
	));
};

?>

<div id="zts-networks-list-page" class="zts-fpbx-list-page">

<h2 class="zts-page-title"><?php echo _('Networks'); ?></h2>

<p class="help-block zts-section-lead">
	<?php echo _('Configure network-specific settings by IP range (CIDR notation). Settings are applied based on the phone\'s IP address during provisioning.'); ?>
</p>

<?php if (count($networks) == 0): ?>
	<div class="bootstrap-table zts-bt-wrap zts-bt-wrap-empty">
	<div class="fixed-table-container" style="padding-bottom:0;">
	<div class="fixed-table-body">
		<div id="zts-networks-list-toolbar" class="zts-table-toolbar clearfix">
			<div class="pull-left zts-list-toolbar-actions">
				<a href="<?php echo htmlspecialchars($zts_networks_edit_href('')); ?>" class="btn btn-primary">
					<i class="fa fa-plus"></i> <?php echo _('Add Network'); ?>
				</a>
			</div>
		</div>
	</div>
	</div>
	</div>
	<p class="zts-list-empty-msg"><?php echo _('No networks configured yet.'); ?></p>
<?php else: ?>
<form id="zts-networks-list-form" method="post" action="<?php echo htmlspecialchars($zts_networks_form_action); ?>">
	<input type="hidden" name="zts_networks_bulk" id="zts_networks_bulk" value="">
<div class="bootstrap-table zts-bt-wrap">
<div class="fixed-table-container" style="padding-bottom:0;">
<div class="fixed-table-body">
	<div id="zts-networks-list-toolbar" class="zts-table-toolbar clearfix">
		<div class="pull-left zts-list-toolbar-actions">
			<a href="<?php echo htmlspecialchars($zts_networks_edit_href('')); ?>" class="btn btn-primary">
				<i class="fa fa-plus"></i> <?php echo _('Add Network'); ?>
			</a>
			<button type="button" id="zts-networks-btn-edit" class="btn btn-primary" disabled="disabled" data-section="zts-network-list">
				<i class="fa fa-pencil"></i> <span><?php echo _('Edit'); ?></span>
			</button>
			<button type="button" id="zts-networks-btn-delete" class="btn btn-danger btn-remove" disabled="disabled" data-type="internal" data-section="zts-network-list">
				<i class="fa fa-trash"></i> <span><?php echo _('Delete'); ?></span>
			</button>
		</div>
		<div class="pull-right zts-toolbar-search-wrap">
			<input type="search" class="form-control zts-list-search zts-network-search" id="zts-network-search" placeholder="<?php echo htmlspecialchars(_('Search'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
		</div>
	</div>
	<div class="table-responsive zts-table-responsive">
	<table id="zts-network-list" class="table table-striped table-bordered table-hover zts-fpbx-list-table">
		<thead>
			<tr>
				<th class="bs-checkbox" data-field="state" data-checkbox="true">
					<div class="th-inner">
						<input name="btSelectAll" type="checkbox" class="btSelectAll" title="<?php echo htmlspecialchars(_('Select all'), ENT_QUOTES, 'UTF-8'); ?>">
					</div>
					<div class="fht-cell"></div>
				</th>
				<?php $zts_render_sort_th('name', _('Name')); ?>
				<?php $zts_render_sort_th('cidr', _('CIDR Range')); ?>
				<th class="zts-actions-th">
					<div class="th-inner"><?php echo _('Actions'); ?></div>
					<div class="fht-cell"></div>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($networks as $network): ?>
				<?php
				$is_default = ((string) $network['id'] === '-1' || (int) $network['id'] === -1);
				$nid = (int) $network['id'];
				$search_blob = $network['name'].' '.$network['cidr'];
				$row_edit_href = htmlspecialchars($zts_networks_edit_href($network['id']), ENT_QUOTES, 'UTF-8');
				?>
				<tr class="zts-network-row zts-list-row" data-zts-search="<?php echo htmlspecialchars($search_blob, ENT_QUOTES, 'UTF-8'); ?>">
					<td class="bs-checkbox">
						<input type="checkbox" class="btSelectItem" name="network_ids[]" value="<?php echo $is_default ? '-1' : (int) $nid; ?>" form="zts-networks-list-form"<?php echo $is_default ? ' data-zts-default="1"' : ''; ?>>
					</td>
					<td><?php echo htmlspecialchars($network['name']); ?></td>
					<td><?php echo htmlspecialchars($network['cidr']); ?></td>
					<td class="zts-row-actions">
						<a href="<?php echo $row_edit_href; ?>" class="zts-action-icon" title="<?php echo htmlspecialchars(_('Edit'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-edit"></i></a>
						<?php if (!$is_default): ?>
						<a href="#" class="zts-action-icon zts-action-delete" data-network-id="<?php echo $nid; ?>" title="<?php echo htmlspecialchars(_('Delete'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-trash-o"></i></a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</div>
</div>
</div>
</form>
<script>
(function ($) {
	var form = $('#zts-networks-list-form');
	var table = $('#zts-network-list');
	if (!form.length || !table.length) {
		return;
	}
	var editBase = <?php echo json_encode($zts_networks_edit_js_base); ?>;
	var btnEdit = $('#zts-networks-btn-edit');
	var btnDelete = $('#zts-networks-btn-delete');
	var searchInput = $('#zts-network-search');
	var bulk = $('#zts_networks_bulk');
	var selectAll = table.find('thead .btSelectAll');

	function selectedNumericIds() {
		var ids = [];
		table.find('tbody .btSelectItem:checked').each(function () {
			var v = parseInt($(this).val(), 10);
			if (!isNaN(v)) {
				ids.push(v);
			}
		});
		return ids;
	}

	function selectedDeletableIds() {
		var ids = [];
		table.find('tbody .btSelectItem:checked').each(function () {
			var v = parseInt($(this).val(), 10);
			if (v > 0) {
				ids.push(v);
			}
		});
		return ids;
	}

	function deleteToolbarEnabled() {
		var boxes = table.find('tbody .btSelectItem:checked');
		if (boxes.length < 1) {
			return false;
		}
		var ok = true;
		boxes.each(function () {
			var v = parseInt($(this).val(), 10);
			if (v === -1 || isNaN(v) || v <= 0) {
				ok = false;
			}
		});
		return ok;
	}

	function updateToolbar() {
		var num = selectedNumericIds();
		var n = num.length;
		btnDelete.prop('disabled', !deleteToolbarEnabled());
		btnEdit.prop('disabled', n !== 1);
		var vis = table.find('tbody tr.zts-network-row:visible .btSelectItem').length;
		var checkedVis = table.find('tbody tr.zts-network-row:visible .btSelectItem:checked').length;
		selectAll.prop('checked', vis > 0 && checkedVis === vis);
	}

	selectAll.on('change', function () {
		var on = $(this).prop('checked');
		table.find('tbody tr.zts-network-row:visible .btSelectItem').prop('checked', on);
		updateToolbar();
	});

	table.on('change', 'tbody .btSelectItem', function () {
		updateToolbar();
	});

	form.on('submit', function (e) {
		if ($.trim(bulk.val()) === '') {
			e.preventDefault();
		}
	});

	searchInput.on('keydown', function (e) {
		if ((e.key && e.key === 'Enter') || e.which === 13) {
			e.preventDefault();
			updateToolbar();
		}
	});

	function submitSingleRowDelete(id) {
		var box = table.find('tbody .btSelectItem[value="' + String(id).replace(/"/g, '\\"') + '"]');
		if (!box.length) {
			return;
		}
		var msg = (window.ZTS_I18N && ZTS_I18N.confirmDeleteNetworks) ? ZTS_I18N.confirmDeleteNetworks : '';
		if (!window.confirm(msg)) {
			return;
		}
		table.find('tbody .btSelectItem').prop('checked', false);
		box.prop('checked', true);
		bulk.val('delete');
		form.trigger('submit');
	}

	btnEdit.on('click', function () {
		var ids = selectedNumericIds();
		if (ids.length === 1) {
			window.location.href = editBase + ids[0];
		}
	});

	btnDelete.on('click', function () {
		var ids = selectedDeletableIds();
		if (ids.length < 1) {
			return;
		}
		var msg = (window.ZTS_I18N && ZTS_I18N.confirmDeleteNetworks) ? ZTS_I18N.confirmDeleteNetworks : '';
		if (!window.confirm(msg)) {
			return;
		}
		bulk.val('delete');
		form.trigger('submit');
	});

	table.on('click', '.zts-action-delete', function (e) {
		e.preventDefault();
		submitSingleRowDelete($(this).attr('data-network-id'));
	});

	searchInput.on('input', function () {
		var q = $.trim($(this).val());
		var matcher = window.ZtsListSearch && window.ZtsListSearch.rowMatches
			? window.ZtsListSearch.rowMatches
			: function (query, hay) {
				return query === '' || hay.indexOf(query.toLowerCase()) !== -1;
			};
		table.find('tbody tr.zts-network-row').each(function () {
			var hay = $(this).attr('data-zts-search') || '';
			$(this).toggle(matcher(q, hay, ''));
		});
		table.find('tbody tr.zts-network-row:hidden .btSelectItem').prop('checked', false);
		selectAll.prop('checked', false);
		updateToolbar();
	});

	updateToolbar();
})(window.jQuery || window.$);
</script>
<?php endif; ?>

<?php require __DIR__.'/partials/zts_list_view_styles.php'; ?>

</div>
