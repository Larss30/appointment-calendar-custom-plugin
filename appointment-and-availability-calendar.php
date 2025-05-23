<?php

/**
* Plugin Name: Appointment and Availability Calendar
* Description: A plugin that allows caregivers and care recipients to book appointments and manage availability.
* Version: 1.2
* Author: MLK Marketing
**/

defined('ABSPATH') or die('No script kiddies please!');

include_once(plugin_dir_path(__FILE__) . 'includes/redirects.php');
include_once(plugin_dir_path(__FILE__) . 'includes/jet-form-hooks.php');

include_once(plugin_dir_path(__FILE__) . 'shortcodes/caregiver-customer-login.php');
include_once(plugin_dir_path(__FILE__) . 'shortcodes/care-recipient-pages.php');
include_once(plugin_dir_path(__FILE__) . 'shortcodes/caregiver-pages.php');
include_once(plugin_dir_path(__FILE__) . 'shortcodes/applicants-pages.php');
include_once(plugin_dir_path(__FILE__) . 'shortcodes/roster-schedule.php');
include_once(plugin_dir_path(__FILE__) . 'shortcodes/edit-profile-form.php');
include_once(plugin_dir_path(__FILE__) . 'shortcodes/availability.php');

$placeholder_profile = plugin_dir_url(__FILE__) . '../assets/images/profile-sample.png';

function mec_add_user_from_taxonomy_enqueue_scripts() {
    wp_enqueue_style('mec-add-user-from-taxonomy-style', plugin_dir_url(__FILE__) . 'css/styles.css');
    wp_enqueue_script('custom-popup-script', plugin_dir_url(__FILE__) . '/js/custom-popup.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'mec_add_user_from_taxonomy_enqueue_scripts');

// Create a new user on new Caregiver taxonomy
function create_user_on_new_caregiver_taxonomy($term_id) {

    $taxonomy = 'caregiver';

    $term = get_term($term_id, $taxonomy);

    $term_slug = $term->slug;
    
    $username = sanitize_user($term_slug);
    $email = $add_form_details['email'] ? $add_form_details['email'] : isset($_POST['email']);

    $date_today = date('Ymd');
    $password = 'klsplaceh0ld3rp4ass' . $term_id . $date_today;

    if (username_exists($username) || email_exists($email)) {
        return;
    }

    $user_id = wp_create_user($username, $password, $email);

    if(!is_wp_error($user_id)){
        
        // adds linked taxonomy meta as user role 'care_giver'
        $user = new WP_User($user_id);
        $user->set_role('care_giver');

        update_term_meta($term_id, 'connected_user_id', $user_id);

        // Save the term ID in the user's meta
        update_user_meta($user_id, 'connected_taxonomy_term_id', $term_id);

        return; 
        
    }
    
}
// Create a new user on new Care Recipient taxonomy
function create_user_on_new_customer_taxonomy($term_id) {

    $taxonomy = 'care_recipient';

    $term = get_term($term_id, $taxonomy);

    $term_slug = $term->slug;
    
    $username = sanitize_user($term_slug);
    $email = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '' ;
    $date_today = date('Ymd');
    $password = 'klsplaceh0ld3rp4ass' . $term_id . $date_today;

    if (username_exists($username) || email_exists($email)) { 
        return;
    }

    $user_id = wp_create_user($username, $password, $email);

    if(!is_wp_error($user_id)){
        
        // adds linked taxonomy meta as user role 'customer'
        $user = new WP_User($user_id);
        $user->set_role('customer');

        // save the linked user ID for future reference
        update_term_meta($term_id, 'connected_user_id', $user_id);
        update_user_meta($user_id, 'connected_taxonomy_term_id', $term_id);

    }

    // Save the term ID in the user's meta
    update_user_meta($user_id, 'connected_taxonomy_term_id', $term_id);

    return;

}
add_action('created_care_recipient', 'create_user_on_new_customer_taxonomy', 10, 3);
add_action('created_caregiver', 'create_user_on_new_caregiver_taxonomy', 10, 3);


// Add User ID column to the Caregiver taxonomy
add_filter('manage_edit-caregiver_columns', 'add_user_id_column_to_caregiver_taxonomy');
add_action('manage_caregiver_custom_column', 'show_user_id_column_data_caregiver', 10, 3);
function add_user_id_column_to_caregiver_taxonomy($columns) {
    // Insert the column at the appropriate place
    $columns['user_id'] = 'User ID'; // Add 'User ID' column

    return $columns;
}
function show_user_id_column_data_caregiver($content, $column_name, $term_id) {
    if ($column_name === 'user_id') {
        // Retrieve the connected user ID from term meta
        $user_id = get_term_meta($term_id, 'connected_user_id', true);

        // Output the user ID or any other information you'd like to display
        $content = $user_id ? $user_id : 'No User Connected';
    }

    return $content;
}

// Add User ID column to the Customer taxonomy

add_filter('manage_edit-care_recipient_columns', 'add_user_id_column_to_customer_taxonomy');
add_action('manage_care_recipient_custom_column', 'show_user_id_column_data_customer', 10, 3);

function add_user_id_column_to_customer_taxonomy($columns) {
    // Insert the column at the appropriate place
    $columns['user_id'] = 'User ID'; // Add 'User ID' column

    return $columns;
}
function show_user_id_column_data_customer($content, $column_name, $term_id) {
    if ($column_name === 'user_id') {
        // Retrieve the connected user ID from term meta
        $user_id = get_term_meta($term_id,  'connected_user_id', single: true);

        // Output the user ID or any other information you'd like to display
        $content = $user_id ? $user_id : 'No User Connected';
    }

    return $content;
}

add_filter('manage_users_columns', 'add_connected_user_column');
add_action('manage_users_custom_column', 'show_connected_user_column_data', 10, 3);

function add_connected_user_column($columns) {
    // Add a custom column for 'connected_term_id'
    $columns['connected_term_id'] = 'Connected Term ID'; // Column header
    return $columns;
}

function show_connected_user_column_data($value, $column_name, $user_id) {
    if ($column_name === 'connected_term_id') {
        // Retrieve the connected term ID for this user (or any custom meta)
        $term_id = get_user_meta($user_id, 'connected_taxonomy_term_id', true);
        $caregiver_term = get_term($term_id, 'caregiver');
        $customer_term = get_term($term_id, 'care_recipient');
        $term = $caregiver_term ? $caregiver_term : $customer_term;
        
        // If a term is connected, display its name or ID
        if ($term && !is_wp_error($term)) {
            $value = $term->term_id;
        } else {
            $value = 'No Term Connected';
        }
    }
    return $value;
}

// helper - get user by term id
function get_user_by_term($term_id) {
    $user_id = get_term_meta($term_id, 'connected_user_id', true);
    return $user_id ? get_user_by('ID', $user_id) : null;
}

// helper - get term by user id
function get_term_by_user($user_id, $taxonomy) {
    $term_id = get_user_meta($user_id, 'connected_taxonomy_term_id', true);
    return $term_id ? get_term($term_id, $taxonomy) : null;
}