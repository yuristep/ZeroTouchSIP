/**
 * General Settings — Line Key template tabs (Contact Manager Group style).
 */
(function (window, $) {
	'use strict';

	if (!$) {
		return;
	}

	var MAX_TEMPLATES = 20;
	var TPL_PREFIX = 'tpl_';
	var tplSeq = 0;
	var addInProgress = false;

	function placeholderName() {
		return window._ztsLkTplPlaceholderName || 'New';
	}

	function newTemplateId() {
		tplSeq += 1;
		return TPL_PREFIX + String(Date.now()) + '_' + String(tplSeq);
	}

	function sanitizeTplId(id) {
		return String(id).replace(/[^a-zA-Z0-9_-]/g, '');
	}

	function replaceTplId(html, tplId) {
		return html.split('__TPLID__').join(tplId);
	}

	function instanceIdForTpl(tplId) {
		return 'zts-lk-tpl-' + sanitizeTplId(tplId);
	}

	function normName(name) {
		return $.trim(String(name)).toLowerCase();
	}

	function isPlaceholderName(name) {
		return normName(name) === normName(placeholderName());
	}

	function tabCount($panel) {
		return $panel.find('#zts-lk-tpl-tabs > li').not('.zts-lk-tpl-tab-add').length;
	}

	function hasUnfinishedDraft($panel) {
		var draft = false;
		$panel.find('#zts-lk-tpl-tab-content > .tab-pane[data-tpl-id]').each(function () {
			var $pane = $(this);
			var name = $.trim($pane.find('.zts-lk-tpl-name-input').first().val());
			if (name === '' || isPlaceholderName(name) || $pane.find('.zts-lk-tpl-is-draft').length) {
				draft = true;
				return false;
			}
		});
		return draft;
	}

	function collectTemplateNames($panel) {
		var names = [];
		$panel.find('#zts-lk-tpl-tab-content > .tab-pane[data-tpl-id]').each(function () {
			var name = $.trim($(this).find('.zts-lk-tpl-name-input').first().val());
			if (name !== '' && !isPlaceholderName(name)) {
				names.push(name);
			}
		});
		return names;
	}

	function findDuplicateName(names) {
		var seen = {};
		for (var i = 0; i < names.length; i++) {
			var key = normName(names[i]);
			if (seen[key]) {
				return names[i];
			}
			seen[key] = true;
		}
		return null;
	}

	function setDraftState($pane, isDraft) {
		if (isDraft) {
			$pane.attr('data-zts-lk-draft', '1');
			if (!$pane.find('.zts-lk-tpl-is-draft').length) {
				var tplId = $pane.attr('data-tpl-id');
				$pane.find('.zts-lk-toolbar-tpl').first().prepend(
					$('<input type="hidden" class="zts-lk-tpl-is-draft">').attr({
						name: 'linekey_tpl[' + tplId + '][is_draft]',
						value: '1'
					})
				);
			}
		} else {
			$pane.removeAttr('data-zts-lk-draft');
			$pane.find('.zts-lk-tpl-is-draft').remove();
		}
	}

	function syncTabLabel($pane) {
		var tplId = $pane.attr('data-tpl-id');
		var name = $.trim($pane.find('.zts-lk-tpl-name-input').first().val());
		if (!name || isPlaceholderName(name)) {
			name = placeholderName();
		}
		$('#zts-lk-tpl-tabs a[href="#zts-lk-tpl-pane-' + tplId + '"] .zts-lk-tpl-tab-label').text(name);
	}

	function markNameInputValidity($pane, $panel) {
		var name = $.trim($pane.find('.zts-lk-tpl-name-input').first().val());
		var $input = $pane.find('.zts-lk-tpl-name-input').first();
		var dup = false;
		if (name !== '' && !isPlaceholderName(name)) {
			var key = normName(name);
			var count = 0;
			$panel.find('.zts-lk-tpl-name-input').each(function () {
				if (normName($(this).val()) === key) {
					count += 1;
				}
			});
			dup = count > 1;
		}
		$input.toggleClass('zts-lk-tpl-name-dup', dup);
		return dup;
	}

	function enablePaneForm($pane) {
		$pane.find('fieldset.zts-lk-editor-prototype-fieldset').each(function () {
			var $fs = $(this);
			$fs.replaceWith($('<div class="zts-lk-editor-enabled"></div>').append($fs.contents()));
		});
		$pane.find(':input, button').prop('disabled', false);
	}

	function initEditorForPane($pane) {
		var tplId = sanitizeTplId($pane.attr('data-tpl-id'));
		var instanceId = instanceIdForTpl(tplId);
		if (!window.ZtsLinekeysEditor || window.ZtsLinekeysEditor.get(instanceId)) {
			return;
		}
		window.ZtsLinekeysEditor.init({
			instanceId: instanceId,
			maxKeys: 27,
			defaultVisible: 6
		});
	}

	function bindPane($pane, $panel) {
		if ($pane.data('ztsLkTplBound')) {
			return;
		}
		$pane.data('ztsLkTplBound', true);

		$pane.on('input', '.zts-lk-tpl-name-input', function () {
			var name = $.trim($(this).val());
			if (name !== '' && !isPlaceholderName(name)) {
				setDraftState($pane, false);
			} else {
				setDraftState($pane, true);
			}
			syncTabLabel($pane);
			$panel.find('.tab-pane[data-tpl-id]').each(function () {
				markNameInputValidity($(this), $panel);
			});
			updateAddButton($panel);
		});
		$pane.on('click', '.zts-lk-tpl-rename', function (e) {
			e.preventDefault();
			var name = $.trim($pane.find('.zts-lk-tpl-name-input').first().val());
			if (name === '' || isPlaceholderName(name)) {
				window.alert(window._ztsLkTplMsgRenameNew || 'Rename the template first.');
				$pane.find('.zts-lk-tpl-name-input').first().trigger('focus');
				return;
			}
			if (markNameInputValidity($pane, $panel)) {
				var dupMsg = window._ztsLkTplMsgDupName || 'Duplicate template name: %s';
				window.alert(dupMsg.replace('%s', name));
				return;
			}
			setDraftState($pane, false);
			syncTabLabel($pane);
		});
		$pane.on('click', '.zts-lk-tpl-delete-pane', function (e) {
			e.preventDefault();
			if (tabCount($panel) < 2) {
				window.alert(window._ztsLkTplMsgMinOne || 'At least one template is required.');
				return;
			}
			if (!window.confirm(window._ztsLkTplMsgDelete || 'Delete this template?')) {
				return;
			}
			var tplId = $pane.attr('data-tpl-id');
			var $tab = $('#zts-lk-tpl-tabs a[href="#zts-lk-tpl-pane-' + tplId + '"]').closest('li');
			var wasActive = $pane.hasClass('active');
			$pane.remove();
			$tab.remove();
			if (wasActive) {
				$('#zts-lk-tpl-tabs > li:first-child > a').tab('show');
			}
			updateAddButton($panel);
		});
		initEditorForPane($pane);
	}

	function updateAddButton($panel) {
		var $add = $('#zts-lk-tpl-add-tab');
		if (!$add.length) {
			return;
		}
		var atMax = tabCount($panel) >= MAX_TEMPLATES;
		var draft = hasUnfinishedDraft($panel);
		var block = atMax || draft;
		$add.closest('li').toggleClass('disabled', block);
		$add.prop('disabled', block);
		if (atMax) {
			$add.attr('title', window._ztsLkTplMsgMax || 'Maximum templates reached');
		} else if (draft) {
			$add.attr('title', window._ztsLkTplMsgRenameNew || 'Rename New template first');
		} else {
			$add.attr('title', window._ztsLkTplMsgAdd || 'Add template');
		}
	}

	function validateBeforeSave($panel) {
		if (hasUnfinishedDraft($panel)) {
			return window._ztsLkTplMsgRenameNew || 'Rename the New template first.';
		}
		var names = collectTemplateNames($panel);
		if (names.length < 1) {
			return window._ztsLkTplMsgMinOne || 'At least one template is required.';
		}
		var dup = findDuplicateName(names);
		if (dup) {
			var msg = window._ztsLkTplMsgDupName || 'Duplicate template name: %s';
			return msg.replace('%s', dup);
		}
		return '';
	}

	function addTemplate($panel) {
		if (addInProgress) {
			return;
		}
		if (hasUnfinishedDraft($panel)) {
			window.alert(window._ztsLkTplMsgRenameNew || 'Rename the New template first.');
			return;
		}
		if (tabCount($panel) >= MAX_TEMPLATES) {
			return;
		}
		var $src = $('#zts-lk-tpl-clone-source > .tab-pane').first();
		if (!$src.length) {
			return;
		}

		addInProgress = true;
		var tplId = newTemplateId();
		if ($('#zts-lk-tpl-pane-' + tplId).length) {
			addInProgress = false;
			return;
		}

		var ph = placeholderName();
		var html = replaceTplId($src[0].outerHTML, tplId);
		var $pane = $(html);
		$pane.attr('id', 'zts-lk-tpl-pane-' + tplId);
		$pane.attr('data-tpl-id', tplId);
		enablePaneForm($pane);
		$pane.find('.zts-lk-tpl-name-input').val(ph);
		setDraftState($pane, true);
		$('#zts-lk-tpl-tab-content').append($pane);

		var $li = $('<li role="presentation"></li>');
		var $a = $('<a href="#zts-lk-tpl-pane-' + tplId + '" data-toggle="tab" role="tab" class="zts-lk-tpl-tab-link"></a>');
		$a.append($('<span class="zts-lk-tpl-tab-label"></span>').text(ph));
		$li.append($a);
		$('#zts-lk-tpl-tabs .zts-lk-tpl-tab-add').before($li);

		bindPane($pane, $panel);
		$a.tab('show');
		syncTabLabel($pane);
		$pane.find('.zts-lk-tpl-name-input').first().trigger('focus').trigger('select');
		updateAddButton($panel);

		window.setTimeout(function () {
			addInProgress = false;
		}, 400);
	}

	function initPanel($panel) {
		if ($panel.data('ztsLkTplInited')) {
			return;
		}
		$panel.data('ztsLkTplInited', true);

		$panel.find('#zts-lk-tpl-tab-content > .tab-pane[data-tpl-id]').each(function () {
			var $pane = $(this);
			var name = $.trim($pane.find('.zts-lk-tpl-name-input').first().val());
			if (name === '' || isPlaceholderName(name)) {
				setDraftState($pane, true);
			}
			bindPane($pane, $panel);
			markNameInputValidity($pane, $panel);
		});

		$panel.on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
			var href = $(this).attr('href');
			if (!href || href === '#') {
				return;
			}
			initEditorForPane($(href));
		});

		$('#zts-lk-tpl-add-tab').off('click.ztsLkTplAdd').on('click.ztsLkTplAdd', function (e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			if ($(this).closest('li').hasClass('disabled') || $(this).prop('disabled')) {
				if (hasUnfinishedDraft($panel)) {
					window.alert(window._ztsLkTplMsgRenameNew || 'Rename the New template first.');
				}
				return;
			}
			addTemplate($panel);
		});

		var $form = $('#zts-general-edit-form');
		if ($form.length) {
			$form.off('submit.ztsLkTpl').on('submit.ztsLkTpl', function (e) {
				var err = validateBeforeSave($panel);
				if (err) {
					e.preventDefault();
					window.alert(err);
					return false;
				}
			});
		}

		updateAddButton($panel);
	}

	$(function () {
		var $panel = $('#zts-lk-templates-panel');
		if (!$panel.length) {
			return;
		}
		initPanel($panel);
	});
})(window, window.jQuery || window.$);
