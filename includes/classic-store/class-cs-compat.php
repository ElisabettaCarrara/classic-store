<?php
/**
 * Class CS_Compat file.
 *
 * Handles compatibility with WOO addons.
 *
 * @version  CS-1.0.0
 * @package  ClassicStore/Compat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CS_Compat class.
 */
class CS_Compat {

	/**
	 * Compat option name.
	 *
	 * @var string
	 */
	const OPTION = 'cs_compat_woo';

	/**
	 * Setup class.
	 *
	 * @since CS-1.0.0
	 */
	public function __construct() {
		add_filter( 'woocommerce_general_settings', array( $this, 'add_setting' ), 10, 1 );
		add_action( 'add_option_' . self::OPTION, array( $this, 'added_option' ), 10, 1 );
		add_action( 'update_option_' . self::OPTION, array( $this, 'updated_option' ), 10, 3 );
	}

	/**
	 * Handle compat option added.
	 *
	 * Hook to add_option_{$option}
	 *
	 * @since CS-1.0.0
	 * @param string $option Option name.
	 */
	public function added_option( $option ) {
		if ( $option !== self::OPTION ) {
			return;
		}
		$this->maybe_install_plugin();
	}

	/**
	 * Handle compat option updated.
	 *
	 * Hook to update_option_{$option}
	 *
	 * @since CS-1.0.0
	 * @param string $old_value Old option value.
	 * @param string $value     Option value.
	 * @param string $option    Option name.
	 */
	public function updated_option( $old_value, $value, $option ) {
		if ( $option !== self::OPTION || $old_value === $value ) {
			return;
		}
		$this->maybe_install_plugin();
	}

	/**
	 * Install or remove compat plugin
	 *
	 * @since CS-1.0.0
	 */
	public function maybe_install_plugin() {
		$option_value = get_option( self::OPTION );
		if ( ! in_array( $option_value, array( 'yes', 'no' ), true ) ) {
			return;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}
		require_once ABSPATH . '/wp-admin/includes/plugin.php';

		$plugin_file = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
		$file_exists = $wp_filesystem->exists( $plugin_file );
		if ( $file_exists ) {
			$content          = $wp_filesystem->get_contents_array( $plugin_file );
			$is_compat_file   = str_contains( $content[2], 'Classic Store Compatibility' );
			$is_plugin_active = is_plugin_active( 'woocommerce/woocommerce.php' );
		}

		if ( $option_value === 'yes' ) {
			if ( $file_exists && $is_compat_file ) {
				if ( ! $is_plugin_active ) {
					// Just need activation.
					activate_plugin( $plugin_file );
					return;
				}
				// Already in place and activated.
				return;
			}
			if ( $file_exists && ! $is_compat_file ) {
				// Something already in place, unable to complete the operation.
				update_option( self::OPTION, 'no' );
				$this->handle_exception( 101 );
				return;
			}
			if ( ! $wp_filesystem->mkdir( WP_PLUGIN_DIR . '/woocommerce/' ) ) {
				update_option( self::OPTION, 'no' );
				$this->handle_exception( 102 );
				return;
			}
			if ( ! $wp_filesystem->copy( __DIR__ . '/woocommerce/woocommerce.php', $plugin_file ) ) {
				update_option( self::OPTION, 'no' );
				$this->handle_exception( 103 );
				return;
			}
			wp_cache_delete( 'plugins', 'plugins' );
			if ( is_wp_error( activate_plugin( $plugin_file ) ) ) {
				update_option( self::OPTION, 'no' );
				$this->handle_exception( 104 );
				return;
			}
		}

		if ( $option_value === 'no' ) {
			if ( ! $file_exists ) {
				// Already removed.
				return;
			}
			if ( $file_exists && ! $is_compat_file ) {
				// Something already in place, unable to complete the operation.
				$this->handle_exception( 001 );
				return;
			}
			deactivate_plugins( '/woocommerce/woocommerce.php' );
			if ( ! $wp_filesystem->rmdir( dirname( $plugin_file ), true ) ) {
				update_option( self::OPTION, 'yes' );
				$this->handle_exception( 'Remove', 'Can not remove the plugin.' );
				return;
			}
		}
	}

	/**
	 * Handle errors on install/uninstall compat plugin.
	 *
	 * @since CS-1.0.0
	 * @param int $code Error code.
	 */
	private function handle_exception( $code ) {
		$dict = array(
			001 => __( 'Another plugin is already in place. Not going to remove it.', 'classic-store' ),
			002 => __( 'Can\'t remove compatibility plugin. Can\'t delete the plugin.', 'classic-store' ),
			101 => __( 'Can\'t install compatibility plugin. Another plugin is already in place.', 'classic-store' ),
			102 => __( 'Can\'t install compatibility plugin. Can\'t create the directory the directory .', 'classic-store' ),
			103 => __( 'Can\'t install compatibility plugin. Can\'t copy the plugin file.', 'classic-store' ),
			104 => __( 'Can\'t install compatibility plugin. Can\'t activate the plugin.', 'classic-store' ),
		);

		\WC_Admin_Settings::add_error( esc_html( $dict[ $code ] ?? __( 'Unknown error.', 'classic-store' ) ) );
	}

	/**
	 * Add settings to Classic Store General Settings.
	 *
	 * Hook to woocommerce_general_settings
	 *
	 * @since CS-1.0.0
	 * @param array $settings Classic Store General Settings array.
	 * @return array
	 */
	public function add_setting( $settings ) {
		$compat_settings = array(
			array(
				'title' => __( 'WooCommerce extensions', 'classic-store' ),
				'type'  => 'title',
				'id'    => 'compatibility_options',
			),
			array(
				'title'    => __( 'Compatibility mode', 'classic-store' ),
				'desc'     => __( 'Enable compatibility mode for WooCommerce extensions.', 'classic-store' ),
				'desc_tip' => __( 'When this options is enabled, a fake WooCommerce plugin is created and activated.<br>You\'ll find a <code>woocommerce/woocommerce.php</code> plugin in your plugins folder.', 'classic-store' ),
				'id'       => self::OPTION,
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'compatibility_options',
			),
		);
		return array_merge( $settings, $compat_settings );
	}
}

new CS_Compat();
