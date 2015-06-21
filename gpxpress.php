<?php

/*
 * Copyright 2012-2014 David Keen <david@davidkeen.com>
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
 * Plugin URI: http://davidkeen.github.com/gpxpress/
 * Description: Embed beautiful maps of GPX tracks.
 * Version: 1.4
 * Author: David Keen
 * Author URI: http://davidkeen.com
*/

// Constants
define( 'GPXPRESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GPXPRESS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GPXPRESS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Includes
include_once GPXPRESS_PLUGIN_DIR . 'includes/Gpxpress.php';

// The main plugin class
$gpxpress = new Gpxpress();

// Actions
add_action( 'wp_enqueue_scripts', array( $gpxpress, 'wp_enqueue_scripts' ) );
add_action( 'admin_enqueue_scripts', array( $gpxpress, 'admin_enqueue_scripts' ) );
add_action( 'admin_menu', array( $gpxpress, 'admin_menu' ) );
add_action( 'admin_init', array( $gpxpress, 'admin_init' ) );

// Filters
add_filter( 'plugin_action_links_' . GPXPRESS_PLUGIN_BASENAME, array( $gpxpress, 'add_settings_link' ) );
add_filter( 'upload_mimes', array( $gpxpress, 'add_gpx_mime' ) );

// Shortcodes
add_shortcode( 'gpxpress', array( $gpxpress, 'gpxpress_shortcode' ) );
