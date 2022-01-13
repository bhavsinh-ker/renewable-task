<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package catalog_site
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function renewable_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'renewable_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function renewable_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'renewable_pingback_header' );

/**
 * get page id by template name
 */
function get_page_id_by_template ( $template_path ) {
    if ( $template_path == "" ) {
        return false;
    }

    global $wpdb;
    $result = $wpdb->get_row( "SELECT `post_id` FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wp_page_template' && meta_value= '$template_path'", OBJECT );
    if ( is_wp_error( $result ) || empty( $result ) || !isset( $result->post_id ) ) {
        return false;
    }
    return $result->post_id;
}

/**
 * get page url by template name
 */
function get_page_url_by_template( $template_path ) {
    $post_id = get_page_id_by_template ( $template_path );
    if ( $post_id === false ) {
        return "";
    }
    return get_the_permalink( $post_id );
}