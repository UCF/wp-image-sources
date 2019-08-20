<?php
/**
 * Registers WP Images Sources Config options
 */
if ( ! class_exists( 'WPIS_Config' ) ) {
	class WPIS_Config {
		public static
			$option_prefix = 'wpis_',
			$option_defaults = array(
				'filter_content' => true
			);

		/**
		 * Creates options via the WP Options API that are utiltized by the
		 * plugin. Intended to be run on plugin activation.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @return void
		 */
		public static function add_options() {
			$defaults = self::$option_defaults;

			add_option( self::$option_prefix . 'filter_content', $defaults['filter_content'] );
		}

		/**
		 * Deletes options via the WP Options API that are utilized by the
		 * plugin. Intended to be run on plugin uninstallation.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @return void
		 */
		public static function delete_options() {
			delete_option( self::$option_prefix . 'filter_content' );
		}

		/**
		 * Returns a list of default plugin options. Applied any overridden
		 * default values set within the options page.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_option_defaults() {
			$defaults = self::$option_defaults;

			$configurable_defaults = array(
				'filter_content' => get_option( self::$option_prefix . 'filter_content', $defaults['filter_content'] )
			);

			$configurable_defaults = self::format_options( $configurable_defaults );

			$defaults = array_merge( $defaults, $configurable_defaults );

			return $defaults;
		}

		/**
		 * Returns an array with plugin defaults applied
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param array $list The list of options to apply defaults to
		 * @param boolean $list_keys_only Modifies results to only return array key
		 * 								  values present in $list.
		 * @return array
		 */
		public static function apply_option_defaults( $list, $list_keys_only=false ) {
			$defaults = self::get_option_defaults();
			$options = array();

			if ( $list_keys_only ) {
				foreach( $list as $key => $val ) {
					$options[$key] = ! empty( $val ) ? $val : $defaults[$key];
				}
			} else {
				$options = array_merge( $defaults, $list );
			}

			return $options;
		}

		/**
		 * Performs typecasting and sanitization on an array of plugin options.
		 * @author Jim Barnes
		 * @param array $list The list of options to format.
		 * @return array
		 */
		public static function format_options( $list ) {
			foreach( $list as $key => $val ) {
				switch( $key ) {
					case 'filter_content':
						$list[$key] = filter_var( $val, FILTER_VALIDATE_BOOLEAN );
					default:
						break;
				}
			}

			return $list;
		}

		/**
		 * Applies formatting to a single options. Intended to be passed to the
		 * option_{$option} hook.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param mixed $value The value to be formatted
		 * @param string $option_name The name of the option being formatted.
		 * @return mixed
		 */
		public static function format_option( $value, $option_name ) {
			$option_name_no_prefix = str_replace( self::$option_prefix, '', $option_name );

			$option_formatted = self::format_options( array( $option_name_no_prefix => $value ) );
			return $option_formatted;
		}

		/**
		 * Adds filters for plugin options that apply
		 * our formatting rules to option values.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @return void
		 */
		public static function add_option_formatting_filters() {
			$defaults = self::$option_defaults;

			foreach( $defaults as $option => $default ) {
				$option_name = self::$option_prefix . $option;
				add_filter( "option_{$option_name}", array( 'WPIS_Config', 'format_option' ), 10, 2 );
			}
		}

		/**
		 * Utility method for returning an option from the WP Options API
		 * or a plugin option default.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param string $option_name The name of the option to retrieve.
		 * @return mixed
		 */
		public static function get_option_or_default( $option_name ) {
			$option_name_no_prefix = str_replace( self::$option_prefix, '', $option_name );
			$option_name = self::$option_prefix . $option_name_no_prefix;

			$option = get_option( $option_name );
			$option_formatted = self::apply_option_defaults( array(
				$option_name_no_prefix => $option
			), true );

			return $option_formatted[$option_name_no_prefix];
		}

		/**
		 * Initializes setting registration with the Settings API.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @return void
		 */
		public static function settings_init() {
			$settings_slug = 'wpis_settings';
			$defaults = self::$option_defaults;
			$display_fn = array( 'WPIS_Config', 'display_settings_field' );

			foreach( $defaults as $key => $val ) {
				register_setting(
					$settings_slug,
					self::$option_prefix . $key
				);
			}

			add_settings_section(
				'wpis_general',
				'General Settings',
				'',
				$settings_slug
			);

			add_settings_field(
				self::$option_prefix . 'filter_content',
				'Add WebP to Content',
				$display_fn,
				$settings_slug,
				'wpis_general',
				array(
					'label_for'   => self::$option_prefix . 'filter_content',
					'description' => 'When checked, a WebP <code>source</code> element will be automatically added to images if available.',
					'type'        => 'checkbox'
				)
			);
		}

		/**
		 * Displays an individual settings's field markup.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param array The field's argument array
		 * @return string The formatted html of the field
		 */
		public static function display_settings_field( $args ) {
			$option_name = $args['label_for'];
			$description = $args['description'];
			$field_type  = $args['type'];
			$options     = isset( $args['options'] ) ? $args['options'] : null;
			$current_val = self::get_option_or_default( $option_name );
			$markup      = '';

			switch( $field_type ) {
				case 'checkbox':
					ob_start();
				?>
					<input type="checkbox" id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>"<?php echo ( $current_val === true ) ? ' checked' : ''; ?>>
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
				case 'select':
					ob_start();
					if ( $options ) :
				?>
					<select id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>">
					<?php foreach( $options as $val => $text ) : ?>
						<option value="<?php echo $value; ?>"<?php echo ( $current_val == $value ) ? ' selected' : ''; ?>><?php echo $text; ?></option>
					<?php endforeach; ?>
					</select>
				<?php else : ?>
					<p style="color: #d54e21;">There was an error retrieving the choices for this field.</p>
				<?php
					endif;
				?>
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
				case 'number':
				case 'date':
				case 'email':
				case 'month':
				case 'tel':
				case 'text':
				case 'time':
				case 'url':
				default:
					ob_start();
				?>
					<input type="<?php echo $field_type; ?>" id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>" value="<?php echo $current_val; ?>">
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
			}

			echo $markup;
		}

		/**
		 * Registers the settings page to display in the WordPress admin.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @return string The resulting page's hook suffix.
		 */
		public static function add_options_page() {
			$page_title = 'WP Image Sources Settings';
			$menu_title = 'WP Image Sources';
			$capability = 'manage_options';
			$menu_slug  = 'wpis_settings';
			$callback   = array( 'WPIS_Config', 'options_page_html' );

			return add_options_page(
				$page_title,
				$menu_title,
				$capability,
				$menu_slug,
				$callback
			);
		}

		/**
		 * Displays the plugins's settings page form.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @return void
		 */
		public static function options_page_html() {
			ob_start();
		?>
			<div class="wrap">
				<h1><?php echo get_admin_page_title(); ?></h1>
				<form method="post" action="options.php">
					<?php
						settings_fields( 'wpis_settings' );
						do_settings_sections( 'wpis_settings' );
						submit_button();
					?>
				</form>
			</div>
		<?php
			echo ob_get_clean();
		}
	}
}
