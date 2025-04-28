<?php
//CALLS THEME'S DEFAULT CSS
function total_child_enqueue_parent_theme_style() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
}
add_action( 'wp_enqueue_scripts', 'total_child_enqueue_parent_theme_style' );

// ENQUEUE CUSTOM SCRIPTS -- custom.js //
function enqueue_custom_scripts() {
    // Ensure jQuery is loaded first
    wp_enqueue_script('jquery');

    // Enqueue custom script is loaded that it loads after jQuery
    wp_enqueue_script('custom-js', get_stylesheet_directory_uri() . '/js/custom.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

// CUSTOM FUNCTION TO ADD TEXT BELOW LOGO
function custom_header_text() { ?>
    <div class="logo-title clr">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Front-end Developer</a>
    </div>
<?php }
add_action( 'wpex_hook_site_logo_inner', 'custom_header_text', 20 );


function custom_breadcrumb_home_url( $home_url ) {
    return '/gnome-shop-demo/';  // Gnome Shop Home
}
add_filter( 'woocommerce_breadcrumb_home_url', 'custom_breadcrumb_home_url' );


// Customize WooCommerce breadcrumbs: home text, remove categories, and change separator
function custom_woocommerce_breadcrumbs_setup( $defaults ) {
    $defaults['delimiter'] = ' &raquo; '; // Custom separator >>
    return $defaults;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'custom_woocommerce_breadcrumbs_setup' );

// Remove category link in Woocommerce breadcrumbs
function custom_woocommerce_breadcrumbs( $crumbs, $breadcrumb ) {
    // Change 'Home' text
    if ( isset( $crumbs[0][0] ) ) {
        $crumbs[0][0] = 'Gnome Shop Home';
    }

    // Remove product category links
    foreach ( $crumbs as $key => $crumb ) {
        if ( isset( $crumb[1] ) && strpos( $crumb[1], 'product-category' ) !== false ) {
            unset( $crumbs[ $key ] );
        }
    }

    return array_values( $crumbs ); // Re-index the array
}
add_filter( 'woocommerce_get_breadcrumb', 'custom_woocommerce_breadcrumbs', 20, 2 );

// Adds .btn-secondary class to Woocommerce checkout buttons
function custom_add_secondary_class_script() {
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.thwmscf-buttons .button' ).forEach(function(btn) {
                    btn.classList.add('btn-secondary');
                });
            });
        </script>
        <?php
    }
}
add_action( 'wp_footer', 'custom_add_secondary_class_script', 100 );


// REMOVES WOOCOMMERCE SHOPPING CART FROM DEFAULT NAV MENU
function remove_woo_menu_icon_for_specific_page($items, $args) {
    if (is_singular() && get_the_ID() == 9 && $args->theme_location == 'main_menu') {
        // Remove <li> with class "woo-menu-icon"
        $items = preg_replace('/<li[^>]*class="[^"]*woo-menu-icon[^"]*"[^>]*>.*?<\/li>/s', '', $items);
    }
    return $items;
}
add_filter('wp_nav_menu_items', 'remove_woo_menu_icon_for_specific_page', 10, 2);


// CUSTOM FUNCTIONS TO CALCULATE TIME BETWEEN DATES //
// GET CURRENT YEAR
function current_year() {
    return date('Y');
}
add_shortcode('year', 'current_year');

// YEARS OF WEB DEV EXPERIENCE
function years_experience() {
    $startYear = 2008;
    return date('Y') - $startYear;
}
add_shortcode('experience_years', 'years_experience');

// EMPLOYMENT DURATION CALCULATOR
function calculate_date_difference($atts) {
    // Extracting shortcode attributes
    $atts = shortcode_atts(
        array(
            'start_date' => '',
            'end_date'   => '',
        ),
        $atts,
        'date_difference'
    );

    // Check if start_date is provided
    if (empty($atts['start_date'])) {
        return 'Please provide a start date.';
    }

    // Use current date if end_date is not provided
    if (empty($atts['end_date'])) {
        $atts['end_date'] = date('Y-m-d'); // Current date in 'YYYY-MM-DD'
    }

    // Convert date strings to DateTime objects
    $startDate = new DateTime($atts['start_date']);
    $endDate = new DateTime($atts['end_date']);

    // Calculate the difference
    $interval = $startDate->diff($endDate);

    // Prepare the result components
    $years = $interval->y;
    $months = $interval->m;

    // Format the result based on conditions
    $result = '';

    if ($years > 0) {
        $result .= $years . ' year' . ($years > 1 ? 's' : '');
    }

    if ($months > 0) {
        if (!empty($result)) {
            $result .= ', ';
        }
        $result .= $months . ' month' . ($months > 1 ? 's' : '');
    }

    return $result;
}
add_shortcode('date_difference', 'calculate_date_difference');

// YEARS BETWEEN DATES CALCULATOR
// Used to calculate the years of experience I have with technical skills
function calculate_year_difference($atts) {
    // Extracting shortcode attributes
    $atts = shortcode_atts(
        array(
            'year_began' => '', // Default value is an empty string
        ),
        $atts,
        'year_difference'
    );

    // Current year
    $currYear = date('Y');

    // Convert the provided year_began to an integer
    $yearBegan = intval($atts['year_began']);

    // Check if year_began is not provided, is less than or equal to 0, or is not a valid integer
    if (empty($yearBegan) || $yearBegan <= 0) {
        return 'Please provide a valid positive integer for year_began.';
    }

    // Calculate the difference
    $difference = $currYear - $yearBegan;

    return $difference;
}
add_shortcode('year_difference', 'calculate_year_difference');

function social_icons_shortcode() {
    $html = '
    <div class="profile-social-icons">
        <a href="https://www.linkedin.com/in/chrisbrosky/" class="social-icon linkedin" title="LinkedIn" target="_blank"><i class="fa-brands fa-linkedin"></i></a>
        
        <a href="https://www.instagram.com/seebrosky32" class="social-icon instagram" title="Instagram" target="_blank"><i class="fa-brands fa-square-instagram"></i></a>
        
        <a href="https://github.com/seebrosky/resume/" class="social-icon github" title="GitHub" target="_blank"><i class="fa-brands fa-square-github"></i></a>
    </div>';
    return $html;
}
add_shortcode('social_icons', 'social_icons_shortcode');


// HERO BUTTONS
function hero_buttons_shortcode() {
    ob_start();
    ?>
    <div class="hero-btn-wrapper">
        <a href="#contact" class="hero-btn theme-button contact">
            <span class="fa fa-solid fa-phone" aria-hidden="true"></span>
            <span class="hero-btn-text">Contact Me</span>
        </a>
        <a href="javascript:void(0)" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/seebrosky'}); return false;" class="hero-btn theme-button calendly">
            <span class="fa fa-regular fa-calendar-plus" aria-hidden="true"></span>
            <span class="hero-btn-text">Book a Meeting</span>
        </a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('hero_buttons', 'hero_buttons_shortcode');


// Formats Phone Numbers into 1-XXX-XXX-XXXX format
function format_phone_number($phone) {
    $digits = preg_replace('/\D/', '', $phone);

    if (strlen($digits) === 11 && $digits[0] === '1') {
        return '1-' . substr($digits, 1, 3) . '-' . substr($digits, 4, 3) . '-' . substr($digits, 7);
    }

    if (strlen($digits) === 10) {
        return '1-' . substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
    }

    return $phone;
}

// CALENDLY SCHEDULER SHORTCODE
function enqueue_calendly_script() {
    wp_enqueue_script('calendly-widget', 'https://assets.calendly.com/assets/external/widget.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_calendly_script');

// Enables Calendly Popup
function calendly_popup_shortcode() {
    return '<a href="javascript:void(0)" onclick="Calendly.initPopupWidget({url: \'https://calendly.com/seebrosky?hide_gdpr_banner=1\'}); return false;">Book a Meeting!</a>';
}
add_shortcode('calendly_popup', 'calendly_popup_shortcode');


// Tells Litespeed Cache not to cache the Flight Finder page
add_action( 'template_redirect', function() {
    if ( is_page( 7124 ) ) {
        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
    }
}, 0 );

// REMOVE QUERY STRINGS - Excluding Google Fonts
function wpex_remove_script_version( $src ) {
    if ( strpos( $src, 'ver=' ) ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'script_loader_src', 'wpex_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', 'wpex_remove_script_version', 15, 1 );

?>
