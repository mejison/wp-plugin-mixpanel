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

define("MP_PROJECT_ID", 2893655);
define("MP_EVENT", "Visit");
define("MP_TOKEN", "85d1055d88b102a7ce9f9615f68257bd");
define("MP_USERNAME", "php.d18d76.mp-service-account");
define("MP_PASSWORD", "9iQRtcHUhGwUuEukwpn1X1HmdevcsR8p");

add_filter( 'the_content', 'filter_the_content_in_the_main_loop', 1 );
function filter_the_content_in_the_main_loop( $content ) {

    if (is_singular() && in_the_loop() && is_main_query() && ! is_admin()) {
        $post = get_post();
        $location = getusercountrycode("191.101.203.233"); // by deafult 

        $mp = Mixpanel::getInstance(MP_TOKEN, ["debug" => true]);
        $mp->track("Visit", [
            "post_id" => $post->ID,
            "post_author_id" => $post->post_author,
            "post_name" => $post->post_name,
            
            "city" => $location['geoplugin_city'] ?? "",
            "region" => $location['geoplugin_region'] ?? "",
            "country_code" => $location['geoplugin_countryCode'] ?? "",
            "country_name" => $location['geoplugin_countryName'] ?? "",
            "referer" => $_SERVER['HTTP_REFERER'],
        ]); 
    }
    return $content;
}

function getusercountrycode($ip) {
    $ip = ! empty($ip) ? $ip : $_SERVER['REMOTE_ADDR'];
    $ch = curl_init();
    $curlConfig = [
        CURLOPT_URL            => "http://www.geoplugin.net/json.gp?ip=" . $ip,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => false
    ];
    curl_setopt_array($ch, $curlConfig);
    $result = curl_exec($ch);
    curl_close($ch);
    $json_a=json_decode($result, true);
    return $json_a;
}

function getViewsFromMixPanel($post_id) {
    $firstDayOfYear = date('Y-m-d', strtotime('first day of january this year'));
    $lastDyaOfYear = date('Y-m-d', strtotime('last day of December this year'));
    $data = [
        'project_id' => MP_PROJECT_ID,
        'event' => MP_EVENT,
        'from_date' => $firstDayOfYear,
        'to_date' => $lastDyaOfYear,
        'on' => '1',
        'where' => 'properties["post_id"] == ' . $post_id,
    ];

    $query = http_build_query($data);
    $ch = curl_init();
    $curlConfig = [
        CURLOPT_URL            => "https://mixpanel.com/api/2.0/segmentation/sum?" . $query,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . MP_USERNAME . ':' . MP_PASSWORD
        ]
    ];
    curl_setopt_array($ch, $curlConfig);
    $result = curl_exec($ch);
    curl_close($ch);
    $json_a=json_decode($result, true);
    return $json_a;
}

function wpufe_dashboard_change_head( $args ) {
   printf( '<th  width="150">%s</th>', __( 'Results', 'wpuf' ) );
   printf( '<th  width="150">%s</th>', __( '', 'wpuf' ) );
}
add_action( 'wpuf_account_posts_head_col', 'wpufe_dashboard_change_head', 10, 2 );


/** Add a new table cell to the dashboard table rows.
* It adds a form for changing the post status of each post via ajax call.
* @param array $args dashboard query arguments
* @param object $post current rows post object
* @return void
* @output of the below code will be like https://prnt.sc/tapb0o
*/


function wpufe_dashboard_row_col( $args, $post ) {
    $mixPanelStats = getViewsFromMixPanel($post->ID);
    $views = 0;
    if (isset($mixPanelStats['results'])) {
        $views = array_sum(array_values($mixPanelStats['results']));
    }
    ?>
<td>
    <?php
        echo number_format($views) . ' Views';
    ?>
</td>
<td>
    <span onClick='vueInstance.openReport(<?php echo json_encode(['post' => [
            'post_id' => $post->ID, 
            'post_author' => $post->post_author, 
            'post_title' => $post->post_title
        ],
        'stats' => $mixPanelStats,
        ]); ?>)' style="color: blue !important; cursor:pointer;">See Report</span>
</td>
<?php
}
add_action( 'wpuf_account_posts_row_col', 'wpufe_dashboard_row_col', 10, 2 );

function wpuf_account_show_report_dialog($args, $post) {
    require( plugin_dir_path( __FILE__ ) . "/report.php");
}

add_action( 'wpuf_account_show_report', 'wpuf_account_show_report_dialog', 10, 2);


function topByProp($prop_name, $post_id) {
    $firstDayOfYear = date('Y-m-d', strtotime('first day of january this year'));
    $today = date('Y-m-d', strtotime('today'));

    $data = [
        'project_id' => MP_PROJECT_ID,
        'event' => json_encode([MP_EVENT]),
        'from_date' => $firstDayOfYear,
        'to_date' => $today,
        'where' => 'properties["post_id"] == ' . $post_id,
    ];

    $query = http_build_query($data);
    $ch = curl_init();
    $curlConfig = [
        CURLOPT_URL            => "https://data.mixpanel.com/api/2.0/export?" . $query,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . MP_USERNAME . ':' . MP_PASSWORD
        ]
    ];
    curl_setopt_array($ch, $curlConfig);
    $result = curl_exec($ch);
    curl_close($ch);
    $items = explode("\n", $result);
    $items = array_map('json_decode', $items);

    $data = [];
    foreach($items as $row) {
        if ( ! empty($row->properties->{$prop_name})) {
            if (empty($data[$row->properties->{$prop_name}])) {
                $data[$row->properties->{$prop_name}] = 0;
            }
    
            $data[$row->properties->{$prop_name}] ++;
        }
    }
    return $data;
}

function at_rest_visit_endpoint($prop_name) {
    $data = [
        'sites' => [],
        'countries' => []
    ];

    $post_id = $_GET['post_id'] ?? false;

    if ( ! $post_id) {
        return new WP_REST_Response(['message' => 'Post Id required!']);   
    }

    $data['countries'] = topByProp("country_name", $post_id);
    $data['sites'] = topByProp("referer", $post_id);
    
    return new WP_REST_Response($data);
}

function at_rest_init() {
    // route url: domain.com/wp-json/$namespace/$route
    $namespace = 'mixpanel/v1';
    $routeTypes = [
        'visit',
    ];

    foreach($routeTypes as $type) {
        register_rest_route($namespace, $type, array(
            'methods'   => WP_REST_Server::READABLE,
            'callback'  => 'at_rest_' . $type . '_endpoint'
        ));
    }
}

add_action('rest_api_init', 'at_rest_init');

// https://data.mixpanel.com/api/2.0/export?project_id=2893655&event=["Visit"]&from_date=2023-01-01&to_date=2023-01-25&where=properties["post_id"] == 22