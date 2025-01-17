<?php
/**
 * Site Breadcrumbs.
 *
 * @package toolbelt
 */

// Don't display in the WordPress admin.
if ( is_admin() ) {
	return;
}


/**
 * A function to display breadcrumbs on a category or single post/ page.
 * Should work for all post types.
 *
 * This module must be:
 * a) enabled in the plugin settings
 * b) have the function below added to the theme. It won't work without this
 * function.
 *
 * @return void
 */
function toolbelt_breadcrumbs() {

	if ( ! apply_filters( 'toolbelt_display_breadcrumbs', true ) ) {
		return;
	}

	// Quit on the homepage.
	if ( is_front_page() ) {
		return;
	}

	$breadcrumb_type = toolbelt_breadcrumb_type();

	// Quit if the content is a top level item.
	if ( ! $breadcrumb_type ) {
		return;
	}

	$breadcrumbs = toolbelt_breadcrumb_get( $breadcrumb_type );

	if ( empty( $breadcrumbs ) ) {
		return;
	}

	toolbelt_styles( 'breadcrumbs' );

	/**
	 * Output the breadcrumbs.
	 * Sanitization is ignored since we are already sanitizing the values when the string is generated.
	 */
	echo toolbelt_breadcrumb_html( $breadcrumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

}


/**
 * Generate the breadcrumb list.
 * The list is stored in a transient for faster retrieval.
 *
 * @param array $type Information about the type of breadcrumb list to get.
 * @return array
 */
function toolbelt_breadcrumb_get( $type ) {

	$breadcrumbs = array();
	$breadcrumbs[] = array( home_url( '/' ), __( 'Home', 'wp-toolbelt' ) );

	switch ( $type[0] ) {

		case 'post':
			$breadcrumbs = array_merge( $breadcrumbs, toolbelt_breadcrumb_post_hierarchical() );
			break;

		case 'taxonomy':
			$breadcrumbs = array_merge( $breadcrumbs, toolbelt_breadcrumb_tax_hierarchical( $type[1] ) );
			break;

		default:
			$breadcrumbs = array();
			break;

	}

	return $breadcrumbs;

}


/**
 * Generate the breadcrumb html.
 *
 * @param array $breadcrumbs List of breadcrumbs to display as html.
 * @return string
 */
function toolbelt_breadcrumb_html( $breadcrumbs ) {

	$html = '';

	foreach ( $breadcrumbs as $crumb ) {

		if ( null !== $crumb[1] ) {

			$html .= toolbelt_breadcrumb_link( $crumb[0], $crumb[1] );

		} else {

			$html .= toolbelt_breadcrumb_item( $crumb[0] );

		}
	}

	return '<ol class="entry-breadcrumbs toolbelt-breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList">' . $html . '</ol>';

}


/**
 * Get the type of breadcrumb trail to generate.
 *
 * This works out if it's a post or taxonomy trail.
 *
 * @return false|array Item 0 = breadcrumb type (taxonomy or post), Item 1 = type of breadcrumb type. Eg, archive or tag.
 */
function toolbelt_breadcrumb_type() {

	// Single post/ page breadcrumbs.
	$post_type = 'page';
	if ( ! is_page() ) {
		$post_type = get_query_var( 'post_type' );
	}

	if ( is_post_type_hierarchical( $post_type ) ) {
		return array(
			'post',
			$post_type,
		);
	}

	// Taxonomy archive.
	$taxonomy = 'category';
	if ( ! is_category() ) {
		$taxonomy = get_query_var( 'taxonomy' );
	}

	if ( is_taxonomy_hierarchical( $taxonomy ) ) {
		return array(
			'taxonomy',
			$taxonomy,
		);
	}

	// No idea, so let's quit.
	return false;

}


/**
 * Generate a hierarchical breadcrumb trail.
 *
 * @param string $taxonomy The taxonomy type for the archive.
 * @return array
 */
function toolbelt_breadcrumb_tax_hierarchical( $taxonomy ) {

	$current = get_term( get_queried_object_id(), $taxonomy );
	$breadcrumbs = array();

	if ( is_wp_error( $current ) ) {
		return $breadcrumbs;
	}

	if ( ! $current instanceof WP_Term ) {
		return $breadcrumbs;
	}

	/**
	 * If the current taxonomy has a parent taxonomy then let's add them all
	 * together.
	 */
	if ( isset( $current->parent ) ) {
		$breadcrumbs = array_merge( $breadcrumbs, toolbelt_get_term_parents( (int) $current->parent, $taxonomy ) );
	}

	$breadcrumbs[] = array( $current->name, null );

	return $breadcrumbs;

}


/**
 * Return the parents for a given taxonomy term ID.
 *
 * This is a recursive function. It calls itself.
 *
 * @param int    $term Taxonomy term whose parents will be returned.
 * @param string $taxonomy Taxonomy name that the term belongs to.
 * @param array  $visited Terms already added to prevent duplicates.
 * @return array A list of links to the term parents.
 */
function toolbelt_get_term_parents( $term, $taxonomy, $visited = array() ) {

	$parent = get_term( $term, $taxonomy );
	$breadcrumbs = array();

	if ( is_wp_error( $parent ) ) {
		return $breadcrumbs;
	}

	if ( ! $parent instanceof WP_Term ) {
		return $breadcrumbs;
	}

	/**
	 * If the term has a parent, and we haven't already added this parent to the
	 * list then let's get the next parent.
	 *
	 * This calls the function again, and will keep looping until we're at the
	 * most distant relative.
	 *
	 * When the function starts returning again it will start adding the
	 * breadcrumbs. Because of the order of the operations the breadcrumbs will
	 * display in the correct order.
	 */
	if ( $parent->parent && ( $parent->parent !== $parent->term_id ) && ! in_array( $parent->parent, $visited, true ) ) {
		$visited[] = $parent->parent;
		$breadcrumbs = array_merge( $breadcrumbs, toolbelt_get_term_parents( $parent->parent, $taxonomy, $visited ) );
	}

	$breadcrumbs[] = array( get_category_link( $parent->term_id ), $parent->name );

	return $breadcrumbs;

}


/**
 * Generate a post breadcrumb trail.
 *
 * By post I am referring to any single post type that supports a hierarchy.
 * This includes pages, and custom post types that can have a parent child
 * relationships.
 *
 * @return array
 */
function toolbelt_breadcrumb_post_hierarchical() {

	$post_id = get_queried_object_id();
	$ancestors = array_reverse( get_post_ancestors( $post_id ) );
	$breadcrumbs = array();

	/**
	 * Loop through the list of post parents and generate a list of breadcrumbs
	 * from the list.
	 */
	if ( $ancestors ) {
		foreach ( $ancestors as $ancestor ) {

			$breadcrumbs[] = array( get_permalink( $ancestor ), get_the_title( $ancestor ) );

		}
	}

	$breadcrumbs[] = array( get_the_title( $post_id ), null );

	return $breadcrumbs;

}


/**
 * Generate a Breadcrumb item.
 *
 * @param string|false $url The url to link to.
 * @param string|false $title The title to display.
 * @return string The html for the breadcrumb item.
 */
function toolbelt_breadcrumb_link( $url, $title ) {

	if ( ! $title || ! $url ) {
		return '';
	}

	global $toolbelt_breadcrumb_position;

	if ( ! isset( $toolbelt_breadcrumb_position ) ) {
		$toolbelt_breadcrumb_position = 0;
	}

	$toolbelt_breadcrumb_position ++;

	$html = '';
	$html .= '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
	$html .= '<a href="%1$s" itemprop="item"><span itemprop="name">%2$s</span></a>';
	$html .= '<meta itemprop="position" content="%3$d" />';
	$html .= '</li>';

	return sprintf(
		$html,
		esc_url( $url ),
		esc_html( $title ),
		$toolbelt_breadcrumb_position
	);

}


/**
 * Generate a Breadcrumb item with no link.
 *
 * @param string|false $title The title to display.
 * @return string The html for the breadcrumb item.
 */
function toolbelt_breadcrumb_item( $title ) {

	if ( ! $title ) {
		return '';
	}

	global $toolbelt_breadcrumb_position;

	$toolbelt_breadcrumb_position ++;

	$html = '';
	$html .= '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
	$html .= '<span itemprop="name">%1$s</span>';
	$html .= '<meta itemprop="position" content="%2$d" />';
	$html .= '</li>';

	return sprintf(
		$html,
		esc_html( $title ),
		$toolbelt_breadcrumb_position
	);

}
