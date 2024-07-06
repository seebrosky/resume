<?php
//CALLS THEME'S DEFAULT CSS
function total_child_enqueue_parent_theme_style() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
}
add_action( 'wp_enqueue_scripts', 'total_child_enqueue_parent_theme_style' );

// ENQUEUE CUSTOM SCRIPT FOR SCROLL TO TOP BUTTON
function theme_enqueue_scripts() {
    wp_enqueue_script( 'scroll-to-top', get_stylesheet_directory_uri() . '/js/scroll-to-top.js', array('jquery'), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_scripts' );

// CUSTOM FUNCTION TO ADD TEXT BELOW LOGO
function custom_header_text() { ?>
    <div class="logo-title clr">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Front-end Developer</a>
    </div>
<?php }
add_action( 'wpex_hook_site_logo_inner', 'custom_header_text', 20 );


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

    // Prepare the results
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
// Used to calculate the years of experience I have with hard skills
function calculate_year_difference_shortcode($atts) {
    // Extracting shortcode attributes
    $atts = shortcode_atts(
        array(
            'year_began' => '',
        ),
        $atts,
        'year_difference'
    );

    // Current year
    $currYear = date('Y');

    // Convert the provided year_began to an integer
    $yearBegan = intval($atts['year_began']);

    // Check if year_began is not provided or not a valid integer
    if (empty($yearBegan)) {
        return 'Please provide a valid value for year_began.';
    }

    // Calculate the difference
    $difference = $currYear - $yearBegan;

    return $difference;
}
add_shortcode('year_difference', 'calculate_year_difference_shortcode');

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
