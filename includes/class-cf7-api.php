<?php
/**
 * Class CF7_API
 *
 * This class handles the integration between Contact Form 7 and an external API.
 *
 * @package  QS_CF7_API
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * The main plugin class.
 */
class QS_CF7_atp_integration {

	/**
	 * The plugin identifier.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name unique plugin id.
	 */
	protected $plugin_name;

	/**
	 * Save the instance of the plugin for static actions.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $instance    an instance of the class.
	 */
	public static $instance;

	/**
	 * A reference to the admin class.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      object
	 */
	public $admin;

	/**
	 * A reference to the plugin status .
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      object    $admin    an instance of the admin class.
	 */
	private $woocommerce_is_active;

	/**
	 * The plugin version.
	 *
	 * @access   public
	 * @var      string    $version    the plugin version.
	 */
	public $version;

	/**
	 * The plugin basename.
	 *
	 * @access   public
	 * @var      string    $plugin_basename    the plugin basename.
	 */
	public $plugin_basename;

	/**
	 * Define the plugin functionality.
	 *
	 * Set plugin name and version , and load dependencies
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name = 'wc-cf7-api-form';
		$this->load_dependencies();

		/**
		 * Create an instance of the admin class
		 *
		 * @var QS_CF7_api_admin
		 */
		$this->admin              = new QS_CF7_api_admin();
		$this->admin->plugin_name = $this->plugin_name;

		/**
		 * Save the instance for static actions
		 */
		self::$instance = $this;
	}

	/**
	 * Loads the required plugin files
	 *
	 * @return void
	 */
	public function load_dependencies() {
		/**
		* General global plugin functions
		*/
		// TODO: Que hace?
		// require_once QS_CF7_API_INCLUDES_PATH . 'class-cf7-helpers.php';.

		/**
		* Admin notices class
		*/
		require_once QS_CF7_API_INCLUDES_PATH . 'class-qs-admin-notices.php';

		/**
		* Admin notices clclass
		*/
		require_once QS_CF7_API_INCLUDES_PATH . 'class-cf7-api-admin.php';
	}

	/**
	 * Get the current plugin instance
	 *
	 * @return QS_CF7_atp_integration
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
