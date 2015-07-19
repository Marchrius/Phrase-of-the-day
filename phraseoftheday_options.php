<?php
if( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

function print_error($message) {
  ?>
  <div class="error"><p><strong><?php echo $message ?></strong></p></div>
  <?php
}

function print_success($message) {
  ?>
  <div class="updated"><p><strong><?php echo $message ?></strong></p></div>
  <?php
}


// defines
class POD_List_Table extends WP_List_Table {
  function __construct(){
    global $status, $page;

    parent::__construct( array(
      'singular'  => __( 'phrase',  POD_PLUGIN_NAME),     //singular name of the listed records
      'plural'    => __( 'phrases', POD_PLUGIN_NAME ),   //plural name of the listed records
      'ajax'      => false        //does this table support ajax?
      ) );
    }

    function no_items() {
      _e( 'No phrase found, dude. Add some from above form.', POD_PLUGIN_NAME );
    }

    function column_default( $item, $column_name ) {
      switch( $column_name ) {
        case 'id':
        case 'phrase':
        case 'author':
        return $item[ $column_name ];
        default:
        return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
      }
    }
    function get_columns() {
      $columns = array(
        'cb'        => '<input type="checkbox" />',
        'id'         => __( 'Id', POD_PLUGIN_NAME ),
        'phrase'     => __( 'Phrase', POD_PLUGIN_NAME ),
        'author'     => __( 'Author', POD_PLUGIN_NAME )
      );
      return $columns;
    }

    function process_bulk_action() {
      global $wpdb;

      if ( 'delete' === $this->current_action() ) {
        $recordsToDelete = (gettype($_GET['id']) == 'string') ? array( $_GET['id'] ) : $_GET['id'];
        $success = 1;
        foreach($recordsToDelete as $id) {
          $ret = $wpdb->delete(POD_TABLE_NAME, array( 'id' => $id ), array( "%d" ));
          if ($ret != 1) {
            $success = 0;
          }
        }
        if ($success != 1) {
          print_error(__( 'Error while deleting phrase.', POD_PLUGIN_NAME));
          // echo '<script type="text/javascript">window.location = "?page='. $_REQUEST['page'].'&success=0"</script>';
        } else {
          print_success(__( 'Phrase deleted correctly.', POD_PLUGIN_NAME));
          // echo '<script type="text/javascript">window.location = "?page='. $_REQUEST['page'].'&success=1"</script>';
        }
      }
    }

    function usort_reorder( $a, $b ) {
      $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'author';
      $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
      $result = strcmp( $a[$orderby], $b[$orderby] );
      return ( $order === 'asc' ) ? $result : -$result;
    }

    function prepare_items() {
      global $wpdb;
      $per_page = 5;
      $columns  = $this->get_columns();
      $hidden   = array( 'id' );
      $sortable = $this->get_sortable_columns();
      $this->_column_headers = array( $columns, $hidden, $sortable );
      $this->process_bulk_action();

      $pod_list = $wpdb->get_results("SELECT * FROM " . POD_TABLE_NAME, ARRAY_A);
      $screen = get_current_screen();
      usort( $pod_list, array( &$this, 'usort_reorder' ) );
      $current_page = $this->get_pagenum();
      $total_items = count($pod_list);
      $pod_list = array_slice($pod_list,(($current_page-1)*$per_page),$per_page);
      $this->items = $pod_list;
      $this->set_pagination_args( array(
        'total_items' => $total_items,                  //WE have to calculate the total number of items
        'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
        'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
      }

      function column_cb($item) {
        return sprintf(
        '<input type="checkbox" name="id[]" value="%s" />', $item['id']
      );
    }

    function get_sortable_columns() {
      $sortable_columns = array(
        'id'  => array('id',false),
        'phrase' => array('phrase',false),
        'author'   => array('author',false)
      );
      return $sortable_columns;
    }

    function column_phrase($item) {
      $str = '<a href="?page='.$_REQUEST['page'].'&action=%s&id='.$item['id'].'">%s</a>';
      $actions = array(
        'edit'      => sprintf($str, 'edit',   __('Edit')),
        'delete'    => sprintf($str, 'delete', __('Delete')),
      );
      return sprintf('%1$s %2$s', $item['phrase'], $this->row_actions($actions) );
    }

    function get_bulk_actions() {
      $actions = array(
        'delete'    => __( 'Delete' )
      );
      return $actions;
    }
  } // Class



  add_action('admin_menu', 'pod_add_pages');

  // action function for above hook
  function pod_add_pages() {
    $title = __('Phrase of the Day', POD_PLUGIN_NAME );
    $all_title = __( 'All phrases', POD_PLUGIN_NAME );
    $add_title = __( 'Add new', POD_PLUGIN_NAME );
    $opts_title = __( 'Phrases settings', POD_PLUGIN_NAME ) ;
    $cap = 'manage_options';
    $slug = POD_PLUGIN_NAME;

    add_menu_page($title.' - '.$all_title, $title, $cap, $slug, 'pod_all_phrases_page' );
    add_submenu_page($slug, $title, $all_title, $cap, $slug, 'pod_all_phrases_page' );
    add_submenu_page($slug, $add_title, $add_title, $cap, $slug.'-new_phrase', 'pod_new_phrase');
    add_submenu_page( $slug, $title, 'Impostazioni', $cap, $slug.'-settings', 'pod_settings_page' );
    add_options_page( $title, $opts_title, $cap, $slug.'-settings-legacy', 'pod_settings_page' );
  }

  function pod_settings_page() {
    echo '<div class="wrap">';
    echo "<h1>" . __( 'Settings' ) . "</h1>";
    //must check that the user has the required capability
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    if ($_POST['setting_updated']=='true' && $_GET['setting_updated']=='true') {
      print_success(__( 'Settings saved.', POD_PLUGIN_NAME));
      $settings_saved=0;
    }
    ?>

    <form name="form1" method="post" action="options.php">
      <input type="hidden" name="setting_updated" value="true">
          <? settings_fields(POD_PLUGIN_NAME.'-settings'); ?>
          <? do_settings_sections(POD_PLUGIN_NAME.'-settings'); ?>
          <? submit_button(__('Save Changes')) ?>
      </form>
    </div>

    <?php

  }

  function pod_settings_validate($input) {
    if (!isset($input['pod_active'])) {
        $input['pod_active'] = 0;
    }
    if (!isset($input['pod_show_author'])) {
        $input['pod_show_author'] = 0;
    }
    return $input;
    $updated = 0;
    $isSubmitted = isset($_POST['hidden_field']) && $_POST['hidden_field'] == "Y";
    $pod_active = get_option( 'pod_active' );
    $pod_show_author = get_option( 'pod_show_author' );

    if ( $isSubmitted ) {
      $pod_active = isset($_POST['pod_active']) ? 1 : 0;
      $pod_show_author = isset($_POST['pod_show_author']) ? 1 : 0;
      update_option( 'pod_active', $pod_active );
      update_option( 'pod_show_author', $pod_show_author );
      $updated = 1;
    }
  }

  function pod_all_phrases_page() {
    //must check that the user has the required capability
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    ?>
    <div class="wrap">
    <h2><?php _e( 'Phrases', POD_PLUGIN_NAME ); ?> <a href="?page=<?php echo POD_PLUGIN_NAME."-new_phrase"?>" class="add-new-h2"><?php _e('Add new', POD_PLUGIN_NAME) ?></a></h2>
    <?php

    $listTable = new POD_List_Table();
    $listTable->prepare_items();
    ?>

    <form id="phrase-filter" method="get" action="">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
    <!-- Now we can render the completed list table -->
    <?php $listTable->display(); ?>
    </form>
  </div>
    <?php
  }

  function pod_new_phrase() {
    //must check that the user has the required capability
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    echo '<div class="wrap">';
    echo "<h2>" . __( 'Add a new phrase', POD_PLUGIN_NAME) . "</h2>";
    global $wpdb;

    $pod_phrase = isset($_POST['phrase_text']) ? $_POST['phrase_text'] : "";
    $pod_author = isset($_POST['phrase_author']) ? $_POST['phrase_author'] : "";
    $pos_hidden_action = isset($_POST['hidden_field']) ? $_POST['hidden_field'] : "";

    if ( $pos_hidden_action == "add" && $pod_phrase != '' && $pod_author != '' ) {
      $pos_results = $wpdb->get_results('SELECT phrase FROM '.POD_TABLE_NAME.' WHERE phrase="'.$pod_phrase.'"');
      if ($wpdb->num_rows > 0) {
        print_error(__( 'Error phrase already exists.', POD_PLUGIN_NAME));
      } else {
        $wpdb->insert(POD_TABLE_NAME, array(
          'phrase' => stripslashes($pod_phrase),
          'author' => stripslashes($pod_author)
        ),
        array(
          "%s",
          "%s")
        );
        if ($wpdb->insert_id > 0) {
          print_success(__( 'Phrase added correctly.', POD_PLUGIN_NAME));
          $pod_phrase = "";
          $pod_author = "";
        } else {
          print_error(__( 'Error while adding phrase.', POD_PLUGIN_NAME));
        }
      }
    }
    ?>

    <form name="form1" method="post" action="">
      <input type="hidden" name="hidden_field" value="add">
      <table width="100%">
        <tr><td>
          <label for="phrase_text"><?php _e('Phrase to add ', POD_PLUGIN_NAME )?>: </label>
        </td><td>
          <textarea id="phrase_thext" value="" name="phrase_text" rows="6" cols="100%"><?php _e(stripslashes($pod_phrase)) ?></textarea>
        </td></tr>
        <tr><td>
          <label for="phrase_author"><?php _e( 'Author', POD_PLUGIN_NAME )?>: </label>
        </td><td>
          <input id="phrase_author" type="text" value="<?php _e(stripslashes($pod_author)) ?>" name="phrase_author" size="100%">
        </td></tr><tr><td>
          <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Add phrase', POD_PLUGIN_NAME ) ?>" />
          </p>
        </td></tr>
      </table>
    </form>
  </div>
  <?php
}

?>
