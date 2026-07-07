<?php
/* Shared intro: well only (after h1 on every ZeroTouchSIP page); provisioning alert is on General Settings */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<div class="well well-sm">
	<p><?php echo Zts_ModuleBranding::description(); ?></p>
	<p class="text-muted" style="margin-bottom:0;">
		<?php echo Zts_ModuleBranding::introHint(); ?>
	</p>
</div>
