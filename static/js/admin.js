jQuery(function ($) {

	var localized = _fw_ext_mega_menu;

	// Screen Options: Show advanced menu properties: Icon Checkbox
	(function () {

		var container = '#menu-to-edit';
		var selector = jQuery('body.branch-4-0, body.branch-4-1, body.branch-4-2, body.branch-4-3').length
			? '#icon-hide' // WP <= 4.3
			: '.hide-column-tog[name="icon-hide"]'; // WP 4.4+

		$(document).on('change', selector, function () {
			$(container).toggleClass('screen-options-icon', $(this).is(':checked'));
		});

		$(selector).trigger('change');
	})();

	// Mega Menu Column Title: input
	(function (selector) {

		$(document).on('change', selector, function () {
			$(this).closest('li').find(selector).val($(this).val());
		});
		// $(selector).trigger('change') is not necessary since those two fields
		// are populated by WordPress with the same value (title)

	})('.mega-menu-title, .edit-menu-item-title');

	// Mega Menu Column Title: checkbox
	(function (selector) {

		$(document).on('change', selector, function () {
			var checkbox = $(this);
			checkbox.closest('p').find('.mega-menu-title').prop('readonly', checkbox.is(':checked'));
		});
		$(selector).trigger('change');

	})('.mega-menu-title-off');

	// Use as Mega Menu Checkbox
	(function () {

		var menu = $('#menu-to-edit');

		function update()
		{
			menu.children().removeClass('mega-menu');
			menu.find('.mega-menu-title').prop('disabled', true);
			menu.find('.edit-menu-item-title').prop('disabled', false);
			menu.children('.menu-item-depth-0:has(.mega-menu-enabled:checked)').each(function () {
				var item = $(this);
				item.addClass('mega-menu');
				item.nextUntil('.menu-item-depth-0').addClass('mega-menu');
				item.siblings('.mega-menu').find('.mega-menu-title').prop('disabled', false);
				item.siblings('.mega-menu').find('.edit-menu-item-title').prop('disabled', true);
			});
		}

		$(document).on('change', '.menu-item-depth-0 .mega-menu-enabled', update);
		// FIXME our handler should be called after WP handler
		menu.on('sortstop', function () {
			setTimeout(update, 1);
		});

		update();

	})();

	// Monitor icon state and reflect it with dependent fields
	(function (selector) {

		$(document).on('change', selector, function (event) {
			var field = $(this).closest('.field-mega-menu-icon');
			var value = $(this).val();
			field.toggleClass('empty', value == '');
			field.find('.mega-menu-icon-i').attr('class', 'mega-menu-icon-i ' + value);
		});
		$(selector).trigger('change');

	})('.field-mega-menu-icon [data-subject=mega-menu-icon-input]');

	(function(){
		var modal = new fw.OptionsModal({
			title: localized.icon_option.label,
			options: [{
				icon: localized.icon_option
			}],
			values: {
				icon: ''
			},
			size: 'small'
		}), eventProxy = new Backbone.Model;

		// Immediately close dialog after clicking on icon
		$(modal.frame.$el).on('change', '.fw-option-type-icon input[type="hidden"]', function () {
			modal.set('values', {
				icon: $(this).val()
			});
			modal.frame.close();
		});

		{
			// Resize icon list to fit entire window
			function resizeIconList() {
				var option = modal.frame.$el.find('#fw-backend-option-fw-edit-options-modal-icon'),
					frame_content = option.closest('.media-frame-content'),
					icon_list = option.find('.js-option-type-icon-list');

				// get rid of bottom border
				option.closest('.fw-row').css('border-bottom', 'none');

				// resize icon list to fit entire window
				icon_list.css('max-height', 'none').height(1000000);
				frame_content.scrollTop(1000000);
				icon_list.height(icon_list.height() - frame_content.scrollTop());
			}

			modal.on('change:html', resizeIconList);
			$(window).resize(resizeIconList);
		}

		// Replace [Save] button by [Cancel]
		$(modal.frame.$el).find('.media-toolbar-primary')
			.html('<a href="#" class="button media-button button-large">Cancel</a>')
			.find('a').on('click', function (event) {
			event.preventDefault();
			modal.frame.close();
		});

		// Add/Edit Icon Buttons
		$(document).on('click', '[data-action=mega-menu-pick-icon]', function (event) {

			event.preventDefault();

			// prevent previous item event listener execution
			eventProxy.stopListening(modal);

			modal.set('values', {
				icon: $(event.target).closest('.field-mega-menu-icon').find('input').val()
			});

			// Listen for values change
			eventProxy.listenTo(modal, 'change:values', function(modal, values) {
				$(event.target).closest('.field-mega-menu-icon').find('input').val(values.icon).trigger('change');
			});

			modal.open();
		});
	})();

	// Remove Icon Button
	$(document).on('click', '[data-action=mega-menu-remove-icon]', function (event) {
		event.preventDefault();
		event.stopPropagation();
		$(this).closest('.field-mega-menu-icon').find('input').val('').trigger('change');
	});

	// The problem is in using **change** event for initialization.
	//
	// Internally WordPress listen this inputs for **change** event
	// and sets **menuChanged** flag. It also sets window.onbeforeunload handler
	// which decides whether or not display
	//
	//     "The changes you made will be lost if you navigate away from this page."
	//
	// dialog based on this flag.
	wpNavMenu.menusChanged = false;

	/**
	 * Item options
	 */
	(function(){
		var inst = {
			values: {},
			options: localized.item_options,
			$input: null,
			// Save item values in form hidden input
			updateInput: function(){
				if (inst.$input === null) {
					inst.$input = $('<input type="hidden" name="fw-megamenu-items-values" value="[]" />');
					$('#update-nav-menu').append(inst.$input);
				}

				inst.$input.val(JSON.stringify(inst.values));
			},
			modal: new fw.OptionsModal({
				options: []
			}),
			eventProxy: new Backbone.Model({}),
			initUi: function ($item) {
				if ($item.data('fw-ext-megamenu-options-ui-initialized')) {
					return; // already initialized
				} else {
					$item.data('fw-ext-megamenu-options-ui-initialized', true);
				}

				var id = $item.find('input.menu-item-data-db-id:first').val(),
					$button = $('<button type="button" disabled="disabled" class="button fw-megamenu-stngs"></button>')
						.text(localized.l10n.item_options_btn);

				$item.find('.field-mega-menu-icon:first')
					.append('<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>')
					.append($button);

				$.ajax({
					url: ajaxurl,
					method: 'post',
					dataType: 'json',
					data: {
						action: 'fw_ext_megamenu_item_values',
						id: id
					}
				}).done(function(r){
					if (r.success) {
						$button.removeAttr('disabled');
						inst.values[id] = r.data.values;
					} else {
						$button.text('Ajax Error');
					}
				}).fail(function(x, y, error){
					$button.text(String(error));
				});
			}
		};

		if (!_.isEmpty(inst.options)) {
			// Add ui elements on item box open
			$('#update-nav-menu').on('click', '.menu-item > .menu-item-bar .item-edit', function(e){
				_.defer(_.partial(inst.initUi, $(this).closest('.menu-item')));
			});

			// Prepare and open modal on button click
			$('#update-nav-menu').on('click', '.menu-item > .menu-item-settings button.fw-megamenu-stngs', function(e){
				var id = $(e.target).closest('.menu-item').find('input.menu-item-data-db-id:first').val();

				inst.eventProxy.stopListening(inst.modal);
				inst.modal.set('values', inst.values[id]);
				inst.eventProxy.listenTo(inst.modal, 'change:values', function(){
					inst.values[id] = inst.modal.get('values');
					inst.updateInput();
				});

				inst.modal.set('options', inst.options);

				inst.modal.open();
			});
		}
	})();
});
