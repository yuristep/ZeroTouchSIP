/**
 * SIP line assignment table editor (phones_edit Line Configuration).
 */
(function (window) {
	'use strict';

	var MAX_LINES = 16;
	var DEFAULT_VISIBLE = 2;

	function getJq() {
		return window.jQuery || window.$;
	}

	function registerEditor($) {
		if (!$) {
			return false;
		}
		if (window.ZtsLinesEditor) {
			return true;
		}

		function LinesEditor(options) {
			this.instanceId = options.instanceId;
			this.maxLines = options.maxLines || MAX_LINES;
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

		LinesEditor.prototype.visibleRows = function () {
			return this.$tbody.find('.zts-line-row').filter(function () {
				return $(this).css('display') !== 'none';
			});
		};

		LinesEditor.prototype.highestVisibleId = function () {
			var max = this.defaultVisible;
			this.visibleRows().each(function () {
				var id = parseInt($(this).attr('data-line-id'), 10);
				if (!isNaN(id) && id > max) {
					max = id;
				}
			});
			return max;
		};

		LinesEditor.prototype.clearRow = function ($row) {
			if (!$row.length) {
				return;
			}
			$row.find('select[name="line[]"]').each(function () {
				this.selectedIndex = 0;
			});
			$row.find('input[name="label[]"]').val('');
			$row.find('.btSelectItem').prop('checked', false);
		};

		LinesEditor.prototype.hideExtraRow = function ($row) {
			if (!$row.length || !$row.hasClass('zts-line-extra')) {
				return;
			}
			this.clearRow($row);
			$row.hide();
		};

		LinesEditor.prototype.selectedIds = function () {
			var ids = [];
			this.visibleRows().find('.btSelectItem:checked').each(function () {
				var v = parseInt($(this).val(), 10);
				if (!isNaN(v)) {
					ids.push(v);
				}
			});
			return ids;
		};

		LinesEditor.prototype.selectedDeletableIds = function () {
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

		LinesEditor.prototype.focusRow = function ($row) {
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

		LinesEditor.prototype.updateToolbar = function () {
			var ids = this.selectedIds();
			this.$btnEdit.prop('disabled', ids.length !== 1);
			this.$btnDelete.prop('disabled', this.selectedDeletableIds().length < 1);
			this.$btnAdd.prop('disabled', this.highestVisibleId() >= this.maxLines);
			var vis = this.visibleRows().find('.btSelectItem').length;
			var checkedVis = this.visibleRows().find('.btSelectItem:checked').length;
			this.$selectAll.prop('checked', vis > 0 && checkedVis === vis);
		};

		LinesEditor.prototype.showNextRow = function () {
			var target = this.highestVisibleId() + 1;
			if (target > this.maxLines) {
				return;
			}
			var $row = this.$tbody.find('.zts-line-row[data-line-id="' + target + '"]');
			if ($row.length) {
				$row.show();
				$row.find('.btSelectItem').prop('checked', true);
				this.focusRow($row);
				this.updateToolbar();
			}
		};

		LinesEditor.prototype.bind = function () {
			var self = this;
			this.$btnAdd.on('click', function () {
				self.showNextRow();
			});
			this.$btnEdit.on('click', function () {
				var ids = self.selectedIds();
				if (ids.length === 1) {
					self.focusRow(self.$tbody.find('.zts-line-row[data-line-id="' + ids[0] + '"]'));
				}
			});
			this.$btnDelete.on('click', function () {
				var ids = self.selectedDeletableIds();
				ids.sort(function (a, b) { return b - a; });
				for (var j = 0; j < ids.length; j++) {
					self.hideExtraRow(self.$tbody.find('.zts-line-row[data-line-id="' + ids[j] + '"]'));
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
			this.$table.on('click', '.zts-line-edit', function (e) {
				e.preventDefault();
				var $row = $(this).closest('.zts-line-row');
				self.visibleRows().find('.btSelectItem').prop('checked', false);
				$row.find('.btSelectItem').prop('checked', true);
				self.focusRow($row);
				self.updateToolbar();
			});
			this.$table.on('click', '.zts-line-remove', function (e) {
				e.preventDefault();
				self.hideExtraRow($(this).closest('.zts-line-row'));
				self.updateToolbar();
			});
		};

		window.ZtsLinesEditor = {
			instances: {},
			init: function (options) {
				if (!options || !options.instanceId) {
					return null;
				}
				if (this.instances[options.instanceId]) {
					return this.instances[options.instanceId];
				}
				var ed = new LinesEditor(options);
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
		var queue = window.ZtsLinesReadyQueue;
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
					window.console.error('ZtsLinesEditor ready callback failed', err);
				}
			}
		}
	}

	window.ZtsLinesWhenReady = function (fn) {
		if (typeof fn !== 'function') {
			return;
		}
		if (!window.ZtsLinesReadyQueue) {
			window.ZtsLinesReadyQueue = [];
		}
		if (window.ZtsLinesEditor && getJq()) {
			fn();
			return;
		}
		window.ZtsLinesReadyQueue.push(fn);
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
