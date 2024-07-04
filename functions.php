<?php
//CALLS TOTAL'S DEFAULT CSS
function total_child_enqueue_parent_theme_style() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
}
add_action( 'wp_enqueue_scripts', 'total_child_enqueue_parent_theme_style' );

// CUSTOM FUNCTION TO ADD TEXT BELOW LOGO
function custom_header_text() { ?>
    <div class="logo-title clr">front-end developer</div>
<?php }
add_action( 'wpex_hook_site_logo_inner', 'custom_header_text', 20 );

// ScrollTo JS FADES OUT TEXT AT TOP OF BROWSER
function sk_enqueue_scripts() {
    wp_enqueue_script( 'scrollTo', get_stylesheet_directory_uri() . '/js/jquery.scrollTo.min.js', array( 'jquery' ), '1.4.5-beta', true );
    wp_enqueue_script( 'home', get_stylesheet_directory_uri() . '/js/home.js', array( 'scrollTo' ), '', true );
}
add_action( 'wp_enqueue_scripts', 'sk_enqueue_scripts' );

// REMOVE QUERY STRINGS - excluding Google Fonts
function wpex_remove_script_version( $src ) {
    if ( strpos( $src, 'ver=' ) ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'script_loader_src', 'wpex_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', 'wpex_remove_script_version', 15, 1 );


// Visit https://resume.chrisbrosky.com/#work-experience to see these duration calculators in action
// CURRENT YEAR SHORTCODE
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

    // Format the results based on conditions
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

?>
