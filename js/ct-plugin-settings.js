/**
 * CT Plugin Settings JavaScript
 *
 * This is loaded in WordPress admin when the CT Plugin Settings class is used.
 */

jQuery(document).ready(function ($) {

	// Get section
	var section = location.hash.replace('#', '');

	// Activate section specified in URL hash
	// Or, first tab if no hash in URL or if hash is invalid
	// Or, first tab if just restored defaults
	if (!section || !$("#ctps-tabs .nav-tab[data-section^='" + section + "']").length || $('#setting-error-restore_defaults').length) { // use first if none specified or if section is wrong
		section = $('#ctps-tabs .nav-tab:first-child').attr('data-section');
	}

	// Switch the section
	ctps_switch_section(section);

	// Now show tabs + form after initial setup
	$('#ctps-tabs, #ctps-form').fadeIn('fast');

	// Change tab/section on click
	$('#ctps-tabs .nav-tab').on('click', function () {

		var section;

		section = $(this).attr('data-section');

		ctps_switch_section(section);

		$('.settings-error').remove(); // hide message

	});

	// Move checkbox fields not having a name into same row/cell as one having name above them
	$('.ctps-checkbox').each(function () {

		var row;

		row = $(this).parents('tr');

		// Does this field NOT have a name?
		if ($('th', row).html() == '') { // th holds name

			// Previous row
			var prev_row = row.prev('tr');

			// Does immediately preceding row contain checkbox AND have name?
			if ($('td .ctps-checkbox', prev_row).length && $('th', prev_row).html() != '') {

				// Move this checkbox field to bottom of that row
				$('td', prev_row).append('<div>' + $('td', row).html());

				// Remove old row
				row.remove();

			}

		}

	});

	// Open media uploader on button click.
	$('body').on('click', '.ctps-upload-file', function (event) {

		var frame;

		// Stop click to URL
		event.preventDefault();

		// Input element
		$input_element = $(this).prev('input');

		// Only if button not disabled.
		if (!$(this).hasClass('button-disabled')) {

			// Media frame
			frame = wp.media({
				title: $(this).attr('data-ctps-upload-title'),
				library: { type: $(this).attr('data-ctps-upload-type') },
				multiple: false
			});

			// Open media frame
			frame.open();

			// Set attachment URL on click of button
			// (don't do on 'close' so user can cancel)
			frame.on('select', function () {

				var attachments, attachment;

				// Get attachment data
				attachments = frame.state().get('selection').toJSON();
				attachment = attachments[0];

				// An attachment is selected
				if (typeof attachment != 'undefined') {

					// Set attachment URL on input
					if (attachment.url) {

						$input_element.val(attachment.url); // input is directly before button

						// Trigger change in case field is set to show an image preview.
						$input_element.trigger('keyup');

					}

				}

			});

		}

	});

	// Show image when upload field changed.
	var $image_upload_elements = $('input[data-ctps-upload-show-image].ctps-upload');
	$image_upload_elements.each(function () {

		// Detect URL change.
		$(this).on('change, keyup', function () {

			var $input = $(this);

			// Get image width.
			var image_width = $input.data('ctps-upload-show-image');

			// Have width.
			if (image_width) {

				// Get image URL.
				var image_url = $input.val().trim();

				// Have image URL.
				if (image_url) {

					// Get container to add image to end of.
					var $container = $($input).parent('.ctps-section');

					// Get existing image, if any.
					var image_preview_url = $('.ctps-upload-image', $container).attr('src');

					// Same image not already present.
					if (image_url !== image_preview_url) {

						// Remove any existing preview image.
						$('.ctps-upload-image', $container).remove();

						// Add image element.
						$container.append('<img class="ctps-upload-image" style="width: ' + image_width + 'px; display: none;">');

						// Add src to image and fade in.
						$('.ctps-upload-image', $container)
							.attr('src', image_url)
							.fadeIn('fast');

					}

				}

				// No image URL.
				else {
					// Remove any existing preview image.
					$('.ctps-upload-image', $container).remove();
				}
			}

		});


	}).trigger('keyup'); // trigger on first load.

	// Prevent clicks on disabled buttons.
	$('#ctps-form a.button-disabled').on('click', function (e) {
		e.preventDefault();
	});


});

/**
 * Switch Tab/Section
 */

function ctps_switch_section(section) {

	// Add class to <body>
	// This is helpful for CSS based on section
	jQuery('#ctps-tabs .nav-tab').each(function () { // remove all first
		jQuery('body').removeClass('ctps-active-section-' + jQuery(this).attr('data-section'));
	});
	jQuery('body').addClass('ctps-active-section-' + section);

	// Activate tab
	jQuery('#ctps-tabs .nav-tab').removeClass('nav-tab-active');
	jQuery("#ctps-tabs .nav-tab[data-section^='" + section + "']").addClass('nav-tab-active');

	// Show description
	jQuery('.ctps-section-desc').hide(); // hide others
	jQuery('#ctps-section-desc-' + section).fadeIn('fast');

	// Show settings
	jQuery('#ctps-form tr').hide(); // hide others
	jQuery('#ctps-form tr').each(function () {

		if (jQuery('.ctps-section-' + section, this).length) { // show row if it has setting belonging to section
			jQuery(this).fadeIn('fast');
		}

	});

	// Show button only if section has fields
	// Add-on Licenses section will always begin empty
	if (jQuery('.ctps-section-' + section + ' .ctps-field').length) {
		jQuery('#ctps-form .submit').show();
	} else {
		jQuery('#ctps-form .submit').hide();
	}

	// Add hash
	jQuery('#ctps-form').attr('action', 'options.php#' + section); // add to <form> post so tab stays same
	window.location.hash = section; // always show hash (such as first load w/none)

}
