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
    // The name of the div containing the map.
    const MAP_DIV = 'gpxpressMap';

    // MapQuest tile layer
    const OMQ_TILE_LAYER = 'http://{s}.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.png';
    const OMQ_ATTRIBUTION = 'Data, imagery and map information provided by <a href=\"http://open.mapquest.co.uk\" target=\"_blank\">MapQuest</a>';
    const OMQ_SUBDOMAINS = '["otile1","otile2","otile3","otile4"]';

    // String containing a JS array of latlong pairs, parsed from the GPX file in the shortcode handler.
    // Format: [[12.34,98.76],[56.78,54.32],...]
    private $latlong = '';

    /**
     * The register_activation_hook callback.
     * This method is run when the plugin is activated.
     */
    public function on_activate() {

        $defaultOptions = array('path_colour' => 'magenta');

        // Try to set the option (will return false if it already exists)
        if (!add_option('gpxpress_options', $defaultOptions)) {

            // The option was already set. Make sure any *new* default values are added set.
            $options = get_option('gpxpress_options');
            foreach ($defaultOptions as $key => $val) {
                if (!isset($options[$key]) ) {
                    $options[$key] = $defaultOptions[$key];
                }
            }
            update_option('gpxpress_options', $options);
        }
    }

    /**
     * The wp_enqueue_scripts action callback.
     * This is the hook to use when enqueuing items that are meant to appear on the front end.
     * Despite the name, it is used for enqueuing both scripts and styles.
     */
    public function wp_enqueue_scripts() {

        // Styles
        wp_register_style('leaflet-css', 'http://cdn.leafletjs.com/leaflet-0.4/leaflet.css');
        wp_enqueue_style('leaflet-css');

        // Scripts
        wp_register_script('leaflet-js', 'http://cdn.leafletjs.com/leaflet-0.4/leaflet.js');
        wp_enqueue_script('leaflet-js');
    }

    /**
     * The wp_footer action callback.
     *
     * Outputs the javascript to show the map.
     */
    public function wp_footer() {
        $options = get_option('gpxpress_options');

        echo '
            <script type="text/javascript">
            //<![CDATA[
            var map = L.map("' . self::MAP_DIV . '");
            L.tileLayer("' . self::OMQ_TILE_LAYER . '", {
                attribution: "' . self::OMQ_ATTRIBUTION . '",
                maxZoom: 18,
                subdomains: ' . self::OMQ_SUBDOMAINS . '
            }).addTo(map);
            var polyline = L.polyline(' . $this->latlong . ', {color: "' . $options['path_colour'] . '"}).addTo(map);

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
    public function add_gpx_mime($existing_mimes = array()) {

        // Add file extension 'extension' with mime type 'mime/type'
        $existing_mimes['gpx'] = 'application/gpx+xml';

        // and return the new full result
        return $existing_mimes;
    }

    /**
     * The [gpxpress] shortcode handler.
     *
     * This shortcode inserts a map of the GPX track.
     * The 'src' parameter should be used to give the url containing the GPX data.
     * The 'width' and 'height' parameters set the width and height of the map in pixels. (Default 600x400)
     * Eg: [gpxpress src=http://www.example.com/my_file.gpx width=600 height=400]
     *
     * @param string $atts an associative array of attributes.
     * @return string the shortcode output to be inserted into the post body in place of the shortcode itself.
     */
    public function gpxpress_shortcode($atts) {

        // Extract the shortcode arguments into local variables named for the attribute keys (setting defaults as required)
        $defaults = array(
            'src' => null,
            'width' => 600,
            'height' => 400);
        extract(shortcode_atts($defaults, $atts));

        // Create a div to show the map.
        $ret = '<div id="' . self::MAP_DIV .'" style="width: ' . $width . 'px; height: ' . $height .'px">&#160;</div>';

        // Parse the latlongs from the GPX and save them to a global variable to be used in the JS later.
        // String format: [[12.34,98.76],[56.78,54.32]]
        $pairs = array();
        $xml = simplexml_load_file($src);
        foreach ($xml->trk->trkseg->trkpt as $trkpt) {
            $pairs[] = '[' . $trkpt['lat'] . ',' . $trkpt['lon'] . ']';
        }
        $this->latlong = '[' . implode(',', $pairs) . ']';

        return $ret;
    }

    // TODO: Move admin stuff into separate class.

    /**
     * Filter callback to add a link to the plugin's settings.
     *
     * @param $links
     * @return array
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=gpxpress">' . __("Settings", "GPXpress") . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * admin_menu action callback.
     */
    public function admin_menu() {
        add_options_page('GPXpress Options', 'GPXpress', 'manage_options', 'gpxpress', array($this, 'options_page'));
    }

    /**
     * Creates the plugin options page.
     * See: http://ottopress.com/2009/wordpress-settings-api-tutorial/
     * And: http://codex.wordpress.org/Settings_API
     */
    public function options_page() {

        // Authorised?
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
    public function admin_init() {

        // Register a setting and its sanitization callback.
        // Parameters are:
        // $option_group - A settings group name. Must exist prior to the register_setting call. (settings_fields() call)
        // $option_name - The name of an option to sanitize and save.
        // $sanitize_callback - A callback function that sanitizes the option's value.
        register_setting('gpxpress-options', 'gpxpress_options', array($this, 'validate_options'));

        // Add the 'General Settings' section to the options page.
        // Parameters are:
        // $id - String for use in the 'id' attribute of tags.
        // $title - Title of the section.
        // $callback - Function that fills the section with the desired content. The function should echo its output.
        // $page - The type of settings page on which to show the section (general, reading, writing, media etc.)
        add_settings_section('general', 'General Settings', array($this, 'general_section_content'), 'gpxpress');


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
    	add_settings_field('path_colour', 'Path colour', array($this, 'path_colour_input'), 'gpxpress', 'general');
    }

    /**
     * Fills the section with the desired content. The function should echo its output.
     */
    public function general_section_content() {
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
    public function path_colour_input() {
        $options = get_option('gpxpress_options');
    	echo "<input id='path_colour' name='gpxpress_options[path_colour]' size='40' type='text' value='{$options['path_colour']}' />";
    }

    // TODO
    public function validate_options($input) {
        $options = get_option('gpxpress_options');

        // Validate path colour
        $options['path_colour'] = $input['path_colour'];

        return $options;
    }
}


