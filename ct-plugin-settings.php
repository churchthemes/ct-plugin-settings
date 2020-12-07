<?php
/**
 * CT Plugin Settings
 *
 * This class generates a tabbed settings page for WordPress plugins.
 * It also provides a method for retieving settings while considering defaults.
 *
 * See Church Content plugin for example usage:
 *
 * https://github.com/churchthemes/church-theme-content
 *
 * @package   CT_Plugin_Settings
 * @copyright Copyright (c) 2013 - 2020, ChurchThemes.com
 * @link      https://github.com/churchthemes/ct-plugin-settings
 * @license   GPLv2 or later
 */

// No direct access.
if (! defined( 'ABSPATH' )) {
	exit;
}

// Class may be used in multiple plugins.
if (! class_exists( 'CT_Plugin_Settings' )) { // in case class used in both theme and plugin.

	/**
	 * Main class.
	 *
	 * @since 0.6
	 */
	class CT_Plugin_Settings {

		/**
		 * Plugin version.
		 *
		 * @since 0.6.
		 * @var string
		 */
		public $version;

		/**
		 * Settings configuration
		 *
		 * @since 0.6
		 * @var array
		 */
		public $config;

		/**
		 * Plugin file
		 *
		 * @since 0.7
		 * @var array
		 */
		public $plugin_file;

		/**
		 * Plugin's directory name
		 *
		 * @since 0.7
		 * @var array
		 */
		public $plugin_dir;

		/**
		 * Sections data
		 *
		 * @since 0.9
		 * @var array
		 */
		public $sections;

		/**
		 * Fields data
		 *
		 * @since 0.6
		 * @var array
		 */
		public $fields;

		/**
		 * Settings page hook suffix
		 *
		 * This matches get_current_screen() base
		 *
		 * @since 0.9
		 * @var string
		 */
		public $page_hook_suffix;

		/**
		 * Constructor
		 *
		 * @since 0.6
		 * @access public
		 * @param array $config Configuration for settings page, menu and fields.
		 */
		public function __construct( $config ) {

			// Version.
			$this->version = '1.2.3';

			// Prepare data.
			$this->prepare_data( $config );

			// Add page.
			add_action( 'admin_menu', array( &$this, 'add_page' ) );

			// Add plugin action link (Plugins page).
			add_filter( 'plugin_action_links_' . $this->plugin_file, array( &$this, 'add_plugin_action_link' ) );

			// Add fields.
			add_action( 'admin_init', array( &$this, 'add_fields' ) );

			// Enqueue styles.
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );

			// Enqueue scripts.
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

		}

		/**
		 * Prepare Data
		 *
		 * @since 0.7
		 * @param array $config Settings configuration.
		 */
		public function prepare_data( $config ) {

			// Make config available.
			$this->config = apply_filters( 'ctps_config', $config );

			// Prepare plugin data.
			$this->plugin_file = plugin_basename( $this->config['plugin_file'] ); // plugin-name/plugin-name.php.
			$this->plugin_dir = dirname( $this->plugin_file ); // plugin-name (useful for menu slug).

			// Prepare sections.
			$this->prepare_sections();

			// Prepare fields.
			$this->prepare_fields();

		}

		/**
		 * Prepare Sections
		 *
		 * Make them filterable
		 *
		 * @since 0.9
		 * @access public
		 */
		public function prepare_sections() {

			$this->sections = $this->config['sections'];

			// Filter sections.
			$this->sections = apply_filters( 'ctps_sections', $this->sections );

			// Filter individual sections.
			foreach ($this->sections as $section_key => $section) {
				$this->sections[ $section_key ] = apply_filters( 'ctps_section-' . $section_key, $section );
			}

		}

		/**
		 * Prepare Fields
		 *
		 * Convert settings config into simple key => value array, without sections
		 *
		 * @since 0.6
		 * @access public
		 */
		public function prepare_fields() {

			$this->fields = array();

			// Add fields from config.
			foreach ($this->sections as $section_key => $section) {

				// Get fields for section.
				$fields = isset( $section['fields'] ) ? (array) $section['fields'] : arrray();

				// Filter fields.
				$fields = apply_filters( 'ctps_fields', $fields, $section_key );
				$fields = apply_filters( 'ctps_fields-' . $section_key, $fields );

				// Loop fields.
				foreach ($fields as $field_id => $field_config) {

					$field_config['id'] = $field_id; // set key as ID in field config.
					$field_config['section'] = $section_key; // make section easily accessible.

					$this->fields[ $field_id ] = $field_config;

				}

			}

		}

		/**
		 * Add Page
		 *
		 * This will add a link to the Settings menu.
		 *
		 * @since 0.7
		 * @access public
		 */
		public function add_page() {

			// Add options page to menu.
			$this->page_hook_suffix = add_options_page(
				$this->config['page_title'],             // text shown in window title.
				esc_html( $this->config['menu_title'] ), // text shown in menu.
				'manage_options',                        // role/capability with access.
				$this->plugin_dir,                       // unique menu/page slug.
				array( &$this, 'page_content' )          // callback providing output for page.
			);

			// Action after save.
			add_action( 'load-' . $this->page_hook_suffix, array( &$this, 'after_save' ) );

		}

		/**
		 * After Save
		 *
		 * Provide an action that runs after save is done (after redirect to &settings-updated=true)
		 *
		 * @since 0.9
		 * @access public
		 */
		public function after_save() {

			// Plugin settings just saved.
			if ($this->is_settings_page() && ! empty( $_GET['settings-updated'] )) {
				do_action( 'ctps_after_save' );
			}

		}

		/**
		 * Add Plugin Action Link
		 *
		 * This will insert a "Settings" link into the plugin's action links (Plugin page's list)
		 *
		 * @since 0.7
		 * @access public
		 * @param array $links Existing action links.
		 * @return array Modified action links.
		 */
		public function add_plugin_action_link( $links ) {

			// Have links array?
			if (is_array( $links )) {

				// Append "Settings" link.
				$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . $this->plugin_dir ) ) . '">' . __( 'Settings' ) . '</a>'; // use core WP 'Settings' string for this class to be plugin-agnostic.

			}

			return $links;

		}

		/**
		 * Add Fields
		 *
		 * @since 0.6
		 * @access public
		 */
		public function add_fields() {

			// Add section for all options on the page.
			add_settings_section(
				$this->config['option_id'],          // section ID (using same as master option ID).
				'',                                  // title of section (none since one section used for all).
				array( &$this, 'settings_content' ), // callback that produces output above setting fields.
				$this->plugin_dir                    // menu page.
			);

			// Add fields from config.
			foreach ($this->fields as $id => $field) {

				// Name.
				$name = '';
				if (! empty( $field['name'] )) {

					// Get name.
					$name = $field['name'];

					// Append text after name.
					if (! empty( $field['after_name'] )) {
						$name .= ' <span class="ctps-after-name">' . $field['after_name'] . '</span>';
					}

					// Allow only basic HTML.
					$name = wp_kses(
						$name,
						array(
							'i' => array(),
							'em' => array(),
							'br' => array(),
							'span' => array(
								'class' => array(),
								'id' => array(),
							),
							'a' => array(
								'href' => array(),
								'target' => array(),
							),
							'code' => array(),
						)
					);

				}

				// Add field.
				add_settings_field(
					$id,
					$name,
					array( &$this, 'field_content' ), // callback for rendering the field.
					$this->plugin_dir,                // menu page.
					$this->config['option_id'],       // settings section (same name as master option since one used for all fields).
					array(                            // arguments to pass to field_content callback.
						$id,
						$field,
						'class' => 'ctps-field-' . $id,
					)
				);

			}

			// Register the fields.
			register_setting(
				$this->config['option_id'],
				$this->config['option_id'],
				array( &$this, 'sanitize' ) // callback for sanitization before saving.
			);

		}

		/**
		 * Enqueue Stylesheets
		 *
		 * Load stylesheets only when settings page is being shown.
		 *
		 * @since 0.6
		 * @access public
		 */
		public function enqueue_styles() {

			// Don't load where not needed.
			if ($this->is_settings_page()) {
				wp_enqueue_style( 'ct-plugin-settings', trailingslashit( $this->config['url'] ) . 'css/ct-plugin-settings.css', false, $this->version ); // bust cache on update.
			}

		}

		/**
		 * Enqueue Scripts
		 *
		 * Load scripts only when option page is being shown.
		 *
		 * @since 0.6
		 * @access public
		 */
		public function enqueue_scripts() {

			// Don't load where not needed.
			if ($this->is_settings_page()) {

				// Enable media-upload and thickbox for upload fields.
				wp_enqueue_media();

				// Settings JS.
				wp_enqueue_script( 'ct-plugin-settings', trailingslashit( $this->config['url'] ) . 'js/ct-plugin-settings.js', false, $this->version ); // bust cache on update.

			}

		}

		/**
		 * Page Content
		 *
		 * This displays the settings page tabs and fields.
		 *
		 * @since 0.6
		 * @access public
		 */
		public function page_content() {

			// Output contents.
			?>
			<div id="ctps-content" class="wrap">

				<h2><?php echo esc_html( $this->config['page_title'] ); ?></h2>

				<?php if (! empty( $this->config['desc'] )) : ?>

					<p>
						<?php
							echo wp_kses(
								/* translators: %1$s is URL to Add-ons */
								$this->config['desc'],
								array(
									'b' => array(),
									'strong' => array(),
									'i' => array(),
									'em' => array(),
									'br' => array(),
									'a' => array(
										'href' => array(),
										'target' => array(),
									),
									'span' => array(
										'class' => array(),
										'id' => array(),
									),
									'code' => array(),
								)
							);
						?>
					</p>

				<?php endif; ?>

				<h2 id="ctps-tabs" class="nav-tab-wrapper">

					<?php foreach ($this->sections as $slug => $section) : ?>

						<a href="#<?php echo esc_attr( $slug ); ?>" data-section="<?php echo esc_attr( $slug ); ?>" class="nav-tab"><?php echo esc_html( $section['title'] ); ?></a>

					<?php endforeach; ?>

				</h2>

				<form id="ctps-form" method="post" action="options.php">

					<?php settings_fields( $this->config['option_id'] ); ?>

					<?php do_settings_sections( $this->plugin_dir ); ?>

					<?php submit_button(); ?>

				</form>

			</div>
			<?php

		}

		/**
		 * Settings Content
		 *
		 * Output will appear above the fields.
		 *
		 * @since 0.6
		 * @access public
		 */
		public function settings_content() {

			if (! empty( $this->sections )) {

				// Output description for each section.
				foreach ($this->sections as $section_slug => $section) {

					// JavaScript will show the desription for the active tab.
					if (! empty( $section['desc'] )) {
						echo '<p id="ctps-section-desc-' . esc_attr( $section_slug ) . '" class="ctps-section-desc">';
						echo wp_kses(
							$section['desc'],
							array(
								'b' => array(),
								'strong' => array(),
								'i' => array(),
								'em' => array(),
								'br' => array(),
								'a' => array(
									'href' => array(),
									'target' => array(),
								),
								'span' => array(
									'class' => array(),
									'id' => array(),
								),
								'code' => array(),
							)
						);
						echo '</p>';
					}

				}

			}

		}

		/**
		 * Field Content
		 *
		 * Render output for a field based on its type as specified in $field from config
		 *
		 * @since 0.6
		 * @access public
		 * @param array $args Field arguments.
		 */
		public function field_content( $args ) {

			$data = array();

			// Get field config from arguments.
			$data['id'] = $args[0];
			$data['field'] = $args[1];

			// Prepare strings.
			$data['value'] = $this->get( $data['id'] );
			$data['esc_value'] = esc_attr( $this->get( $data['id'] ) );
			$data['esc_element_id'] = 'ctps-field-' . esc_attr( $data['id'] );

			// Default classes.
			// Prepare styles for elements (core WP styling).
			$default_classes = array(
				'text'     => 'regular-text',
				'url'      => 'regular-text',
				'textarea' => '',
				'upload'   => 'regular-text',
				'checkbox' => '',
				'radio'    => '',
				'select'   => '',
				'number'   => 'small-text',
				'content'  => '',
			);

			// Start classes array.
			$classes = array();

			// Always use these classes.
			$classes[] = 'ctps-field';
			$classes[] = 'ctps-' . $data['field']['type'];

			// Type class.
			if (! empty( $default_classes[ $data['field']['type'] ] )) {
				$classes[] = $default_classes[ $data['field']['type'] ];
			}

			// Custom class.
			if (! empty( $data['field']['class'] )) {
				$classes[] = trim( $data['field']['class'] );
			}

			// Build classes string.
			$data['classes'] = implode( ' ', $classes );

			// Common attributes.
			$data['common_atts'] = 'name="' . esc_attr( $this->config['option_id'] . '[' . $data['id'] . ']' ) . '" class="' . esc_attr( $data['classes'] ) . '"';
			if (! empty( $data['field']['attributes'] )) { // add custom attributes.

				foreach ($data['field']['attributes'] as $attr_name => $attr_value) {
					$data['common_atts'] .= ' ' . $attr_name . '="' . esc_attr( $attr_value ) . '"';
				}

			}

			// Inline radio inputs?
			$inline_class = '';
			if (! empty( $data['field']['inline'] )) {
				$inline_class = ' ctps-inline';
			}

			// Use custom function to output field.
			if (! empty( $data['field']['custom_content'] )) {
				$html = call_user_func( $data['field']['custom_content'], $args, $data );
			}

			// Standard output based on type.
			else {

				$html = '';

				// Switch thru types to render differently.
				switch ($data['field']['type']) {

					// Text.
					case 'text':
					case 'url': // same as text.

						$html = '<input type="text" ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '" value="' . $data['esc_value'] . '" />';

						break;

					// Textarea.
					case 'textarea':

						$html = '<textarea ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '">' . esc_textarea( $data['value'] ) . '</textarea>';

						break;

					// Upload (URL).
					case 'upload':

						$upload_button = isset( $data['field']['upload_button'] ) ? $data['field']['upload_button'] : '';
						$upload_title = isset( $data['field']['upload_title'] ) ? $data['field']['upload_title'] : '';
						$upload_type = isset( $data['field']['upload_type'] ) ? $data['field']['upload_type'] : '';
						$upload_show_image = isset( $data['field']['upload_show_image'] ) ? $data['field']['upload_show_image'] : '';

						$html  = '<input type="text" ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '" value="' . $data['esc_value'] . '" data-ctps-upload-show-image="' . esc_attr( $upload_show_image ) . '" />';
						$html .= '<input type="button" value="' . esc_attr( $upload_button ) . '" class="upload_button button ctps-upload-file" data-ctps-upload-type="' . esc_attr( $upload_type ) . '" data-ctps-upload-title="' . esc_attr( $upload_title ) . '" /> ';

						break;

					// Checkbox.
					case 'checkbox':

						$html  = '<input type="hidden" ' . $data['common_atts'] . ' value="" />'; // causes unchecked box to post empty value (helps with default handling).

						$html .= '<label for="' . $data['esc_element_id'] . '">';

						$html .= '	<input type="checkbox" ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '" value="1"' . checked( '1', $data['value'], false ) . '/>';

						if (! empty( $data['field']['checkbox_label'] )) {
							$html .= $data['field']['checkbox_label'];
						}

						$html .= '</label>';

						break;

					// Radio.
					case 'radio':

						if (! empty( $data['field']['options'] )) {

							foreach ($data['field']['options'] as $option_value => $option_text) {

								$esc_radio_id = $data['esc_element_id'] . '-' . $option_value;

								$html .= '<div class="ctps-radio-container' . esc_attr( $inline_class ) . '">';
								$html .= '	<label for="' . $esc_radio_id . '">';
								$html .= '		<input type="radio" ' . $data['common_atts'] . ' id="' . $esc_radio_id . '" value="' . esc_attr( $option_value ) . '"' . checked( $option_value, $data['value'], false ) . '/>' . esc_html( $option_text );
								$html .= '	</label>';
								$html .= '</div>';

							}

						}

						break;

					// Select.
					case 'select':

						if (! empty( $data['field']['options'] )) {

							$html .= '<select ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '">';

							foreach ($data['field']['options'] as $option_value => $option_text) {
								$html .= '<option value="' . esc_attr( $option_value ) . '" ' . selected( $option_value, $data['value'], false ) . '> ' . esc_html( $option_text ) . '</option>';
							}

							$html .= '</select>';

						}

						break;

					// Number.
					case 'number':

						$html = '<input type="number" ' . $data['common_atts'] . ' id="' . $data['esc_element_id'] . '" value="' . $data['esc_value'] . '" />';

						break;

					// Content.
					// Just content from 'content' argument. Nothing to save.
					case 'content':

						$html = '';

						if (! empty( $data['field']['content'] )) {
							$html .= wp_kses_post( $data['field']['content'] );
						}

						break;

				}

			}

			// Filter field after content (before description).
			$html = apply_filters( 'ctps_field_content_after', $html, $args );

			// Add description beneath.
			if (! empty( $data['field']['desc'] )) {
				$html .= '<p class="description">';
				$html .= wp_kses(
					$data['field']['desc'],
					array(
						'b' => array(),
						'strong' => array(),
						'a' => array(
							'href' => array(),
							'target' => array(),
						),
						'br' => array(),
						'span' => array(
							'class' => array(),
							'id' => array(),
						),
						'code' => array(),
					)
				);
				$html .= '</p>';
			}

			// Wrap field.
			$html = '<div class="ctps-section ctps-section-' . esc_attr( $this->fields[ $data['id'] ]['section'] ) . '"> ' . $html . '</div>';

			// Output filterable.
			echo apply_filters( 'ctps_field_content', $html, $args );

		}

		/**
		 * Sanitization
		 *
		 * Sanitize values before saving.
		 *
		 * @since 0.6
		 * @access public
		 * @param array $input Values being saved.
		 * @return array Sanitized values.
		 * @global array $allowedposttags.
		 */
		public function sanitize( $input ) {

			global $allowedposttags;

			// Define the array for the updated options.
			$output = array();

			// Loop values.
			foreach ($input as $key => $value) {

				// Invalid key.
				// It is possible a plugin providing a setting has been deactivated.
				// This avoids undefined index notice.
				if (! isset( $this->fields[ $key ] )) {
					$value = '';
				}

				// Valid key.
				else {

					// Trim all values.
					$value = trim( stripslashes( $value ) );

					// Sanitize based on type.
					switch ($this->fields[ $key ]['type']) {

						// Text.
						// Textarea.
						case 'text':
						case 'textarea':

							// Strip tags if config does not allow HTML.
							if (empty( $this->fields[ $key ]['allow_html'] )) {
								$value = trim( wp_strip_all_tags( $value ) );
							}

							// Sanitize HTML in case used (remove evil tags like script, iframe) - same as post content.
							if (! current_user_can( 'unfiltered_html' )) { // admin only.
								$value = stripslashes( wp_filter_post_kses( addslashes( $value ), $allowedposttags ) );
							}

							break;

							// URL.
							// Upload (value is URL).
							case 'url':
							case 'upload': // Regular (URL).

								$value = esc_url_raw( $value ); // force valid URL or use nothing.

								break;

						// Checkbox.
						case 'checkbox':

							$value = ! empty( $value ) ? '1' : '';

							break;

						// Radio.
						// Select.
						case 'radio':
						case 'select':

							// If option invalid, stick with current value.
							if (! isset( $this->fields[ $key ]['options'][$value] )) {
								$value = $this->get( $key );
							}

							break;

						// Number.
						case 'number':

							// If not a number, use default.
							if (! is_numeric( $value )) {
								$value = $this->fields[ $key ]['default'];
							}

							break;

						// Content.
						case 'content':

							// No value to save.
							$value = '';

							break;

					}

					// Run additional custom sanitization function if config requires it.
					if (! empty( $this->fields[ $key ]['custom_sanitize'] )) {
						$value = call_user_func( $this->fields[ $key ]['custom_sanitize'], $value, $this->fields[ $key ] );
					}

					// Final trim.
					$value = trim( $value );

				}

				// Add to output array.
				$output[ $key ] = $value;

			}

			// Return clean values, make filterable.
			return apply_filters( 'ctps_sanitize', $output, $input );

		}

		/**
		 * Is Options Page
		 *
		 * Are we on the options page for the plugin?
		 *
		 * @since 0.6
		 * @access public
		 * @return bool True if on options page
		 */
		public function is_settings_page() {

			$screen = get_current_screen();

			$is_settings_page = false;

			if ($this->page_hook_suffix === $screen->base) {
				$is_settings_page = true;
			}

			return apply_filters( 'ctps_is_settings_page', $is_settings_page );

		}

		/**
		 * Get Setting
		 *
		 * The plugin should wrap this in its own setting getter for more convenient use.
		 * This also handles returning defaults if necessary.
		 *
		 * @since 0.6
		 * @access public
		 * @param string $setting Setting slug.
		 * @return mixed Value of setting.
		 */
		public function get( $setting ) {

			$value = '';

			// Get option's array of settings to pull value from.
			$settings = get_option( $this->config['option_id'] );

			// Get default value.
			$default = isset( $this->fields[ $setting ]['default'] ) ? $this->fields[ $setting ]['default'] : '';

			// Setting not saved - use default value.
			if (! isset( $settings[ $setting ] )) {
				$value = $default;
			}

			// Option has been saved.
			else {

				// Value is empty when not allowed, set default (no_empty true or is radio).
				if (empty( $settings[ $setting ] ) && ( ! empty( $this->fields[ $setting ]['no_empty'] ) || 'radio' === $this->fields[ $setting ]['type'] )) {
					$value = $default;
				}

				// Otherwise, stick with current value.
				else {
					$value = $settings[ $setting ];
				}

			}

			// Return filterable.
			return apply_filters( 'ctps_get', $value, $setting, $this );

		}

		/**
		 * Update Setting
		 *
		 * Update a single setting's value in the option array.
		 *
		 * @since 1.1
		 * @access public
		 * @param string $setting Setting slug.
		 * @param string $value Value to set.
		 */
		public function update( $setting, $value ) {

			// Get option's array of settings.
			$settings = get_option( $this->config['option_id'] );

			// Update value in array.
			$settings[ $setting ] = $value;

			// Re-save option's array.
			update_option( $this->config['option_id'], $settings );

		}

	}

}
