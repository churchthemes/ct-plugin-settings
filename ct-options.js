/**
 * CT Options JavaScript
 *
 * This is loaded in WordPress admin when the CT Options class is used.
 */

jQuery( document ).ready( function( $ ) {

	// Activate section specified in URL hash
	// or first tab if no hash in URL or if hash is invalid
	// or first tab if just restored defaults
	var section = location.hash.replace('#', '');
	if (!section || !$("#cto-tabs .nav-tab[data-section^='" + section + "']").length || $('#setting-error-restore_defaults').length) { // use first if none specified or if section is wrong
		section = $("#cto-tabs .nav-tab:first-child").attr('data-section');
	}
	cto_switch_section(section);
	$('#cto-tabs, #cto-form').fadeIn(); // now show tabs + form after initial setup

	// Change tab/section on click
	$('#cto-tabs .nav-tab').click(function() {
		var section = $(this).attr('data-section');
		cto_switch_section(section);
		jQuery('.settings-error').remove(); // hide message
	});

	// Move checkbox fields not having a name into same row/cell as one having name above them
	$('.cto-checkbox').each(function() {

		var row = $(this).parents('tr');

		// Does this field NOT have a name?
		if ($('th', row).html() == '') { // th holds name

			// Does immediately preceding row contain checkbox AND have name?
			var prev_row = row.prev('tr');
			if ($('td .cto-checkbox', prev_row).length && $('th', prev_row).html() != '') {

				// Move this checkbox field to bottom of that row
				$('td', prev_row).append('<div>' + $('td', row).html());

				// Remove old row
				row.remove();

			}

		}

	})

});

/**
 * Switch Tab/Section
 */

function cto_switch_section(section) {

	// Activate tab
	jQuery('#cto-tabs .nav-tab').removeClass('nav-tab-active');
	jQuery("#cto-tabs .nav-tab[data-section^='" + section + "']").addClass('nav-tab-active');

	// Show settings
	jQuery('#cto-form tr').hide(); // hide others
	jQuery('#cto-form tr').each(function() {
		if (jQuery('.cto-section-' + section, this).length) { // show row if it has setting belonging to section
			jQuery(this).fadeIn();
		}
	});

	// Add hash
	jQuery('#cto-form').attr('action', 'options.php#' + section); // add to <form> post so tab stays same
	window.location.hash = section; // always show hash (such as first load w/none)

}