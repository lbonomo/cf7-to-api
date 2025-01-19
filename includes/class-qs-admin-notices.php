<?php
/**
 * Class QS_Admin_Notices
 *
 * This class handles the admin notices for the CF7 to API plugin.
 *
 * @package CF7_to_API
 */

namespace Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Admin notices class.
 */
class QS_Admin_notices {

	/**
	 * Holds an array of notices to be displayed
	 *
	 * @var object
	 */
	private $notices;

	/**
	 * Holds an array of plugin options
	 *
	 * @var array
	 */
	public $notices_options = array();

	/**
	 * The main class construcator
	 */
	public function __construct() {

		$this->register_hooks();

		$this->get_plugin_options();
	}

	/**
	 * Registers class filters and actions
	 *
	 * @return void
	 */
	public function register_hooks() {
		/**
		 * Display notices hook.
		 */
		add_action( 'admin_notices', array( $this, 'qs_admin_notices' ) );

		/**
		 * Catch dismiss notice action and add it to the dismissed notices array.
		 */
		add_action( 'wp_ajax_qs_cf7_api_admin_dismiss_notices', array( $this, 'qs_admin_dismiss_notices' ) );

		/**
		 * Enqueue admin scripts and styles.
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
	}

	/**
	 * Load admin scripts and styles.
	 *
	 * @return void
	 */
	public function load_admin_scripts() {
		wp_register_style( 'qs-cf7-api-admin-notices-css', QS_CF7_API_ADMIN_CSS_URL . 'admin-notices-style.css', false, '1.0.0' );
		wp_enqueue_style( 'qs-cf7-api-admin-notices-css' );
		wp_register_script( 'qs-cf7-api-admin-notices-script', QS_CF7_API_ADMIN_JS_URL . 'admin-notices-script.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'qs-cf7-api-admin-notices-script' );
	}

	/**
	 * Dismiss notice and save it to the plugin options
	 *
	 * @return void
	 */
	public function qs_admin_dismiss_notices() {
		// TODO add nonce check.
		$id = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : ''; //phpcs:ignore

		if ( $id ) {
			$this->notices_options['dismiss_notices'][ $id ] = true;
			$this->update_plugin_options();
		}
		wp_die( 'updated' );
	}

	/**
	 * Get the plugin admin options.
	 *
	 * @return void
	 */
	private function get_plugin_options() {
		$this->notices_options = apply_filters( 'get_plugin_options', get_option( 'qs_cf7_api_notices_options' ) );
	}

	/**
	 * Save the plugin admin options
	 *
	 * @return void
	 */
	private function update_plugin_options() {
		update_option( 'qs_cf7_api_notices_options', $this->notices_options );
	}

	/**
	 * Display the notices that resides in the notices collection.
	 *
	 * @return void
	 */
	public function qs_admin_notices() {
		if ( $this->notices ) {
			foreach ( $this->notices as $admin_notice ) {
				/**
				 * Only disply the notice if it wasnt dismiised in the past.
				 */
				$classes = array(
					"notice notice-{$admin_notice['type']}",
					'is-dismissible',
				);

				$id = $admin_notice['id'];
				if ( ! $admin_notice['dismissable_forever'] || ( ! isset( $this->notices_options['dismiss_notices'][ $id ] ) || ! $this->notices_options['dismiss_notices'][ $id ] ) ) {
					if ( $admin_notice['dismissable_forever'] ) {
						$classes[] = 'qs-cf7-api-dismiss-notice-forever';
					}
					printf(
						"<div id='%s' class='%s'><p>%s</p></div>",
						esc_attr( $admin_notice['id'] ),
						esc_attr( implode( ' ', $classes ) ),
						esc_attr( $admin_notice['notice'] )
					);
				}
			}
		}
	}

	/**
	 * Adds notices to the class notices collection.
	 *
	 * @param array $notice An array of notice message and notice type.
	 * @return void
	 */
	public function wp_add_notice( $notice = '' ) {

		if ( $notice ) {
			$this->notices[] = array(
				'id'                  => $notice['id'],
				'notice'              => $notice['notice'],
				'type'                => isset( $notice['type'] ) ? $notice['type'] : 'warning',
				'dismissable_forever' => isset( $notice['dismissable_forever'] ) ? $notice['dismissable_forever'] : false,
			);
		}
	}
}
