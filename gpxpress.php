<?php

/*
 * Copyright 2012 David Keen <david@davidkeen.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/*
 * Plugin Name: GPXpress
 * Plugin URI: https://github.com/davidkeen/gpxpress
 * Description: Wordpress plugin to display GPX tracks.
 * Version: 0.1
 * Author: David Keen
 * Author URI: http://davidkeen.com
*/

include_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
include_once plugin_dir_path(__FILE__) . 'includes/Gpxpress.php';

$gpxpress = new Gpxpress();

// Hooks
register_activation_hook(__FILE__, array($gpxpress, 'on_activate'));

// Actions
add_action('wp_enqueue_scripts', array($gpxpress, 'include_javascript'));
add_action('wp_footer', array($gpxpress, 'wp_footer'));
add_action('admin_menu', array($gpxpress, 'admin_menu'));
add_action('admin_init', array($gpxpress, 'admin_init'));

// Filters
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_{$plugin}", array($gpxpress, 'add_settings_link'));
add_filter("upload_mimes", array($gpxpress, 'add_gpx_mime'));