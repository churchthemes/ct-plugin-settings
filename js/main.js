/**
 * CT Plugin Settings JavaScript
 *
 * This is loaded in WordPress admin when the CT Plugin Settings class is used.
 */

jQuery( document ).ready( function( $ ) {

	// Get section
	var section = location.hash.replace( '#', '' );

	// Activate section specified in URL hash
	// Or, first tab if no hash in URL or if hash is invalid
	// Or, first tab if just restored defaults
	if ( ! section || ! $( "#ctps-tabs .nav-tab[data-section^='" + section + "']" ).length || $( '#setting-error-restore_defaults' ).length ) { // use first if none specified or if section is wrong
		section = $( '#ctps-tabs .nav-tab:first-child' ).attr( 'data-section' );
	}

	// Switch the section
	ctps_switch_section( section );

	// Now show tabs + form after initial setup
	$( '#ctps-tabs, #ctps-form' ).fadeIn( 'fast' );

	// Change tab/section on click
	$( '#ctps-tabs .nav-tab' ).click( function() {

		var section;

		section = $( this ).attr( 'data-section' );

		ctps_switch_section( section );

		$( '.settings-error' ).remove(); // hide message

	} );

	// Move checkbox fields not having a name into same row/cell as one having name above them
	$( '.ctps-checkbox' ).each( function() {

		var row;

		row = $( this ).parents( 'tr' );

		// Does this field NOT have a name?
		if ( $( 'th', row ).html() == '' ) { // th holds name

			// Previous row
			var prev_row = row.prev( 'tr' );

			// Does immediately preceding row contain checkbox AND have name?
			if ( $( 'td .ctps-checkbox', prev_row ).length && $( 'th', prev_row ).html() != '' ) {

				// Move this checkbox field to bottom of that row
				$( 'td', prev_row ).append( '<div>' + $( 'td', row ).html() );

				// Remove old row
				row.remove();

			}

		}

	} )

} );

/**
 * Switch Tab/Section
 */

function ctps_switch_section( section ) {

	// Activate tab
	jQuery( '#ctps-tabs .nav-tab' ).removeClass( 'nav-tab-active' );
	jQuery( "#ctps-tabs .nav-tab[data-section^='" + section + "']" ).addClass( 'nav-tab-active' );

	// Show settings
	jQuery( '#ctps-form tr' ).hide(); // hide others
	jQuery( '#ctps-form tr' ).each( function() {

		if ( jQuery( '.ctps-section-' + section, this ).length ) { // show row if it has setting belonging to section
			jQuery( this ).fadeIn( 'fast' );
		}

	} );

	// Add hash
	jQuery( '#ctps-form' ).attr( 'action', 'options.php#' + section ); // add to <form> post so tab stays same
	window.location.hash = section; // always show hash (such as first load w/none)

}