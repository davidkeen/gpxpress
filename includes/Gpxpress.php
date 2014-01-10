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
    // MapQuest tile layer
    const MQ_TILE_ATTRIBUTION = 'Tiles Courtesy of <a href=\"http://www.mapquest.com/\" target=\"_blank\">MapQuest</a> <img src=\"http://developer.mapquest.com/content/osm/mq_logo.png\">';
    const MQ_SUBDOMAINS = '["otile1","otile2","otile3","otile4"]';
    const MQ_OSM_TILE_LAYER = 'http://{s}.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.png';
    const MQ_AERIAL_TILE_LAYER = 'http://{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.png';
    const MQ_OSM_ATTRIBUTION = '© OpenStreetMap';
    const MQ_AERIAL_ATTRIBUTION = 'Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency';

    // Default values for all plugin options.
    // To add a new option just add it to this array.
    private $defaultOptions = array(
        'path_colour' => 'magenta',
        'width' => 600,
        'height' => 400,
        'showstart' => false,
        'showfinish' => false);
    private $options;


    public function __construct() {

        // Set up the options array
        $this->options = get_option('gpxpress_options');
        if (!is_array($this->options)) {

            // We don't have any options set yet.
            $this->options = $this->defaultOptions;

            // Save them to the DB.
            update_option('gpxpress_options', $this->options);
        } else if (count(array_diff_key($this->defaultOptions, $this->options)) > 0) {

            // The option was set but we don't have all the option values.
            foreach ($this->defaultOptions as $key => $val) {
                if (!isset($this->options[$key]) ) {
                    $this->options[$key] = $this->defaultOptions[$key];
                }
            }

            // Save them to the DB.
            update_option('gpxpress_options', $this->options);
        }
    }

    /**
     * Checks if the shortcode is used in the page.
     * Adapted from code by Ian Dunn <ian@iandunn.name>.
     *
     * @param $posts the posts
     * @return bool true if the shortcode is used.
     */
    private function shortcodeCalled($posts) {
        foreach ($posts as $p) {
            preg_match('/'. get_shortcode_regex() .'/s', $p->post_content, $matches);
            if (is_array($matches) && array_key_exists(2, $matches) && $matches[2] == 'gpxpress') {
                return true;
            }
        }
        return false;
    }

    /**
     * The wp_enqueue_scripts action callback.
     * This is the hook to use when enqueuing items that are meant to appear on the front end.
     * Despite the name, it is used for enqueuing both scripts and styles.
     */
    public function wp_enqueue_scripts() {

        // Styles
        wp_register_style('leaflet-css', 'http://cdn.leafletjs.com/leaflet-0.7/leaflet.css');

        // Scripts
        wp_register_script('leaflet-js', 'http://cdn.leafletjs.com/leaflet-0.7/leaflet.js');
        wp_register_script('icons', plugins_url('js/icons.js', dirname(__FILE__)));

        // Only enqueue the scripts if the shortcode is used.
        global $posts;
        if ($this->shortcodeCalled($posts)) {
            wp_enqueue_style('leaflet-css');
            wp_enqueue_script('leaflet-js');

            wp_enqueue_script('icons');
            wp_localize_script('icons', 'iconsData', array(
                'iconPath' => plugins_url('icons', dirname(__FILE__))));
        }
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
     * The 'showStart' and 'showFinish' parameters toggle the start/finish markers.
     * Eg: [gpxpress src=http://www.example.com/my_file.gpx width=600 height=400 showStart=true showFinish=false]
     *
     * @param string $atts an associative array of attributes.
     * @return string the shortcode output to be inserted into the post body in place of the shortcode itself.
     */
    public function gpxpress_shortcode($atts) {

        static $mapCount = 0;
        $divId = 'gpxpressMap_' . ++$mapCount;

        // Extract the shortcode arguments into local variables named for the attribute keys (setting defaults as required)
        $defaults = array(
            'src' => GPXPRESS_PLUGIN_DIR . '/demo.gpx',
            'width' => $this->options['width'],
            'height' => $this->options['height'],
            'showstart' => $this->options['showstart'],
            'showfinish' => $this->options['showfinish']);
        extract(shortcode_atts($defaults, $atts));

        // Create a div to show the map.
        $ret = '<div id="' . $divId .'" style="width: ' . $width . 'px; height: ' . $height .'px">&#160;</div>';

        // Parse the latlongs from the GPX to a JS array
        // String format: [[12.34,98.76],[56.78,54.32]]
        $pairs = array();
        $xml = simplexml_load_file($src);
        foreach ($xml->trk->trkseg->trkpt as $trkpt) {
            $pairs[] = '[' . $trkpt['lat'] . ',' . $trkpt['lon'] . ']';
        }
        $latlong = '[' . implode(',', $pairs) . ']';

        // The track start latlong ('[12.34,98.76]')
        $start = $pairs[0];

        // The track finish latlong
        $finish = end(array_values($pairs));

        // The javascript
        // We need to produce the javascript in the shortcode as we may have more than one map per page.
        // We use global js 'map' and 'polyline' vars here. This seems to work fine with multiple maps per page...
        $ret .= '
            <script type="text/javascript">
            //<![CDATA[
            var map = L.map("' . $divId . '");
            L.tileLayer("' . self::MQ_OSM_TILE_LAYER . '", {
                attribution: "' . self::MQ_OSM_ATTRIBUTION . ' | ' . self::MQ_TILE_ATTRIBUTION . '",
                maxZoom: 18,
                subdomains: ' . self::MQ_SUBDOMAINS . '
            }).addTo(map);
            var polyline = L.polyline(' . $latlong . ', {color: "' . $this->options['path_colour'] . '"}).addTo(map);

            // zoom the map to the polyline
            map.fitBounds(polyline.getBounds());
            //]]>
            </script>
        ';

        // Add markers if required (user submitted attributes will be strings not real booleans which we store in the DB)
        if ($showstart === true || $showstart === 'true') {
            $ret .= '
            <script type="text/javascript">
            //<![CDATA[
            L.marker(' . $start . ', {icon: startIcon}).addTo(map);
            //]]>
            </script>
            ';
        }
        if ($showfinish === true || $showfinish === 'true') {
            $ret .= '
            <script type="text/javascript">
            //<![CDATA[
            L.marker(' . $finish . ', {icon: finishIcon}).addTo(map);
            //]]>
            </script>
            ';
        }

        return $ret;
    }

    public function admin_enqueue_scripts() {

        // Farbtastic for colour picker.
        wp_enqueue_style('farbtastic');
        wp_enqueue_script('farbtastic');
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
        add_settings_field('width', 'Map width (in pixels)', array($this, 'width_input'), 'gpxpress', 'general');
        add_settings_field('height', 'Map height (in pixels)', array($this, 'height_input'), 'gpxpress', 'general');
        add_settings_field('showstart', 'Show start marker', array($this, 'showstart_input'), 'gpxpress', 'general');
        add_settings_field('showfinish', 'Show finish marker', array($this, 'showfinish_input'), 'gpxpress', 'general');
    }

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
     */
    public function path_colour_input() {
    	echo "<input id='path_colour' name='gpxpress_options[path_colour]' type='text' value='{$this->options['path_colour']}' />";
        echo "<div id='pathcolourpicker'></div>";
         echo '
            <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function() {
                jQuery("#pathcolourpicker").hide();
                jQuery("#pathcolourpicker").farbtastic("#path_colour");
                jQuery("#path_colour").click(function(){jQuery("#pathcolourpicker").slideToggle()});
            });
            //]]>
            </script>';
    }

    public function width_input() {
        echo "<input id='width' name='gpxpress_options[width]' type='text' value='{$this->options['width']}' />";
    }

    public function height_input() {
        echo "<input id='height' name='gpxpress_options[height]' type='text' value='{$this->options['height']}' />";
    }

    public function showstart_input() {
        echo "<input id='showstart' name='gpxpress_options[showstart]' type='checkbox' value='true' " . checked(true, $this->options['showstart'], false) . "/>";
    }

    public function showfinish_input() {
        echo "<input id='showfinish' name='gpxpress_options[showfinish]' type='checkbox' value='true' " . checked(true, $this->options['showfinish'], false) . "/>";
    }

    public function validate_options($input) {

        // TODO: Do we need to list all options here or only those that we want to validate?

        // Validate path colour
        $this->options['path_colour'] = $input['path_colour'];

        // Validate width and height
        if ($input['width'] < 0) {
            $this->options['width'] = 0;
        } else {
            $this->options['width'] = $input['width'];
        }

        if ($input['height'] < 0) {
            $this->options['height'] = 0;
        } else {
            $this->options['height'] = $input['height'];
        }

        // If the checkbox has not been checked, we void it
        if (!isset($input['showstart'])) {
            $input['showstart'] = null;
        }
        // We verify if the input is a boolean value
        $this->options['showstart'] = ($input['showstart'] == true ? true : false);

        if (!isset($input['showfinish'])) {
            $input['showfinish'] = null;
        }
        $this->options['showfinish'] = ($input['showfinish'] == true ? true : false);

        return $this->options;
    }
}


