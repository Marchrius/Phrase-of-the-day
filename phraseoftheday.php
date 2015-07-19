<?php
/**
 * @package PhraseOfTheDay
*/
/*
Plugin Name: Phrase of the Day
Plugin URI: http://marchrius.altervista.org/blog/
Description: This plug-in shows a random phrase saved in your database in the descrition of your header.
Author: Matteo Gaggiano
Version: 1.0
Author URI: http://marchrius.altervista.org/blog/
Text Domain: phraseoftheday
Domain Path: /languages/
*/

/*
License:
Phrase of the day: a Wordpress plugin.
    Copyright (C) 2015  Matteo Gaggiano

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/
define ( POD_PLUGIN_NAME , 'phraseoftheday' );
define ( POD_PREFIX , POD_PLUGIN_NAME . '_' );
define ( POD_TABLE_NAME , POD_PREFIX . 'phrases' );
define ( POD_DESCRIPTION_KEY , 'phrase' );
define ( POD_AUTHOR_KEY, 'author' );
define ( POD_PLUGIN_DIR, plugin_dir_path( __FILE__ ) );


require_once(POD_PLUGIN_DIR . '/'.POD_PREFIX.'main.php');
require_once(POD_PLUGIN_DIR . '/'.POD_PREFIX.'options.php');

register_activation_hook(__FILE__, 'phraseoftheday_first_install');

add_action( 'plugins_loaded', 'pod_load_textdomain' );
add_action( 'admin_init', 'pod_register_setting' );

add_filter('bloginfo', 'phraseoftheday_filter_description', 10, 2);

?>
