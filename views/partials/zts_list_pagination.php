<?php
/* Bootstrap-table fixed-table-pagination (FreePBX 17 / extensions pattern) */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$zts_pagination_id = isset($zts_pagination_id) ? preg_replace('/[^a-z0-9_-]/i', '', (string) $zts_pagination_id) : 'zts-list';
if ($zts_pagination_id === '')
{
	$zts_pagination_id = 'zts-list';
}
$zts_page_sizes = isset($zts_page_sizes) && is_array($zts_page_sizes) ? $zts_page_sizes : array(10, 25, 50, 100);
$zts_default_page_size = isset($zts_default_page_size) ? (int) $zts_default_page_size : 10;
if (!in_array($zts_default_page_size, $zts_page_sizes, true))
{
	$zts_default_page_size = (int) $zts_page_sizes[0];
}
?>
<div class="fixed-table-pagination" id="<?php echo htmlspecialchars($zts_pagination_id, ENT_QUOTES, 'UTF-8'); ?>-pagination" data-zts-pagination-root="1">
	<div class="float-left pagination-detail">
		<span class="pagination-info" id="<?php echo htmlspecialchars($zts_pagination_id, ENT_QUOTES, 'UTF-8'); ?>-pagination-info"></span>
		<div class="page-list">
			<div class="btn-group dropdown dropup">
				<button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
					<span class="page-size" id="<?php echo htmlspecialchars($zts_pagination_id, ENT_QUOTES, 'UTF-8'); ?>-page-size-label"><?php echo (int) $zts_default_page_size; ?></span>
					<span class="caret"></span>
				</button>
				<div class="dropdown-menu" role="menu">
					<?php foreach ($zts_page_sizes as $ps): ?>
					<a href="#" class="dropdown-item zts-page-size-opt<?php echo ((int) $ps === $zts_default_page_size) ? ' active' : ''; ?>" data-size="<?php echo (int) $ps; ?>"><?php echo (int) $ps; ?></a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php echo htmlspecialchars(_('records per page'), ENT_QUOTES, 'UTF-8'); ?>
		</div>
	</div>
	<div class="float-right pagination">
		<ul class="pagination" id="<?php echo htmlspecialchars($zts_pagination_id, ENT_QUOTES, 'UTF-8'); ?>-pagination-pages"></ul>
	</div>
</div>
