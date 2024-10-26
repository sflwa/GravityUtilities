<?php
/**
 * Helper functions
 *
 * @since 2.1.0
 *
 * @author Naomi C. Bush for gravity+ <support@gravityplus.pro>
 */
class GFPGFU_Helper {

	/**
	 * Get all of the pages that have a Gravity Forms shortcode or block
	 *
	 * @since 2.1.0
	 *
	 * @author Naomi C. Bush for gravity+ <support@gravityplus.pro>
	 */
	public static function get_pages() {
		
		$pages = array();

		//<!-- wp:gravityforms/form {"formId":"67","ajax":true} /-->


		$query = new WP_Query( array( 's' => '<!-- wp:gravityforms/form', 'nopaging' => true ) );

		if ( $query->have_posts() ) {

			global $post;

			while( $query->have_posts() ) {

				$query->the_post();

				$block_occurrences = preg_match_all( "/(wp:gravityforms\/form {\"formId\":\")([0-9]*)(\".*)(-->)/", $post->post_content, $matches );

				if ( ! empty( $block_occurrences ) ) {

					foreach ( $matches[2] as $match ) {

						if ( empty( $pages ) ) {

							$pages[] = array( 'form_id' => $match, 'name' => $post->post_title, 'edit_url' => get_edit_post_link() , 'view_url' => get_permalink() );

						}
						else {

							$multiple_blocks_for_same_form = false;

							foreach ( $pages as $page ) {

								if ( $page['form_id'] == $match && $post->post_title == $page['name'] ) {

									$multiple_blocks_for_same_form = true;

									break;

								}

							}

							if ( ! $multiple_blocks_for_same_form ) {

								$pages[] = array( 'form_id' => $match, 'name' => $post->post_title, 'edit_url' => get_edit_post_link() , 'view_url' => get_permalink() );

							}

						}

					}

				}

			}

		}

		$query = new WP_Query( array( 's' => '[gravityform', 'nopaging' => true ) );

		if ( $query->have_posts() ) {

			global $post;

			while( $query->have_posts() ) {

				$query->the_post();

				$shortcode_instances = preg_match_all( "/(\[gravityform)(.*)(id=\")([0-9]*)(\".*)(\])/", $post->post_content, $matches );

				if ( ! empty( $shortcode_instances ) ) {

					foreach ( $matches[4] as $match ) {

						if ( empty( $pages ) ) {

							$pages[] = array( 'form_id' => $match, 'name' => $post->post_title, 'edit_url' => get_edit_post_link() , 'view_url' => get_permalink() );

						}
						else {

							$multiple_shortcodes_for_same_form = false;

							foreach ( $pages as $page ) {

								if ( $page['form_id'] == $match && $post->post_title == $page['name'] ) {

									$multiple_shortcodes_for_same_form = true;

									break;

								}

							}

							if ( ! $multiple_shortcodes_for_same_form ) {

								$pages[] = array( 'form_id' => $match, 'name' => $post->post_title, 'edit_url' => get_edit_post_link() , 'view_url' => get_permalink() );

							}

						}

					}

				}

			}

		}
		
		
		return $pages;

	}

	/**
	 * Get data for all feed addons, excluding the ones of exclude_types
	 *
	 * Returns an array of add-ons with slug, class, and title for add-ons
	 *
	 * @since 2.3.0
	 *
	 * @author Naomi C. Bush for gravity+ <support@gravityplus.pro>
	 *
	 * @param array $exclude_types
	 *
	 * @return array
	 */
	public static function get_addons( $exclude_types = array() ){

		$addon_data = array();

		$exclude_types = array_merge( array( 'GFP_Utility_AddOn' ), $exclude_types );

		$addons = array_unique( GFAddOn::get_registered_addons() );

		foreach( $addons as $addon ) {

			$callable = array( $addon, 'get_instance' );

			if ( is_callable( $callable ) ) {
				/**
				 * @var GFAddOn $a ;
				 */
				$a = call_user_func( array( $addon, 'get_instance' ) );

				if ( is_a( $a, 'GFFeedAddOn' ) ) {

					$excluded = false;

					foreach( $exclude_types as $type ) {

						if ( is_a( $a, $type ) ) {

							$excluded = true;

							break;

						}
					}

					if ( ! $excluded ) {

						$addon_data[ $a->get_slug() ] = apply_filters( 'gfp_gfutility_get_addons_addon_data', array( 'class' => $addon, 'title' => $a->get_short_title() ), $a );

					}

				}

			}

		}


		return apply_filters( 'gfp_gfutility_get_addons', $addon_data, $addons );

	}

	/**
	 * @see GFFeedAddOn::get_feeds
	 *
	 * @since 2.3.0
	 *
	 * @author Naomi C. Bush for gravity+ <support@gravityplus.pro>
	 *         
	 * @param string $addon_slug
	 * @param null   $form_id
	 *
	 * @return array|null|object
	 */
	public static function get_feeds( $addon_slug, $form_id = null ) {
		
		global $wpdb;

		$form_filter = is_numeric( $form_id ) ? $wpdb->prepare( 'AND form_id=%d', absint( $form_id ) ) : '';

		$sql = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}gf_addon_feed
                               WHERE addon_slug=%s {$form_filter} ORDER BY 'feed_order', 'id' ASC", $addon_slug
		);

		$results = $wpdb->get_results( $sql, ARRAY_A );
		
		foreach ( $results as &$result ) {
			
			$result['meta'] = json_decode( $result['meta'], true );
		
		}

		return $results;
	}

	/**
	 * @see GFFeedAddOn::get_feed
	 * 
	 * @since 2.3.0
	 *
	 * @author Naomi C. Bush for gravity+ <support@gravityplus.pro>
	 *
	 * @param $id
	 *
	 * @return array|false
	 */
	public static function get_feed( $id ) {
		
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gf_addon_feed WHERE id=%d", $id );

		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		if ( ! $row ) {
			
			return false;
		
		}

		$row['meta'] = json_decode( $row['meta'], true );

		return $row;
	}

	/**
	 * @see GFAddOn::add_note
	 *
	 * @since  2.5.0
	 *
	 * @author Naomi C. Bush for gravity+ <support@gravityplus.pro>
	 *
	 * @param      $entry_id
	 * @param      $note
	 * @param null $note_type
	 */
	public static function add_note( $entry_id, $note, $note_type = null ) {

		$user_id   = 0;
		$user_name = __( 'Utility', 'gravityformsutility' );

		GFFormsModel::add_note( $entry_id, $user_id, $user_name, $note, $note_type );

	}

}