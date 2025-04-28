<?php
// admin-settings.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1) Register menu and settings
add_action( 'admin_menu', 'serpapi_add_admin_menu' );
add_action( 'admin_init', 'serpapi_settings_init' );

function serpapi_add_admin_menu() {
    add_options_page(
        'SerpApi Flights',
        'SerpApi Flights',
        'manage_options',
        'serpapi-flights',
        'serpapi_options_page'
    );
}

function serpapi_settings_init() {
    register_setting( 'serpapi_plugin', 'serpapi_api_key' );
}

// 2) Render the settings page
function serpapi_options_page() {
    $api_key = get_option( 'serpapi_api_key', '' );
    ?>
    <div class="wrap">
      <h1>SerpApi Flight Search Settings</h1>
      <?php settings_errors( 'serpapi_plugin' ); ?>

      <form method="post" action="options.php">
        <?php settings_fields( 'serpapi_plugin' ); ?>
        <table class="form-table">
          <tr>
            <th><label for="serpapi_api_key">API Key</label></th>
            <td>
              <input
                id="serpapi_api_key"
                name="serpapi_api_key"
                type="text"
                value="<?php echo esc_attr( $api_key ); ?>"
                class="regular-text"
              />
            </td>
          </tr>
        </table>
        <?php submit_button( 'Save API Key' ); ?>
      </form>

      <h2>Test Connection</h2>
      <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <?php wp_nonce_field( 'serpapi_test_connection' ); ?>
        <input type="hidden" name="action" value="serpapi_test_connection" />
        <?php submit_button( 'Test API Connection', 'primary' ); ?>
      </form>
    </div>
    <?php
}

// 3) Handle Test Connection
add_action( 'admin_post_serpapi_test_connection', 'serpapi_handle_test_connection' );
function serpapi_handle_test_connection() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    check_admin_referer( 'serpapi_test_connection' );

    $dep = 'JFK';
    $arr = 'LAX';
    $out = date( 'Y-m-d', strtotime( '+1 day' ) );
    $ret = date( 'Y-m-d', strtotime( '+2 days' ) );
    $api_key = get_option( 'serpapi_api_key', '' );

    $params = [
        'engine'       => 'google_flights',
        'departure_id' => $dep,
        'arrival_id'   => $arr,
        'outbound_date'=> $out,
        'return_date'  => $ret,
        'adults'       => 1,
        'api_key'      => $api_key,
    ];
    $response = wp_remote_get( 'https://serpapi.com/search.json?' . http_build_query( $params ), [ 'timeout' => 15 ] );

    if ( is_wp_error( $response ) ) {
        $msg  = 'Error: ' . $response->get_error_message();
        $type = 'error';
    } else {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['best_flights'] ) || ! empty( $body['cheapest_flights'] ) || ! empty( $body['flights'] ) ) {
            $msg  = 'Success! Found flights for JFK→LAX.';
            $type = 'success';
        } else {
            $msg  = 'Error. No flights returned for JFK→LAX.';
            $type = 'error';
        }
    }

    set_transient( 'serpapi_test_result', compact( 'msg', 'type' ), 30 );

    $redirect = add_query_arg(
        [ 'page' => 'serpapi-flights', 'settings-updated' => 'true' ],
        admin_url( 'options-general.php' )
    );
    wp_safe_redirect( $redirect );
    exit;
}

// 4) Display the test result
add_action( 'admin_notices', 'serpapi_test_result_notice' );
function serpapi_test_result_notice() {
    if ( ! isset( $_GET['page'] ) || 'serpapi-flights' !== $_GET['page'] ) {
        return;
    }
    $result = get_transient( 'serpapi_test_result' );
    if ( ! $result ) {
        return;
    }
    printf(
        '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
        esc_attr( $result['type'] === 'success' ? 'success' : 'error' ),
        esc_html( $result['msg'] )
    );
    delete_transient( 'serpapi_test_result' );
}
