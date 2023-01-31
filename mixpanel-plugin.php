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

    if (is_single() && ! is_admin()) {

        $post = get_post();
        if ( ! empty($post)) {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            $location = getusercountrycode($ip); // by deafult 

            $mp = Mixpanel::getInstance(MP_TOKEN, ["debug" => true]);
            $mp->track("Visit", [
                "post_id" => $post->ID,
                "post_author_id" => $post->post_author,
                "post_name" => $post->post_name,
                
                "city" => $location['geoplugin_city'] ?? "",
                "region" => $location['geoplugin_region'] ?? "",
                "country_code" => $location['geoplugin_countryCode'] ?? "",
                "country_name" => $location['geoplugin_countryName'] ?? "",
                "referer" => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            ]); 
        }
        
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
    $today = date('Y-m-d', strtotime('today'));
    if (empty($_SESSION['visitors'])) {
        $data = [
            'project_id' => MP_PROJECT_ID,
            'event' => MP_EVENT,
            'name' => 'post_id',
            'type' => 'general',
            'unit' => 'day',
            'from_date' => $firstDayOfYear,
            'to_date' => $today,
        ];
    
        $query = http_build_query($data);
        $ch = curl_init();
        $curlConfig = [
            CURLOPT_URL            => "https://mixpanel.com/api/2.0/events/properties?" . $query,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . MP_USERNAME . ':' . MP_PASSWORD
            ]
        ];
        curl_setopt_array($ch, $curlConfig);
        $result = curl_exec($ch);
        curl_close($ch);
        $json=json_decode($result, true);
    
        if ( ! empty($json['data']['values'])) {
            $rows = array_map('array_sum', $json['data']['values']);
            $_SESSION['visitors'] = $rows;
            return $rows[$post_id];
        }
    } else {
        if (isset($_SESSION['visitors'][$post_id])) {
            return $_SESSION['visitors'][$post_id];
        }
    }
    
    return 0;
}

function wpufe_dashboard_change_head( $args ) {
    printf( '<th  width="150">%s</th>', __( 'Date', 'wpuf' ) );
    printf( '<th  width="200">%s</th>', __( 'Results', 'wpuf' ) );
    printf( '<th>%s</th>', __( '', 'wpuf' ) );
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
    $views = 0;
    $views = getViewsFromMixPanel($post->ID);
    $mixPanelStats = isset($_SESSION['visitors']) ? $_SESSION['visitors'] : [];
    if ($views) {
        ?>
<td>
    <?php
        echo date("m/d/y", time());
    ?>
</td>
<td>
    <?php
                echo number_format($views) . ' Views';
            ?>
    <span onClick='vueInstance.openReport(<?php echo json_encode(['post' => [
            'post_id' => $post->ID, 
            'post_author' => $post->post_author, 
            'post_title' => $post->post_title,
            'post_date' => $post->post_date,
        ],
        'stats' => $mixPanelStats,
        ]); ?>)' style="color: blue !important; cursor:pointer; white-space: nowrap; margin-left: 30px">Traffic
        Report</span>
</td>
<td>
    <a href="https://realtywire.com/results-report"
        style="color: blue !important; cursor:pointer; white-space: nowrap; margin-left: 30px" target="blank">Clipping
        Report</a>
</td>
<?php
    } else {
        ?>
<td>
    <?php
                    echo "0 Views";
                ?>
</td>
<?php
    }
    ?>
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

function aggregateEventCounts($post_id) {
    $firstDayOfThisMonth = date('Y-m-d', strtotime('first day of this month'));
    $today = date('Y-m-d', strtotime('today'));

    $data = [
        'project_id' => MP_PROJECT_ID,
        'event' => MP_EVENT,
        'unit' => 'day',
        'on' => 1,
        'from_date' => $firstDayOfThisMonth,
        'to_date' => $today,
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
    return json_decode($result, true);
}

function at_rest_visit_endpoint($prop_name) {
    $post_id = $_GET['post_id'] ?? false;
    if ( ! $post_id) {
        return new WP_REST_Response(['message' => 'Post Id required!']);   
    }
    
   // $siteNames = ['4 Release', 'Arboretum Realty', 'Arbor Realty', 'Axio Press', 'Azure Realty', 'Bayfront Homes', 'Bayfront Properties', 'Bayfront Realty', 'Bell Real Estate', 'Cascade Realty', 'Collier Real Estate', 'Convergence Press', 'Florida Newswire', 'Golf Real Estate', 'Go Realty', 'Island Real Estate', 'Luxury Real Estate', 'News Feed', 'Newswire for Homes', 'Oceanfront Real Estate', 'Oceanfront Realty', 'Press Release 360', 'Press Release Spin', 'Real Estate Buzz', 'Real Estate PR', 'Real Estate Realtor', 'Real Estate Retriever', 'Realty Group', 'Realty Hub', 'Realty Logic', 'Realty News', 'retrenz.com', 'River Homes', 'Targeted Pressrelease Distribution', 'Top Listings', 'Treviso Properties', 'Tropical Realty', 'US Realty', 'Vacation Real Estate', 'viral wire', 'Viz Release', 'Z Press Release'];
    $siteUrls = ['https://4release.com/', 'http://arboretumrealty.com', 'http://arbor-realty.com', 'http://axiopress.com', 'http://azure-realty.com', 'http://bayfront-homes.com', 'http://bayfront-properties.com/', 'http://bayfront-realty.com', 'http://bell-real-estate.com', 'https://cascade-realty.com/', 'http://collier-real-estate.com', 'http://convergencepress.com', 'https://flnewswire.com', 'http://golf-real-estate.com', 'https://gorealty.homes/', 'http://island-real-estate.com', 'https://luxuryrealestate.news', 'https://newsfeed.homes', 'https://newswire.homes', 'http://oceanfront-real-estate.com', 'http://oceanfront-realty.com', 'http://pressrelease360.com', 'http://pressreleasespin.com', 'http://real-estate.buzz', 'https://realestatepr.biz', 'http://real-estate-realtor.com', 'http://real-estate-retriever.com', 'https://realtygroup.homes', 'https://realtyhub.homes', 'http://realty-logic.com', 'https://realtynews.biz', 'https://retrenz.com', 'http://river-homes.com', 'http://targetedpressreleasedistribution.com', 'https://toplistings.homes', 'https://treviso-properties.com/', 'http://tropical-realty.com', 'https://usrealty.homes', 'http://vacation-real-estate.com', 'https://viral-wire.com', 'https://vizrelease.com/category/real-estate-press-release/', 'http://zpressrelease.com'];
    $sitesStats = topByProp("referer", $post_id);
    $keys = array_keys($sitesStats);    
    

    $sites = [];
    foreach($siteUrls as $url) {
        $parse = parse_url($url);
        $host = ! empty($parse['host']) ? $parse['host'] : false;
        if ($host) {
            // echo $host . " ";
            $positionIndex = (int) preg_grep('/' . $host . '/i', $keys);
            $sites[$url] = isset($sitesStats[$positionIndex]) ? $sitesStats[$positionIndex] : 0;
        }
        // die;
        $sites[$url] = 0;
    }

    $data = [
        'sites' => [],
        'countries' => []
    ];


    $data['countries'] = topByProp("country_name", $post_id);
    $data['sites'] = $sites; //topByProp("referer", $post_id);
    $data['graph'] = aggregateEventCounts($post_id);
    
    return new WP_REST_Response($data);
}

function at_rest_init() {
    // route url: domain.com/wp-json/$namespace/$route
    ini_set('max_execution_time', -1);
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