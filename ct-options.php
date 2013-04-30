<?php
/**
 * churchthemes.com Options Class
 *
 * This makes it easy to setup tabbed option pages for themes and plugins.
 */

if ( ! class_exists( 'CT_Options' ) ) { // in case class used in both theme and plugin

	class CT_Options {

		function __construct( $config ) {

			// Version - used in cache busting
			$this->version = '0.6.1'; // April 30, 2013

			// Config
			$this->config = $config;
			
			// Prepare fields
			$this->prepare_fields();
			
			// Add fields
			add_action( 'admin_init', array( &$this, 'add_fields' ) );
			
			// Enqueue styles
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
			
			// Enqueue scripts
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

		}

		/**
		 * Prepare Fields
		 *
		 * Convert options config into simple key => value array, without sections
		 */
		 
		function prepare_fields() {
		
			$this->fields = array();
		
			// Add fields from config
			$sections = $this->config['sections'];
			foreach( $sections as $section_key => $section ) {
			
				// Loop options in section
				if ( ! empty( $section['fields'] ) ) {
					foreach( $section['fields'] as $field_id => $field_config ) {
						$field_config['id'] = $field_id; // set key as ID in field config
						$field_config['section'] = $section_key; // make section easily accessible
						$this->fields[$field_id] = $field_config;
					}
				}
				
			}
			
		}

		/**
		 * Add Fields
		 */

		function add_fields() {
			
			// Add section for all options on the page
			add_settings_section(
				$this->config['option_id'], 			// section ID (using same as master option ID)
				'', 									// title of section (none since one section used for all)
				array( &$this, 'settings_content' ),	// callback that produces output above setting fields
				$this->config['menu_slug'] 				// menu page
			);
			
			// Add fields from config
			foreach( $this->fields as $id => $field ) {
			
				add_settings_field(
					$id,
					! empty( $field['name'] ) ? $field['name'] : '',
					array( &$this, 'field_content' ),	// callback for rendering the field
					$this->config['menu_slug'],			// menu page
					$this->config['option_id'],			// settings section (same name as master option since one used for all fields)
					array(								// arguments to pass to field_content callback
						$id,
						$field
					)
				);
			
			}
		
			// Register the fields
			register_setting(
				$this->config['option_id'],
				$this->config['option_id'],
				array( &$this, 'sanitize' ) // callback for sanitization before saving
			);		
			
		}
		
		/**
		 * Enqueue Stylesheets
		 *
		 * Load stylesheets only when option page is being shown.
		 */
		
		function enqueue_styles() {

			// Don't load where not needed
			if ( $this->is_options_page() ) {

				wp_enqueue_style( 'ct-options', trailingslashit( CTOPTIONS_URL ) . 'ct-options.css', false, $this->version ); // bust cache on update

			}
			
		}
		
		/**
		 * Enqueue Scripts
		 *
		 * Load scripts only when option page is being shown.
		 */
		
		function enqueue_scripts() {

			// Don't load where not needed
			if ( $this->is_options_page() ) {
			
				wp_enqueue_script( 'ct-options', trailingslashit( CTOPTIONS_URL ) . 'ct-options.js', false, $this->version ); // bust cache on update

			}
			
		}
		
		/**
		 * Page Content
		 *
		 * This displays the option page tabs and fields.
		 */

		function page_content() {

			// Output contents
			?>
			<div id="ctoptions-content" class="wrap">

				<?php screen_icon(); ?>
				
				<h2><?php esc_html_e( $this->config['page_title'] ); ?></h2>
				
				<?php settings_errors(); ?>
				
				<h2 id="ctoptions-tabs" class="nav-tab-wrapper">
					<?php foreach( $this->config['sections'] as $slug => $section ) : ?>
					<a href="#<?php echo esc_attr( $slug ); ?>" data-section="<?php echo esc_attr( $slug ); ?>" class="nav-tab"><?php echo esc_html( $section['title'] ); ?></a>
					<?php endforeach; ?>
				</h2>

				<form id="ctoptions-form" method="post" action="options.php">
				
					<?php
					settings_fields( $this->config['option_id'] );
					do_settings_sections( $this->config['menu_slug'] );
					submit_button();
					?>
					
					<input type="submit" class="button-secondary" name="restore_defaults" value="<?php _e( 'Restore Defaults', 'church-theme' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to restore default values for settings in all tabs?', 'church-theme' ) ); ?>');" />
				
				</form>
				
			</div>
			<?php

		}
		
		/**
		 * Settings Content
		 *
		 * Output will appear above the option fields.
		 */

		function settings_content() {		
			// nothing
		}
		
		/**
		 * Field Content
		 *
		 * Render output for a field based on its type as specified in $field from config
		 */

		function field_content( $args ) {
		
			$data = array();
		
			// Get field config from arguments
			$data['id'] = $args[0];
			$data['field'] = $args[1];
			
			// Prepare strings
			$data['value'] = $this->get( $data['id'] );
			$data['esc_value'] = esc_attr( $this->get( $data['id'] ) );
			$data['esc_element_id'] = 'ctoptions-field-' . esc_attr( $data['id'] );
			
			// Prepare styles for elements (core WP styling)
			$default_classes = array(
				'text'		=> 'regular-text',
				'textarea'	=> '',
				'checkbox'	=> '',
				'radio'		=> '',
				'select'	=> '',
				'number'	=> 'small-text'
			);
			$classes = array();
			$classes[] = 'ctoptions-' . $data['field']['type'];
			if ( ! empty( $default_classes[$data['field']['type']] ) ) {
				$classes[] = $default_classes[$data['field']['type']];
			}
			if ( ! empty( $data['field']['class'] ) ) {
				$classes[] = $data['field']['class'];
			}
			$data['classes'] = implode( ' ', $classes );
			
			// Common attributes
			$data['common_atts'] = 'name="' . esc_attr( $this->config['option_id'] . '[' . $data['id'] . ']' ) . '" class="' . esc_attr( $data['classes'] ) . '"';
			if ( ! empty( $data['field']['attributes'] ) ) { // add custom attributes
				foreach( $data['field']['attributes'] as $attr_name => $attr_value ) {
					$data['common_atts'] .= ' ' . $attr_name . '="' . esc_attr( $attr_value ) . '"';
				}		
			}
		
			// Use custom function to output field
			if ( ! empty( $data['field']['custom_content'] ) ) {
				$html = call_user_func( $data['field']['custom_content'], $args, $data );
			}
		
			// Standard output based on type
			else {
			
				// Switch thru types to render differently
				$html = '';
				switch ( $data['field']['type'] ) {
				
					// Text
					case 'text':
					
						$html = '<input type="text" ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '" value="' . $data['esc_value'] . '" />';
					
						break;

					// Textarea
					case 'textarea':
					
						$html = '<textarea ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '">' . esc_textarea( $data['value'] ) . '</textarea>';
						
						// special esc func for textarea
					
						break;

					// Checkbox
					case 'checkbox':
					
						$html  = '<input type="hidden" ' . $data['common_atts'] . ' value="" />'; // causes unchecked box to post empty value (helps with default handling)
						$html .= '<label for="' . $data['esc_element_id'] . '">';
						$html .= '	<input type="checkbox" ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '" value="1"' . checked( '1', $data['value'], false ) . '/>';
						if ( ! empty( $data['field']['checkbox_label'] ) ) {
							$html .= ' ' . $data['field']['checkbox_label'];
						}
						$html .= '</label>';
						
						break;

					// Radio
					case 'radio':
					
						if ( ! empty( $data['field']['options'] ) ) {
						
							foreach( $data['field']['options'] as $option_value => $option_text ) {
							
								$esc_radio_id = $data['esc_element_id'] . '-' . $option_value;
							
								$html .= '<div>';				
								$html .= '	<label for="' . $esc_radio_id . '">';
								$html .= '		<input type="radio" ' . $data['common_atts'] . ' id="' . $esc_radio_id . '" value="' . esc_attr( $option_value ) . '"' . checked( $option_value, $data['value'], false ) . '/> ' . esc_html( $option_text );
								$html .= '	</label>';
								$html .= '</div>';
								
							}
							
						}
					
						break;

					// Select
					case 'select':
					
						if ( ! empty( $data['field']['options'] ) ) {
						
							$html .= '<select ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '">';
							foreach( $data['field']['options'] as $option_value => $option_text ) {
								$html .= '<option value="' . esc_attr( $option_value ) . '" ' . selected( $option_value, $data['value'], false ) . '> ' . esc_html( $option_text ) . '</option>';
							}
							$html .= '</select>';
							
						}
					
						break;
				
					// Number
					case 'number':
					
						$html = '<input type="number" ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '" value="' . $data['esc_value'] . '" />';
					
						break;

				}
			
			}
			
			// Add description beneath
			if ( ! empty( $data['field']['desc'] ) ) {
				$html .= '<p class="description">' . $data['field']['desc'] . '</p>';
			}
			
			// Wrap field
			$html = '<div class="ctoptions-section-' . esc_attr( $this->fields[$data['id']]['section'] ) . '"> ' . $html . '</div>';
			
			// Output filterable
			echo apply_filters( 'ctoptions_field_content', $html, $args );
		
		}
		
		/**
		 * Sanitization
		 *
		 * Sanitize values before saving. Also handles restoring defaults.
		 */

		function sanitize( $input ) {
		
			global $allowedposttags;
						
			// Restore Defaults clicked
			// Wipe user input so all options get cleared
			if ( ! empty( $_POST['restore_defaults'] ) ) {
				$input = array();
				add_settings_error( $this->config['option_id'], 'restore_defaults', __( 'Defaults restored.', 'church-theme' ), 'updated' );			
			}
			
			// Define the array for the updated options
			$output = array();
			
			// Loop values
			foreach( $input as $key => $value ) {
			
				// Sanitization for all
				$value = trim( stripslashes( $value ) );
				
				// Sanitize based on type
				switch ( $this->fields[$key]['type'] ) {
				
					// Text
					// Textarea
					case 'text':
					case 'textarea':
										
						// Strip tags if config does not allow HTML
						if ( empty( $this->fields[$key]['allow_html'] ) ) {
							$value = trim( strip_tags( $value ) );
						}
				
						// Sanitize HTML in case used (remove evil tags like script, iframe) - same as post content
						$value = stripslashes( wp_filter_post_kses( addslashes( $value ), $allowedposttags ) );
						
						break;

					// Checkbox
					case 'checkbox':

						$value = ! empty( $value ) ? '1' : '';
					
						break;

					// Radio
					// Select
					case 'radio':
					case 'select':
					
						// If option invalid, stick with current value
						if ( ! isset( $this->fields[$key]['options'][$value] ) ) {
							$value = $this->get( $key );
						}
					
						break;
				
					// Number
					case 'number':
					
						$value = (int) $value; // force number
					
						break;

				}
				
				// Run additional custom sanitization function if config requires it
				if ( ! empty( $this->fields[$key]['custom_sanitize'] ) ) {
					$value = call_user_func( $this->fields[$key]['custom_sanitize'], $value );
				}			
				
				// Final trim
				$value = trim( $value );
				
				// Add to output array
				$output[$key] = $value;
				
			}

			// Return clean values, make filterable
			return apply_filters( 'ctoptions_sanitize', $output, $input );
		
		}
		
		/**
		 * Is Options Page
		 *
		 * Are we on an options page for a theme or plugin?
		 */
		
		function is_options_page() {
		
			$screen = get_current_screen();
			
			$is_options_page = false;
			
			// Only on an options page
			if ( preg_match( '/^.*_' . $this->config['menu_slug'] . '$/', $screen->base ) ) {
				$is_options_page = true;
			}
			
			return apply_filters( 'ctoptions_is_options_page', $is_options_page );
		
		}
		
		/**
		 * Get Option
		 *
		 * options.php should wrap this in its own option getter for more convenient use in templates, etc.
		 * This also handles returning defaults if necessary.
		 */
		 
		function get( $option ) {

			$value = '';
		
			// Get options array to pull value from
			$options = get_option( $this->config['option_id'] );
			
			// Get default value
			$default = isset( $this->fields[$option]['default'] ) ? $this->fields[$option]['default'] : '';
			
			// Option not saved - use default value
			if ( ! isset( $options[$option] ) ) {
				$value = $default;
			}
			
			// Option has been saved
			else {
				
				// Value is empty when not allowed, set default (no_empty true or is radio)
				if ( empty( $options[$option] ) && ( ! empty( $this->fields[$option]['no_empty'] ) || 'radio' == $$this->fields[$option]['type'] ) ) {
					$value = $default;
				}
				
				// Otherwise, stick with current value
				else {
					$value = $options[$option];
				}

			}

			// Return filterable
			return apply_filters( 'ctoptions_get', $value, $option );
			
		}
		
	}
	
}