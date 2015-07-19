<?php

function phraseoftheday_filter_description($param1, $param2)
{
  if ($param2 != "description") {
    return $param1;
  }
  $options = get_option( POD_PLUGIN_NAME . '-settings');
  if ($options['pod_active'] == 0) {
    return $param1;
  }

  $dateTime = $options['pod_last_time'];
  global $wpdb;
  $pod_query = "SELECT * FROM " . POD_TABLE_NAME;
  $pod_results = $wpdb->get_results($pod_query, ARRAY_A);
  $pod_num_of_rows = $wpdb->num_rows;
  if ($pod_num_of_rows <= 0) {
    return $param1;
  }
  $pod_row_to_print = rand ( 0 , $pod_num_of_rows-1 );
  $pod_object_to_return = $pod_results[$pod_row_to_print];
  $pod_phrase = $pod_object_to_return[POD_DESCRIPTION_KEY];
  $pod_author = $pod_object_to_return[POD_AUTHOR_KEY];
  return  $pod_phrase . (($options['pod_show_author']==1) ? ' <span style="font-size:70%"><i>~'.$pod_author.'</i></span>' : '');
}

function phraseoftheday_first_install() {
   	global $wpdb;
		$sql = "CREATE TABLE " . POD_TABLE_NAME . " (
		`id` mediumint(9) NOT NULL AUTO_INCREMENT,
		`phrase` longtext NOT NULL,
		`author` tinytext NOT NULL,
		UNIQUE KEY id (id)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
    $opts = get_option(POD_PLUGIN_NAME.'-settings', array(
      'pod_active' => 1,
      'pod_show_author' => 0,
      'pod_last_time' => new DateTime('now')->format('U'),
      'pod_time_interval' => 1*60*24 /* 1sec*60*60*24 = 1day */ ));
    update_option(POD_PLUGIN_NAME.'-settings', $opts);
}

function pod_load_textdomain() {
  load_plugin_textdomain( POD_PLUGIN_NAME, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function pod_register_setting() {
  register_setting(    POD_PLUGIN_NAME.'-settings', POD_PLUGIN_NAME.'-settings', 'pod_settings_validate' );
  add_settings_section('pod_main', __('Main Settings', POD_PLUGIN_NAME), 'pod_sect', POD_PLUGIN_NAME.'-settings');
  add_settings_field(  'pod_active', __( 'Active', POD_PLUGIN_NAME), 'pod_active', POD_PLUGIN_NAME.'-settings', 'pod_main');
  add_settings_field(  'pod_show_author', __( 'Show Author', POD_PLUGIN_NAME), 'pod_show_author', POD_PLUGIN_NAME.'-settings', 'pod_main');
  add_settings_field( 'pod_time_interval', __( 'Time interval', POD_PLUGIN_NAME, 'pod_time_interval', POD_PLUGIN_NAME.'-settings', 'pod_main'));
  function pod_sect()
  {
    echo '<p>'.__( 'General Settings' , POD_PLUGIN_NAME ).'</p>';
  }
  function pod_active() {
    $opts = get_option(POD_PLUGIN_NAME.'-settings');
    $opt = $opts['pod_active'];
    $checked = $opt==1 ? "checked" : "";
    echo '<input type="checkbox" name="'.POD_PLUGIN_NAME.'-settings[pod_active]" id="pod_active" value="1" '. $checked .'/><label for="pod_active">'.__( 'Activate the plugin', POD_PLUGIN_NAME).'</label>';
  }
  function pod_show_author() {
    $opts = get_option(POD_PLUGIN_NAME.'-settings');
    $opt = $opts['pod_show_author'];
    $checked = $opt==1 ? "checked" : "";
    echo '<input type="checkbox" name="'.POD_PLUGIN_NAME.'-settings[pod_show_author]" id="pod_show_author" value="1" '.$checked.' /><label for="pod_show_author">'.__( 'Show the author name', POD_PLUGIN_NAME).'</label>';
  }
  function pod_time_interval() {

  }
}

?>
