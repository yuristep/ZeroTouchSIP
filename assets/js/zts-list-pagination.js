/**
 * ZeroTouchSIP list pagination (bootstrap-table fixed-table-pagination / BS4).
 */
(function (global) {
	'use strict';

	var DEFAULT_SIZES = [10, 25, 50, 100];
	var VOID_HREF = 'javascript:void(0)';

	function i18n(key, fallback) {
		if (global.ZTS_I18N && global.ZTS_I18N[key]) {
			return global.ZTS_I18N[key];
		}
		return fallback;
	}

	function storageKey(id) {
		return 'zts_pagination_size_' + id;
	}

	function loadPageSize(id, fallback) {
		try {
			var v = parseInt(global.sessionStorage.getItem(storageKey(id)), 10);
			if (v > 0) {
				return v;
			}
		} catch (e) { /* ignore */ }
		return fallback;
	}

	function savePageSize(id, size) {
		try {
			global.sessionStorage.setItem(storageKey(id), String(size));
		} catch (e) { /* ignore */ }
	}

	/**
	 * @param {number} totalPages
	 * @param {number} page 1-based
	 * @return {number[]}
	 */
	function pageNumbers(totalPages, page) {
		if (totalPages <= 1) {
			return totalPages === 1 ? [1] : [];
		}
		if (totalPages <= 7) {
			var all = [];
			var i;
			for (i = 1; i <= totalPages; i++) {
				all.push(i);
			}
			return all;
		}
		var pages = [];
		var left = Math.max(2, page - 2);
		var right = Math.min(totalPages - 1, page + 2);
		pages.push(1);
		if (left > 2) {
			pages.push(-1);
		}
		for (i = left; i <= right; i++) {
			pages.push(i);
		}
		if (right < totalPages - 1) {
			pages.push(-1);
		}
		pages.push(totalPages);
		return pages;
	}

	function pageLink($, label, text) {
		return $('<a class="page-link"></a>')
			.attr('href', VOID_HREF)
			.attr('aria-label', label)
			.html(text);
	}

	/**
	 * @param {object} opts
	 * @return {object|null}
	 */
	function bind(opts) {
		var $ = global.jQuery || global.$;
		if (!$ || !opts || !opts.$table || !opts.$table.length || !opts.id) {
			return null;
		}
		var id = opts.id;
		var table = opts.$table;
		var rowSelector = opts.rowSelector || 'tr.zts-list-row';
		var pageSizes = opts.pageSizes || DEFAULT_SIZES;
		var defaultSize = opts.defaultPageSize || pageSizes[0] || 10;
		var onChange = opts.onChange;
		var $root = $('#' + id + '-pagination');
		var $info = $('#' + id + '-pagination-info');
		var $pages = $('#' + id + '-pagination-pages');
		var $sizeLabel = $('#' + id + '-page-size-label');
		if (!$root.length || !$info.length || !$pages.length) {
			return null;
		}

		var state = {
			page: 1,
			pageSize: loadPageSize(id, defaultSize)
		};
		if (pageSizes.indexOf(state.pageSize) === -1) {
			state.pageSize = defaultSize;
		}
		$sizeLabel.text(String(state.pageSize));
		$root.find('.zts-page-size-opt').removeClass('active');
		$root.find('.zts-page-size-opt[data-size="' + state.pageSize + '"]').addClass('active');

		function matchingRows() {
			return table.find('tbody ' + rowSelector).not('.zts-search-hidden');
		}

		function apply() {
			var $rows = matchingRows();
			var total = $rows.length;
			var totalPages = total > 0 ? Math.ceil(total / state.pageSize) : 0;
			if (state.page > totalPages) {
				state.page = totalPages > 0 ? totalPages : 1;
			}
			if (state.page < 1) {
				state.page = 1;
			}
			table.find('tbody ' + rowSelector).addClass('zts-page-hidden');
			if (total > 0) {
				var start = (state.page - 1) * state.pageSize;
				var end = start + state.pageSize;
				$rows.slice(start, end).removeClass('zts-page-hidden');
			}
			var from = total === 0 ? 0 : ((state.page - 1) * state.pageSize + 1);
			var to = total === 0 ? 0 : Math.min(state.page * state.pageSize, total);
			var infoTpl = i18n('paginationInfo', 'Records %from% to %to% of %total%');
			$info.text(
				infoTpl
					.replace('%from%', String(from))
					.replace('%to%', String(to))
					.replace('%total%', String(total))
			);
			renderPages(totalPages);
			if (typeof onChange === 'function') {
				onChange();
			}
		}

		function renderPages(totalPages) {
			$pages.empty();
			if (totalPages < 1) {
				return;
			}
			var prevTitle = i18n('paginationPrev', 'previous page');
			var nextTitle = i18n('paginationNext', 'next page');
			var gotoTpl = i18n('paginationGoto', 'go to page');
			var $prev = $('<li class="page-item page-pre"></li>');
			if (state.page <= 1) {
				$prev.addClass('disabled');
			}
			$prev.append(pageLink($, prevTitle, '&lsaquo;'));
			$pages.append($prev);
			var nums = pageNumbers(totalPages, state.page);
			var n;
			for (n = 0; n < nums.length; n++) {
				var num = nums[n];
				if (num === -1) {
					var $sep = $('<li class="page-item page-last-separator disabled"></li>');
					$sep.append(pageLink($, '', '&hellip;'));
					$pages.append($sep);
					continue;
				}
				var $li = $('<li class="page-item"></li>');
				if (num === state.page) {
					$li.addClass('active');
				}
				$li.append(
					pageLink($, gotoTpl + ' ' + num, String(num))
						.data('page', num)
				);
				$pages.append($li);
			}
			var $next = $('<li class="page-item page-next"></li>');
			if (state.page >= totalPages) {
				$next.addClass('disabled');
			}
			$next.append(pageLink($, nextTitle, '&rsaquo;'));
			$pages.append($next);
		}

		$pages.off('click.ztsPag').on('click.ztsPag', 'a.page-link', function (e) {
			e.preventDefault();
			var $a = $(this);
			var $li = $a.closest('li');
			if ($li.hasClass('disabled') || $li.hasClass('page-last-separator')) {
				return;
			}
			if ($li.hasClass('page-pre')) {
				state.page -= 1;
			} else if ($li.hasClass('page-next')) {
				state.page += 1;
			} else if ($a.data('page')) {
				state.page = parseInt($a.data('page'), 10) || 1;
			}
			apply();
		});

		$root.off('click.ztsPagSize').on('click.ztsPagSize', '.zts-page-size-opt', function (e) {
			e.preventDefault();
			var size = parseInt($(this).data('size'), 10);
			if (!size || pageSizes.indexOf(size) === -1) {
				return;
			}
			state.pageSize = size;
			state.page = 1;
			savePageSize(id, size);
			$sizeLabel.text(String(size));
			$root.find('.zts-page-size-opt').removeClass('active');
			$(this).addClass('active');
			apply();
		});

		apply();

		return {
			refresh: function (resetPage) {
				if (resetPage) {
					state.page = 1;
				}
				apply();
			},
			resetPage: function () {
				state.page = 1;
				apply();
			}
		};
	}

	global.ZtsListPagination = {
		bind: bind
	};
})(typeof window !== 'undefined' ? window : this);
