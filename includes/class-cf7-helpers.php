<?php
/**
 * Class helpers
 *
 * @package QS_CF7_API
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class QS_CF7_helpers
 *
 * A helper class for managing template parts in the CF7 to API plugin.
 */
class QS_CF7_helpers {

	/**
	 * Get template part function that will get the required templates files
	 *
	 * @param  string $slug [the file slug].
	 * @param  string $name [the name of the tempate].
	 * @return void
	 */
	public static function qs_get_template_part( $slug, $name = '' ) {
		$template = '';

		if ( $name ) {
			$template = locate_template( array( "{$slug}-{$name}.php", QS_CF7_API_TEMPLATE_PATH . "{$slug}-{$name}.php" ) );
		}

		if ( ! $template && $name && file_exists( QS_CF7_API_TEMPLATE_PATH . "/templates/{$slug}-{$name}.php" ) ) {
			$template = locate_template( array( "{$slug}-{$name}.php", QS_CF7_API_TEMPLATE_PATH . "/templates/{$slug}-{$name}.php" ) );
		}

		if ( ! $template && $name && file_exists( QS_WL_PLUGIN_PATH . "/templates/{$slug}-{$name}.php" ) ) {
			$template = QS_WL_PLUGIN_PATH . "/templates/{$slug}-{$name}.php";
		}

		if ( ! $template ) {
			$template = locate_template( array( "{$slug}.php", QS_WL_TEMPLATE_PATH . "{$slug}.php" ) );
		}

		$template = apply_filters( 'qs_get_template_part', $template, $slug, $name );

		if ( $template ) {
			load_template( $template, false );
		}
	}
}
