<?php
/* Shared bootstrap-table list styles (phones_list, networks_list) — FreePBX 17 */
// SPDX-License-Identifier: GPL-3.0-or-later
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
static $zts_list_view_styles_loaded = false;
if ($zts_list_view_styles_loaded) {
	return;
}
$zts_list_view_styles_loaded = true;
?>
<style>
/* List page shell (align with zts-fpbx-edit-page / General Settings) */
.zts-fpbx-list-page .zts-page-title {
	margin-top: 0;
	margin-bottom: 16px;
	font-size: 24px;
	font-weight: 300;
	line-height: 1.1;
	color: inherit;
}
.zts-fpbx-list-page .zts-section-lead {
	margin: 0 0 18px;
	font-size: 13px;
	color: #737373;
}
.zts-fpbx-list-page .zts-list-empty-msg {
	margin-top: 12px;
	color: #737373;
}
/* bootstrap-table–style shell */
.zts-fpbx-list-page .zts-bt-wrap .fixed-table-container,
.zts-fpbx-list-page .zts-bt-wrap-empty .fixed-table-container {
	border: 1px solid #ddd;
	border-radius: 4px;
	background-color: #fff;
}
.zts-fpbx-list-page .zts-table-toolbar {
	padding: 8px 15px;
	border-bottom: 1px solid #ddd;
	background-color: #f9f9f9;
	border-radius: 4px 4px 0 0;
}
.zts-fpbx-list-page .zts-bt-wrap-empty .zts-table-toolbar {
	border-bottom: none;
	border-radius: 4px;
}
.zts-fpbx-list-page .zts-table-toolbar .pull-left .btn {
	margin-right: 6px;
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-table-toolbar .zts-list-toolbar-actions .btn {
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-toolbar-search-wrap {
	margin-left: 10px;
	padding-right: 0;
}
.zts-fpbx-list-page .zts-table-toolbar .zts-list-search {
	width: 220px;
	min-width: 120px;
	display: inline-block;
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-list-search-group {
	position: relative;
	display: inline-block;
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-list-search-group .zts-list-search {
	width: 220px;
	min-width: 120px;
	padding-right: 30px;
}
.zts-fpbx-list-page .zts-list-search-clear {
	position: absolute;
	right: 4px;
	top: 50%;
	margin-top: -10px;
	border: 0;
	background: transparent;
	color: #999;
	padding: 2px 6px;
	line-height: 1;
	font-size: 16px;
	cursor: pointer;
	display: none;
	z-index: 3;
}
.zts-fpbx-list-page .zts-list-search-clear.zts-is-visible {
	display: inline-block;
}
.zts-fpbx-list-page .zts-list-search-clear:hover,
.zts-fpbx-list-page .zts-list-search-clear:focus {
	color: #333;
	outline: none;
}
.zts-fpbx-list-page .zts-list-search-clear .fa {
	pointer-events: none;
}
.zts-fpbx-list-page .zts-table-responsive {
	border-radius: 0;
}
.zts-fpbx-list-page .zts-table-responsive > .table {
	margin-bottom: 0;
}
.zts-fpbx-list-page .zts-fpbx-list-table thead th {
	vertical-align: top;
	border-bottom-width: 1px;
}
.zts-fpbx-list-page .zts-fpbx-list-table thead th .fht-cell {
	height: 0;
}
.zts-fpbx-list-page .zts-fpbx-list-table thead th .th-inner {
	padding: 8px;
	line-height: 1.42857143;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.zts-fpbx-list-page .zts-fpbx-list-table thead th a.zts-th-sort {
	color: inherit;
	text-decoration: none;
	display: block;
}
.zts-fpbx-list-page .zts-fpbx-list-table thead th a.zts-th-sort:hover .th-inner,
.zts-fpbx-list-page .zts-fpbx-list-table thead th a.zts-th-sort:focus .th-inner {
	color: #337ab7;
}
.zts-fpbx-list-page .zts-fpbx-list-table thead th .th-inner.sortable {
	cursor: pointer;
}
.zts-fpbx-list-page .zts-fpbx-list-table thead th.bs-checkbox,
.zts-fpbx-list-page .zts-fpbx-list-table tbody td.bs-checkbox {
	width: 36px;
	text-align: center;
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-fpbx-list-table .btSelectAll,
.zts-fpbx-list-page .zts-fpbx-list-table .btSelectItem {
	margin: 0;
	cursor: pointer;
}
.zts-fpbx-list-page .zts-fpbx-list-table thead th.zts-actions-th {
	white-space: nowrap;
	vertical-align: middle;
	text-align: center;
}
.zts-fpbx-list-page .zts-fpbx-list-table tbody td.zts-row-actions {
	white-space: nowrap;
	vertical-align: middle;
	font-size: 14px;
	line-height: 1.2;
	display: flex;
	flex-direction: row;
	flex-wrap: nowrap;
	align-items: center;
	justify-content: center;
}
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions .zts-action-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 2px 5px;
	margin: 0;
	color: #333;
	text-decoration: none;
	cursor: pointer;
	position: relative;
	z-index: 2;
	pointer-events: auto;
}
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions .zts-action-icon .fa {
	pointer-events: none;
}
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions .zts-action-icon:hover,
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions .zts-action-icon:focus {
	color: #337ab7;
	text-decoration: none;
}
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions .zts-action-delete:hover,
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions .zts-action-delete:focus {
	color: #c9302c;
}
/* phones_list: PJSIP status in Actions column */
.zts-fpbx-list-page .zts-fpbx-list-table .zts-pjsip-status .fa {
	pointer-events: none;
}
.zts-fpbx-list-page .zts-fpbx-list-table a.zts-pjsip-status,
.zts-fpbx-list-page .zts-fpbx-list-table a.zts-pjsip-status:hover,
.zts-fpbx-list-page .zts-fpbx-list-table a.zts-pjsip-status:focus,
.zts-fpbx-list-page .zts-fpbx-list-table a.zts-pjsip-status:active {
	text-decoration: none;
	border-bottom: none;
	box-shadow: none;
}
.zts-fpbx-list-page .zts-fpbx-list-table a.zts-pjsip-status[href]:hover .fa,
.zts-fpbx-list-page .zts-fpbx-list-table a.zts-pjsip-status[href]:focus .fa {
	color: #337ab7;
}
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions .zts-pjsip-status.zts-pjsip-online .fa {
	color: #3c763d;
}
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions .zts-pjsip-status.zts-pjsip-unavailable .fa {
	color: #999;
}
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions .zts-pjsip-status {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 2px 5px;
	margin: 0;
	cursor: default;
	position: relative;
	z-index: 2;
	line-height: 1;
	text-decoration: none;
}
.zts-fpbx-list-page .zts-fpbx-list-table .zts-row-actions a.zts-pjsip-status {
	cursor: pointer;
}
/* phones_list: line key toolbar segment */
.zts-fpbx-list-page .zts-table-toolbar .zts-phone-list-linekey-toolbar {
	margin-left: 14px;
	padding-left: 14px;
	border-left: 1px solid #ccc;
}
.zts-fpbx-list-page .zts-table-toolbar .zts-phone-list-linekey-label {
	margin: 0 8px 0 0;
	font-weight: 600;
	vertical-align: middle;
	line-height: 30px;
}
.zts-fpbx-list-page .zts-table-toolbar .zts-phone-list-linekey-toolbar .zts-linekey-template-select {
	display: inline-block;
	width: 200px;
	margin-right: 6px;
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-table-toolbar .zts-phone-list-linekey-toolbar .btn {
	vertical-align: middle;
}
/* Search / pagination row visibility */
.zts-fpbx-list-page .zts-fpbx-list-table tbody tr.zts-search-hidden,
.zts-fpbx-list-page .zts-fpbx-list-table tbody tr.zts-page-hidden {
	display: none !important;
}
/* phones_list: bootstrap-table bootstrap4 (extensions list pattern) */
.zts-fpbx-list-page .zts-phone-list-bt .fixed-table-toolbar .btn {
	margin-right: 6px;
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-phone-list-bt #zts-phones-list-toolbar .zts-phone-list-linekey-toolbar {
	display: inline-block;
	margin-left: 14px;
	padding-left: 14px;
	border-left: 1px solid #ccc;
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-phone-list-bt .zts-phone-list-linekey-label {
	margin: 0 8px 0 0;
	font-weight: 600;
	vertical-align: middle;
	line-height: 30px;
}
.zts-fpbx-list-page .zts-phone-list-bt .zts-linekey-template-select {
	display: inline-block;
	width: 200px;
	margin-right: 6px;
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-phone-list-bt .zts-phones-search-wrap .zts-list-search-group {
	position: relative;
	display: inline-block;
	vertical-align: middle;
}
.zts-fpbx-list-page .zts-phone-list-bt .zts-phones-search-wrap .search-input {
	padding-right: 30px;
	min-width: 220px;
}
.zts-fpbx-list-page .zts-phone-list-bt .zts-list-search-clear {
	position: absolute;
	right: 4px;
	top: 50%;
	margin-top: -10px;
	border: 0;
	background: transparent;
	color: #999;
	padding: 2px 6px;
	line-height: 1;
	font-size: 16px;
	cursor: pointer;
	display: none;
	z-index: 3;
}
.zts-fpbx-list-page .zts-phone-list-bt .zts-list-search-clear.zts-is-visible {
	display: inline-block;
}
.zts-fpbx-list-page .zts-phone-list-bt .zts-list-search-clear:hover,
.zts-fpbx-list-page .zts-phone-list-bt .zts-list-search-clear:focus {
	color: #333;
	outline: none;
}
.zts-fpbx-list-page .zts-phone-list-bt .zts-list-search-clear .fa {
	pointer-events: none;
}
.zts-fpbx-list-page .zts-phone-list-bt .zts-fpbx-list-table thead th a.zts-th-sort {
	color: inherit;
	text-decoration: none;
	display: block;
}
</style>
