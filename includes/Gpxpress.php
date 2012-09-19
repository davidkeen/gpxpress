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

class Gpxpress
{
    /**
     * register_activation_hook callback.
     */
    function on_activate() {

        $defaultOptions = array(
            'path_colour' => 'red');
        add_option('gpxpress_options', $defaultOptions);

        // Check we have the correct values if the option already existed.
        $options = get_option('gpxpress_options');
        foreach ($defaultOptions as $key => $val) {
            if (!isset($options[$key]) ) {
                $options[$key] = $defaultOptions[$key];
            }
        }
        update_option('gpxpress_options', $options);
    }

    /**
     * wp_enqueue_scripts action callback.
     */
    function include_javascript() {
        wp_register_style('leaflet-css', 'http://cdn.leafletjs.com/leaflet-0.4/leaflet.css');
        wp_enqueue_style('leaflet-css');

        wp_register_script('leaflet-js', 'http://cdn.leafletjs.com/leaflet-0.4/leaflet.js');
        wp_enqueue_script('leaflet-js');
    }

    /**
     * wp_footer action callback.
     *
     * Outputs the javascript to show the map.
     */
    function wp_footer() {

        // String containing a JS array of latlong pairs, parsed from the GPX file in the shortcode handler.
        global $latlong;

        $options = get_option('gpxpress_options');

        echo '
            <script type="text/javascript">
            //<![CDATA[
            var map = L.map("gpxpressMap");
            L.tileLayer("http://{s}.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.png", {
                attribution: "Data, imagery and map information provided by <a href=\"http://open.mapquest.co.uk\" target=\"_blank\">MapQuest</a>",
                maxZoom: 18,
                subdomains: ["otile1","otile2","otile3","otile4"]
            }).addTo(map);
            var polyline = L.polyline(' . $latlong . ', {color: "' . $options['path_colour'] . '"}).addTo(map);

            // zoom the map to the polyline
            map.fitBounds(polyline.getBounds());
            //]]>
            </script>';
    }

    /**
     * Filter callback to allow .gpx file uploads.
     *
     * @param array $existing_mimes the existing mime types.
     * @return array the allowed mime types.
     */
    function add_gpx_mime($existing_mimes = array()) {

        // Add file extension 'extension' with mime type 'mime/type'
        $existing_mimes['gpx'] = 'application/gpx+xml';

        // and return the new full result
        return $existing_mimes;
    }

    /**
     * Filter callback to add a link to the plugin's settings.
     *
     * @param $links
     * @return array
     */
    function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=gpxpress">' . __("Settings", "GPXpress") . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * admin_menu action callback.
     */
    function admin_menu() {
        global $gpxpress;
        add_options_page('GPXpress Options', 'GPXpress', 'manage_options', 'gpxpress', array($gpxpress, 'options_page'));
    }

    /**
     * Creates the plugin options page.
     * See: http://ottopress.com/2009/wordpress-settings-api-tutorial/
     * And: http://codex.wordpress.org/Settings_API
     */
    function options_page() {

        // AUthorised?
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Start the settings form.
        echo '
            <div class="wrap">
            <h2>GPXpress Settings</h2>
            <form method="post" action="options.php">';

        // Display the hidden fields and handle security.
        settings_fields('gpxpress-options');

        // Print out all settings sections.
        do_settings_sections('gpxpress');

        // Finish the settings form.
        echo '
            <input class="button-primary" name="Submit" type="submit" value="Save Changes" />
            </form>
            </div>';
    }

    /**
     * admin_init action callback.
     */
    function admin_init() {
        global $gpxpress;

        // Register a setting and its sanitization callback.
        // Parameters are:
        // $option_group - A settings group name. Must exist prior to the register_setting call. (settings_fields() call)
        // $option_name - The name of an option to sanitize and save.
        // $sanitize_callback - A callback function that sanitizes the option's value.
        register_setting('gpxpress-options', 'gpxpress_options', array($gpxpress, 'validate_options'));

        // Add the 'General Settings' section to the options page.
        // Parameters are:
        // $id - String for use in the 'id' attribute of tags.
        // $title - Title of the section.
        // $callback - Function that fills the section with the desired content. The function should echo its output.
        // $page - The type of settings page on which to show the section (general, reading, writing, media etc.)
        add_settings_section('general', 'General Settings', array($gpxpress, 'general_section_content'), 'gpxpress');


        // Register the options
        // Parameters are:
        // $id - String for use in the 'id' attribute of tags.
        // $title - Title of the field.
        // $callback - Function that fills the field with the desired inputs as part of the larger form.
        //             Name and id of the input should match the $id given to this function. The function should echo its output.
        // $page - The type of settings page on which to show the field (general, reading, writing, ...).
        // $section - The section of the settings page in which to show the box (default or a section you added with add_settings_section,
        //            look at the page in the source to see what the existing ones are.)
        // $args - Additional arguments
    	add_settings_field('path_colour', 'Path colour', array($gpxpress, 'path_colour_input'), 'gpxpress', 'general');
    }

    /**
     * Fills the section with the desired content. The function should echo its output.
     */
    function general_section_content() {
        // Nothing to see here.
    }

    /**
     * Fills the field with the desired inputs as part of the larger form.
     * Name and id of the input should match the $id given to this function. The function should echo its output.
     *
     * Name value must start with the same as the id used in register_setting.
     *
     * TODO: Genericise this to take a name param.
     *
     */
    function path_colour_input() {
        $options = get_option('gpxpress_options');
    	echo "<input id='path_colour' name='gpxpress_options[path_colour]' size='40' type='text' value='{$options['path_colour']}' />";
    }

    // TODO
    function validate_options($input) {
        $options = get_option('gpxpress_options');

        // Validate path colour
        $options['path_colour'] = $input['path_colour'];

        return $options;
    }
}


