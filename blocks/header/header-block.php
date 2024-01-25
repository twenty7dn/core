<?php

// Hook to enqueue your scripts
function custom_title_block_enqueue_block_editor_assets() {
    wp_enqueue_style(
        'my-custom-gutenberg-styles', // Handle for your stylesheet
        plugins_url( 'editor.css', __FILE__ ), // Path to your stylesheet
        array(), // Dependencies
        filemtime( plugin_dir_path( __FILE__ ) . 'editor.css' ) // Version number
    );
    wp_enqueue_script(
        'my-custom-gutenberg-scripts', // Handle for your stylesheet
        plugins_url( 'editor.js', __FILE__ ), // Path to your stylesheet
        array('wp-blocks', 'wp-dom-ready', 'wp-edit-post'), // Dependencies
        filemtime( plugin_dir_path( __FILE__ ) . 'editor.js' ), // Version number
        true
    );
}
add_action( 'enqueue_block_editor_assets', 'custom_title_block_enqueue_block_editor_assets' );

function save_custom_post_title_field($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['editor-post-title'])) {
        $custom_post_title = sanitize_text_field($_POST['editor-post-title']);
        wp_update_post(array(
            'ID' => $post_id,
            'post_title' => $custom_post_title,
        ));
    }
}
add_action('publish_post', 'save_custom_post_title_field');

function set_featured_image_callback(): void
{
    // Verify the AJAX nonce (for security)
//    check_ajax_referer('set_featured_image_nonce', 'security');

    // Set the featured image for the post
    if (intval($_POST['post_id']) > 0 && intval($_POST['image_id']) > 0) {
        set_post_thumbnail(intval($_POST['post_id']), intval($_POST['image_id']));
    } else {
        delete_post_thumbnail(intval($_POST['post_id']));
    }

    // Always exit to prevent further execution
    wp_die();
}

// Hook the server-side function to the 'wp_ajax_' action
add_action('wp_ajax_set_featured_image', 'set_featured_image_callback');
add_action('wp_ajax_nopriv_set_featured_image', 'set_featured_image_callback');


// Add a custom meta box to the post editor screen
function custom_featured_image_meta_box() {
    add_meta_box(
        'custom-featured-image-meta-box',
        'Custom Featured Image Data',
        'render_custom_featured_image_meta_box',
        'post', // Replace 'post' with the post type you want to add this to
        'side',  // Change the context (e.g., 'normal', 'side', 'advanced')
        'default'
    );
}

// Render the content of the custom meta box
function render_custom_featured_image_meta_box($post) {
    $fp = get_post_meta( get_post_thumbnail_id( $post->ID ), 'acf_wpo_sweet_spot', true ) ? get_post_meta( get_post_thumbnail_id( $post->ID ), 'acf_wpo_sweet_spot', true ) : array( 'x' => 50, 'y' => 50 );
    // Get the existing custom featured image data
    $custom_featured_image_data = json_encode( array(
        'post_id' => $post->ID,
        'image_id' => get_post_thumbnail_id( $post->ID ),
        'image_url' => get_the_post_thumbnail_url( $post->ID, 'full' ),
        'focal_point' => array(
            'x' => intval( $fp['x'] ),
            'y' => intval( $fp['y'] )
        )
    ) );

    // Output the JSON data
    ?>
    <textarea id="cover-data" name="custom-featured-image-data" rows="5" readonly disabled style="width: 100%;"><?php echo esc_textarea($custom_featured_image_data); ?></textarea>
    <?php
}

// Save the custom featured image data when the post is saved or updated
function save_custom_featured_image_data($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
}

// Hook the functions to WordPress actions
add_action('add_meta_boxes', 'custom_featured_image_meta_box');

// Function to get the custom field acf_wpo_sweet_spot for an attachment.
function get_attachment_custom_field() {
    // Get the attachment ID from the AJAX request.
    $attachment_id = intval($_POST['image_id']);

    // Retrieve the custom field value using get_post_meta.
    $sweet_spot = get_post_meta($attachment_id, 'acf_wpo_sweet_spot', true) ? get_post_meta($attachment_id, 'acf_wpo_sweet_spot', true) : array( 'x' => 50, 'y' => 50 );

    $data = array(
        'x' => intval( $sweet_spot['x'] ),
        'y' => intval( $sweet_spot['y'] )
    );

    // Return the custom field value as a JSON response.
    wp_send_json($data);
    wp_die();
}

// Hook the AJAX handler to both logged-in and non-logged-in users.
add_action('wp_ajax_get_attachment_custom_field', 'get_attachment_custom_field');
add_action('wp_ajax_nopriv_get_attachment_custom_field', 'get_attachment_custom_field');


add_action('wp_ajax_toggle_post_sticky_status', 'toggle_post_sticky_status_callback');

function toggle_post_sticky_status_callback() {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if ($post_id && current_user_can('edit_post', $post_id)) {
        if (is_sticky($post_id)) {
            unstick_post($post_id);
            echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-opacity="0.35" d="M8,6.2V4H7V2H17V4H16V12L18,14V16H17.8L14,12.2V4H10V8.2L8,6.2M20,20.7L18.7,22L12.8,16.1V22H11.2V16H6V14L8,12V11.3L2,5.3L3.3,4L20,20.7M8.8,14H10.6L9.7,13.1L8.8,14Z" /></svg>';
        } else {
            stick_post($post_id);
            echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16,12V4H17V2H7V4H8V12L6,14V16H11.2V22H12.8V16H18V14L16,12Z" /></svg>';
        }
    }
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16,12V4H17V2H7V4H8V12L6,14V16H11.2V22H12.8V16H18V14L16,12Z" /></svg>';
    wp_die(); // Always include wp_die() in AJAX functions to terminate the script.
}
