<?php
/**
* Plugin Name: mixpanel-plugin
* Plugin URI: https://www.your-site.com/
* Description: mixpanel-plugin.
* Version: 0.1
* Author: mejison
* Author URI: https://www.your-site.com/
**/
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

include( plugin_dir_path( __FILE__ ) . "/vendor/autoload.php");

define("MIXPANEL_TOKEN", "85d1055d88b102a7ce9f9615f68257bd");

add_filter( 'the_content', 'filter_the_content_in_the_main_loop', 1 );
function filter_the_content_in_the_main_loop( $content ) {

    if (is_singular() && in_the_loop() && is_main_query()) {
        $post = get_post();
        $location = getusercountrycode("191.101.203.233"); // by deafult 

        $mp = Mixpanel::getInstance(MIXPANEL_TOKEN, ["debug" => true]);
        $mp->track("Visit", [
            "post_id" => $post->ID,
            "post_author_id" => $post->post_author,
            "post_name" => $post->post_name,
            
            "city" => $location['geoplugin_city'] ?? "",
            "region" => $location['geoplugin_region'] ?? "",
            "country_code" => $location['geoplugin_countryCode'] ?? "",
            "country_name" => $location['geoplugin_countryName'] ?? "",
        ]); 
    }
    return $content;
}

function getusercountrycode($ip) {
    $ip = ! empty($ip) ? $ip : $_SERVER['REMOTE_ADDR'];
    $ch = curl_init();
    $curlConfig = array(
        CURLOPT_URL            => "http://www.geoplugin.net/json.gp?ip=" . $ip,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => false
    );
    curl_setopt_array($ch, $curlConfig);
    $result = curl_exec($ch);
    curl_close($ch);
    $json_a=json_decode($result, true);
    return $json_a;
}