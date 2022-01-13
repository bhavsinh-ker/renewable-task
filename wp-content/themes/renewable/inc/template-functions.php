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
    $page_name = ( isset( $_GET['page'] ) && esc_attr( $_GET['page'] )  != "" ) ? $_GET['page'] : 'renewable-books';
    $books_list = new Renewable_Books_List();
    $books_list->prepare_items();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Books', 'renewable'); ?></h1>
        <a href="?page=<?php echo $page_name; ?>&action=add-new" class="page-title-action"><?php _e('Add New', 'renewable'); ?></a>
        <hr class="wp-header-end">
        <form method="post">
            <input type="hidden" name="page" value="<?php echo $page_name; ?>">
            <?php
                $books_list->display();
            ?>
        </form>
    </div>    
    <?php
}

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Renewable_Books_List extends WP_List_Table {

    public function __construct(){
        global $status, $page;
        parent::__construct( array(
                'singular'  => __( 'book', 'renewable' ),
                'plural'    => __( 'books', 'renewable' ),
                'ajax'      => false
        ) );
        add_action( 'admin_head', array( $this, 'admin_header' ) );
    }

    public function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'renewable-books' != $page )
        return;
        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 5%; }';
        echo '.wp-list-table .column-name { width: 20%; }';
        echo '.wp-list-table .column-description { width: 45%; }';
        echo '.wp-list-table .column-price { width: 20%;}';
        echo '.wp-list-table .column-create_date { width: 20%;}';
        echo '</style>';
    }

    public function no_items() {
        _e( 'No books found.', 'renewable' );
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
          'name'  => array('name',false),
          'price'   => array('price',false),
          'create_date' => array('create_date',false)
        );
        return $sortable_columns;
    }

    public function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'name' => __( 'Name', 'renewable' ),
            'price'      => __( 'Price', 'renewable' ),
            'description'    => __( 'Description', 'renewable' ),
            'create_date'    => __( 'Create Date', 'renewable' )
        );
         return $columns;
    }

    public function column_name($item) {
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&book=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
            'delete'    => sprintf('<a onclick="return confirm('."'%s'".')" href="?page=%s&action=%s&book=%s">Delete</a>', __('Are you sure?', 'renewable'), $_REQUEST['page'], 'delete', $item['id']),
        );
      
        return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions) );
    }

    public function column_price($item){
        return $item['price'];
    }

    public function column_description($item) {
        return apply_filters( 'the_excerpt', wp_trim_words( $item['description'], 10, "..." ) );
    }

    public function column_create_date($item){
        return $item['create_date'];
    }

    public function get_bulk_actions() {
        $actions = array(
          'delete'    => 'Delete'
        );
        return $actions;
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="book[]" value="%s" />', $item['id']
        );
    }

    public function prepare_items() {
        global $wpdb;
        $per_page = 3;
        $current_page = $this->get_pagenum();
        $offset = ($current_page-1 ) * $per_page;
        $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}books" );

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $allowed_orderby = array(
            'id',
            'name',
            'price',
            'create_date'
        );
        $orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby ) ) ? $_GET['orderby'] : 'id';
        $order = ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) ? 'ASC' : 'DESC';
        $books_data = $wpdb->get_results( 
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}books ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset),
            ARRAY_A
        );

        $this->set_pagination_args( array(
          'total_items' => $total_items,
          'per_page'    => $per_page
        ) );

        $this->items = $books_data;
    }
}