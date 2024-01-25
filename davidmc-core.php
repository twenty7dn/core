<?php
/**
 * Plugin Name: Twenty7 Degrees North Core Plugin
 * Description: Adds core functionality to the Twenty7 Degrees North headless theme.
 * Author: David M. Coleman
 * Author URI: https://davidmc.io/
 * Version: 1.0.23
 */

/**
 * Requirements
 */
require ( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );
require ( plugin_dir_path( __FILE__ ) . 'inc/misc.php' );
require ( plugin_dir_path( __FILE__ ) . 'inc/filter.php' );
require ( plugin_dir_path( __FILE__ ) . 'inc/acf.php' );
require ( plugin_dir_path( __FILE__ ) . 'inc/acf-options.php' );
require ( plugin_dir_path( __FILE__ ) . 'inc/favicon.php' );
require ( plugin_dir_path( __FILE__ ) . 'blocks/header/header-block.php');

add_action('admin_enqueue_scripts', function () {
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page == "options-general.php") {
        wp_enqueue_script('davidmc-options-admin', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array(), '1.0.23', true);
    }
});


add_filter( 'rest_url_prefix', 'twentyseven_api_slug');
function twentyseven_api_slug( $slug ) {
    return 'api';
}

function twentyseven_site_logo() {
    return get_field( 'options', 'logo' );
}

function twentyseven_rest_index( $response ) {
    if( have_rows('links', 'wp_settings') ) {
        $connect = array();
        while (have_rows('links', 'wp_settings')) : the_row();
            $link = get_sub_field('link');
            $icon = get_sub_field('icon_svg');

            // Create a new DOMDocument and load the SVG
            $dom = new DOMDocument();
            $dom->loadXML($icon);

            // Get the <svg> element and set width and height
            $svg = $dom->getElementsByTagName('svg')->item(0);
            $svg->setAttribute('width', '32');
            $svg->setAttribute('height', '32');

            // Find all <path> elements and set their fill attribute
            $paths = $dom->getElementsByTagName('path');
            foreach ($paths as $path) {
                $path->setAttribute('fill', 'currentColor');
            }

            // Save the updated SVG
            $sanitized_svg = $dom->saveXML();

            $connect[] = array(
                'link' => array(
                    'href' => $link['url'],
                    'target' => $link['target'] ? $link['target'] : '_blank'
                ),
                'icon' => $sanitized_svg
            );
        endwhile;
    } else {
        $connect = false;
    }

    $imgix = new Imgix();

    $site_name = esc_html( get_bloginfo( 'name' ) ); // Assuming bloginfo('name') returns the site name

    $favicon_tags = '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( $imgix->get_src( get_field( 'favicon', 'wp_settings' ), array( 'fm' => 'png', 'w' => 180, 'h' => 180 ) ) ) . '">';
    $favicon_tags .= '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $imgix->get_src( get_field( 'favicon', 'wp_settings' ), array( 'fm' => 'png', 'w' => 32, 'h' => 32, 'mask' => 'ellipse' ) ) ) . '">';
    $favicon_tags .= '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url( $imgix->get_src( get_field( 'favicon', 'wp_settings' ), array( 'fm' => 'png', 'w' => 16, 'h' => 16, 'mask' => 'ellipse' ) ) ) . '">';
    $favicon_tags .= '<link rel="manifest" href="https://' . CDN_DOMAIN . '/core/template/twentyseven-theme/assets/icons/site.webmanifest">';
    $favicon_tags .= '<link rel="shortcut icon" href="https://' . CDN_DOMAIN . '/core/template/twentyseven-theme/assets/icons/favicon.ico">';
    $favicon_tags .= '<meta name="apple-mobile-web-app-title" content="' . $site_name . '">';
    $favicon_tags .= '<meta name="application-name" content="' . $site_name . '">';
    $favicon_tags .= '<meta name="msapplication-TileColor" content="#000000">';
    $favicon_tags .= '<meta name="msapplication-config" content="https://' . CDN_DOMAIN . '/core/template/twentyseven-theme/assets/icons/browserconfig.xml">';
    $favicon_tags .= '<meta name="theme-color" content="#000000" />';


    $data = $response->data;
    $data['shortname'] = get_option( 'shortname' );
    $data['site_icon'] = get_field( 'icon', 'wp_settings' );
    $data['site_icon_url'] = wp_get_attachment_image_url( get_field( 'icon', 'wp_settings' ), 'full' );
    $data['site_logo'] = get_field( 'logo', 'wp_settings' );
    $data['site_logo_url'] = wp_get_attachment_image_url( get_field( 'logo', 'wp_settings' ), 'full' );
    $data['site_background'] = get_field( 'background', 'wp_settings' );
    $data['site_background_url'] = wp_get_attachment_image_url( get_field( 'background', 'wp_settings' ), 'full' );
    $data['site_background_credit'] = strip_tags( get_field( 'image_credit', get_field( 'background', 'wp_settings' ) ), '<a>' );
    $data['site_connect'] = $connect;
    $data['site_footer'] = array(
        'desktop' => html_entity_decode( str_replace( '[y]', date('Y'), get_field( 'desktop_footer', 'wp_settings' ) ) ),
        'mobile' => html_entity_decode( str_replace( '[y]', date('Y'), get_field( 'mobile_footer', 'wp_settings' ) ) ),
    );
    $data['site_favicon'] = $favicon_tags;
    unset($data['namespaces']);
    unset($data['authentication']);
    unset($data['routes']);
    unset($data['multisite']);
    unset($data['links']);
    unset($data['_links']);

    $desired_order = ['name', 'shortname', 'description', 'url', 'home', 'gmt_offset', 'timezone_string', 'site_icon', 'site_icon_url', 'site_logo', 'site_logo_url', 'site_background', 'site_background_url', 'site_background_credit', 'site_connect', 'site_footer', 'site_favicon'];

    $data = reorder_array_by_keys( $data, $desired_order );

    $response->set_data( $data );
    return $response;
 }
 add_filter( 'rest_index', 'twentyseven_rest_index', 999, 1 );

function reorder_array_by_keys($array, $order) {
    $reordered_array = [];

    foreach ($order as $key) {
        if (array_key_exists($key, $array)) {
            $reordered_array[$key] = $array[$key];
        }
    }

    return $reordered_array;
}

function acf_options_route() {
    return str_replace_deep( home_url('/'), HEADLESS_FRONTEND_URL, get_fields('options') );
}

// Menu locations
add_action( 'rest_api_init', function() {
    register_rest_route( 'wp', '/v2/menu', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'wp_menu_route',
    ));
});

function wp_menu_route() {
    $menuLocations = get_nav_menu_locations(); // Get nav locations set in theme, usually functions.php)
    return $menuLocations;
}

// Individual menus
add_action( 'rest_api_init', function() {
    register_rest_route( 'wp', '/v2/menu/(?P<id>\d+)', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'wp_menu_single',
    ));
});

function wp_menu_single($data) {
    $menuID = $data['id']; // Get the menu from the ID
    $primaryNav = wp_get_nav_menu_items($menuID); // Get the array of wp objects, the nav items for our queried location.
    return $primaryNav;
}

function custom_remove_parent_category_slug( $rules ) {
    $new_rules = array();

    $categories = get_categories( array(
        'hide_empty' => false,
    ) );

    foreach ( $categories as $category ) {
        $category_slug = $category->slug;

        // Rule for category pages
        $new_rules[ $category_slug . '/?$' ] = 'index.php?category_name=' . $category_slug;

        // Rule for paginated category pages
        $new_rules[ $category_slug . '/page/([0-9]{1,})/?$' ] = 'index.php?category_name=' . $category_slug . '&paged=$matches[1]';
    }

    return $new_rules + $rules;
}
add_filter( 'rewrite_rules_array', 'custom_remove_parent_category_slug' );


function custom_remove_parent_term_slug( $termlink, $term, $taxonomy ) {
    if ( 'category' === $taxonomy ) {
        // Get the category base from options
        $category_base = get_option( 'category_base' );

        // Check if the category base exists and is not empty
        if ( $category_base && ! empty( $category_base ) ) {
            $term_slug = $term->slug;
            $termlink = home_url( user_trailingslashit( "$category_base/$term_slug", 'category' ) );
        }
    }
    return $termlink;
}
add_filter( 'term_link', 'custom_remove_parent_term_slug', 10, 3 );

function new_excerpt_more($more) {
    return '&hellip;';
}
add_filter('excerpt_more', 'new_excerpt_more');

// Add filter to respond with next and previous post in post response.
add_filter( 'rest_prepare_post', function( $response, $post, $request ) {

    if ( get_post_type( $post ) == 'nav_menu_item' ) {
        $response->data['link'] = str_replace( site_url( '/' ), '/', $response->data['url'] );
    }

    // Only do this for single post requests.
    if( $request->get_param('per_page') === 1 ) {
          global $post;
          // Get the so-called next post.
          $next = get_adjacent_post( false, '', false );
          // Get the so-called previous post.
          $previous = get_adjacent_post( false, '', true );
          // Format them a bit and only send id and slug (or null, if there is no next/previous post).
          $response->data['next'] = ( is_a( $next, 'WP_Post') ) ? array( "id" => $next->ID, "slug" => $next->post_name, "title" => get_the_title( $next->ID ) ) : null;
          $response->data['previous'] = ( is_a( $previous, 'WP_Post') ) ? array( "id" => $previous->ID, "slug" => $previous->post_name, "title" => get_the_title( $previous->ID ) ) : null;

          if ( get_post_type( $post ) === 'page' ) {
            $response->data['template'] = get_page_template_slug( $post );
          }

          $topics = array();
          foreach ( get_the_category( $post->ID ) as $topic ) {
            $topics[] = array(
                'id' => $topic->term_id,
                'name' => $topic->name,
                'slug' => $topic->slug
            );
          }

          $response->data['terms']['topics'] = $topics;

          $tags = array();
          foreach ( get_the_tags( $post->ID ) as $tag ) {
            $tags[] = array(
                'id' => $tag->term_id,
                'parent_id' => $tag->parent,
                'name' => $tag->name,
                'slug' => $tag->slug
            );
          }

          $response->data['terms']['tags'] = $tags;
    }

    $subtitle = apply_filters( 'plugins/wp_subtitle/get_subtitle', '', array(
        'before'  => null,
        'after'   => null,
        'post_id' => $post->ID
    ) );

    if ( empty( $subtitle ) ) {
        $response->data['subtitle'] = false;
    } else {
        $response->data['subtitle'] = array(
            'rendered' => $subtitle
        );
    }

    $response->data['description'] = array(
        'rendered' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post->ID ), true ), '48', '...' )
    );

    if ( get_post_meta( get_post_thumbnail_id( $post->ID ), 'acf_wpo_sweet_spot', true ) ) {
        $fp = array(
            'x' => get_post_meta( get_post_thumbnail_id( $post->ID ), 'acf_wpo_sweet_spot', true )['x'],
            'y' => get_post_meta( get_post_thumbnail_id( $post->ID ), 'acf_wpo_sweet_spot', true )['y']
        );
    } else {
        $fp = array(
            'x' => 50,
            'y' => 50
        );
    }

    $response->data['featured_media'] = has_post_thumbnail( $post->ID ) ? array(
        'id' => get_post_thumbnail_id( $post->ID ),
        'src' => get_the_post_thumbnail_url( $post->ID, 'full' ),
        'color' => get_color_data( get_post_thumbnail_id( $post->ID ), 'dominant_color_hex', true ),
        'fp' => $fp,
        'credit' => strip_tags( get_field( 'image_credit', get_post_thumbnail_id( $post->ID ) ), '<a>' )
    ) : false;
  
    return $response;
}, 10, 3 );

if ( !is_admin() && $_GET['per_page'] == 1 ) {
    add_action(
        'rest_api_init',
        function () {

            if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
                require ABSPATH . 'wp-admin/includes/post.php';
            }

            // Surface all Gutenberg blocks in the WordPress REST API
            $post_types = get_post_types_by_support( [ 'editor' ] );
            foreach ( $post_types as $post_type ) {
                if ( use_block_editor_for_post_type( $post_type ) ) {
                    register_rest_field(
                        $post_type,
                        'blocks',
                        [
                            'get_callback' => function ( array $post ) {
                                $blocks = parse_blocks( $post['content']['raw'] );
                                foreach ( $blocks as $index => $block ) {
                                    if ( $block['blockName'] === 'core/image' ) {
                                        $blocks[$index]['media_source'] = wp_get_attachment_url( $blocks[$index]['attrs']['id'] );

                                        $blocks[$index]['title'] = get_the_title( $blocks['attrs']['id'] );
                                        preg_match( "/<figcaption ?.*>(.*)<\/figcaption>/", $block['innerHTML'], $matches );
                                        $re = "/(<a\\b[^<>]*href=['\"]?http[^<>]+)>/is"; 
                                        $str = "<a href=\"davidmc.io\">\n<a href=\"davidmc.io\">\n<a another=\"val\" href=\"https://davidmc.io\">\n"; 
                                        $subst = "$1 target=\"_blank\" rel=\"noopener noreferrer\">"; 
                                        $blocks[$index]['caption'] = preg_replace($re, $subst, preg_replace('#<figcaption.*?>(.*?)</figcaption>#i', '\1', $matches[0] ) );
                                    } elseif ( $block['blockName'] === 'acf/bookmark' || $block['blockName'] === 'core/quote' ) {
                                        $blocks[$index]['innerHTML'] = render_block( $block );
                                    } elseif ( $block['blockName'] === 'core/heading' ) {
                                        $textContent = strip_tags(render_block( $block ));

                                        // Sanitize the textContent for use as an ID
                                        $sanitizedId = sanitize_title($textContent);

                                        $blocks[$index]['innerHTML'] = preg_replace('/<h([1-6])(.*?)>/', '<h$1 id="' . esc_attr($sanitizedId) . '"$2>', $block['innerHTML']);
                                    }
                                    $blocks[$index]['uuid'] = uniqid();
                                }
                                return $blocks;
                            },
                        ]
                    );
                }
            }
        }
    );
}



/**
 * Change the maximum of per_page for /wp/v2/tags/ from 100 to 120
 */
add_filter( 'rest_endpoints', function( $endpoints )
{
    if( isset( $endpoints['/wp/v2/posts'][0]['args']['per_page']['maximum'] ) )
        $endpoints['/wp/v2/posts'][0]['args']['per_page']['maximum'] = 9999;

    if( isset( $endpoints['/wp/v2/pages'][0]['args']['per_page']['maximum'] ) )
        $endpoints['/wp/v2/pages'][0]['args']['per_page']['maximum'] = 9999;

    if( isset( $endpoints['/wp/v2/tags'][0]['args']['per_page']['maximum'] ) )
        $endpoints['/wp/v2/tags'][0]['args']['per_page']['maximum'] = 9999;

    if( isset( $endpoints['/wp/v2/categories'][0]['args']['per_page']['maximum'] ) )
        $endpoints['/wp/v2/categories'][0]['args']['per_page']['maximum'] = 9999;

    if( isset( $endpoints['/wp/v2/search'][0]['args']['per_page']['maximum'] ) )
        $endpoints['/wp/v2/search'][0]['args']['per_page']['maximum'] = 9999;

    if( isset( $endpoints['/wp/v2/users'][0]['args']['per_page']['maximum'] ) )
        $endpoints['/wp/v2/users'][0]['args']['per_page']['maximum'] = 9999;

    return $endpoints;  
} );




function remove_category_hierarchy_from_permalink( $category, $parents = '' ) {
    // Only include the last category (no parents)
    return $category->slug;
}

add_filter( 'category_link', 'remove_category_hierarchy_from_permalink', 10, 2 );


add_filter('bcn_register_rest_endpoint', 'my_bcn_endpoint_filter', 10, 4);
function my_bcn_endpoint_filter($register_rest_endpoint, $endpoint, $version, $methods) {
    if($endpoint === 'post') {
        $register_rest_endpoint = true;
    } elseif($endpoint === 'term') {
        $register_rest_endpoint = true;
    }
    return $register_rest_endpoint;
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'wp/v2', '/term/category/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_category_breadcrumb',
    ) );
} );

function get_category_breadcrumb( $data ) {
    $category_id = $data['id']; // Get the current category ID

    if ($category_id) {
        $ancestors = get_ancestors($category_id, 'category');
        $ancestors = array_reverse($ancestors); // Reverse the array to get the hierarchy from top to bottom
    
        $breadcrumb = array();
    
        foreach ($ancestors as $ancestor_id) {
            $ancestor_category = get_category($ancestor_id);
            $breadcrumb[] = array(
                'id' => $ancestor_category->term_id,
                'name' => $ancestor_category->name,
                'slug' => $ancestor_category->slug,
                'current' => false,
            );
        }
    
        // Add the current category to the breadcrumb with 'current' set to true
        $current_category = get_category($category_id);
        $breadcrumb[] = array(
            'id' => $current_category->term_id,
            'name' => $current_category->name,
            'slug' => $current_category->slug,
            'current' => true,
        );

        return $breadcrumb;
    }
}



function multi_subdomain_permalink( $permalink ) {
    $permalink = str_replace( site_url('/'), '/', $permalink );
    return $permalink;
}
add_filter('page_link','multi_subdomain_permalink');
add_filter('post_link','multi_subdomain_permalink');
add_filter('term_link','multi_subdomain_permalink');
add_filter('tag_link','multi_subdomain_permalink');
add_filter('category_link','multi_subdomain_permalink');
add_filter('post_type_link','multi_subdomain_permalink');


function wp_get_instagram_feed( $request ) {
    $username = $request['username'];
    $key = 'instagram_feed_' . $username;
    $data = get_transient( $key );
    if ( !isset( $data ) || empty( $data ) ) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://instagram130.p.rapidapi.com/account-feed?username=$username",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "X-RapidAPI-Host: instagram130.p.rapidapi.com",
                "X-RapidAPI-Key: d7dbf0308fmshd11c14198c4500ep1e592fjsnaa22bd4a26ee"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $imgix = new Imgix();
        $data = array();
        foreach ( json_decode( $response, true ) as $index => $post ) {
            $data[$index]['id'] = $post['node']['id'];
            $data[$index]['username'] = $post['node']['owner']['username'];
            $data[$index]['post_link'] = 'https://instagram.com/p/' . $post['node']['shortcode'];
            $data[$index]['thumbnail_html']['rendered'] = $imgix->get_remote_image( $post['node']['display_url'], array(
                '' => array(
                    'w' => 256,
                    'h' => 256
                )
            ), array( 'instagram-thumbnail' ) );
            if ( $index === 11 ) break;
        }

        set_transient( $key, $data, DAY_IN_SECONDS );
    }
    return $data;
}

// Make sure to include the JWT library
use \Firebase\JWT\JWT;

add_action('wp_login', 'set_jwt_token_on_login', 10, 2);

function set_jwt_token_on_login($user_login, $user) {
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600; // JWT valid for 1 hour
    $payload = array(
        'userid' => $user->ID,
        'iat' => $issuedAt,
        'exp' => $expirationTime
    );

    // Replace 'your-secret-key' with your actual secret key
    $jwt = JWT::encode($payload, JWT_AUTH_SECRET_KEY, 'HS256');

    // Set the JWT token in an HttpOnly cookie
    setcookie('jwt_token', $jwt, [
        'expires' => $expirationTime,
        'path' => '/',
        'domain' => '.davidmc.io',
        'secure' => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax' // SameSite attribute for modern browsers
    ]);
}


add_action('wp_logout', 'clear_jwt_token_on_logout');

function clear_jwt_token_on_logout() {
    // Clear the JWT token cookie by setting its expiration to a past time
    unset($_COOKIE['jwt_token']);
    setcookie('jwt_token', '', time() - 3600, '/');
}


// function always_show_admin_bar() {
//     return true;
// }
// add_filter('show_admin_bar', 'always_show_admin_bar');

// add_action('init', function() {
//     error_log(print_r(getallheaders(), true)); // Logs headers to the PHP error log
// });


add_action( 'rest_api_init', function () {
    register_rest_route( 'wp/v2', '/auth-check', array(
        'methods' => 'GET',
        'callback' => 'check_user_authentication'
    ));
});

function check_user_authentication( WP_REST_Request $request ) {
    return new WP_REST_Response( array(
        'current_user_id' => get_current_user_id()
    ), 200 );
}

add_action( 'admin_bar_menu', 'customize_my_wp_admin_bar', 80 );
function customize_my_wp_admin_bar( $wp_admin_bar ) {

    //Get a reference to the new-content node to modify.
    $new_content_node = $wp_admin_bar->get_node('site-name');

    if ( is_admin() ) {
        $new_content_node->href = HEADLESS_FRONTEND_URL;
    }

    //Get a reference to the new-content node to modify.
    $new_content_node_def = $wp_admin_bar->get_node('view-site');

    if ( is_admin() ) {
        $new_content_node_def->href = HEADLESS_FRONTEND_URL;
    }

    //Update Node.
    $wp_admin_bar->add_node($new_content_node);
    $wp_admin_bar->add_node($new_content_node_def);

    $wp_admin_bar->remove_node('themes');
    $wp_admin_bar->remove_node('widgets');
    $wp_admin_bar->remove_node('background');
    $wp_admin_bar->remove_node('header');
    $wp_admin_bar->remove_node('customize');
    $wp_admin_bar->remove_node('comments');
    $wp_admin_bar->remove_node('tm-suspend');
    $wp_admin_bar->remove_node('search');
    $wp_admin_bar->remove_node('wp-rest-cache-clear');
    $wp_admin_bar->remove_node('rank-math');
    if ( !is_admin() ) {
        $wp_admin_bar->remove_node('vercel-deploy-button');
    }

}


function sendWebhookOnEvent($event_type) {
    $webhook_url = HEADLESS_FRONTEND_URL . '/api/invalidate-cache';

    $request_args = array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-WordPress-Event' => $event_type,
        ),
    );

    error_log('Sending webhook with event type: ' . $event_type);

    $response = wp_safe_remote_request($webhook_url, $request_args);

    if (is_wp_error($response)) {
        error_log('Webhook request failed: ' . $response->get_error_message());
    } else {
        error_log('Webhook request successful. Response: ' . wp_remote_retrieve_body($response));
    }
}

function onUserLogin($user_login, $user) {
    sendWebhookOnEvent('login');
}

function onUserLogout() {
    sendWebhookOnEvent('logout');
}

// Hook the custom functions to user login and logout actions
add_action('wp_login', 'onUserLogin', 10, 2);
add_action('wp_logout', 'onUserLogout');

add_action('rest_api_init', function () {
    register_rest_route( 'wp/v2', '/head/(?P<url>\S+)', array(
        'methods'         => WP_REST_Server::READABLE,
        'callback'        => 'davidmc_get_head',
        'permission_callback' => '__return_true'
    ));
});


function davidmc_get_head( $request ) {
    $data = json_decode( file_get_contents( rest_url( '/rankmath/v1/getHead?url=' . $request['url'] ) ) );
    if ( $data->success ) {
        status_header(200);
        if ( str_contains( urldecode( $request['url'] ), site_url( '/page/' ) ) ) {
            $htmlContent = file_get_contents( urldecode( $request['url'] ) . '?getHead=true' );

            // Initialize DOMDocument for HTML content and suppress errors
            $htmlDom = new DOMDocument();
            libxml_use_internal_errors(true);
            $htmlDom->loadHTML($htmlContent);
            libxml_clear_errors();

            // Extract and process the title tag from HTML content
            $htmlTitleTags = $htmlDom->getElementsByTagName('title');
            $htmlTitle = $htmlTitleTags->item(0)->textContent;
        }

        // Extract head content from data
        $headContent = $data->head;

        // Initialize DOMDocument for head content and suppress errors
        $headDom = new DOMDocument();
        libxml_use_internal_errors(true);
        $headDom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $headContent);
        libxml_clear_errors();

        // Define tags and attributes to inspect for URL modification
        $tagsToInspect = [
            'link' => 'href',
            'meta' => 'content',
            'script' => 'src',
        ];

        // Modify URLs within specified tags
        foreach ($tagsToInspect as $tagName => $attributeName) {
            foreach ($headDom->getElementsByTagName($tagName) as $tag) {
                $attributeValue = $tag->getAttribute($attributeName);
                if (str_starts_with($attributeValue, site_url('/wp-content/uploads/'))) {
                    $imgix = new Imgix();
                    $size = extract_sizes_from_url($attributeValue);
                    $newAttributeValue = $imgix->get_src(get_attachment_id_from_url($attributeValue), array(
                        'w' => $size[0],
                        'h' => $size[1],
                        'fm' => 'png'
                    ));
                    $tag->setAttribute($attributeName, $newAttributeValue);
                }
            }
        }

        // Save only the modified head content
        $updatedHeadContent = '';
        foreach ($headDom->getElementsByTagName('head') as $head) {
            $updatedHeadContent .= $headDom->saveHTML($head);
        }

        // Replace the title in the data head with the HTML title
        if (!empty($htmlTitle)) {
            $headTitleTags = $headDom->getElementsByTagName('title');
            $headTitle = $headTitleTags->item(0)->textContent;
            if (!empty($headTitle)) {
                $updatedHeadContent = str_replace($headTitle, $htmlTitle, $updatedHeadContent);
            }
        }

        // Update the data object with the modified head content
        $data->head = $updatedHeadContent;

        // Additional replacements in data head
        $data->head = str_replace(array('secure.davidmc.io'), array('blog.davidmc.io'), $data->head);
    } else {
        status_header(404);
    }
    return new WP_REST_Response($data);
}


function get_attachment_id_from_url($image_url) {
    global $wpdb;

    // Remove size info from the URL (assumes a format like "-1024x1024")
    $pattern = '/-\d+x\d+(?=\.jpg|\.jpeg|\.png|\.gif)/i';
    $clean_url = preg_replace($pattern, '', $image_url);

    // Extract the filename from the URL
    $filename = basename($clean_url);

    // Query the database to get the attachment ID
    $query = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND guid LIKE '%{$filename}'";
    $attachment_id = $wpdb->get_var($query);

    return $attachment_id;
}

function extract_sizes_from_url($url) {
    // Use regex to extract the size part of the URL (e.g., "-1024x1024")
    preg_match('/-(\d+)x(\d+)/', $url, $matches);

    // Check if the size information is found
    if (!empty($matches)) {
        // Return the width and height as an array of integers
        return array_map('intval', array_slice($matches, 1));
    }

    // Return an empty array or null if no size information is found
    return null;
}



/**
 * Filter to change Separator %sep%.
 */
add_filter( 'rank_math/settings/title_separator', function( $sep ) {
    $sep = '&rsaquo;';
    return $sep;
});


function custom_wp_title( $title, $sep ) {
    $sep = '&rsaquo;';
    // Check if it's a paged view
    if ( is_paged() ) {
        global $page, $paged;

        // Get the site title
        $site_title = get_bloginfo( 'name' );

        // Get the site tagline
        $site_tagline = get_bloginfo( 'description' );

        // Get current page number and total number of pages
        $current_page = max( $page, $paged );
        $total_pages = $GLOBALS['wp_query']->max_num_pages;

        // Construct the new title format
        $title = $site_title . ' ' . $sep . ' page ' . $current_page . ' of ' . $total_pages . ' ' . $sep . ' ' . $site_tagline;
    } else {
        // Get the site title
        $site_title = get_bloginfo( 'name' );

        // Get the site tagline
        $site_tagline = get_bloginfo( 'description' );

        // Construct the new title format
        $title = $site_title . ' ' . $sep . ' ' . $site_tagline;
    }

    return $title;
}
add_filter( 'wp_title', 'custom_wp_title', -999, 2 );


add_filter( 'acf_image_sweetspot_enable', '__return_true' );




add_action('admin_init', function() {
    register_setting('general', 'shortname', array(
        'type' => 'string',
        'description' => 'Site Short Name',
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest' => false,
    ));

    add_settings_field(
        'shortname_field',
        'Shortname',
        'shortname_field_callback',
        'general',
        'default'
    );
});

function shortname_field_callback() {
    $shortname = get_option('shortname', '');

    echo '<input type="text" id="shortname_field" name="shortname" value="' . esc_attr($shortname) . '" class="regular-text" />';
    echo '<p class="description">The shorthand title of the site.</p>'; // Add your descriptor text here
}



function replace_permalinks($url): array|string
{
    // Replace 'secure.davidmc.io' with 'blog.davidmc.io'
    $url = rtrim( HEADLESS_FRONTEND_URL, '/' ) . $url;

    return $url;
}

add_filter('post_link', 'replace_permalinks', 10, 1);
add_filter('page_link', 'replace_permalinks', 10, 1);
add_filter('category_link', 'replace_permalinks', 10, 1);
//add_filter('tag_link', 'replace_permalinks', 10, 1);
add_filter('term_link', 'replace_permalinks', 10, 1);

function replace_author_permalinks($url): array|string
{
    // Replace 'secure.davidmc.io' with 'blog.davidmc.io'
    $url = str_replace( site_url('/'), HEADLESS_FRONTEND_URL, $url );

    return $url;
}
add_filter('author_link', 'replace_author_permalinks', 10, 1);


function change_post_type_labels($labels) {
    $labels->name = 'Entries';
    $labels->singular_name = 'Entry';
    $labels->add_new = 'Add New Entry';
    $labels->add_new_item = 'Add New Entry';
    $labels->edit_item = 'Edit Entry';
    $labels->new_item = 'New Entry';
    $labels->view_item = 'View Entry';
    $labels->search_items = 'Search Entries';
    $labels->not_found = 'No Entries found';
    $labels->not_found_in_trash = 'No Entries found in Trash';
    $labels->all_items = 'All Entries';
    $labels->menu_name = 'Entries';
    $labels->name_admin_bar = 'Entry';

    return $labels;
}
add_filter('post_type_labels_post', 'change_post_type_labels');

function change_menu_label() {
    global $menu;
    global $submenu;
    $menu[5][0] = 'Entries';
    $submenu['edit.php'][5][0] = 'All Entries';
    $submenu['edit.php'][10][0] = 'Add New Entry';
    echo '';
}
add_action('admin_menu', 'change_menu_label');

function change_taxonomy_labels($labels) {
    $labels->name = 'Topics';
    $labels->singular_name = 'Topic';
    $labels->menu_name = 'Topics';
    $labels->all_items = 'All Topics';
    $labels->edit_item = 'Edit Topic';
    $labels->view_item = 'View Topic';
    $labels->update_item = 'Update Topic';
    $labels->add_new_item = 'Add New Topic';
    $labels->new_item_name = 'New Topic Name';
    $labels->parent_item = 'Parent Topic';
    $labels->parent_item_colon = 'Parent Topic:';
    $labels->search_items = 'Search Topics';
    $labels->popular_items = 'Popular Topics';
    $labels->separate_items_with_commas = 'Separate topics with commas';
    $labels->add_or_remove_items = 'Add or remove topics';
    $labels->choose_from_most_used = 'Choose from the most used topics';
    $labels->not_found = 'No topics found';
    $labels->no_terms = 'No topics';
    $labels->items_list_navigation = 'Topics list navigation';
    $labels->items_list = 'Topics list';

    return $labels;
}
add_filter('taxonomy_labels_category', 'change_taxonomy_labels');

function change_category_name_in_database($terms) {
    if (is_admin() && isset($_POST['taxonomy']) && $_POST['taxonomy'] === 'category') {
        global $wpdb;
        $wpdb->update($wpdb->terms, ['name' => 'Topic'], ['slug' => 'category']);
    }
    return $terms;
}
add_filter('get_terms', 'change_category_name_in_database');

function davidmc_icons(): void
{
    $imgix = new Imgix();

    $site_name = esc_html( get_bloginfo( 'name' ) ); // Assuming bloginfo('name') returns the site name

    $favicon_tags = '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( $imgix->get_src( get_field( 'favicon', 'wp_settings' ), array( 'fm' => 'png', 'w' => 180, 'h' => 180 ) ) ) . '">';
    $favicon_tags .= '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $imgix->get_src( get_field( 'favicon', 'wp_settings' ), array( 'fm' => 'png', 'w' => 32, 'h' => 32, 'mask' => 'ellipse' ) ) ) . '">';
    $favicon_tags .= '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url( $imgix->get_src( get_field( 'favicon', 'wp_settings' ), array( 'fm' => 'png', 'w' => 16, 'h' => 16, 'mask' => 'ellipse' ) ) ) . '">';
    $favicon_tags .= '<link rel="manifest" href="https://' . CDN_DOMAIN . '/core/template/twentyseven-theme/assets/icons/site.webmanifest">';
    $favicon_tags .= '<link rel="shortcut icon" href="https://' . CDN_DOMAIN . '/core/template/twentyseven-theme/assets/icons/favicon.ico">';
    $favicon_tags .= '<meta name="apple-mobile-web-app-title" content="' . $site_name . '">';
    $favicon_tags .= '<meta name="application-name" content="' . $site_name . '">';
    $favicon_tags .= '<meta name="msapplication-TileColor" content="#000000">';
    $favicon_tags .= '<meta name="msapplication-config" content="https://' . CDN_DOMAIN . '/core/template/twentyseven-theme/assets/icons/browserconfig.xml">';
    $favicon_tags .= '<meta name="theme-color" content="#000000" />';

    echo $favicon_tags;
}
add_action( 'admin_head', 'davidmc_icons', 20 );
add_action( 'login_head', 'davidmc_icons', 20 );


function my_custom_render_block($block_content, $block) {
    if ($block['blockName'] === 'core/heading') {
        // Get the content of the block
        $textContent = strip_tags($block_content);

        // Sanitize the textContent for use as an ID
        $sanitizedId = sanitize_title($textContent);

        // Modify the block content to include the ID
        $block_content = preg_replace('/<h([1-6])(.*?)>/', '<h$1 id="' . esc_attr($sanitizedId) . '"$2>', $block_content);
    }

    return $block_content;
}

add_filter('render_block', 'my_custom_render_block', 10, 2);


function my_save_post_toc($post_id, $post, $update) {
    // Avoiding autosave and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($post_id)) {
        return;
    }

    // Check if it's the correct post type, e.g., 'post' or 'page'
    if ($post->post_type !== 'post') {
        return;
    }

    // Run your TocGenerator here
    $markupFixer  = new TOC\MarkupFixer();
    $tocGenerator = new TOC\TocGenerator();
    $tocContent = $markupFixer->fix($post->post_content);
    $tocContent = $tocGenerator->getHtmlMenu($tocContent, 1, 3);

    // Store the TOC content in post meta
    update_post_meta($post_id, '_my_toc_content', $tocContent);
}
add_action('save_post', 'my_save_post_toc', 10, 3);
add_action('publish_post', 'my_save_post_toc', 10, 3);


function my_register_toc_rest_field() {
    register_rest_field('post', 'toc_content', array(
        'get_callback' => 'my_get_toc_content',
        'schema' => null,
    ));
}

function my_get_toc_content($object, $field_name, $request) {
    // Check if it's a request for a single post by slug
    if (isset($request['slug'])) {
        return get_post_meta($object['id'], '_my_toc_content', true);
    }
    return null;
}
add_action('rest_api_init', 'my_register_toc_rest_field');



function custom_per_page($query_params) {
    // Define the default per page value based on the query type
    $default_per_page = 9999; // Default value for most query types

    // Set the default per page value
    if ( empty($_GET['per_page']) ) {
        $query_params['number'] = $default_per_page;
    }
    if ( empty($_GET['per_page']) ) {
        $query_params['posts_per_page'] = $default_per_page;
    }

    return $query_params;
}

// Add the filter for different query types
add_filter('rest_post_query', 'custom_per_page');
add_filter('rest_page_query', 'custom_per_page');
add_filter('rest_user_query', 'custom_per_page');
add_filter('rest_category_query', 'custom_per_page');
add_filter('rest_post_tag_query', 'custom_per_page');
add_filter('rest_post_search_query', 'custom_per_page');
add_filter('rest_term_search_query', 'custom_per_page');
