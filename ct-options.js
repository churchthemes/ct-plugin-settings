/**
 * CT Options JavaScript
 *
 * This is loaded in WordPress admin when the CT Options class is used.
 */

jQuery(document).ready(function($) {

	// Activate section specified in URL hash
	// or first tab if no hash in URL or if hash is invalid
	// or first tab if just restored defaults
	var section = location.hash.replace('#', '');
	if (!section || !$("#ctoptions-tabs .nav-tab[data-section^='" + section + "']").length || $('#setting-error-restore_defaults').length) { // use first if none specified or if section is wrong
		section = $("#ctoptions-tabs .nav-tab:first-child").attr('data-section');
	}
	ctoptions_switch_section(section);
	$('#ctoptions-tabs, #ctoptions-form').fadeIn(); // now show tabs + form after initial setup
	
	// Change tab/section on click
	$('#ctoptions-tabs .nav-tab').click(function() {
		var section = $(this).attr('data-section');
		ctoptions_switch_section(section);
		jQuery('.settings-error').remove(); // hide message
	});	

	// Move checkbox fields not having a name into same row/cell as one having name above them
	$('.ctoptions-checkbox').each(function() {

		var row = $(this).parents('tr');

		// Does this field NOT have a name?
		if ($('th', row).html() == '') { // th holds name
			
			// Does immediately preceding row contain checkbox AND have name?
			var prev_row = row.prev('tr');
			if ($('td .ctoptions-checkbox', prev_row).length && $('th', prev_row).html() != '') {
			
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

function ctoptions_switch_section(section) {

	// Activate tab
	jQuery('#ctoptions-tabs .nav-tab').removeClass('nav-tab-active');
	jQuery("#ctoptions-tabs .nav-tab[data-section^='" + section + "']").addClass('nav-tab-active');

	// Show settings
	jQuery('#ctoptions-form tr').hide(); // hide others
	jQuery('#ctoptions-form tr').each(function() {
		if (jQuery('.ctoptions-section-' + section, this).length) { // show row if it has setting belonging to section
			jQuery(this).fadeIn();
		}	
	});
	
	// Add hash
	jQuery('#ctoptions-form').attr('action', 'options.php#' + section); // add to <form> post so tab stays same
	window.location.hash = section; // always show hash (such as first load w/none)

}