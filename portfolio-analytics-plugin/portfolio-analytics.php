<?php
/*
Plugin Name: Portfolio Analytics API
Description: Exposes /wp-json/portfolio-analytics/v1/stats with three shortcodes: 
  [realtime_stats], [historical_stats], [user_location_map].
Version:     1.22
Author:      Chris Brosky
*/

if ( ! defined( 'ABSPATH' ) ) exit;
require_once __DIR__ . '/vendor/autoload.php';

// 1) Register REST endpoint
add_action( 'rest_api_init', function(){
    register_rest_route( 'portfolio-analytics/v1', '/stats', [
        'methods'             => 'GET',
        'callback'            => 'pa_get_stats',
        'permission_callback' => '__return_true',
    ]);
});

// 2) Register shortcodes
add_shortcode( 'realtime_stats',    'pa_realtime_widget' );
add_shortcode( 'historical_stats',  'pa_historical_widget' );
add_shortcode( 'user_location_map', 'pa_user_location_map_shortcode' );


// 3) Enqueue frontend styles for all our widgets.
function pa_enqueue_frontend_styles() {
    wp_enqueue_style(
        'portfolio-analytics',
        plugin_dir_url(__FILE__) . 'css/portfolio-analytics.css',
        [],
        '1.0'
    );
}
add_action( 'wp_enqueue_scripts', 'pa_enqueue_frontend_styles' );

/**
 * Fetches realtime, deviceHistoric, new vs returning & historic (continent/country/city).
 */
function pa_get_stats( WP_REST_Request $req ) {
    $key = plugin_dir_path(__FILE__).'analytics-key.json';
    if ( ! file_exists($key) ) {
        return new WP_Error('no_key','Missing analytics-key.json',['status'=>500]);
    }
    $client = new Google_Client();
    $client->setAuthConfig($key);
    $client->addScope('https://www.googleapis.com/auth/analytics.readonly');
    $svc    = new Google_Service_AnalyticsData($client);
    $prop   = 'properties/486187994';

    try {
        // Realtime (5 min intervals)
        $rt = new Google_Service_AnalyticsData_RunRealtimeReportRequest();
        $rt->setMetrics([ new Google_Service_AnalyticsData_Metric(['name'=>'activeUsers']) ]);
        $rtr = $svc->properties->runRealtimeReport($prop,$rt);
        $rows = $rtr->getRows() ?: [];
        $realtime = $rows
            ? intval($rows[0]->getMetricValues()[0]->getValue())
            : 0;

        // Device History (7-day)
        $devReq = new Google_Service_AnalyticsData_RunReportRequest();
        $devReq->setDateRanges([ new Google_Service_AnalyticsData_DateRange([
            'startDate'=>'7daysAgo','endDate'=>'today'
        ])]);
        $devReq->setDimensions([ new Google_Service_AnalyticsData_Dimension(['name'=>'deviceCategory']) ]);
        $devReq->setMetrics([ new Google_Service_AnalyticsData_Metric(['name'=>'activeUsers']) ]);
        $devResp = $svc->properties->runReport($prop,$devReq);
        $deviceHistoric=[]; $total=0;
        foreach( $devResp->getRows()?:[] as $r ){
            $d = ucfirst($r->getDimensionValues()[0]->getValue());
            $u = intval($r->getMetricValues()[0]->getValue());
            $deviceHistoric[] = ['device'=>$d,'users'=>$u];
            $total += $u;
        }
        
        // 7-day Browser breakdown
        $brReq = new Google_Service_AnalyticsData_RunReportRequest();
        $brReq->setDateRanges([ new Google_Service_AnalyticsData_DateRange([
            'startDate'=>'7daysAgo','endDate'=>'today'
        ])]);
        $brReq->setDimensions([ new Google_Service_AnalyticsData_Dimension([
            'name'=>'browser'
        ])]);
        $brReq->setMetrics([ new Google_Service_AnalyticsData_Metric([
            'name'=>'activeUsers'
        ])]);
        $brResp = $svc->properties->runReport($prop, $brReq);
        $browserHistoric = [];
        foreach( $brResp->getRows()?:[] as $r ){
            $name  = $r->getDimensionValues()[0]->getValue();
            $count = intval($r->getMetricValues()[0]->getValue());
            $browserHistoric[] = [ 'browser'=>$name, 'users'=>$count ];
        }        
        

        // New vs Returning (7-day)
        $nrReq = new Google_Service_AnalyticsData_RunReportRequest();
        $nrReq->setDateRanges([ new Google_Service_AnalyticsData_DateRange([
            'startDate'=>'7daysAgo','endDate'=>'today'
        ])]);
        $nrReq->setDimensions([ new Google_Service_AnalyticsData_Dimension(['name'=>'newVsReturning']) ]);
        $nrReq->setMetrics([ new Google_Service_AnalyticsData_Metric(['name'=>'activeUsers']) ]);
        $nrResp = $svc->properties->runReport($prop,$nrReq);
        $newReturning = [];
        foreach( $nrResp->getRows()?:[] as $r ){
            $label = $r->getDimensionValues()[0]->getValue();   // "new" or "returning"
            $count = intval($r->getMetricValues()[0]->getValue());
            $newReturning[$label] = $count;
        }
        
        
        // 7-day Channel grouping (Organic vs Direct)
        $chReq = new Google_Service_AnalyticsData_RunReportRequest();
        $chReq->setDateRanges([ new Google_Service_AnalyticsData_DateRange([
            'startDate'=>'7daysAgo','endDate'=>'today'
        ])]);
        $chReq->setDimensions([ new Google_Service_AnalyticsData_Dimension([
            'name'=>'sessionDefaultChannelGrouping'
        ])]);
        $chReq->setMetrics([ new Google_Service_AnalyticsData_Metric([
            'name'=>'activeUsers'
        ])]);
        $chResp = $svc->properties->runReport($prop, $chReq);
        $channelHistoric = [];
        foreach( $chResp->getRows()?:[] as $r ){
            $chan  = $r->getDimensionValues()[0]->getValue();
            $cnt   = intval($r->getMetricValues()[0]->getValue());
            $channelHistoric[] = [ 'channel'=>$chan, 'users'=>$cnt ];
        }        
        
        
        // Historic table (7-day by continent/country/city)
        $histReq = new Google_Service_AnalyticsData_RunReportRequest();
        $histReq->setDateRanges([ new Google_Service_AnalyticsData_DateRange([
            'startDate'=>'7daysAgo','endDate'=>'today'
        ])]);
        $histReq->setDimensions([
            new Google_Service_AnalyticsData_Dimension(['name'=>'continent']),
            new Google_Service_AnalyticsData_Dimension(['name'=>'country']),
            new Google_Service_AnalyticsData_Dimension(['name'=>'region']),
            new Google_Service_AnalyticsData_Dimension(['name'=>'city']),
        ]);
        $histReq->setMetrics([ new Google_Service_AnalyticsData_Metric(['name'=>'activeUsers']) ]);
        $histResp = $svc->properties->runReport($prop,$histReq);
        $historic = [];
        foreach( $histResp->getRows()?:[] as $r ){
            $dv = $r->getDimensionValues();
            $mv = $r->getMetricValues();
            $historic[] = [
                'continent'=> $dv[0]->getValue(),
                'country'  => $dv[1]->getValue(),
                'city'     => $dv[2]->getValue(),
                'region'   => $dv[3]->getValue(), 
                'users'    => intval($mv[0]->getValue()),
            ];
        }

        return rest_ensure_response([
            'realtimeUsers'     => $realtime,
            'deviceHistoric'    => $deviceHistoric,
            'browserHistoric'   => $browserHistoric,
            'channelHistoric'   => $channelHistoric,
            'totalUsers'        => $total,
            'newReturning'      => $newReturning,
            'historic'          => $historic,
        ]);

    } catch(Exception $e) {
        return new WP_Error('api_error',$e->getMessage(),['status'=>500]);
    }
}


/**
 * Shortcode: [realtime_stats]
 * Displays only the realtime Users count.
 */
function pa_realtime_widget(){
    $url = esc_url(rest_url('portfolio-analytics/v1/stats'));
    ob_start(); ?>
    <div class="pa-users-wrapper">
        <div class="pa-realtime-standalone pa-section">
            <div class="data-header">
                <h3>Current Users</h3>
            </div>
          <div id="pa-realtime-count">…</div>
        </div>
        
        <div class="locale-consent">
            <span>Browser location services must be enabled to properly display the nearby Google map.</span>
        </div>
        
        <div class="reset-btn">
          <button id="pa-realtime-refresh" class="theme-button" style="margin-top:1rem;">
            Refresh
          </button>
        </div>
    </div>
    
    <script>
    (function(){
      var url = '<?php echo $url; ?>',
          el  = document.getElementById('pa-realtime-count'),
          btn = document.getElementById('pa-realtime-refresh');
    
      // fetch activeUsers AND then (re)draw the map
      function updateCountAndMap(){
        fetch(url, { cache: 'no-store' })
          .then(function(r){ return r.json(); })
          .then(function(d){
            el.textContent = d.realtimeUsers;
          })
          .catch(function(){
            el.textContent = '–';
          })
          .finally(function(){
            // only if the map init function exists, redraw it
            if ( typeof window.initPaUserMap === 'function' ) {
              var mapEl = document.getElementById('pa-user-map');
              if ( mapEl ) {
                mapEl.innerHTML = '';      // clear old map DOM
                window.initPaUserMap();    // re-invoke Google Maps callback
              }
            }
          });
      }
    
      // wire up Refresh button to fetch count + redraw map
      btn.addEventListener('click', updateCountAndMap);
    
      // on initial page load: fetch count + draw map once
      updateCountAndMap();
    })();
    </script>
    
    <?php
    return ob_get_clean();
}


/**
 * Shortcode: [historical_stats]
 * Displays the 7-day device %, new vs returning, table & refresh button.
 */
function pa_historical_widget(){
    $url = esc_url(rest_url('portfolio-analytics/v1/stats'));
    ob_start(); ?>
    <h2 class="pa-hist-heading">Historical Data (Past 7 Days)</h2>

    <div class="pa-data-wrapper">
        <div class="pa-section new-returning">
            <div class="data-header">
                <h3>Users</h3>
            </div>
            <div id="pa-hist-new-returning" class="pa-new-returning-data stats-block">Loading…</div>
        </div>
        
        <div class="pa-section device-data">
            <div class="data-header">
                <h3>Device</h3>
            </div>            
            <div id="pa-historical-device-text" class="pa-device-data stats-block">Loading…</div>
        </div>
        
        <div class="pa-section browser-text">
            <div class="data-header">
                <h3>Browser</h3>
            </div>           
            <div id="pa-historical-browser-text" class="pa-browser-text stats-block">Loading…</div>
        </div>
        
        <div class="pa-section channel-text">
            <div class="data-header">
                <h3>Source</h3>
            </div>            
            <div id="pa-historical-channel-text" class="pa-channel-text stats-block">Loading…</div>
        </div>        
        
    </div>

    <div class="pa-table-container">
      <table class="pa-table">
        <thead>
            <tr>
                <th>Continent</th>
                <th>Country</th>
                <th>City</th>
                <th>State</th> 
                <th>Users</th>
            </tr>
        </thead>
        <tbody id="pa-historical-hist"></tbody>
      </table>
    </div>

    <script>
    (function(){
      var url   = '<?php echo $url; ?>',
        devEl = document.getElementById('pa-historical-device-text'),
        nrEl  = document.getElementById('pa-hist-new-returning'),
        brEl  = document.getElementById('pa-historical-browser-text'),
        chEl  = document.getElementById('pa-historical-channel-text'),          
        tblEl = document.getElementById('pa-historical-hist');

        function render(d){
          // Device %
          devEl.innerHTML = d.deviceHistoric.map(function(x){
            return '<div><span class="analytics-bold-text">'+x.device+' </span> '
                 + (x.users/d.totalUsers*100).toFixed(1) + '%</div>';
          }).join('');
        
          // New vs Returning
          nrEl.innerHTML = Object.entries(d.newReturning)
            .filter(function(kv){
              return kv[0] === 'new' || kv[0] === 'returning';
            })
            .map(function(kv){
              var labelText = kv[0] === 'new' ? 'New:' : 'Returning:';
              var pct       = ((kv[1] / d.totalUsers) * 100).toFixed(1) + '%';
              return '<div>'
                   +   '<span class="analytics-bold-text">' + labelText + '</span> '
                   +   pct
                   + '</div>';
            })
            .join('');
            
            var brEl = document.getElementById('pa-historical-browser-text');
            if(brEl && d.browserHistoric){
                brEl.innerHTML = d.browserHistoric.map(function(b){
                  var pct = ((b.users/d.totalUsers)*100).toFixed(1)+'%';
                  return '<div><span class="analytics-bold-text">'+ b.browser +':</span> '+pct+'</div>';
                }).join('');
            }       
            
            // Channel Source
            var chEl = document.getElementById('pa-historical-channel-text');
            if (chEl && d.channelHistoric) {
                chEl.innerHTML = d.channelHistoric.map(function(x){
                  var pct = ((x.users/d.totalUsers)*100).toFixed(1) + '%';
                  return '<div><span class="analytics-bold-text">'+ x.channel + ':</span> ' + pct + '</div>';
                }).join('');
            }            
            

          // Table
          tblEl.innerHTML = '';
          d.historic.slice(0,10).forEach(function(r){
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>'+r.continent+'</td>'
                         + '<td>'+r.country  +'</td>'
                         + '<td>'+r.region   +'</td>'
                         + '<td>'+r.city     +'</td>'
                         + '<td>'+r.users    +'</td>';
            tblEl.appendChild(tr);
          });
        }

        // Initial load
        function updateData(){
        devEl.innerHTML = '<div>Loading…</div>';
        nrEl.innerHTML  = '<div>Loading…</div>';
        tblEl.innerHTML = '<tr><td colspan="4">Loading…</td></tr>';
        fetch(url).then(r=>r.json()).then(render)
          .catch(function(){ tblEl.innerHTML='<tr><td colspan="4">Error loading data</td></tr>';});
        }

      updateData();
    })();
    </script>
    <?php
    return ob_get_clean();
}


/**
 * Shortcode: [user_location_map]
 * Displays only your standalone map.
 */
function pa_user_location_map_shortcode(){
    $api_key = 'AIzaSyBFVKOdkCUZXXXXXXXX8XXXXXXXXXX';
    ob_start(); ?>
    
    <!--<div id="pa-user-map" class="pa-user-map"></div>-->
    
    <figure class="pa-map-figure">
      <div id="pa-user-map" class="pa-user-map"></div>
      <figcaption class="pa-map-caption">
        Your current location!
      </figcaption>
    </figure>    
    
    <script>
      // 1) Define the callback **on window** _before_ loading the API
      window.initPaUserMap = function(){
        var c = document.getElementById('pa-user-map');
        if (!navigator.geolocation) {
          c.innerHTML = '<p>Geolocation not supported.</p>';
          return;
        }
        navigator.geolocation.getCurrentPosition(function(pos){
          var ll  = { lat: pos.coords.latitude, lng: pos.coords.longitude },
              map = new google.maps.Map(c, { center: ll, zoom: 12 });
          new google.maps.Marker({ position: ll, map: map, title: 'You are here' });
        }, function(err){
          c.innerHTML = '<p>Error: ' + err.message + '</p>';
        });
      };
    </script>
    
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_js($api_key); ?>&callback=initPaUserMap"
  async defer></script>
    <?php
    return ob_get_clean();
}
