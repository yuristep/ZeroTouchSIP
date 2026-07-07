/**
 * ZeroTouchSIP list search: plain substring + FreePBX dial patterns + basic regexp.
 */
(function (global) {
	'use strict';

	function trim(s) {
		return (s || '').replace(/^\s+|\s+$/g, '');
	}

	/**
	 * @param {string} q
	 * @return {boolean}
	 */
	function isPatternQuery(q) {
		q = trim(q);
		if (q === '') {
			return false;
		}
		if (/^re:/i.test(q)) {
			return true;
		}
		if (q.charAt(0) === '/' && q.indexOf('/', 1) > 1) {
			return true;
		}
		if (q.indexOf('[') !== -1 || q.indexOf(']') !== -1) {
			return true;
		}
		if (/[XZN]/.test(q) && /[\dXZN\[\]._\-+|*]/.test(q)) {
			return true;
		}
		if (/[.*+?^${}()|\\]/.test(q) && !/^[\d\s]+$/.test(q)) {
			return true;
		}
		return false;
	}

	/**
	 * FreePBX dial pattern → RegExp (full string match on extension).
	 * X=[0-9], Z=[1-9], N=[2-9], .=.*
	 *
	 * @param {string} pattern
	 * @return {RegExp|null}
	 */
	function dialPatternToRegExp(pattern) {
		pattern = trim(pattern);
		if (pattern === '') {
			return null;
		}
		if (/^re:/i.test(pattern)) {
			try {
				return new RegExp(pattern.slice(3));
			} catch (e) {
				return null;
			}
		}
		if (pattern.charAt(0) === '/' && pattern.lastIndexOf('/') > 0) {
			var end = pattern.lastIndexOf('/');
			var body = pattern.slice(1, end);
			var flags = pattern.slice(end + 1) || 'i';
			try {
				return new RegExp(body, flags);
			} catch (e) {
				return null;
			}
		}
		if (pattern.charAt(0) === '_') {
			pattern = pattern.slice(1);
		}
		var out = '^';
		var inClass = false;
		var i;
		for (i = 0; i < pattern.length; i++) {
			var c = pattern.charAt(i);
			if (inClass) {
				out += c;
				if (c === ']' && pattern.charAt(i - 1) !== '\\') {
					inClass = false;
				}
				continue;
			}
			if (c === '[') {
				inClass = true;
				out += c;
				continue;
			}
			if (c === 'X') {
				out += '[0-9]';
				continue;
			}
			if (c === 'Z') {
				out += '[1-9]';
				continue;
			}
			if (c === 'N') {
				out += '[2-9]';
				continue;
			}
			if (c === '.') {
				out += '.*';
				continue;
			}
			if (/[\\^$.*+?()|{}]/.test(c)) {
				out += '\\' + c;
				continue;
			}
			out += c;
		}
		out += '$';
		try {
			return new RegExp(out);
		} catch (e2) {
			return null;
		}
	}

	/**
	 * @param {string} query
	 * @param {string} hay lowercased blob
	 * @param {string} extensions space-separated extension numbers
	 * @return {boolean}
	 */
	function rowMatches(query, hay, extensions) {
		query = trim(query);
		if (query === '') {
			return true;
		}
		hay = (hay || '').toLowerCase();
		extensions = trim(extensions || '');

		if (!isPatternQuery(query)) {
			return hay.indexOf(query.toLowerCase()) !== -1;
		}

		var re = dialPatternToRegExp(query);
		if (!re) {
			return hay.indexOf(query.toLowerCase()) !== -1;
		}

		var extList = extensions ? extensions.split(/\s+/) : [];
		var j;
		for (j = 0; j < extList.length; j++) {
			if (extList[j] !== '' && re.test(extList[j])) {
				return true;
			}
		}

		if (extList.length === 0 && hay !== '') {
			var parts = hay.match(/\b[0-9]{2,}\b/g) || [];
			for (j = 0; j < parts.length; j++) {
				if (re.test(parts[j])) {
					return true;
				}
			}
		}

		return false;
	}

	global.ZtsListSearch = {
		isPatternQuery: isPatternQuery,
		dialPatternToRegExp: dialPatternToRegExp,
		rowMatches: rowMatches
	};

	var STORAGE_KEY = 'zerotouchsip.phones_list.search';

	function rowMatcher() {
		if (global.ZtsListSearch && global.ZtsListSearch.rowMatches) {
			return global.ZtsListSearch.rowMatches;
		}
		return function (query, hay) {
			return query === '' || hay.indexOf(query.toLowerCase()) !== -1;
		};
	}

	function loadStoredSearch() {
		try {
			return sessionStorage.getItem(STORAGE_KEY) || '';
		} catch (e) {
			return '';
		}
	}

	function saveStoredSearch(q) {
		try {
			sessionStorage.setItem(STORAGE_KEY, q);
		} catch (e) {
			/* ignore */
		}
	}

	function filterPhoneRows($, table, q, onDone) {
		q = trim(q);
		var matcher = rowMatcher();
		table.find('tbody tr.zts-phone-row').each(function () {
			var $row = $(this);
			var hay = ($row.attr('data-zts-search') || '').toLowerCase();
			var exts = $row.attr('data-zts-extensions') || '';
			var show = true;
			try {
				show = matcher(q, hay, exts);
			} catch (e) {
				show = hay.indexOf(q.toLowerCase()) !== -1;
			}
			$row.toggleClass('zts-search-hidden', !show);
		});
		table.find('tbody tr.zts-phone-row.zts-search-hidden .btSelectItem').prop('checked', false);
		if (typeof onDone === 'function') {
			onDone();
		}
	}

	function updateClearButton($input, $clear) {
		if (!$clear || !$clear.length) {
			return;
		}
		$clear.toggleClass('zts-is-visible', trim($input.val()) !== '');
	}

	/**
	 * @param {object} opts
	 * @param {jQuery} opts.$input
	 * @param {jQuery} [opts.$clear]
	 * @param {jQuery} opts.$table
	 * @param {function} [opts.onFilter]
	 */
	function bindPhonesListSearch(opts) {
		var $ = global.jQuery || global.$;
		if (!$ || !opts || !opts.$input || !opts.$input.length || !opts.$table || !opts.$table.length) {
			return false;
		}
		var $input = opts.$input;
		var $clear = opts.$clear;
		var table = opts.$table;
		var onFilter = opts.onFilter;

		function apply(q, skipSave) {
			q = trim(q);
			if (!skipSave) {
				saveStoredSearch(q);
			}
			filterPhoneRows($, table, q, onFilter);
			updateClearButton($input, $clear);
		}

		var stored = loadStoredSearch();
		if (stored !== '') {
			$input.val(stored);
		}
		apply($input.val(), true);

		$input.off('.ztsPhoneSearch').on('input.ztsPhoneSearch keyup.ztsPhoneSearch', function () {
			apply($input.val(), false);
		});

		if ($clear && $clear.length) {
			$clear.off('.ztsPhoneSearch').on('click.ztsPhoneSearch', function (e) {
				e.preventDefault();
				$input.val('');
				apply('', false);
				$input.trigger('focus');
			});
		}
		return true;
	}

	global.ZtsPhonesListSearch = {
		storageKey: STORAGE_KEY,
		bind: bindPhonesListSearch,
		loadStored: loadStoredSearch,
		saveStored: saveStoredSearch,
		filterRows: filterPhoneRows
	};
})(typeof window !== 'undefined' ? window : this);
