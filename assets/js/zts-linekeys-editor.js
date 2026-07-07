/**
 * Line Keys (BLF / Speed Dial) table editor — shared by phones_edit and general_edit templates.
 */
(function (window) {
	'use strict';

	var MAX_KEYS = 27;
	var DEFAULT_VISIBLE = 6;

	function getJq() {
		return window.jQuery || window.$;
	}

	function normalizeKeysMap(keys) {
		if (!keys || typeof keys !== 'object') {
			return {};
		}
		if (Object.prototype.toString.call(keys) === '[object Array]') {
			var out = {};
			for (var i = 0; i < keys.length; i++) {
				if (keys[i]) {
					out[i + 1] = keys[i];
				}
			}
			return out;
		}
		return keys;
	}

	function registerEditor($) {
		if (!$) {
			return false;
		}
		if (window.ZtsLinekeysEditor) {
			return true;
		}

		function LinekeysEditor(options) {
			this.instanceId = options.instanceId;
			this.maxKeys = options.maxKeys || MAX_KEYS;
			this.defaultVisible = options.defaultVisible || DEFAULT_VISIBLE;
			this.$wrap = $('#' + this.instanceId + '-container');
			this.$table = $('#' + this.instanceId + '-table');
			this.$tbody = $('#' + this.instanceId + '-body');
			this.$btnAdd = $('#' + this.instanceId + '-btn-add');
			this.$btnEdit = $('#' + this.instanceId + '-btn-edit');
			this.$btnDelete = $('#' + this.instanceId + '-btn-delete');
			this.$selectAll = this.$table.find('thead .btSelectAll');
			this.bind();
			this.updateToolbar();
		}

		LinekeysEditor.prototype.visibleRows = function () {
			return this.$tbody.find('.zts-linekey-row').filter(function () {
				return $(this).css('display') !== 'none';
			});
		};

		LinekeysEditor.prototype.highestVisibleId = function () {
			var max = this.defaultVisible;
			this.visibleRows().each(function () {
				var id = parseInt($(this).attr('data-linekey-id'), 10);
				if (!isNaN(id) && id > max) {
					max = id;
				}
			});
			return max;
		};

		LinekeysEditor.prototype.clearRow = function ($row) {
			if (!$row.length) {
				return;
			}
			$row.find('select[name$="[type][]"], select[name="linekey_type[]"]').val('0');
			$row.find('input[type="text"]').val('');
			$row.find('select[name$="[line][]"], select[name="linekey_line[]"]').each(function () {
				this.selectedIndex = 0;
			});
			$row.find('.btSelectItem').prop('checked', false);
		};

		LinekeysEditor.prototype.hideExtraRow = function ($row) {
			if (!$row.length || !$row.hasClass('zts-linekey-extra')) {
				return;
			}
			this.clearRow($row);
			$row.hide();
		};

		LinekeysEditor.prototype.selectedIds = function () {
			var ids = [];
			this.visibleRows().find('.btSelectItem:checked').each(function () {
				var v = parseInt($(this).val(), 10);
				if (!isNaN(v)) {
					ids.push(v);
				}
			});
			return ids;
		};

		LinekeysEditor.prototype.selectedDeletableIds = function () {
			var ids = [];
			var dv = this.defaultVisible;
			this.visibleRows().find('.btSelectItem:checked').each(function () {
				var v = parseInt($(this).val(), 10);
				if (!isNaN(v) && v > dv) {
					ids.push(v);
				}
			});
			return ids;
		};

		LinekeysEditor.prototype.focusRow = function ($row) {
			if (!$row.length) {
				return;
			}
			var $el = $row.find('select, input[type="text"]').filter(':visible').first();
			if ($el.length && $row[0].scrollIntoView) {
				$row[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
			}
			if ($el.length) {
				$el.trigger('focus');
			}
		};

		LinekeysEditor.prototype.updateToolbar = function () {
			var ids = this.selectedIds();
			this.$btnEdit.prop('disabled', ids.length !== 1);
			this.$btnDelete.prop('disabled', this.selectedDeletableIds().length < 1);
			this.$btnAdd.prop('disabled', this.highestVisibleId() >= this.maxKeys);
			var vis = this.visibleRows().find('.btSelectItem').length;
			var checkedVis = this.visibleRows().find('.btSelectItem:checked').length;
			this.$selectAll.prop('checked', vis > 0 && checkedVis === vis);
		};

		LinekeysEditor.prototype.showNextRow = function () {
			var target = this.highestVisibleId() + 1;
			if (target > this.maxKeys) {
				return;
			}
			var $row = this.$tbody.find('.zts-linekey-row[data-linekey-id="' + target + '"]');
			if ($row.length) {
				$row.show();
				$row.find('.btSelectItem').prop('checked', true);
				this.focusRow($row);
				this.updateToolbar();
			}
		};

		LinekeysEditor.prototype.setRowData = function (keyId, data) {
			var $row = this.$tbody.find('.zts-linekey-row[data-linekey-id="' + keyId + '"]');
			if (!$row.length || !data) {
				return;
			}
			if (data.type !== undefined && data.type !== null) {
				$row.find('select[name$="[type][]"], select[name="linekey_type[]"]').val(String(data.type));
			}
			if (data.line !== undefined && data.line !== null) {
				$row.find('select[name$="[line][]"], select[name="linekey_line[]"]').val(String(data.line));
			}
			if (data.value !== undefined && data.value !== null) {
				$row.find('input[name$="[value][]"], input[name="linekey_value[]"]').val(String(data.value));
			}
			if (data.label !== undefined && data.label !== null) {
				$row.find('input[name$="[label][]"], input[name="linekey_label[]"]').val(String(data.label));
			}
			if (data.extension !== undefined && data.extension !== null) {
				$row.find('input[name$="[extension][]"], input[name="linekey_extension[]"]').val(String(data.extension));
			}
			var pickup = data.pickup_value !== undefined ? data.pickup_value : data.pickup;
			if (pickup !== undefined && pickup !== null) {
				$row.find('input[name$="[pickup][]"], input[name="linekey_pickup[]"]').val(String(pickup));
			}
		};

		LinekeysEditor.prototype.applyKeysMap = function (keys) {
			var self = this;
			keys = normalizeKeysMap(keys);
			if (!keys || typeof keys !== 'object') {
				return;
			}
			if (!this.$tbody.length) {
				return;
			}
			this.$tbody.find('.zts-linekey-row').each(function () {
				var $row = $(this);
				self.clearRow($row);
				if ($row.hasClass('zts-linekey-extra')) {
					$row.hide();
				} else {
					$row.show();
				}
			});
			for (var i = 1; i <= this.maxKeys; i++) {
				var rowData = keys[i] || keys[String(i)];
				if (!rowData) {
					continue;
				}
				var filled = String(rowData.type) !== '0' || rowData.value || rowData.label || rowData.extension
					|| rowData.pickup_value || rowData.pickup;
				if (i > this.defaultVisible && filled) {
					this.$tbody.find('.zts-linekey-row[data-linekey-id="' + i + '"]').show();
				}
				this.setRowData(i, rowData);
			}
			this.updateToolbar();
		};

		LinekeysEditor.prototype.bind = function () {
			var self = this;
			this.$btnAdd.on('click', function () {
				self.showNextRow();
			});
			this.$btnEdit.on('click', function () {
				var ids = self.selectedIds();
				if (ids.length === 1) {
					self.focusRow(self.$tbody.find('.zts-linekey-row[data-linekey-id="' + ids[0] + '"]'));
				}
			});
			this.$btnDelete.on('click', function () {
				var ids = self.selectedDeletableIds();
				ids.sort(function (a, b) { return b - a; });
				for (var j = 0; j < ids.length; j++) {
					self.hideExtraRow(self.$tbody.find('.zts-linekey-row[data-linekey-id="' + ids[j] + '"]'));
				}
				self.updateToolbar();
			});
			this.$selectAll.on('change', function () {
				var on = $(this).prop('checked');
				self.visibleRows().find('.btSelectItem').prop('checked', on);
				self.updateToolbar();
			});
			this.$table.on('change', 'tbody .btSelectItem', function () {
				self.updateToolbar();
			});
			this.$table.on('click', '.zts-linekey-edit', function (e) {
				e.preventDefault();
				var $row = $(this).closest('.zts-linekey-row');
				self.visibleRows().find('.btSelectItem').prop('checked', false);
				$row.find('.btSelectItem').prop('checked', true);
				self.focusRow($row);
				self.updateToolbar();
			});
			this.$table.on('click', '.zts-linekey-remove', function (e) {
				e.preventDefault();
				self.hideExtraRow($(this).closest('.zts-linekey-row'));
				self.updateToolbar();
			});
		};

		window.ZtsLinekeysEditor = {
			instances: {},
			normalizeKeysMap: normalizeKeysMap,
			init: function (options) {
				if (!options || !options.instanceId) {
					return null;
				}
				if (this.instances[options.instanceId]) {
					return this.instances[options.instanceId];
				}
				var ed = new LinekeysEditor(options);
				this.instances[options.instanceId] = ed;
				return ed;
			},
			get: function (instanceId) {
				return this.instances[instanceId] || null;
			},
			ensure: function (options) {
				return this.init(options) || this.get(options.instanceId);
			}
		};

		return true;
	}

	function runReadyQueue() {
		var queue = window.ZtsLinekeysReadyQueue;
		if (!queue || !queue.length) {
			return;
		}
		var $ = getJq();
		if (!registerEditor($)) {
			return;
		}
		while (queue.length) {
			var fn = queue.shift();
			try {
				fn();
			} catch (err) {
				if (window.console && window.console.error) {
					window.console.error('ZtsLinekeysEditor ready callback failed', err);
				}
			}
		}
	}

	window.ZtsLinekeysWhenReady = function (fn) {
		if (typeof fn !== 'function') {
			return;
		}
		if (!window.ZtsLinekeysReadyQueue) {
			window.ZtsLinekeysReadyQueue = [];
		}
		if (window.ZtsLinekeysEditor && getJq()) {
			fn();
			return;
		}
		window.ZtsLinekeysReadyQueue.push(fn);
		runReadyQueue();
	};

	function boot() {
		if (registerEditor(getJq())) {
			runReadyQueue();
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	var polls = 0;
	var pollId = window.setInterval(function () {
		polls += 1;
		if (registerEditor(getJq())) {
			runReadyQueue();
			window.clearInterval(pollId);
		} else if (polls > 80) {
			window.clearInterval(pollId);
		}
	}, 50);
})(window);
