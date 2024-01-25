<?php
/**
 * Advanced Custom Fields Options Pages
 */

class ACF_Custom_Settings {

    private $field_group_key;
    private $field_group_name;
    private $settings_page;
    private $group_instruction;

    public function __construct($args = array()) {
        $this->field_group_key = $args['group_ID'] ?? '';
        $this->field_group_name = $args['group_name'] ?? '';
        $this->group_instruction = $args['group_instruction'] ?? '';
        $this->settings_page = $args['settings_page'] ?? 'general';

        add_action('admin_init', array($this, 'add_acf_fields_to_general_settings'));
        add_action('admin_init', array($this, 'save_acf_fields'));
    }

    public function save_acf_fields() {
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page == "options.php" && $_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!current_user_can('manage_options')) {
                return;
            }

            $fields = acf_get_fields($this->field_group_key);

            foreach ($fields as $field) {
                if ($field['required'] && trim($_POST['acf'][$field['key']]) == '') {
                    // Required field is empty
                    // Add a WordPress settings error
                    add_settings_error(
                        'my-settings-errors',
                        esc_attr($field['key']),
                        'Error: ' . $field['label'] . ' is required.',
                        'error'
                    );
                } else {
                    // Save the field value
                    update_field($field['key'], $_POST['acf'][$field['key']] ?? NULL, 'wp_settings');
                }
            }

            do_action('save_options');

            // Continue with the normal processing if there are no errors
        }
    }


    public function add_acf_fields_to_general_settings() {
        if (!$this->field_group_key) {
            return;
        }

        $field_group = acf_get_field_group($this->field_group_key);
        if (!$field_group) {
            return;
        }

        add_settings_section(
            'acf_custom_section_' . $this->field_group_key,
            $this->field_group_name,
            array($this, 'acf_custom_section_callback'),
            $this->settings_page
        );

        $fields = acf_get_fields($this->field_group_key);
        foreach ($fields as $field) {
            if ( $field['required'] ) {
                $field['label'] = $field['label'] . '<span class="required">&nbsp;*</span>';
            }

            $clean_field = $field;
            $clean_field['label'] = false;
            $clean_field['instructions'] = false;

            add_settings_field(
                $field['key'],
                $field['label'] . sprintf( '<p class="description">%s</p>', esc_html( $field['instructions'] ) ),
                array($this, 'render_acf_field'),
                $this->settings_page,
                'acf_custom_section_' . $this->field_group_key,
                array('field' => $clean_field)
            );

            register_setting($this->settings_page, $field['name']);
        }
    }

    public function acf_custom_section_callback() {
        if ( !empty( $this->group_instruction ) ) {
            echo '<p>' . $this->group_instruction . '</p>';
        }
    }

    public function render_acf_field($args) {
        $field = $args['field'];

//        error_log( print_r( $this->settings_page ) );

        // Retrieve the current value from the database
        $value = get_field($field['name'], 'wp_settings');

        // Check if there is a saved value. If not, use the default value if set
        if ($value === false && isset($field['default_value'])) {
            $value = $field['default_value'];
        }

        // Set the retrieved value to the field
        $field['value'] = $value;

        // Render the field with the current value
        acf_render_field_wrap($field);
    }

}

// Fetch all ACF field groups
$field_groups = acf_get_field_groups();

foreach ($field_groups as $field_group) {
    // Loop through each location set for the field group
    foreach ($field_group['location'] as $locations) {
        foreach ($locations as $location) {
            // Check for a specific location type (e.g., options_page)
            if ($location['param'] == 'wp_options') {
                // Initialize ACF_Custom_Settings for this field group
                new ACF_Custom_Settings(array(
                    'group_ID' => $field_group['key'],
                    'group_name' => $field_group['acfe_display_title'] ? $field_group['acfe_display_title'] : $field_group['title'],
                    'group_instruction' => $field_group['description'],
                    'settings_page' => $location['value'] // Adjust as needed
                ));
                break 2; // Break out of both inner loops
            }
        }
    }
}

add_action('acf/update_value', function($value, $post_id, $field) {
    // Check if the updated field is 'icon' and it is within 'wp_settings'
    if ($field['name'] == 'icon' && $post_id == 'wp_settings') {
        // To prevent recursion, add a static variable
        static $updating = false;

        if (!$updating) {
            $updating = true;

            // Your custom code here
            // Example: update the field with the URL of the attachment image
            $data = get_option( 'uip-global-settings' );
            $data['site-settings']->general->globalLogo->url = wp_get_attachment_image_url( get_field( $field['name'], $post_id ), 'full' );
            $data['site-settings']->general->globalLogoDarkMode->url = wp_get_attachment_image_url( get_field( $field['name'], $post_id ), 'full' );
            update_option('uip-global-settings', $data);
            update_option('uip-global-settings', $data);
            update_option('uip-global-settings', $data);

            $updating = false;
        }
    }
    return $value;
}, 10, 3);
