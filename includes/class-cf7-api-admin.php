<?php
/**
 * Class CF7_API_Admin
 *
 * This class handles the admin functionalities for the CF7 to API integration.
 *
 * @package QS_CF7_API
 */

namespace Includes;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class QS_CF7_api_admin
 *
 * This class handles the admin functionalities for the CF7 to API plugin.
 */
class QS_CF7_api_admin {
	/**
	 * Holds the plugin options
	 *
	 * @var [type]
	 */
	private $options;

	/**
	 * Holds athe admin notices class
	 *
	 * @var [QS_Admin_notices]
	 */
	private $admin_notices;

	/**
	 * The text domain for internationalization.
	 *
	 * @var string $textdomain
	 */
	private $textdomain;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public $plugin_name;

	/**
	 * PLugn is active or not
	 *
	 * @var string
	 */
	private $plugin_active;

	/**
	 * API errors array
	 *
	 * @var [type]
	 */
	private $api_errors;

	/**
	 * Stores the post data.
	 *
	 * @var mixed $post
	 */
	private $post;

	/**
	 * CF7_API_Admin constructor.
	 *
	 * Initializes the CF7_API_Admin class by setting up admin notices,
	 * initializing the api_errors array, and registering hooks.
	 */
	public function __construct() {
		$this->admin_notices = new QS_Admin_notices();
		$this->api_errors    = array();
		$this->register_hooks();
	}

	/**
	 * Check if contact form 7 is active
	 *
	 * @return void
	 */
	public function verify_dependencies() {
		if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			$notice = array(
				'id'                  => 'cf7-not-active',
				'type'                => 'warning',
				'notice'              => __( 'Contact form 7 api integrations requires CONTACT FORM 7 Plugin to be installed and active', $this->textdomain ),
				'dismissable_forever' => false,
			);

			$this->admin_notices->wp_add_notice( $notice );
		}
	}

	/**
	 * Registers the required admin hooks
	 *
	 * @return void
	 */
	public function register_hooks() {
		/**
		 * Check if required plugins are active
		*
		 * @var [type]
		 */
		add_action( 'admin_init', array( $this, 'verify_dependencies' ) );

		/*before sending email to user actions */
		add_action( 'wpcf7_mail_sent', array( $this, 'qs_cf7_send_data_to_api' ) );

		/* adds another tab to contact form 7 screen */
		add_filter( 'wpcf7_editor_panels', array( $this, 'add_integrations_tab' ), 1, 1 );

		/* actions to handle while saving the form */
		add_action( 'wpcf7_save_contact_form', array( $this, 'qs_save_contact_form_details' ), 10, 1 );

		add_filter( 'wpcf7_contact_form_properties', array( $this, 'add_sf_properties' ), 10, 2 );
		add_filter( 'wpcf7_pre_construct_contact_form_properties', array( $this, 'add_sf_properties' ), 10, 2 );
	}

	/**
	 * Sets the form additional properties
	 *
	 * @param array $properties Properties.
	 * @return array
	 */
	public function add_sf_properties( $properties ) {
		// add mail tags to allowed properties.
		$properties['wpcf7_api_data']     = isset( $properties['wpcf7_api_data'] ) ? $properties['wpcf7_api_data'] : array();
		$properties['wpcf7_api_data_map'] = isset( $properties['wpcf7_api_data_map'] ) ? $properties['wpcf7_api_data_map'] : array();
		$properties['template']           = isset( $properties['template'] ) ? $properties['template'] : '';
		$properties['json_template']      = isset( $properties['json_template'] ) ? $properties['json_template'] : '';

		return $properties;
	}

	/**
	 * Adds a new tab on conract form 7 screen
	 *
	 * @param string $panels Panel name.
	 * @return array
	 */
	public function add_integrations_tab( $panels ) {
		$integration_panel = array(
			'title'    => __( 'API Integration', $this->textdomain ),
			'callback' => array( $this, 'wpcf7_integrations' ),
		);

		$panels['qs-cf7-api-integration'] = $integration_panel;

		return $panels;
	}
	/**
	 * Collect the mail tags from the form.
	 *
	 * @param object $post Post.
	 * @return [type] [description]
	 */
	public function get_mail_tags( $post ) {
		$tags = apply_filters( 'qs_cf7_collect_mail_tags', $post->scan_form_tags() );

		foreach ( (array) $tags as $tag ) {
			$type = trim( $tag['type'], ' *' );
			if ( empty( $type ) || empty( $tag['name'] ) ) {
				continue;
			}
			$mailtags[] = $tag;
		}

		return $mailtags;
	}

	/**
	 * The admin tab display, settings and instructions to the admin user
	 *
	 * @param  object $post Post.
	 * @return void Print admin page.
	 */
	public function wpcf7_integrations( $post ) {
		$wpcf7_api_data               = $post->prop( 'wpcf7_api_data' );
		$wpcf7_api_data_map           = $post->prop( 'wpcf7_api_data_map' );
		$wpcf7_api_data_template      = $post->prop( 'template' );
		$wpcf7_api_json_data_template = wp_unslash( $post->prop( 'json_template' ) );
		$mail_tags                    = $this->get_mail_tags( $post );

		$wpcf7_api_data['base_url']         = isset( $wpcf7_api_data['base_url'] ) ? $wpcf7_api_data['base_url'] : '';
		$wpcf7_api_data['basic_auth']       = isset( $wpcf7_api_data['basic_auth'] ) ? $wpcf7_api_data['basic_auth'] : '';
		$wpcf7_api_data['bearer_auth']      = isset( $wpcf7_api_data['bearer_auth'] ) ? $wpcf7_api_data['bearer_auth'] : '';
		$wpcf7_api_data['send_to_api']      = isset( $wpcf7_api_data['send_to_api'] ) ? $wpcf7_api_data['send_to_api'] : '';
		$wpcf7_api_data['override_message'] = isset( $wpcf7_api_data['override_message'] ) ? $wpcf7_api_data['override_message'] : '';
		$wpcf7_api_data['input_type']       = isset( $wpcf7_api_data['input_type'] ) ? $wpcf7_api_data['input_type'] : 'params';
		$wpcf7_api_data['method']           = isset( $wpcf7_api_data['method'] ) ? $wpcf7_api_data['method'] : 'GET';
		$wpcf7_api_data['debug_log']        = true;

		$debug_url    = get_post_meta( $post->id(), 'qs_cf7_api_debug_url', true );
		$debug_result = get_post_meta( $post->id(), 'qs_cf7_api_debug_result', true );
		$debug_params = get_post_meta( $post->id(), 'qs_cf7_api_debug_params', true );

		$error_logs      = get_post_meta( $post->id(), 'api_errors', true );
		$xml_placeholder = __(
			'*** THIS IS AN EXAMPLE ** USE YOUR XML ACCORDING TO YOUR API DOCUMENTATION **
      <update>
      <user clientid="" username="user_name" password="mypassword" />
      <reports>
      <report tag="NEW">
      <fields>
      <field id="1" name="REFERENCE_ID" value="[your-name]" />
      <field id="2" name="DESCRIPTION" value="[your-email]" />
      </field>
      </reports>
      </update>
      ',
			$this->textdomain
		);

		$json_placeholder = __(
			'*** THIS IS AN EXAMPLE ** USE YOUR JSON ACCORDING TO YOUR API DOCUMENTATION **
      { "name":"[fullname]", "age":30, "car":null }
      ',
			$this->textdomain
		);
		// TODO: Maybe I can implement a template part, to separate the HTML.
		?>


		<h2><?php echo esc_html( __( 'API Integration', $this->textdomain ) ); ?></h2>

		<fieldset>
			<?php do_action( 'before_base_fields', $post ); ?>

			<div class="cf7_row">

				<label for="wpcf7-sf-send_to_api">
					<input type="checkbox" id="wpcf7-sf-send_to_api" name="wpcf7-sf[send_to_api]" <?php checked( $wpcf7_api_data['send_to_api'], 'on' ); ?>/>
					<?php esc_attr_e( 'Send to api ?', $this->textdomain ); ?>
				</label>

			</div>

			<div class="cf7_row">

				<label for="wpcf7-sf-override_message">
					<input type="checkbox" id="wpcf7-sf-override_message" name="wpcf7-sf[override_message]" <?php checked( $wpcf7_api_data['override_message'], 'on' ); ?>/>
					<?php esc_attr_e( 'Override message with api response body?', $this->textdomain ); ?>
				</label>

			</div>

			<div class="cf7_row">
				<label for="wpcf7-sf-base_url">
					<?php esc_attr_e( 'Base url', $this->textdomain ); ?>
					<input type="text" id="wpcf7-sf-base_url" name="wpcf7-sf[base_url]" class="large-text" value="<?php echo esc_attr( $wpcf7_api_data['base_url'] ); ?>" />
				</label>
			</div>

			<hr>

			<div class="cf7_row">
				<label for="wpcf7-sf-basic_auth">
					<?php esc_attr_e( 'Basic auth', $this->textdomain ); ?>
					<input type="text" id="wpcf7-sf-basic_auth" name="wpcf7-sf[basic_auth]" class="large-text" value="<?php echo esc_attr( $wpcf7_api_data['basic_auth'] ); ?>"
					placeholder="e.g. user:secret"/>
				</label>
			</div>
		  
			<p><?php esc_attr_e( '- OR -', $this->textdomain ); ?></p>

			<div class="cf7_row">
				<label for="wpcf7-sf-bearer_auth">
					<?php esc_attr_e( 'Bearer auth key', $this->textdomain ); ?>
					<input type="text" id="wpcf7-sf-bearer_auth" name="wpcf7-sf[bearer_auth]" class="large-text" value="<?php echo esc_attr( $wpcf7_api_data['bearer_auth'] ); ?>"
					placeholder="e.g. a94a8fe5ccb19ba61c4c0873d391e987982fbbd3"/>
				</label>
			</div>
		  
			<hr>

			<div class="cf7_row">
			<label for="wpcf7-sf-input_type">
				<span class="cf7-label-in"><?php esc_attr_e( 'Input type', $this->textdomain ); ?></span>
				<select id="wpcf7-sf-input_type" name="wpcf7-sf[input_type]">
				<option value="params" <?php isset( $wpcf7_api_data['input_type'] ) ? selected( $wpcf7_api_data['input_type'], 'params' ) : ''; ?>>
					<?php esc_attr_e( 'Parameters - GET/POST', $this->textdomain ); ?>
				</option>
				<option value="xml" <?php isset( $wpcf7_api_data['input_type'] ) ? selected( $wpcf7_api_data['input_type'], 'xml' ) : ''; ?>>
					<?php esc_attr_e( 'XML', $this->textdomain ); ?>
				</option>
				<option value="json" <?php isset( $wpcf7_api_data['input_type'] ) ? selected( $wpcf7_api_data['input_type'], 'json' ) : ''; ?>>
					<?php esc_attr_e( 'json', $this->textdomain ); ?>
				</option>
				</select>
			</label>
			</div>

			<div class="cf7_row" data-qsindex="params,json">
				<label for="wpcf7-sf-method">
					<span class="cf7-label-in"><?php esc_attr_e( 'Method', $this->textdomain ); ?></span>
					<select id="wpcf7-sf-base_url" name="wpcf7-sf[method]">
						<option value="GET" <?php selected( $wpcf7_api_data['method'], 'GET' ); ?>>GET</option>
						<option value="POST" <?php selected( $wpcf7_api_data['method'], 'POST' ); ?>>POST</option>
					</select>
				</label>
			</div>

			<?php do_action( 'after_base_fields', $post ); ?>

		</fieldset>


		<fieldset data-qsindex="params">
		<div class="cf7_row">
		<h2><?php echo esc_html( __( 'Form fields', $this->textdomain ) ); ?></h2>

				<table>
					<tr>
						<th><?php esc_attr_e( 'Form fields', $this->textdomain ); ?></th>
						<th><?php esc_attr_e( 'API Key', $this->textdomain ); ?></th>
			<th></th>
					</tr>
				<?php foreach ( $mail_tags as $mail_tag ) : ?>

					<?php if ( 'checkbox' === $mail_tag->type ) : ?>
						<?php foreach ( $mail_tag->values as $checkbox_row ) : ?>
					<tr>
					<th style="text-align:left;"><?php echo esc_attr( $mail_tag->name ); ?> (<?php echo esc_attr( $checkbox_row ); ?>)</th>
								<td>
									<input
										type="text"
										id="sf-<?php echo esc_attr( $mail_tag->name ); ?>" 
										name="qs_wpcf7_api_map[<?php echo esc_attr( $mail_tag->name ); ?>][<?php echo esc_attr( $checkbox_row ); ?>]" 
										class="large-text" 
										value="<?php echo isset( $wpcf7_api_data_map[ $mail_tag->name ][ $checkbox_row ] ) ? esc_attr( $wpcf7_api_data_map[ $mail_tag->name ][ $checkbox_row ] ) : ''; ?>"
									/>
								</td>
					</tr>
				<?php endforeach; ?>
				<?php else : ?>
				<tr>
						<th style="text-align:left;"><?php echo esc_attr( $mail_tag->name ); ?></th>
						<td>
							<input
								type="text" 
								id="sf-<?php echo esc_attr( $mail_tag->name ); ?>" 
								name="qs_wpcf7_api_map[<?php echo esc_attr( $mail_tag->name ); ?>]" 
								class="large-text" 
								value="<?php echo isset( $wpcf7_api_data_map[ $mail_tag->name ] ) ? esc_attr( $wpcf7_api_data_map[ $mail_tag->name ] ) : ''; ?>"
							/>
						</td>
				</tr>
				<?php endif; ?>

				<?php endforeach; ?>

				</table>

		</div>
		</fieldset>

	<fieldset data-qsindex="xml">
		<div class="cf7_row">
		<h2><?php echo esc_html( __( 'XML Template', $this->textdomain ) ); ?></h2>

		<legend>
			<?php foreach ( $mail_tags as $mail_tag ) : ?>
			<span class="xml_mailtag mailtag code">[<?php echo esc_attr( $mail_tag->name ); ?>]</span>
			<?php endforeach; ?>
		</legend>

		<textarea 
			name="template" 
			rows="12" 
			dir="ltr" 
			placeholder="<?php echo esc_attr( $xml_placeholder ); ?>"
			><?php echo isset( $wpcf7_api_data_template ) ? esc_xml( $wpcf7_api_data_template ) : ''; ?></textarea>
		</div>
	</fieldset>

	<fieldset data-qsindex="json">
		<div class="cf7_row">
		<h2><?php echo esc_html( __( 'JSON Template', $this->textdomain ) ); ?></h2>

		<legend>
			<?php foreach ( $mail_tags as $mail_tag ) : ?>
				<?php if ( 'checkbox' === $mail_tag->type ) : ?>
					<?php foreach ( $mail_tag->values as $checkbox_row ) : ?>
				<span class="xml_mailtag mailtag code">[<?php echo esc_attr( $mail_tag->name ); ?>-<?php echo esc_attr( $checkbox_row ); ?>]</span>
				<?php endforeach; ?>
			<?php else : ?>
				<span class="xml_mailtag mailtag code">[<?php echo esc_attr( $mail_tag->name ); ?>]</span>
			<?php endif; ?>
			<?php endforeach; ?>
		</legend>

		<textarea 
			name="json_template"
			rows="12"
			dir="ltr"
			placeholder="<?php echo esc_attr( $json_placeholder ); ?>"
			><?php echo isset( $wpcf7_api_json_data_template ) ? esc_js( $wpcf7_api_json_data_template ) : ''; ?></textarea>
		</div>
	</fieldset>

		<?php if ( $wpcf7_api_data['debug_log'] ) : ?>
	<fieldset>
		<div class="cf7_row">
		<label class="debug-log-trigger">
			+ <?php esc_attr_e( 'DEBUG LOG ( View last transmission attempt )', $this->textdomain ); ?>
		</label>
		<div class="debug-log-wrap">
			<h3 class="debug_log_title"><?php esc_attr_e( 'LAST API CALL', $this->textdomain ); ?></h3>
			<div class="debug_log">
			<h4><?php esc_attr_e( 'Called url', $this->textdomain ); ?>:</h4>
			<textarea rows="1"><?php echo esc_attr( trim( $debug_url ) ); ?></textarea>
			<h4><?php esc_attr_e( 'Params', $this->textdomain ); ?>:</h4>
			<textarea rows="10"><?php print_r( $debug_params ); //phpcs:ignore ?></textarea>
			<h4><?php esc_attr_e( 'Remote server result', $this->textdomain ); ?>:</h4>
			<textarea rows="10"><?php print_r( $debug_result );  //phpcs:ignore ?></textarea>
			<h4><?php esc_attr_e( 'Error logs', $this->textdomain ); ?>:</h4>
			<textarea rows="10"><?php print_r( $error_logs );  //phpcs:ignore ?></textarea>
			</div>
		</div>
		</div>
	</fieldset>
			<?php
endif;
	}

	/**
	 * Saves the API settings.
	 *
	 * @param  object $contact_form Contact form.
	 * @return void                 [description]
	 */
	public function qs_save_contact_form_details( $contact_form ) {

		$properties = $contact_form->get_properties();
		// TODO: Processing form data without nonce verification.
		$properties['wpcf7_api_data']     = isset( $_POST['wpcf7-sf'] ) ? $_POST['wpcf7-sf'] : ''; // phpcs:ignore
		$properties['wpcf7_api_data_map'] = isset( $_POST['qs_wpcf7_api_map'] ) ? $_POST['qs_wpcf7_api_map'] : ''; // phpcs:ignore
		$properties['template']           = isset( $_POST['template'] ) ? $_POST['template'] : ''; // phpcs:ignore
		$properties['json_template']      = isset( $_POST['json_template'] ) ? $_POST['json_template'] : ''; // phpcs:ignore

		// Extract custom placeholders.
		$record_type = isset( $properties['wpcf7_api_data']['input_type'] ) ? $properties['wpcf7_api_data']['input_type'] : 'params';
		if ( 'json' === $record_type || 'xml' === $record_type ) {
			$template = 'json' === $record_type ? $properties['json_template'] : $properties['template'];
			preg_match_all( '/\[(\w+(-\d+)?)\]/', $template, $matches, PREG_PATTERN_ORDER );
			$properties['wpcf7_api_data_map'] = array_merge( array_fill_keys( $matches[1], '' ), $properties['wpcf7_api_data_map'] );
		}

		$contact_form->set_properties( $properties );
	}

	/**
	 * The handler that will send the data to the api
	 *
	 * @param  object $wpcf7_contactform Contact form.
	 * @return void                      [description]
	 */
	public function qs_cf7_send_data_to_api( $wpcf7_contactform ) {

		$this->clear_error_log( $wpcf7_contactform->id() );

		$submission = WPCF7_Submission::get_instance();

		$this->post                = $wpcf7_contactform;
		$qs_cf7_data               = $wpcf7_contactform->prop( 'wpcf7_api_data' );
		$qs_cf7_data_map           = $wpcf7_contactform->prop( 'wpcf7_api_data_map' );
		$qs_cf7_data_template      = $wpcf7_contactform->prop( 'template' );
		$qs_cf7_data_json_template = $wpcf7_contactform->prop( 'json_template' );
		$qs_cf7_data['debug_log']  = true; // always save last call results for debugging.

		/* check if the form is marked to be sent via API */
		if ( isset( $qs_cf7_data['send_to_api'] ) && 'on' === $qs_cf7_data['send_to_api'] ) {

			$record_type = isset( $qs_cf7_data['input_type'] ) ? $qs_cf7_data['input_type'] : 'params';

			if ( 'json' === $record_type ) {
				$qs_cf7_data_template = $qs_cf7_data_json_template;
			}

			$record        = $this->get_record( $submission, $qs_cf7_data_map, $record_type, $template = $qs_cf7_data_template );
			$record['url'] = $qs_cf7_data['base_url'];

			if ( isset( $record['url'] ) && $record['url'] ) {

				do_action( 'qs_cf7_api_before_sent_to_api', $record );

				$response         = $this->send_lead( $record, $qs_cf7_data['debug_log'], $qs_cf7_data['method'], $record_type, $qs_cf7_data['basic_auth'], $qs_cf7_data['bearer_auth'] );
				$override_message = isset( $qs_cf7_data['override_message'] ) && 'on' === $qs_cf7_data['override_message'];
				$message          = false;

				if ( is_wp_error( $response ) ) {
					$message = 'Something went wrong';
					$this->log_error( $response, $wpcf7_contactform->id() );
				}

				// Unauthorized.
				if ( array_key_exists( 'response', $response ) && array_key_exists( 'code', $response['response'] ) ) {
					$code = $response['response']['code'];
				}

				switch ( $code ) {
					case '401':
						$message = 'Unauthorized: pleas check auth settings';
						break;
					case '400':
						$message = 'One or more validation errors occurred.';
						break;
					default:
						$body_string = wp_remote_retrieve_body( $response );
						$body        = json_decode( $body_string );
						if ( 0 === json_last_error() ) {
							// If get a 'message', send it, if not send CF7 message.
							$this->logger( __LINE__, $body_string );
							$message = property_exists( $body, 'message' ) ? $body->message : false;
						} else {
							// TODO: wpcf7-message-mail-sent-ng.
							$this->logger( __LINE__, $body_string );
							$message = false;
						}
						break;
				}

				if ( $override_message && $message ) {
					$submission->set_response( $message );
				}
				do_action( 'qs_cf7_api_after_sent_to_api', $record, $response );
			}
		}
	}
	/**
	 * CREATE ERROR LOG FOR RECENT API TRANSMISSION ATTEMPT
	 *
	 * @param  string $wp_error [description].
	 * @param  int    $post_id  [description].
	 * @return void             [description].
	 */
	public function log_error( $wp_error, $post_id ) {
		$this->api_errors[] = $wp_error;
		update_post_meta( $post_id, 'api_errors', $this->api_errors );
	}

	/**
	 * Clears the error log for a given post.
	 *
	 * This function deletes the 'api_errors' post meta associated with the specified post ID.
	 *
	 * @param int $post_id The ID of the post for which to clear the error log.
	 * @return void
	 */
	public function clear_error_log( $post_id ) {
		delete_post_meta( $post_id, 'api_errors' );
	}

	/**
	 * Print log. file_name[line_number]: message
	 *
	 * @param integer $line Line number.
	 * @param mixer   $message Text, object or array to log.
	 * @return void
	 */
	public function logger( $line, $message ) {
		if ( true === WP_DEBUG ) {
			$tamplate = '%s[%d]: %s';
			switch ( gettype( $message ) ) {
				case 'integer':
				case 'string':
					error_log( sprintf( $tamplate, __FILE__, $line, $message ) ); //phpcs:ignore
					break;
				case 'object':
				case 'array':
					error_log( sprintf( $tamplate, __FILE__, $line, print_r( $message, true ) )  //phpcs:ignore
					);
					break;
				case 'boolean':
					$message = $message ? 'true' : 'false';
					error_log( sprintf( $tamplate, __FILE__, $line, print_r( $message, true ) ) ); //phpcs:ignore
					break;
				default:
					error_log(sprintf( "Type: %s", gettype( $message ) ) ); //phpcs:ignore
					break;
			}
		}
	}

	/**
	 * Convert the form keys to the API keys according to the mapping instructions.
	 *
	 * @param  object $submission      [description].
	 * @param  array  $qs_cf7_data_map [description].
	 * @param  string $type            Type.
	 * @param  string $template        Template.
	 * @return mixed                   [description]
	 */
	public function get_record( $submission, $qs_cf7_data_map, $type = 'params', $template = '' ) {

		$submited_data  = $submission->get_posted_data();
		$uploaded_files = $submission->uploaded_files();
		$record         = array();

		if ( 'params' === $type ) {

			foreach ( $qs_cf7_data_map as $form_key => $qs_cf7_form_key ) {

				if ( $qs_cf7_form_key ) {

					if ( is_array( $qs_cf7_form_key ) ) {
						// arrange checkbox arrays.
						foreach ( $submited_data[ $form_key ] as $value ) {
							if ( $value ) {
									$record['fields'][ $qs_cf7_form_key[ $value ] ] = apply_filters( 'set_record_value', $value, $qs_cf7_form_key );
							}
						}
					} else {
						$value = isset( $submited_data[ $form_key ] ) ? $submited_data[ $form_key ] : '';

						// flatten radio.
						if ( is_array( $value ) ) {
								$value = reset( $value );
						}

						// handle file input.
						if ( isset( $uploaded_files[ $form_key ] ) ) {
								// Put all files into an array.
								$value = array();
							foreach ( $uploaded_files[ $form_key ] as $path ) {
								$image_content = file_get_contents( $path );      //phpcs:ignore
								$value[]       = base64_encode( $image_content ); //phpcs:ignore
							}
						}

						$record['fields'][ $qs_cf7_form_key ] = apply_filters( 'set_record_value', $value, $qs_cf7_form_key );
					}
				}
			}
		} elseif ( 'xml' === $type || 'json' === $type ) {

			foreach ( $qs_cf7_data_map as $form_key => $qs_cf7_form_key ) {

				if ( is_array( $qs_cf7_form_key ) ) {
					// arrange checkbox arrays.
					foreach ( $submited_data[ $form_key ] as $value ) {
						if ( $value ) {
							$value = apply_filters( 'set_record_value', $value, $qs_cf7_form_key );

							$template = str_replace( "[{$form_key}-{$value}]", $value, $template );
						}
					}
				} else {
					$value = isset( $submited_data[ $form_key ] ) ? $submited_data[ $form_key ] : '';

					// handle line breaks (suggested by Felix Schäfer).
					$value = preg_replace( '/\r|\n/', '\\n', $value );
					$value = str_replace( '\\n\\n', '\n', $value );

					// flatten radio.
					if ( is_array( $value ) ) {
						if ( 1 === count( $value ) ) {
							$value = reset( $value );
						} else {
							$value = implode( ';', $value );
						}
					}

					// handle boolean acceptance fields.
					if ( $this->is_acceptance_field( $form_key ) ) {
						$value = '' === $value ? 'false' : 'true';
					}

					// handle file input.
					if ( isset( $uploaded_files[ $form_key ] ) ) {

						// Put all files into an array.
						$value = array();
						foreach ( $uploaded_files[ $form_key ] as $path ) {
							$image_content = file_get_contents( $path );      //phpcs:ignore
							$value[]       = base64_encode( $image_content ); //phpcs:ignore
						}

						// replace "[$form_key]" with json array of base64 strings.
						$template = preg_replace( //phpcs:ignore
							"/(\")?\[{$form_key}\](\")?/",
							wp_json_encode( $value ),
							$template
						);
					}

					$template = str_replace( "[{$form_key}]", $value, $template );
				}
			}

			// clean unchanged tags.
			foreach ( $qs_cf7_data_map as $form_key => $qs_cf7_form_key ) {
				if ( is_array( $qs_cf7_form_key ) ) {
					foreach ( $qs_cf7_form_key as $field_suffix => $api_name ) {
							$template = str_replace( "[{$form_key}-{$field_suffix}]", '', $template );
					}
				}
			}

			$record['fields'] = $template;

		}

		$record = apply_filters( 'cf7api_create_record', $record, $submited_data, $qs_cf7_data_map, $type, $template );

		return $record;
	}


	/**
	 * Send the lead using wp_remote
	 *
	 * @param  array   $record [description].
	 * @param  boolean $debug  [description].
	 * @param  string  $method [description].
	 * @param  string  $record_type [description].
	 * @param  string  $basic_auth [description].
	 * @param  string  $bearer_auth [description].
	 * @return array          [description].
	 */
	private function send_lead( $record, $debug = false, $method = 'GET', $record_type = 'params', $basic_auth = null, $bearer_auth = null ) {
		global $wp_version;

		// API query template.
		$lead = wp_unslash( $record['fields'] );
		$url  = $record['url'];
		$args = array(
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
			'blocking'    => true,
			'headers'     => array(),
			'cookies'     => array(),
			'body'        => null,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
			'filename'    => null,
		);

		switch ( $method ) {
			case 'GET':
				if ( 'params' === $record_type || 'json' === $record_type ) {
					if ( 'json' === $record_type ) {
						$args['headers']['Content-Type'] = 'application/json';
						$json                            = $this->parse_json( $lead );
						if ( is_wp_error( $json ) ) {
							return $json;
						}
						$args['body'] = $json;
					} else {
							$lead_string = http_build_query( $lead );
							$url = strpos( '?', $url ) ? $url . '&' . $lead_string : $url . '?' . $lead_string;
					}
					$args   = apply_filters( 'qs_cf7_api_get_args', $args );
					$url    = apply_filters( 'qs_cf7_api_get_url', $url, $record );
					$result = wp_remote_get( $url, $args );
				}
				break;

			case 'POST':
				if ( 'params' === $record_type || 'json' === $record_type ) {
					$args['body'] = $lead;

					// Basic auth.
					if ( isset( $basic_auth ) && '' !== $basic_auth ) {
						$args['headers']['Authorization'] = 'Basic ' . base64_encode( $basic_auth ); //phpcs:ignore
					}

					// Bearer auth.
					if ( isset( $bearer_auth ) && '' !== $bearer_auth ) {
						$args['headers']['Authorization'] = 'Bearer ' . $bearer_auth;
					}

					if ( 'xml' === $record_type ) {
						$args['headers']['Content-Type'] = 'text/xml';
						$xml                             = $this->get_xml( $lead );
						if ( is_wp_error( $xml ) ) {
							return $xml;
						}
						$args['body'] = $xml->asXML();
					}

					if ( 'json' === $record_type ) {
						$args['headers']['Content-Type'] = 'application/json';
						$json                            = $this->parse_json( $lead );
						$this->logger( __LINE__, $lead );
						if ( is_wp_error( $json ) ) {
							return $json;
						}
						$args['body'] = $json;
					}

					$args   = apply_filters( 'qs_cf7_api_get_args', $args );
					$url    = apply_filters( 'qs_cf7_api_post_url', $url );
					$result = wp_remote_post( $url, $args );
				}
				break;
			default:
				$message = "Method not supported ($method)";
				$this->logger( __LINE__, $message );
				break;
		}

		if ( $debug ) {
			update_post_meta( $this->post->id(), 'qs_cf7_api_debug_url', $record['url'] );
			update_post_meta( $this->post->id(), 'qs_cf7_api_debug_params', $lead );
			update_post_meta( $this->post->id(), 'qs_cf7_api_debug_result', $result );
		}

		do_action( 'after_qs_cf7_api_send_lead', $result, $record );

		return $result;
	}

	/**
	 * Parses a JSON string and returns the encoded JSON if valid, or a WP_Error if invalid.
	 *
	 * @param string $text The JSON string to parse.
	 * @return string|WP_Error The encoded JSON string if valid, or a WP_Error object if invalid.
	 */
	private function parse_json( $text ) {
		$json = json_decode( $text );

		if ( json_last_error() === JSON_ERROR_NONE ) {
			return wp_json_encode( $json );
		}

		if ( json_last_error() === 0 ) {
			return wp_json_encode( $json );
		}

		return new WP_Error( 'json-error', json_last_error() );
	}

	/**
	 * Parses the given lead data as XML.
	 *
	 * This function attempts to parse the provided lead data string as XML using
	 * the `simplexml_load_string` function. If the XML parsing fails, it returns
	 * a WP_Error object indicating the error.
	 *
	 * @param string $lead The lead data to be parsed as XML.
	 * @return SimpleXMLElement|WP_Error The parsed XML object, or a WP_Error object if parsing fails.
	 */
	private function get_xml( $lead ) {
		$xml = '';
		if ( function_exists( 'simplexml_load_string' ) ) {
			libxml_use_internal_errors( true );

			$xml = simplexml_load_string( $lead );

			if ( false === $xml ) {
				$xml = new WP_Error(
					'xml',
					__( 'XML Structure is incorrect', $this->textdomain )
				);
			}
		}

		return $xml;
	}

	/**
	 * Check field.
	 *
	 * @param string $field_name Field name.
	 * @return bool
	 */
	private function is_acceptance_field( $field_name ) {
		$field = $this->post->scan_form_tags(
			array(
				'type' => 'acceptance',
				'name' => $field_name,
			)
		);

		return 1 === count( $field );
	}
}
