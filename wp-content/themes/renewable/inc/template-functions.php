<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package renewable
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

/**
 * theme setup process
 */
function renewable_theme_setup() {
    /* Create database table if not exist */
    global $wpdb;
    $table_name = $wpdb->base_prefix.'books';
    $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

    if ( ! $wpdb->get_var( $query ) == $table_name ) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        description text NULL,
        price mediumint(9) NULL,
        create_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    /* EOF Create database table if not exist */
}

add_action( 'after_setup_theme', 'renewable_theme_setup' );

/**
 * Add books admin menu
 */
function renewable_books_admin_menu() {
	add_menu_page( 'Books', 'Books', 'manage_options', 'renewable-books', 'renewable_books_admin_menu_callback', 'dashicons-book', 6  );
}

add_action( 'admin_menu', 'renewable_books_admin_menu' );

/**
 * Books admin menu callback
 */
function renewable_books_admin_menu_callback() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Books', 'renewable'); ?></h1>
    </div>
    <?php
}