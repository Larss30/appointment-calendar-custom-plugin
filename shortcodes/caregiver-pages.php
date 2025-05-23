<?php
function display_mec_caregivers() {
    // Get all terms in caregiver taxonomy
    $search_caregiver = isset($_GET['search_caregiver']) ? sanitize_text_field($_GET['search_caregiver']) : '';

    $terms = get_terms(array(
        'taxonomy' => 'caregiver',
        'hide_empty' => false,
        'search' => $search_caregiver,
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
        $output .= '<a href="/account/caregiver-details?id=' . esc_attr($term->term_id) . '" class="profile-single-card">';
        $output .= '<div class="profile-card">';
        $output .= '<img src="' . esc_url($profile_picture) . '" alt="' . esc_attr($user->first_name . ' ' . $user->last_name) . '">';
        $output .= '<div class="profile-card-details">';
        $output .= '<h4>' . esc_attr($user->first_name . ' ' . $user->last_name) . '</h4>';
        $output .= $address ? '<p>' . esc_html($address) . '</p>' : '';

        $output .= '</div></div>';
        $output .= '</a>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('mec_caregivers', 'display_mec_caregivers');

// Register shortcode for caregiver search bar
add_shortcode('caregiver_search_bar', 'display_caregiver_search_bar');
function display_caregiver_search_bar() {
    $search_caregiver = isset($_GET['search_caregiver']) ? sanitize_text_field($_GET['search_caregiver']) : '';

    $output = '<form method="GET" action="">';
    $output .= '<input type="text" name="search_caregiver" value="' . esc_attr($search_caregiver) . '" placeholder="Search...">';
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

// Register shortcode for single caregiver page header
function display_single_caregiver_header() {
    if (!isset($_GET['id'])) {
        return '<p>404 error</p>';
    }

    $term_id = intval($_GET['id']);
    $term = get_term($term_id, 'caregiver');

    if (!$term || is_wp_error($term)) {
        return '<p>Invalid caregiver ID.</p>';
    }

    // Get user meta
    $user = get_user_by_term($term_id);
    $user_profile_picture_id = get_user_meta($user->ID, 'user_profile_picture', true);
    $user_profile_picture = wp_get_attachment_url($user_profile_picture_id);
    $user_profile_picture = $user_profile_picture ? $user_profile_picture : plugin_dir_url(__FILE__) . '../assets/images/profile-sample.png';

    // Initialize output
    $output = '<div class="single-profile-header">';
    $output .= '<h2>' . esc_attr($user->first_name . ' ' . $user->last_name) . '</h2>';
    $output .= '<img src="' . esc_url($user_profile_picture) . '" alt="User Profile Picture">';
    $output .= $user->ID == get_current_user_id() ? '<a href="/edit-profile/">Edit Profile</a>' : '';
    $output .= current_user_can('administrator') ? '<a href="/account/edit-caregiver-user/?id='. $user->ID . '">Edit Profile</a>' : '';
    $output .= '</div>';

    return $output;
}
add_shortcode('single_caregiver_header', 'display_single_caregiver_header');

// Register shortcode for single caregiver page content
function display_single_caregiver_content() {
    if (!isset($_GET['id'])) {
        return '<p>No caregiver ID provided.</p>';
    }

    $term_id = intval($_GET['id']);
    $term = get_term($term_id, 'caregiver');

    if (!$term || is_wp_error($term)) {
        return '<p>Invalid caregiver ID.</p>';
    }

    $user = get_user_by_term($term_id);

    // Get term and user meta
    $message_sent = get_term_meta($term->term_id, 'message_sent', true);
    $temporary_password = get_term_meta($term->term_id, 'temporary_password', true);
    $username = $user->user_login;
    $background = get_user_meta($user->ID, 'background', true);
    $fun_facts = get_user_meta($user->id, 'fun_facts', true);
    $mobile_number = get_user_meta($user->id, 'phone_number', true);
    $email = $user->user_email;

    // Output
    
    if ($user){
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
        $output .= '<div class="background"><h4>Background</h4><p>' . esc_html($background ? $background : 'N/A') . '</p></div>';
        $output .= '<div class="fun-facts"><h4>Fun Facts</h4><p>' . esc_html($fun_facts ? $fun_facts : 'N/A') . '</p></div>';
        $output .= '<div class="group-content">';
        $output .= '<div class="mobile-number"><h4>Mobile Number</h4><p>' . esc_html($mobile_number ? $mobile_number : 'N/A') . '</p></div>';
        $output .= '<div class="email"><h4>Email</h4><p>' . esc_html($email ? $email : 'N/A') . '</p></div>';
        $output .= '</div></div>';
    
    } else {
        $output = '<div class="profile-content"><p>User Not Found. This might be a result of an existing user having the same name or email. Try <a href="/add-caregiver/">adding another caregiver</a></p></div>';
    }

    return $output;

}
add_shortcode('single_caregiver_content', 'display_single_caregiver_content');
?>
