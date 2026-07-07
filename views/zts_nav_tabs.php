<?php
/* Horizontal tab navigation (FreePBX 17 style) */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$yt_url_phones = 'config.php?type=setup&display=zerotouchsip&zerotouchsip_form=phones_list';
$yt_url_networks = 'config.php?type=setup&display=zerotouchsip&zerotouchsip_form=networks_list';
$yt_url_general = 'config.php?type=setup&display=zerotouchsip&zerotouchsip_form=general_edit';
?>
<ul class="nav nav-tabs" role="tablist">
	<li role="presentation"<?php echo !empty($zts_tab_phones_active) ? ' class="active"' : ''; ?>>
		<a href="<?php echo htmlspecialchars($yt_url_phones, ENT_QUOTES, 'UTF-8'); ?>" aria-controls="phones_list" role="tab"><?php echo _('Phones'); ?></a>
	</li>
	<li role="presentation"<?php echo !empty($zts_tab_networks_active) ? ' class="active"' : ''; ?>>
		<a href="<?php echo htmlspecialchars($yt_url_networks, ENT_QUOTES, 'UTF-8'); ?>" aria-controls="networks_list" role="tab"><?php echo _('Networks'); ?></a>
	</li>
	<li role="presentation"<?php echo !empty($zts_tab_general_active) ? ' class="active"' : ''; ?>>
		<a href="<?php echo htmlspecialchars($yt_url_general, ENT_QUOTES, 'UTF-8'); ?>" aria-controls="general_edit" role="tab"><?php echo _('General Settings'); ?></a>
	</li>
</ul>
