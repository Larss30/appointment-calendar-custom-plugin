<?php
function display_mec_care_recipients() {
    // Get all terms in care_recipient taxonomy
    $search_care_recipient = isset($_GET['search_care_recipient']) ? sanitize_text_field($_GET['search_care_recipient']) : '';

    $terms = get_terms(array(
        'taxonomy' => 'care_recipient',
        'hide_empty' => false,
        'search' => $search_care_recipient,
        'orderby' => 'ID',
        'order' => 'DESC'

    ));

    // Initialize output
    $output = '<div class="profile-cards">';
    $placeholder_image = plugin_dir_url(__FILE__) . '../assets/images/profile-sample.png';

    foreach ($terms as $term) {
        // Get term meta
        $user = get_user_by_term($term->term_id);
        $profile_picture = get_user_meta($user->ID, 'user_profile_picture', true);
        $profile_picture = wp_get_attachment_url($profile_picture);
        $address = get_user_meta($user->ID, 'address', true);

        // Use placeholder if no profile picture
        if (!$profile_picture) {
            $profile_picture = $placeholder_image;
        }

        // Append term details to output
        $output .= '<a href="/account/care-recipient-details?id=' . esc_attr($term->term_id) . '" class="profile-single-card">';
        $output .= '<div class="profile-card">';
        $output .= '<img src="' . esc_url($profile_picture) . '" alt="' . esc_attr($user->first_name . ' ' . $user->last_name) . '">';
        $output .= '<div class="profile-card-details">';
        $output .= '<h4>' . esc_attr($user->first_name . ' ' . $user->last_name) . '</h4>';
        if ($address) {
            $output .= '<p>' . esc_html($address) . '</p>';
        }

        $output .= '</div></div>';
        $output .= '</a>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('mec_care_recipients', 'display_mec_care_recipients');

// Register shortcode for care recipient search bar
add_shortcode('care_recipient_search_bar', 'display_care_recipient_search_bar');
function display_care_recipient_search_bar() {
    $search_care_recipient = isset($_GET['search_care_recipient']) ? sanitize_text_field($_GET['search_care_recipient']) : '';

    $output = '<form method="GET" action="">';
    $output .= '<input type="text" name="search_care_recipient" value="' . esc_attr($search_care_recipient) . '" placeholder="Search...">';
    $output .= '<button type="submit"><img src="/wp-content/uploads/2025/02/search-icon.png" alt="search icon"></button>';
    $output .= '</form>';

    ?>
        <style>
            form {
            display:flex;
            }
            form button, form button:hover{
                position:absolute;
                height: 40px;
                padding: 0 20px;
            }
            form button, form button:hover, form button:focus{
                background: none;
            }
            form input{
                padding-left: 60px !important;
            }
        </style>
    <?php

    return $output;
}

// Register shortcode for single care recipient page header
function display_single_care_recipient_header() {
    if (!isset($_GET['id'])) {
        return '<p>404 error</p>';
    }

    $term_id = intval($_GET['id']);
    $term = get_term($term_id, 'care_recipient');

    if (!$term || is_wp_error($term)) {
        return '<p>Invalid care recipient ID.</p>';
    }

    // Get term meta
    $user = get_user_by_term($term_id);
    $user_profile_picture = get_user_meta($user->ID, 'user_profile_picture', true);
    $user_profile_picture = wp_get_attachment_url($user_profile_picture);
    $placeholder_image = plugin_dir_url(__FILE__) . '../assets/images/profile-sample.png';

    // Use placeholder if no profile picture
    if (!$user_profile_picture) {
        $user_profile_picture = $placeholder_image;
    }

    

    // Initialize output
    $output = '<div class="single-profile-header">';
    $output .= '<h2>' .  esc_attr($user->first_name . ' ' . $user->last_name) . '</h2>';
    $output .= '<img src="' . esc_url($user_profile_picture) . '" alt="User Profile Picture">';
    $output .= $user->ID == get_current_user_id() ? '<a href="/edit-profile/">Edit Profile</a>' : '';
    $output .= current_user_can('administrator') ? '<a href="/account/edit-care-recipient-user/?id='. $user->ID . '">Edit Profile</a>' : '';
    $output .= '</div>';

    return $output;
}
add_shortcode('single_care_recipient_header', 'display_single_care_recipient_header');


// Register shortcode for single care recipient page content
function display_single_care_recipient_content() {
    if (!isset($_GET['id'])) {
        return '<p>No care recipient ID provided.</p>';
    }

    $term_id = intval($_GET['id']);
    $term = get_term($term_id, 'care_recipient');
    $user = get_user_by_term($term_id);

    if (!$term || is_wp_error($term)) {
        return '<p>Invalid care recipient ID.</p>';
    }

    // Get user meta
    $medical_info = get_user_meta($user->ID, 'medical_info', true);
    $alerts_risks = get_user_meta($user->ID, 'alerts_risks', true);
    $fun_facts = get_user_meta($user->ID, 'fun_facts', true);
    $attached_form_id = get_user_meta($user->ID, 'user_files', true);
    $mobile_number = get_user_meta($user->ID, 'phone_number', true);
    $shift_details = get_user_meta($user->ID, 'shift_details', true);
    $address = get_user_meta($user->ID, 'address', true);
    $start_time = get_user_meta($user->ID, 'start_time', true) ? get_user_meta($user->ID, 'start_time', true) : null;
    $end_time = get_user_meta($user->ID, 'end_time', true) ? get_user_meta($user->ID, 'end_time', true) : null;
    $personal_preferences = get_user_meta($user->ID, 'personal_preferences', true);
    $start_time_formatted = $start_time ? date('F d, Y h:i A', $start_time) : null; 
    $end_time_formatted = $end_time ? date('F d, Y h:i A', $end_time) : null;

    $message_sent = get_term_meta($term->term_id, 'message_sent', true);
    $username = $user->user_login;
    $temporary_password = get_term_meta($term->term_id, 'temporary_password', true);
    
    $time = ($start_time_formatted || $end_time_formatted) ? $start_time_formatted . " - " . $end_time_formatted : 'No time/appointment added';

    // Output

    if($user){

        $output = '<div class="profile-content">';
        if (current_user_can('administrator') && ($message_sent == 'false' || $message_sent == null)) {
            $output .= '<div class="secure-account-message">';
            $output .= "<p>This user's login credentials are:</p>";
            $output .= "<ul><li>Username: ". $username ."</li><li>Password: ". $temporary_password ."</li></ul>";
            $output .= '<p>Please forward these credentials to the user and advise them to update their password <strong>AS SOON AS POSSIBLE</strong>. Click the button below once the user has safely secured their account.</p>';
            $output .= do_shortcode('[jet_fb_form form_id="3810" submit_type="reload" required_mark="*" fields_layout="column" fields_label_tag="div" enable_progress="" clear=""]');
            $output .= '</div>';
        };
        $output .= '<h3 class="com-acc">Community Account</h3>';
        $output .= '<div class="medical-info"><h4>Medical Info</h4><p>' . esc_html($medical_info ?: 'N/A') . '</p></div>';
        $output .= '<div class="alerts-risks"><h4>Alerts/Risks</h4><p>' . esc_html($alerts_risks ?: 'N/A') . '</p></div>';
        $output .= '<div class="fun-facts"><h4>Fun Facts</h4><p>' . esc_html($fun_facts ?: 'N/A') . '</p></div>';
        $output .= '<div class="single-profile-attachments">';
        if ($attached_form_id) {
            $output .= '<div class="attached-form"><h4>Attached Form</h4> <p>';
            $attached_form_ids = explode(',', $attached_form_id);
            foreach ($attached_form_ids as $form_id) {
                $form_url = wp_get_attachment_url($form_id);
                $output .=  '<a href="' . esc_url($form_url) . '">' . basename($form_url) . '</a><br>';
            }
            $output .=  '</p></div>';
        } else {
            $output .= '<div class="attached-form"><h4>Attached Form</h4><p>N/A</p></div>';
        }
        $output .= '</div>';
        $output .= '<div class="mobile-number"><h4>Mobile Number</h4><p>' . esc_html($mobile_number ?: 'N/A') . '</p></div>';
        $output .= '<div class="group-content">';
        $output .= '<div class="shift-details"><h4>Shift Details</h4><p>' . esc_html($shift_details ?: 'No appointment added assigned to this Recipient') . '</p></div>';
        $output .= '<div class="address"><h4>Address</h4><p>' . esc_html($address ?: 'N/A') . '</p></div>';
        $output .= '<div class="time"><h4>Time</h4><p>' . esc_html($time ?: 'N/A') . '</p></div>';
        $output .= '</div>';
        $output .= '<div class="personal-preference"><h4>Personal Preference</h4><p>' . esc_html($personal_preferences ?: 'N/A') . '</p></div>';
        $output .= '</div></div>';
    
    } else {
        $output = '<div class="profile-content"><p>User Not Found. This might be a result of an existing user having the same name or email. Try <a href="/add-care-recipient/">adding another care recipient</a></p></div>';
    }
    

    return $output;
}
add_shortcode('single_care_recipient_content', 'display_single_care_recipient_content');