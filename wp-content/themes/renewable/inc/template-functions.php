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
        create_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
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
    $page_title = __('Books', 'renewable');
    if ( isset ( $_GET['action'] ) ) {
        switch ( esc_attr ($_GET['action'] ) ) {
            case "add-new":
                $page_title = __('Add New Book', 'renewable');
            break;
            case "edit":
                $page_title = __('Edit Book', 'renewable');
            break;
        }
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo $page_title; ?></h1>
        <?php if ( ! isset( $_GET['action'] ) ) { ?>
        <a href="?page=<?php echo $page_name; ?>&action=add-new" class="page-title-action"><?php _e('Add New', 'renewable'); ?></a>
        <?php } ?>
        <hr class="wp-header-end">
        <?php echo settings_errors(); ?>
        <form method="post">
            <input type="hidden" name="page" value="<?php echo $page_name; ?>">
            <?php 
                if ( isset ( $_GET['action'] ) && '' != esc_attr ( $_GET['action'] ) ) {
                    // add & edit form
                    $form_data = array();
                    echo '<input type="hidden" name="action" value="'.esc_attr ($_GET['action'] ).'">';
                    if( 'edit' == esc_attr ($_GET['action'] ) && isset ( $_GET['book'] ) && '' != esc_attr ($_GET['book'] ) ) {
                        echo '<input type="hidden" name="id" value="'.esc_attr ($_GET['book'] ).'">';
                        global $wpdb;
                        $table_name = $wpdb->base_prefix.'books';
                        $form_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", esc_attr ($_GET['book'] ) ), ARRAY_A );
                    }
                    echo wp_nonce_field( -1, '_wpnonce' );
                    if( $_GET['action'] == "add-new" || $_GET['action'] == "edit" ) {
                        echo get_renewable_books_form_html($form_data);
                    }
                } else {
                    // view book table
                    $books_list = new Renewable_Books_List();
                    $books_list->prepare_items();
                    $books_list->display();
                }
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

function get_renewable_books_form_html( $form_data = NULL ) {
    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="bookName"><?php _e( 'Book Name', 'renewable' ); ?></label>
                </th>
                <td>
                    <input name="name" type="text" id="bookName" class="regular-text" value="<?php echo ( isset( $form_data['name'] ) && '' != $form_data['name'] ) ? esc_attr($form_data['name']) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bookPrice"><?php _e( 'Price', 'renewable' ); ?></label>
                </th>
                <td>
                    <input name="price" type="number" id="bookPrice" class="regular-text" value="<?php echo ( isset( $form_data['price'] ) && '' != $form_data['price'] ) ? esc_attr($form_data['price']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bookDescription"><?php _e( 'Description', 'renewable' ); ?></label>
                </th>
                <td>
                    <textarea name="description" id="description" cols="30" rows="10" id="bookDescription" class="regular-text"><?php echo ( isset( $form_data['description'] ) && '' != $form_data['description'] ) ? esc_attr($form_data['description']) : ''; ?></textarea>
                </td>
            </tr>
        </tbody>
    </table>
    <p class="submit">
        <input type="submit" name="submit" id="BookFormSubmit" class="button button-primary" value="<?php _e( 'Save Book', 'renewable' ); ?>">
    </p>
    <?php
}

function renewable_admin_init() {

    if( ! isset( $_GET['page'] ) || 'renewable-books' != $_GET['page'] ) {
        return;
    }

    if ( ! session_id() ) {
        session_start();
    }

    global $wpdb;
    $table_name = $wpdb->base_prefix.'books';
    $url = get_admin_url( null, 'admin.php?page=renewable-books');
    
    /* Form actions */
    if( isset( $_POST['action'] ) && ( 'add-new' == $_POST['action'] || 'edit' == $_POST['action'] || 'delete' == $_POST['action'] ) ) {
        $error_url = ( isset( $_POST['_wp_http_referer'] ) && '' != $_POST['_wp_http_referer'] ) ? $_POST['_wp_http_referer'] : $url;

        $fields = array( 
            'name' => esc_attr($_POST['name']), 
            'price' => esc_attr($_POST['price']), 
            'description' => esc_attr($_POST['description'])
        );

        $fields_type = array( 
            '%s', 
            '%d',
            '%s'
        );

        if( 'add-new' == $_POST['action'] ) {
            $insert_book = $wpdb->insert( 
                $table_name, 
                $fields, 
                $fields_type
            );

            if( false === $insert_book ) {
                $_SESSION['renewable']['message'][] = array(
                    "status" => 0,
                    "message" => __( 'Something is Wrong! Please Try Again.', 'renewable' )
                );
                header( "Location: ".$error_url );
                die();
            }

            $url = add_query_arg( array(
                'action' => 'edit',
                'book' => $wpdb->insert_id
            ), $url );

            $_SESSION['renewable']['message'][] = array(
                "status" => 1,
                "message" => __( 'Book Added Successfuly.', 'renewable' )
            );
            header( "Location: ".$url );
            die();
        }

        if( 'edit' == $_POST['action'] && ( isset( $_POST['id'] ) && '' != $_POST['id'] ) ) {
            $update_book = $wpdb->update(
                $table_name,
                $fields, 
                array( 
                    'id' => esc_attr ( $_POST['id'] ) 
                ), 
                $fields_type, 
                array( '%d' )
            );

            if( false === $update_book ) {
                $_SESSION['renewable']['message'][] = array(
                    "status" => 0,
                    "message" => __( 'Something is Wrong! Please Try Again.', 'renewable' )
                );
                header( "Location: ".$error_url );
                die();
            }

            $url = add_query_arg( array(
                'action' => 'edit',
                'book' => esc_attr ( $_POST['id'] )
            ), $url );

            $_SESSION['renewable']['message'][] = array(
                "status" => 1,
                "message" => __( 'Book Updated Successfuly.', 'renewable' )
            );

            header( "Location: ".$url );
            die();
        }

        if( 'delete' == $_POST['action'] && ( isset( $_POST['book'] ) && ! empty( $_POST['book'] ) ) ) {
            $ids = implode( ',', $_POST['book'] );
            $books_delete = $wpdb->query( "DELETE FROM $table_name WHERE id IN($ids)" );

            if( false === $books_delete ) {
                $_SESSION['renewable']['message'][] = array(
                    "status" => 0,
                    "message" => __( 'Something is Wrong! Please Try Again.', 'renewable' )
                );
                header( "Location: ".$error_url );
                die();
            }
    
            $_SESSION['renewable']['message'][] = array(
                "status" => 1,
                "message" => __( 'Books Deleted Successfuly', 'renewable' )
            );
            header( "Location: ".$url );
            die();
        }

    }
    /* EOF Form actions */

    /* Book Delete Action */
    if( isset( $_GET['action'] ) && 'delete' == $_GET['action'] && isset( $_GET['book'] ) && '' != $_GET['book'] ) {
        $book_delete = $wpdb->delete(
            $table_name,
            array(
                'id' => esc_attr ( $_GET['book'] ) 
            ),
            array(
                '%d'
            ),
        );

        if( false === $book_delete ) {
            $_SESSION['renewable']['message'][] = array(
                "status" => 0,
                "message" => __( 'Something is Wrong! Please Try Again.', 'renewable' )
            );
            header( "Location: ".$url );
            die();
        }

        $_SESSION['renewable']['message'][] = array(
            "status" => 1,
            "message" => __( 'Book Deleted Successfuly', 'renewable' )
        );
        header( "Location: ".$url );
        die();
    }
    /* EOF Book Delete Action */

    /* Show form messages */
    if( isset( $_SESSION['renewable']['message'] ) && !empty( $_SESSION['renewable']['message'] ) ) {        
        add_action( 'admin_notices', function() {
            foreach( $_SESSION['renewable']['message'] as $data ) {
            ?>
            <div class="notice notice-<?php echo ( isset( $data['status'] ) && 1 === $data['status'] ) ? 'success' : 'error'; ?> is-dismissible">
                <p><?php echo ( isset( $data['message'] ) && $data['message'] != '' ) ? $data['message'] : _e( 'Done!', 'renewable' ); ?></p>
            </div>
            <?php
            }
            unset( $_SESSION['renewable']['message'] );
        });
    }
    /* EOF Show form messages */
}

add_action('admin_init', 'renewable_admin_init');