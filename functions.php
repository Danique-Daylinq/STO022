<?php


/* v6.14.4 */


// Start: includes all css files in assets/css directory
function my_theme_enqueue_all_styles()
{
    $css_dir = get_stylesheet_directory() . '/assets/css/'; // Path to the CSS files
    $css_url = get_stylesheet_directory_uri() . '/assets/css/'; // URL to the CSS files

    // Check if the directory exists
    if (is_dir($css_dir)) {
        // Open the directory
        if ($handle = opendir($css_dir)) {
            // Loop through the files in the directory
            while (false !== ($file = readdir($handle))) {
                // Only include .css files
                if (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
                    // Enqueue each CSS file
                    wp_enqueue_style('my-theme-' . basename($file, '.css'), $css_url . $file);
                }
            }
            closedir($handle);
        }
    }
}

add_action('wp_enqueue_scripts', 'my_theme_enqueue_all_styles');


// Include all PHP files in the assets/php directory
function my_theme_include_all_php_scripts() {
    $php_dir = get_stylesheet_directory() . '/assets/php/';

    if (is_dir($php_dir) && $handle = opendir($php_dir)) {
        while (false !== ($file = readdir($handle))) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                include_once $php_dir . $file;
            }
        }
        closedir($handle);
    }
}
add_action('after_setup_theme', 'my_theme_include_all_php_scripts');


function google_tag_head_script() {
    ?>

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-NKZCFGBG');</script>
    <!-- End Google Tag Manager -->
    
    <?
    }
    add_action( 'wp_head', 'google_tag_head_script' );
    
    function google_tag_after_body_script() {
    ?>
            <!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NKZCFGBG"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <!-- End Google Tag Manager (noscript) -->
    <?
        }
    add_action('wp_footer', 'google_tag_after_body_script');
    

//! All new functions go below this line



// Filters the widget "Recent Posts" to only show posts with the same tags as the current post and hide current post
function filter_recent_posts_widget_query($args) {
    if (!is_single()) {
        return $args; // Only modify for single post pages
    }

    global $post;

    // Get the tags of the current post
    $current_post_tags = wp_get_post_tags($post->ID, array('fields' => 'ids'));

    // Tags to exclude
    $excluded_tags = array(57);

    if (!empty($current_post_tags)) {
        $args['tag__in'] =  array_diff($current_post_tags, $excluded_tags); // Only include posts with the same tags
        $args['post__not_in'] = array($post->ID); // Exclude the current post
    }

    return $args;
}
add_filter('widget_posts_args', 'filter_recent_posts_widget_query');



// Automatically checks the subscribe to newsletter checkbox
function mp_custom_checkbox_default_checked()
{
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select the checkbox by its name attribute (using the slug)
            var checkbox = document.querySelector("input[name='mepr_meld_mij_aan_voor_de_nieuwsbrief']");
            if (checkbox) {
                checkbox.checked = true; // Set the checkbox as checked by default
            }
        });
    </script>
<?php
}
add_action('wp_footer', 'mp_custom_checkbox_default_checked');


// Hide WP-users for non-logged in users
function restrict_rest_api_users_endpoint($endpoints)
{
    if (isset($endpoints['/wp/v2/users'])) {
        // Disable users endpoint for non-logged in users
        if (! is_user_logged_in()) {
            unset($endpoints['/wp/v2/users']);
        }
    }
    return $endpoints;
}
add_filter('rest_endpoints', 'restrict_rest_api_users_endpoint');



// enables related posts shortcode (shortcode: [rpost])
function related_posts_shortcode($attr)
{
    ob_start();
    get_template_part('includes/related-posts');
    return ob_get_clean();
}
add_shortcode('rpost', 'related_posts_shortcode');









// Modify Widget Sidebar Date
add_filter('avia_widget_time', 'change_avia_date_format', 10, 2);
function change_avia_date_format($date, $function)
{
    $output = get_option('date_format');
    return $output;
}

// MemberPress Invoice Tax Roundup
// Rounds up tax to 9,99 instead of 9,9999
function mepr_change_tax_rate($params, $txn)
{
    $params['tax_rate'] = round($txn->tax_rate, 1); // round to 2 numbers. EX: 9.0
    return $params;
}
add_filter('mepr_transaction_email_params', 'mepr_change_tax_rate', 99, 2);

// Modify Invoice Date
function mepr_change_invoice_date($params, $txn)
{
    $created_ts = strtotime($txn->created_at);
    $expires_at = strtotime($txn->expires_at);

    $params['trans_date'] = date_i18n('F j, Y', $created_ts);
    $params['trans_expires_at'] = date_i18n('F j, Y', $expires_at);

    $params['subscr_date'] = date_i18n('F j, Y', $created_ts);
    $params['subscr_expires_at'] = date_i18n('F j, Y', $expires_at);
    return $params;
}

add_filter('mepr_transaction_email_params', 'mepr_change_invoice_date', 99, 2);


// Start: create custom role 'author_pro' to access memberpress
// Fetch the 'editor' role capabilities
$editor_role = get_role('editor');
$editor_capabilities = $editor_role->capabilities;

// Define additional capabilities specific to 'Author Pro'
$author_pro_capabilities = array(
    // Manage options is required to access Betalingen
    // 'manage_options' => true,

    // Remove users is required to access Memberpress
    'remove_users' => true,

);

// Merge 'Editor' capabilities with 'Author Pro' specific capabilities
$author_pro_capabilities = array_merge($editor_capabilities, $author_pro_capabilities);

// Remove the 'author_pro' role in case it already exists to avoid capability conflicts
// remove_role('author_pro');

// Create the 'Author Pro' role with merged capabilities
add_role('author_pro', __('Member Manager'), $author_pro_capabilities);

// End: create custom role 'author_pro' to access memberpress


/* Images sizes */
// remove "responsive images" functionality in WP 4.4 and higher
add_filter('wp_get_attachment_image_attributes', function ($attr) {
    if (isset($attr['sizes']))
        unset($attr['sizes']);
    if (isset($attr['srcset']))
        unset($attr['srcset']);
    return $attr;
}, PHP_INT_MAX);
add_filter('wp_calculate_image_sizes', '__return_false', PHP_INT_MAX);
add_filter('wp_calculate_image_srcset', '__return_false', PHP_INT_MAX);
remove_filter('the_content', 'wp_make_content_images_responsive');

// Disable loads of Enfold & WP image sizes upon upload
// do image sizes manually, double-size with high compression for retina screens
// use Photoshop to set exact double size and quality between Q30 and Q40
add_action('init', 'remove_enfold_image_sizes');
function remove_enfold_image_sizes()
{
    // do NOT remove widget size, is used in backend portfolio items!
    // remove_image_size('widget');
    remove_image_size('square');
    remove_image_size('featured');
    remove_image_size('featured_large');
    remove_image_size('portfolio');
    remove_image_size('portfolio_small');
    remove_image_size('gallery');
    remove_image_size('magazine');
    remove_image_size('masonry');
    remove_image_size('entry_without_sidebar');
    remove_image_size('entry_with_sidebar');
    remove_image_size('shop_thumbnail');
    remove_image_size('shop_catalog');
    remove_image_size('shop_single');
    remove_image_size('shop_gallery_thumbnail');
}
// Remove unneeded default WordPress image sizes
function prefix_remove_default_images($sizes) {
    unset($sizes['large'], $sizes['medium_large']);
    return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'prefix_remove_default_images');




// Start - Unsure if following code works or not

// Format text without fontweight
add_filter("the_content", function ($content) {
    preg_match_all('/<span style="font-weight: 400;">(.*?)<\/span>/', $content, $matches);

    foreach ($matches[0] as $key => $value) {
        $content = str_replace($value, $matches[1][$key], $content);
    }

    return $content;
}, 10, 1);


//[nbsp] shortcode
function nbsp_shortcode($atts, $content = null)
{
    $content = '&nbsp';
    return $content;
}

add_shortcode('nbsp', 'nbsp_shortcode');


// Footer Credits
function credits_shortcode()
{
    $url = 'https://daylinq.nl/modules/credits.html';
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return '';
    }
    $body = wp_remote_retrieve_body($response);
    return $body;
}
add_shortcode('footer_credits', 'credits_shortcode');


/** Get page title **/
function page_title_sc()
{
    return get_the_title();
}

add_shortcode('page_title', 'page_title_sc');


/* Intro shortcode */
function intro_func($atts, $content = "")
{
    return "<p class='intro-text'>$content</p>";
}
add_shortcode('intro', 'intro_func');


/* Label shortcode */
function label_func($atts, $content = "")
{
    return "<span class='meta-label'>$content</span>";
}


/* Browser colour */
function address_mobile_address_bar()
{
    $color = "#414059";
    //this is for Chrome, Firefox OS, Opera and Vivaldi
    echo '<meta name="theme-color" content="' . $color . '">';
    //Windows Phone **
    echo '<meta name="msapplication-navbutton-color" content="' . $color . '">';
    // iOS Safari
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
}
add_action('wp_head', 'address_mobile_address_bar');


/* Changes email subject to subject of form */
add_filter('avf_form_subject', 'avia_change_mail_subject', 10, 3);
function avia_change_mail_subject($subject, $new_post, $form_params)
{
    $subject = urldecode($new_post['3_1']);
    return $subject;
}


// Gives dynamic year for footer
function avia_year_func($atts)
{
    return date("Y");
}
add_shortcode('cur_year', 'avia_year_func');

// Set builder mode to debug
add_action('avia_builder_mode', "builder_set_debug");
function builder_set_debug()
{
    return "debug";
}

// End - Unsure if following code works or not


// DPT - Redirect public feed during beta

function wpse101952_redirect()
{
    global $post;
    $path = $_SERVER['REQUEST_URI'];
    $find = '/feed/';
    if (str_contains($path, $find)) {

        wp_redirect("https://www.stockwatch.nl/");

        exit();
    }
}
add_action('template_redirect', 'wpse101952_redirect');



// Register the 'action' query variable

function greenbrand_register_query_vars($vars) {
  $vars[] = 'action';
  $vars[] = 'subscription';
  $vars[] = 'key';
  return $vars;
}
add_filter('query_vars', 'greenbrand_register_query_vars');

function greenbrand_load_cancel_subscription_template($template) {
  // Check if the 'action=cancel' query parameter is present
  if (get_query_var('action') === 'cancel') {
    $subscription_id = get_query_var('subscription');
    $subscription_key = get_query_var('key');

    // Validate the subscription ID and key
    if (empty($subscription_id) || empty($subscription_key)) {
      wp_die(__('Invalid subscription or key.', 'greenbrand'));
    }

    // Load the cancel-subscription.php template
    $custom_template = locate_template('cancel-subscription.php');
    if ($custom_template) {
      // Pass subscription data to the template
      global $subscription;
      $subscription = new MeprSubscription($subscription_id);

      return $custom_template;
    }
  }

  return $template;
}
add_filter('template_include', 'greenbrand_load_cancel_subscription_template');



function load_pronamic_ideal_textdomain() {
    load_textdomain('pronamic-ideal', WP_LANG_DIR . '/plugins/pronamic-ideal-' . get_locale() . '.mo');
}
add_action('init', 'load_pronamic_ideal_textdomain');