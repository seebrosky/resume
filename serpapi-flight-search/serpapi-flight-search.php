<?php
/*
Plugin Name: SerpApi Flight Search
Description: Pulls Google Flights data using SerpApi and displays it via shortcode.
Version: 2.5
Author: Chris Brosky
*/

if (!defined('ABSPATH')) exit;

function serpapi_get_cities() {
    return [
        'AKL' => 'Auckland, New Zealand (AKL)',
        'ATL' => 'Atlanta, GA (ATL)',
        'BTV' => 'Burlington, VT (BTV)',
        'TFS' => 'Canary Islands, Spain (TFS)',
        'ORD' => 'Chicago, IL (ORD)',
        'DAL' => 'Dallas, TX (DAL)',
        'DEN' => 'Denver, CO (DEN)',
        'GCM' => 'Grand Cayman (GCM)',
        'JFK' => 'New York, NY (JFK)',
        'EYW' => 'Key West, FL (EYW)',
        'LAX' => 'Los Angeles, CA (LAX)',
        'KTM' => 'Nepal, Kathmandu (KTM)',
        'MCO' => 'Orlando, FL (MCO)',
        'CDG' => 'Paris, France (CDG)',
        'PHX' => 'Phoenix, AZ (PHX)',
        'KEF' => 'Reykjavík, Iceland (KEF)',
        'SFO' => 'San Francisco, CA (SFO)',
        'SJO' => 'San Jose, Costa Rica (SJO)',
        'SEA' => 'Seattle, WA (SEA)',
        'SLC' => 'Salt Lake City, UT (SLC)',
        'GEG' => 'Spokane, WA (GEG)',
        'SYD' => 'Sydney, Australia (SYD)',
        'NRT' => 'Tokyo, Japan (NRT)',
    ];
}


// Enqueue scripts
add_action('wp_enqueue_scripts', 'serpapi_enqueue_scripts');
function serpapi_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css','https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
    
    // Plugin Stylesheet
    wp_enqueue_style(
    'serpapi-flights-style',
        plugin_dir_url(__FILE__) . 'css/serpapi-flights.css',
        [],        // no dependencies
        '1.0'
    );    
}

// Load the admin/admin‐settings.php file in the Dashboard
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/admin-settings.php';
}

// UI scripts
add_action('wp_footer','serpapi_ui_scripts',100);
function serpapi_ui_scripts(){
    ?>
    <script>
        jQuery(function($){
            // Initialize datepicker
            $('.datepicker').datepicker({ dateFormat:'yy-mm-dd' });
    
            $(document).on('click', '#clear-results', function(e){
                e.preventDefault();
    
                // 1) Remove the results container
                $('#flight-results').remove();
    
                // 2) Re-build a clean URL with no query string
                var cleanUrl = window.location.origin
                             + window.location.pathname;
    
                // 3) Update the address bar (no reload)
                if ( window.history && window.history.replaceState ) {
                    window.history.replaceState({}, document.title, cleanUrl);
                } else {
                    window.location.href = cleanUrl;
                }
            });
            
            if ( window.location.search.indexOf('search_flights') !== -1 ) {
                // Poll until the results exist
                var check = setInterval(function(){
                  var $res = $('#flight-results');
                  if ( $res.length ) {
                    clearInterval(check);
                    var targetY = $('#flight-results-title').offset().top - 130; // 140px offset
                    window.scrollTo({ top: targetY, behavior: 'smooth' });
                  }
                }, 50);
            }    
    
        });
    </script>
    <?php
}

// Removes #anchor artifacts on URL - page/#anchor-artifact
add_action( 'wp_footer', 'serpapi_remove_anchor_artifacts', 5 );
function serpapi_remove_anchor_artifacts() {
    if ( ! is_singular() ) {
        return;
    }

    global $post;
    if ( empty( $post->post_content ) ) {
        return;
    }

    // only run when either form or results shortcode is on the page
    if ( ! has_shortcode( $post->post_content, 'flight_search_form' )
      && ! has_shortcode( $post->post_content, 'flight_search_results' )
    ) {
        return;
    }
    ?>
    <script>
    (function(){
      if ( window.location.hash ) {
        var clean = window.location.protocol
                  + '//' 
                  + window.location.host 
                  + window.location.pathname 
                  + window.location.search;
        history.replaceState(null, document.title, clean);
      }
    })();
    </script>
    <?php
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                          SHORTCODE SETTINGS                              */
/* ────────────────────────────────────────────────────────────────────────── */

/**
 * FORM SHORTCODE: [flight_search_form]
 */
add_shortcode( 'flight_search_form', 'serpapi_flight_search_form_shortcode' );
function serpapi_flight_search_form_shortcode() {
    ob_start();

    // Get City/IATA codes
    $cities = serpapi_get_cities();

    // Include the form template
    $tpl = plugin_dir_path( __FILE__ ) . 'templates/flight-search-form.php';
    if ( file_exists( $tpl ) ) {
        echo '<h2>Choose Your Destination</h2>';
        include $tpl;
    } else {
        echo '<p><em>Flight search form not found.</em></p>';
    }

    return ob_get_clean();
}

/**
 * RESULTS SHORTCODE: [flight_search_results]
 */
add_shortcode( 'flight_search_results', 'serpapi_flight_search_results_shortcode' );
function serpapi_flight_search_results_shortcode() {
    // Only render if search has been submitted
    if ( ! isset( $_GET['search_flights'] ) ) {
        return '';
    }

    ob_start();

    // Get City/IATA codes
    $cities = serpapi_get_cities();

    // Gather & sanitize inputs
    $dep  = sanitize_text_field( $_GET['departure_id']  ?? '' );
    $arr  = sanitize_text_field( $_GET['arrival_id']    ?? '' );
    $out  = sanitize_text_field( $_GET['outbound_date'] ?? '' );
    $ret  = sanitize_text_field( $_GET['return_date']   ?? '' );
    $type = sanitize_text_field( $_GET['type']          ?? '1' );
    $pax  = intval( $_GET['passengers'] ?? 1 );

    // Normalize dates (DD-MM-YYYY to YYYY-MM-DD)
    if ( preg_match( '/^(\d{2})-(\d{2})-(\d{4})$/', $out, $m ) ) {
        $out = "{$m[3]}-{$m[1]}-{$m[2]}";
    }
    if ( '1' === $type && preg_match( '/^(\d{2})-(\d{2})-(\d{4})$/', $ret, $m ) ) {
        $ret = "{$m[3]}-{$m[1]}-{$m[2]}";
    }

    // Prepare API request
    $api_key = get_option( 'serpapi_flight_search_api_key', '' );
    $params = [
        'engine'        => 'google_flights',
        'departure_id'  => $dep,
        'arrival_id'    => $arr,
        'outbound_date' => $out,
        'adults'        => $pax,
        'type'          => intval( $type ),
        'api_key'       => $api_key,
        'gl'            => 'us',
        'hl'            => 'en',
        'currency'      => 'USD',
    ];

    if ( 1 === intval( $type ) ) {
        $params['return_date'] = $ret;
    }
    $params['return_booking_token'] = 'true';

    $url = 'https://serpapi.com/search.json?' . http_build_query( $params );
    $response = wp_remote_get( $url, [ 'timeout' => 15 ] );

    echo '<div id="flight-results">';
    echo '<h2 id="flight-results-title" class="flight-results-title">Flight Results</h2>';

    if ( is_wp_error( $response ) ) {
        echo '<p>Error: ' . esc_html( $response->get_error_message() ) . '</p>';
    } else {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $flights = $body['best_flights'] ?? $body['cheapest_flights'] ?? $body['flights'] ?? [];

        if ( ! empty( $flights ) ) {
            echo '<ul class="serpapi-flights">';
            foreach ( $flights as $flight ) {
                $segment = $flight['flights'][0] ?? $flight;
                $flight_no    = esc_html( $segment['flight_number'] ?? 'N/A' );
                $travel_class = esc_html( $segment['travel_class'] ?? '' );
                $airplane     = esc_html( $segment['airplane'] ?? '' );

                echo '<li>';
                // Airline
                $logo = ! empty( $segment['airline_logo'] )
                    ? '<img src="' . esc_url( $segment['airline_logo'] ) . '" alt="' . esc_attr( $segment['airline'] ) . ' logo" style="height:24px;vertical-align:middle;margin-right:6px;">'
                    : '';
                echo '<div class="airline-name">' . $logo . '<strong>' . esc_html( $segment['airline'] ?? 'Unknown Airline' ) . '</strong></div>';

                // Flight & plane
                echo '<p><strong>Flight:</strong> ' . $flight_no;
                if ( $airplane ) {
                    echo ' &mdash; ' . $airplane;
                }
                echo '</p>';

                // Class
                if ( $travel_class ) {
                    echo '<p><strong>Class:</strong> ' . $travel_class . '</p>';
                }

                // Segments + layovers
                $segments = $flight['flights'] ?? [ $flight ];
                $layovers = $flight['layovers'] ?? [];

                foreach ( $segments as $i => $seg ) {
                    // Departure
                    if ( ! empty( $seg['departure_airport']['time'] ) ) {
                        $dt = date_create( $seg['departure_airport']['time'] );
                        echo '<p><span aria-hidden="true" class="fa fa-plane-departure"></span> Departs ' . esc_html( $seg['departure_airport']['id'] ?? '' ) . ' on ' . esc_html( date_format( $dt, 'F j, Y \a\t g:ia' ) ) . '</p>';
                    }
                    // Arrival
                    if ( ! empty( $seg['arrival_airport']['time'] ) ) {
                        $dt = date_create( $seg['arrival_airport']['time'] );
                        echo '<p><span aria-hidden="true" class="fa fa-plane-arrival"></span> Arrives ' . esc_html( $seg['arrival_airport']['id'] ?? '' ) . ' on ' . esc_html( date_format( $dt, 'F j, Y \a\t g:ia' ) ) . '</p>';
                    }
                    // Layover
                    if ( isset( $segments[ $i + 1 ] ) && isset( $layovers[ $i ] ) ) {
                        $lay = $layovers[ $i ];
                        $raw_name = $lay['name'] ?? $lay['iata_code'] ?? 'N/A';
                        $city = esc_html( str_replace( 'International', "Int'l", $raw_name ) );
                        $dur = intval( $lay['duration'] ?? 0 );
                        $dur_fmt = floor( $dur/60 ) . 'h ' . sprintf( '%02dm', $dur % 60 );
                        echo '<p class="layover-label"><span>Layover</span> ' . $city . ' (' . $dur_fmt . ')</p>';
                    }
                }

                // Duration & price
                $duration = ! empty( $flight['total_duration'] ) ? floor( $flight['total_duration']/60 ) . 'h ' . sprintf('%02dm', $flight['total_duration']%60) : 'N/A';
                echo '<p><span aria-hidden="true" class="fa fa-clock"></span> Duration: ' . esc_html( $duration ) . '</p>';

                $price = is_numeric( $flight['price'] ?? null ) ? '$' . number_format((float)$flight['price'],2) : esc_html( $flight['price'] ?? 'N/A' );
                echo '<div class="flight-price"><span aria-hidden="true" class="fa fa-sack-dollar"></span> ' . $price . '</div>';

                // Total layover duration
                if ( ! empty( $flight['layover_duration'] ) ) {
                    $ld = intval( $flight['layover_duration'] );
                    echo '<p><strong>Total Layover Duration:</strong> ' . floor($ld/60) . 'h ' . sprintf('%02dm', $ld%60) . '</p>';
                }

                echo '</li>';
            }
            echo '</ul>';

            // Clear Results
            echo '<div class="flight-clear-wrapper">';
            echo '<button id="clear-results" class="theme-button" type="button">Clear Results</button>';
            echo '</div>';
        } else {
            echo '<p class="no-results">No results.</p>';
        }
    }

    echo '</div>';

    return ob_get_clean();
}