<?php
/* Reference block: module publisher (FreePBX section / element-container layout) */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$zts_manifest = Zts_ModuleBranding::moduleManifest();
$zts_repo_url = (string) $zts_manifest['more_info'];
$zts_repo_label = preg_replace('#^https?://#', '', rtrim($zts_repo_url, '/'));
if ($zts_repo_label === '')
{
	$zts_repo_label = $zts_repo_url;
}
$zts_pub_version = trim((string) $zts_manifest['version']);

/**
 * @param string $label
 * @param string $valueHtml trusted HTML for value column
 * @return void
 */
$zts_publisher_row = function ($label, $valueHtml) {
	?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-4 control-label">
							<label><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></label>
						</div>
						<div class="col-md-8"><?php echo $valueHtml; ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
};
?>

<div class="container-fluid zts-publisher-wrap">
	<div class="section-title" data-for="zts-publisher-info">
		<h3>
			<i class="fa fa-plus"></i>
			<?php echo _('Publisher'); ?>
			<small class="text-muted">
				<?php
				echo ' — '.htmlspecialchars((string) $zts_manifest['publisher'], ENT_QUOTES, 'UTF-8');
				if ($zts_pub_version !== '')
				{
					echo ' · v'.htmlspecialchars($zts_pub_version, ENT_QUOTES, 'UTF-8');
				}
				?>
			</small>
		</h3>
	</div>
	<div class="section" data-id="zts-publisher-info" style="display:none;">
		<?php
		$zts_publisher_row(_('Publisher'), htmlspecialchars((string) $zts_manifest['publisher'], ENT_QUOTES, 'UTF-8'));
		$zts_publisher_row(_('Module'), htmlspecialchars(Zts_ModuleBranding::displayName(), ENT_QUOTES, 'UTF-8'));
		if ($zts_pub_version !== '')
		{
			$zts_publisher_row(_('Version'), htmlspecialchars($zts_pub_version, ENT_QUOTES, 'UTF-8'));
		}
		$zts_publisher_row(
			_('Source code'),
			'<a href="'.htmlspecialchars($zts_repo_url, ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener noreferrer">'
				.htmlspecialchars($zts_repo_label, ENT_QUOTES, 'UTF-8')
				.' <i class="fa fa-external-link" aria-hidden="true"></i></a>'
		);
		$zts_publisher_row(
			_('License'),
			'<a href="'.htmlspecialchars((string) $zts_manifest['licenselink'], ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener noreferrer">'
				.htmlspecialchars((string) $zts_manifest['license'], ENT_QUOTES, 'UTF-8').'</a>'
		);
		?>
	</div>
</div>
