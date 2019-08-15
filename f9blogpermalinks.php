<?php
/**
 * Plugin Name:     Blog Permalinks Base
 * Description:     Blog Permalinks Base functions.
 * Author:          Fervidum
 * Author URI:      https://fervidum.github.io/
 * Version:         1.0.0
 * Directory:       https://fervidum.github.io/blogpermalinks
 *
 * @package         f9blogbase
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

// Define F9BLOGBASE_PLUGIN_FILE.
if ( ! defined( 'F9BLOGBASE_PLUGIN_FILE' ) ) {
	define( 'F9BLOGBASE_PLUGIN_FILE', __FILE__ );
}

/**
 * Retrieves the path of a file in the plugin.
 *
 * @param string $file Optional. File to search for in the plugin directory.
 * @return string The path of the file.
 */
function f9blogbase_file_path( $file = '' ) {
	$path = untrailingslashit( plugin_dir_path( F9BLOGBASE_PLUGIN_FILE ) );
	return $path . '/' . ltrim( $file, '/' );
}

/**
 * Use Selfd to updates.
 */
function f9blogbase_register_selfdirectory() {
	require_once f9blogbase_file_path( 'includes/libs/selfd/class-selfdirectory.php' );
	selfd( F9BLOGBASE_PLUGIN_FILE );
}
add_action( 'selfd_register', 'f9blogbase_register_selfdirectory' );

/**
 * Load Localisation files.
 *
 * Locales found in:
 *      - WP_LANG_DIR/plugins/f9blogbase-LOCALE.mo
 *      - WP_PLUGINS_DIR/f9blogbase/languages/LOCALE.mo
 */
function f9blogbase_load_textdomain() {
	$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
	$locale = apply_filters( 'plugin_locale', $locale, 'f9blogbase' );

	unload_textdomain( 'f9blogbase' );
	load_plugin_textdomain( 'f9blogbase', false, plugin_basename( dirname( THRIFTSTORE_PLUGIN_FILE ) ) . '/languages' );
	load_textdomain( 'f9blogbase', f9blogbase_file_path( 'languages/' . $locale . '.mo' ) );
}
add_action( 'init', 'f9blogbase_load_textdomain', 0 );

/**
 * Get permalink settings for blog.
 *
 * @return array
 */
function f9_get_permalink_structure() {
	$saved_permalinks = (array) get_option( 'f9blogbase_permalinks', array() );
	$permalinks       = wp_parse_args(
		array_filter( $saved_permalinks ),
		array(
			'blog_base'              => '',
			'use_verbose_page_rules' => false,
		)
	);

	if ( $saved_permalinks !== $permalinks ) {
		update_option( 'f9blogbase_permalinks', $permalinks );
	}

	$permalinks['blog_rewrite_slug'] = untrailingslashit( $permalinks['blog_base'] );

	return $permalinks;
}

/**
 * Include admin files conditionally.
 */
function f9blogbase_init() {
	$screen = get_current_screen();

	if ( ! $screen || 'options-permalink' !== $screen->id ) {
		return;
	}

	f9blogbase_settings_init();
	f9blogbase_settings_save();
}
add_action( 'current_screen', 'f9blogbase_init' );

/**
 * Init our settings.
 */
function f9blogbase_settings_init() {
	add_settings_section( 'f9blogbase-permalink', __( 'Blog permalinks', 'f9blogbase' ), 'f9blogbase_settings', 'permalink' );
}

/**
 * Show the settings.
 */
function f9blogbase_settings() {
	$permalinks = f9_get_permalink_structure();
	/* translators: %s: Home URL */
	echo wp_kses_post( wpautop( sprintf( __( 'If you like, you may enter custom structures for your blog URLs here. For example, using <code>blog</code> would make your post blog links like <code>%sblog/sample-post/</code>. This setting affects blog post URLs only, not things such as blog post categories.', 'f9blogbase' ), esc_url( home_url( '/' ) ) ) ) );

	$blog_page_id = get_option( 'page_for_posts' );
	$base_slug    = urldecode( ( $blog_page_id > 0 && get_post( $blog_page_id ) ) ? get_page_uri( $blog_page_id ) : _x( 'blog', 'default-slug', 'f9blogbase' ) );

	$structures = array(
		0 => '',
		1 => '/' . trailingslashit( $base_slug ),
	);
	?>
	<table class="form-table f9-permalink-structure">
		<tbody>
			<tr>
				<th><label><input name="blog_permalink" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" class="f9tog" <?php checked( $structures[0], $permalinks['blog_base'] ); ?>> <?php esc_html_e( 'Default', 'f9blogbase' ); ?></label></th>
				<td><code class="default-example"><?php echo esc_html( home_url() ); ?>/?p=123</code> <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/sample-post/</code></td>
			</tr>
			<?php if ( $blog_page_id ) : ?>
				<tr>
					<th><label><input name="blog_permalink" type="radio" value="<?php echo esc_attr( $structures[1] ); ?>" class="f9tog" <?php checked( $structures[1], $permalinks['blog_base'] ); ?>> <?php esc_html_e( 'Blog page base', 'f9blogbase' ); ?></label></th>
					<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/sample-post/</code></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th><label><input name="blog_permalink" id="f9blogbase_custom_selection" type="radio" value="custom" class="tog" <?php checked( in_array( $permalinks['blog_base'], $structures, true ), false ); ?>>
					<?php esc_html_e( 'Custom base', 'f9blogbase' ); ?></label></th>
				<td>
					<input name="blog_permalink_structure" id="f9blogbase_permalink_structure" type="text" value="<?php echo esc_attr( $permalinks['blog_base'] ? trailingslashit( $permalinks['blog_base'] ) : '' ); ?>" class="regular-text code"> <span class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', 'f9blogbase' ); ?></span>
				</td>
			</tr>
		</tbody>
	</table>
	<?php wp_nonce_field( 'f9-permalinks', 'f9-permalinks-nonce' ); ?>
	<script type="text/javascript">
		jQuery( function() {
			jQuery('input.f9tog').change(function() {
				jQuery('#f9blogbase_permalink_structure').val( jQuery( this ).val() );
			});
			jQuery('.permalink-structure input').change(function() {
				jQuery('.f9-permalink-structure').find('code.non-default-example, code.default-example').hide();
				if ( jQuery(this).val() ) {
					jQuery('.f9-permalink-structure code.non-default-example').show();
					jQuery('.f9-permalink-structure input').removeAttr('disabled');
				} else {
					jQuery('.f9-permalink-structure code.default-example').show();
					jQuery('.f9-permalink-structure input:eq(0)').click();
					jQuery('.f9-permalink-structure input').attr('disabled', 'disabled');
				}
			});
			jQuery('.permalink-structure input:checked').change();
			jQuery('#f9blogbase_permalink_structure').focus( function(){
				jQuery('#f9blogbase_custom_selection').click();
			} );
		} );
	</script>
	<?php
}

/**
 * Switch F9blogbase to site language.
 */
function f9_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		// Init F9blogbase locale.
		f9blogbase_load_textdomain();
	}
}

/**
 * Switch F9blogbase language to original.
 */
function f9_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init F9blogbase locale.
		f9blogbase_load_textdomain();
	}
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function f9_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'f9_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * Sanitize permalink values before insertion into DB.
 *
 * Cannot use f9_clean because it sometimes strips % chars and breaks the user's setting.
 *
 * @param  string $value Permalink.
 * @return string
 */
function f9_sanitize_permalink( $value ) {
	global $wpdb;

	$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

	if ( is_wp_error( $value ) ) {
		$value = '';
	}

	$value = esc_url_raw( trim( $value ) );
	$value = str_replace( 'http://', '', $value );
	return untrailingslashit( $value );
}

/**
 * Save the settings.
 */
function f9blogbase_settings_save() {
	if ( ! is_admin() ) {
		return;
	}

	// We need to save the options ourselves; settings api does not trigger save for the permalinks page.

	if ( isset( $_POST['permalink_structure'], $_POST['f9-permalinks-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['f9-permalinks-nonce'] ), 'f9-permalinks' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		f9_switch_to_site_locale();

		$permalinks = (array) get_option( 'f9blogbase_permalinks', array() );

		// Generate blog base.
		$blog_base = isset( $_POST['blog_permalink'] ) ? f9_clean( wp_unslash( $_POST['blog_permalink'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( 'custom' === $blog_base ) {
			if ( isset( $_POST['blog_permalink_structure'] ) ) {
				$blog_base = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', trim( wp_unslash( $_POST['blog_permalink_structure'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} else {
				$blog_base = '/';
			}
		} elseif ( empty( $product_base ) ) {
			$product_base = _x( 'blog', 'slug', 'f9blogbase' );
		}

		$permalinks['blog_base'] = f9_sanitize_permalink( $blog_base );

		// Blog page base may require verbose page rules if nesting pages.
		$blog_page_id   = get_option( 'page_for_posts' );
		$blog_permalink = ( $blog_page_id > 0 && get_post( $blog_page_id ) ) ? get_page_uri( $blog_page_id ) : _x( 'blog', 'default-slug', 'f9blogbase' );

		if ( $blog_page_id && stristr( trim( $permalinks['blog_base'], '/' ), $blog_permalink ) ) {
			$permalinks['use_verbose_page_rules'] = true;
		}

		update_option( 'f9blogbase_permalinks', $permalinks );
		f9_restore_locale();
	}
}

/**
 * Update default rewrite post type post.
 *
 * @param string $post_type Post type.
 */
function f9blogbase_blog_rewrite_rule( $post_type ) {
	global $wp_rewrite;

	if ( 'post' !== $post_type ) {
		return;
	}

	$permalinks = f9_get_permalink_structure();

	if ( $permalinks['blog_rewrite_slug'] ) {
		$prefix = trim( $permalinks['blog_rewrite_slug'], '/' );

		$pairs = array(
			'/embed'                         => 'embed=true',
			'/trackback'                     => 'tb=1',
			'/feed/(feed|rdf|rss|rss2|atom)' => 'feed=$matches[2]',
			'/(feed|rdf|rss|rss2|atom)'      => 'feed=$matches[2]',
			'/page/?([0-9]{1,})'             => 'paged=$matches[2]',
			'/comment-page-([0-9]{1,})'      => 'cpage=$matches[2]',
			'(?:/([0-9]+))?'                 => 'page=$matches[2]',
		);

		foreach ( $pairs as $match => $query ) {
			$match = $prefix . '/([^/]+)' . $match;
			$query = 'index.php?name=$matches[1]&post_type=post&' . $query;
			add_rewrite_rule( $match, $query, 'top' );
		}
	}
}
add_action( 'registered_post_type', 'f9blogbase_blog_rewrite_rule', 10, 2 );

/**
 * Blog without base when used is 404.
 *
 * @param bool   $is_404 Is 404.
 * @param object $wp_query WP_Query object.
 * @return bool
 */
function f9blogbase_blog_404( $is_404, $wp_query ) {
	$permalinks = f9_get_permalink_structure();

	$post = ! isset( $wp_query->query_vars['post_type'] ) || ! $wp_query->query_vars['post_type'];
	$name = isset( $wp_query->query_vars['name'] ) && $wp_query->query_vars['name'];
	if ( $permalinks['blog_rewrite_slug'] && $name && $post ) {
		$post = get_page_by_path( $wp_query->query_vars['name'], OBJECT, 'post' );
		if ( $post && 'post' === $post->post_type ) {
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	return $is_404;
}
add_filter( 'pre_handle_404', 'f9blogbase_blog_404', 10, 2 );

/**
 * Filters the permalink for a post of a custom post type.
 *
 * @param  string  $permalink The post's permalink.
 * @param  WP_Post $post      The post in question.
 * @param  bool    $leavename Whether to keep the post name.
 * @return bool
 */
function f9blogbase_blog_link( $permalink, $post, $leavename ) {
	$permalinks = f9_get_permalink_structure();

	if ( ! $permalinks['blog_rewrite_slug'] ) {
		return $permalink;
	}

	$rewritecode = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		$leavename ? '' : '%postname%',
		'%post_id%',
		'%category%',
		'%author%',
		$leavename ? '' : '%pagename%',
	);

	if ( is_object( $post ) && isset( $post->filter ) && 'sample' === $post->filter ) {
		$sample = true;
	} else {
		$post   = get_post( $post );
		$sample = false;
	}

	if ( empty( $post->ID ) ) {
		return false;
	}

	if ( 'post' !== $post->post_type ) {
		return $permalink;
	}

	$permalink = get_option( 'permalink_structure' );

	/**
	 * Filters the permalink structure for a post before token replacement occurs.
	 *
	 * Only applies to posts with post_type of 'post'.
	 *
	 * @since 3.0.0
	 *
	 * @param string  $permalink The site's permalink structure.
	 * @param WP_Post $post      The post in question.
	 * @param bool    $leavename Whether to keep the post name.
	 */
	$permalink = apply_filters( 'pre_post_link', $permalink, $post, $leavename );

	if ( '' !== $permalink && ! in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft', 'future' ), true ) ) {
		$unixtime = strtotime( $post->post_date );

		$category = '';
		if ( strpos( $permalink, '%category%' ) !== false ) {
			$cats = get_the_category( $post->ID );
			if ( $cats ) {
				$cats = wp_list_sort(
					$cats,
					array(
						'term_id' => 'ASC',
					)
				);

				/**
				 * Filters the category that gets used in the %category% permalink token.
				 *
				 * @since 3.5.0
				 *
				 * @param WP_Term  $cat  The category to use in the permalink.
				 * @param array    $cats Array of all categories (WP_Term objects) associated with the post.
				 * @param WP_Post  $post The post in question.
				 */
				$category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );

				$category_object = get_term( $category_object, 'category' );
				$category        = $category_object->slug;
				if ( $category_object->parent ) {
					$category = get_category_parents( $category_object->parent, false, '/', true ) . $category;
				}
			}
			// Show default category in permalinks, without having to assign it explicitly.
			if ( empty( $category ) ) {
				$default_category = get_term( get_option( 'default_category' ), 'category' );
				if ( $default_category && ! is_wp_error( $default_category ) ) {
					$category = $default_category->slug;
				}
			}
		}

		$author = '';
		if ( strpos( $permalink, '%author%' ) !== false ) {
			$authordata = get_userdata( $post->post_author );
			$author     = $authordata->user_nicename;
		}

		$date           = explode( ' ', date( 'Y m d H i s', $unixtime ) );
		$rewritereplace = array(
			$date[0],
			$date[1],
			$date[2],
			$date[3],
			$date[4],
			$date[5],
			$post->post_name,
			$post->ID,
			$category,
			$author,
			$post->post_name,
		);
		$permalink      = str_replace( $rewritecode, $rewritereplace, $permalink );
		$prefix         = trim( $permalinks['blog_rewrite_slug'], '/' );
		$permalink      = home_url( $prefix . $permalink );
		$permalink      = user_trailingslashit( $permalink, 'single' );
	} else { // If they're not using the fancy permalink option.
		$permalink = home_url( '?p=' . $post->ID );
	}

	return $permalink;
}
add_filter( 'post_link', 'f9blogbase_blog_link', 10, 3 );
