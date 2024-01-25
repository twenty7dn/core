<?php
/**
 * Advanced Custom Fields
 */

// define fields

// add location rules
// Step 1: Add a new location rule type
add_filter('acf/location/rule_types', 'my_acf_custom_location_types');
function my_acf_custom_location_types($choices) {
    // Add a new location rule under the 'Forms' category
    $choices['Forms']['wp_options'] = 'Settings Page';
    return $choices;
}

// Step 2: Define the possible values for your custom rule
add_filter('acf/location/rule_values/wp_options', 'my_acf_custom_location_values');
function my_acf_custom_location_values($choices) {
    // Define the values that your custom location can have
    $choices['general'] = 'General';
    return $choices;
}