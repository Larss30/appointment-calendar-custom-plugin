<?php

// applicant cards
function display_mec_applicants() {
    // Get all terms in caregiver taxonomy
    $search_applicant = isset($_GET['search_applicant']) ? sanitize_text_field($_GET['search_applicant']) : '';

    $args = array(
        'post_type' => 'applicants',
        'posts_per_page' => -1,
        's' => $search_applicant,
        'orderby' => 'ID',
        'order' => 'DESC'
    );
    $query = new WP_Query($args);

    // Initialize output
    $output = '<div class="profile-cards applicants">';
    $placeholder_image = plugin_dir_url(__FILE__) . '../assets/images/profile-sample.png';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $address = get_post_meta($post_id, 'address', true);
            $profile_picture = get_post_meta($post_id, 'user_profile_picture', true);
            $profile_picture = $profile_picture ? wp_get_attachment_url($profile_picture) : plugin_dir_url(__FILE__) . '../assets/images/profile-sample.png';
            $application_status = get_post_meta($post_id, 'application_status', true);

            // icons
            $approved_icon = '/wp-content/uploads/2025/01/approved.png';
            $pending_icon = '/wp-content/uploads/2025/01/pending.png';
            $declined_icon = '/wp-content/uploads/2025/01/declined.png';

            // add icons
            $application_status = $application_status == 'approved' ? '<img src="' . esc_url($approved_icon) . '" alt="Approved"> Approved' : ($application_status == 'pending' ? '<img src="' . esc_url($pending_icon) . '" alt="Pending"> Pending' : '<img src="' . esc_url($declined_icon) . '" alt="Declined"> Declined');

            // Append post details to output
            $output .= '<a href="/account/applicant-details?id=' . esc_attr($post_id) . '" class="profile-single-card">';
            $output .= '<div class="profile-card">';
            $output .= '<img src="' . esc_url($profile_picture) . '" alt="' . esc_attr(get_the_title()) . '">';
            $output .= '<div class="profile-card-details">';
            $output .= '<h4>' . esc_html(get_the_title()) . '</h4>';
            if ($address) {
                $output .= '<p>' . esc_html($address) . '</p>';
            }

            $output .= '</div>';
            $output .= '<div class="application-status">' . $application_status . '</div>';
            $output .= '</div>';
            $output .= '</a>';
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>No applicants found.</p>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('mec_applicants', 'display_mec_applicants');

// applicant search bar
add_shortcode('applicant_search_bar', 'display_applicant_search_bar');
function display_applicant_search_bar() {
    $search_applicant = isset($_GET['search_applicant']) ? sanitize_text_field($_GET['search_applicant']) : '';

    $output = '<form method="GET" action="">';
    $output .= '<input type="text" name="search_applicant" value="' . esc_attr($search_applicant) . '" placeholder="Search...">';
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

// single caregiver page header
function display_single_applicant_header() {
    if (!isset($_GET['id'])) {
        return '<p>404 error</p>';
    }

    $post_id = intval($_GET['id']);
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'applicants') {
        return '<p>Invalid applicant ID.</p>';
    }

    // Get post meta
    $profile_picture = get_post_meta($post_id, 'user_profile_picture', true);
    $profile_picture = $profile_picture ? wp_get_attachment_url($profile_picture) : plugin_dir_url(__FILE__) . '../assets/images/profile-sample.png';

    // Initialize output
    $output = '<div class="single-profile-header applicant">';
    $output .= '<h2>' . esc_html($post->post_title) . '</h2>';
    $output .= '<img src="' . esc_url($profile_picture) . '" alt="User Profile Picture">';
    $output .= '</div>';

    return $output;
}
add_shortcode('single_applicant_header', 'display_single_applicant_header');

// single applicant page main content
function display_single_applicant_content() {
    if (!isset($_GET['id'])) {
        return '<p>No applicant ID provided.</p>';
    }

    $post_id = intval($_GET['id']);
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'applicants') {
        return '<p>Invalid applicant ID.</p>';
    }

    // Get post meta
    $background = get_post_meta($post_id, 'description', true);
    $fun_facts = get_post_meta($post_id, 'fun_facts', true);
    $mobile_number = get_post_meta($post_id, 'phone_number', true);
    $email = get_post_meta($post_id, 'email', true);
    $application_status = get_post_meta($post_id, 'application_status', true);
    $date_created = get_post_meta($post_id, 'date_created', true);
    $date_created = date('F j, Y', strtotime($date_created));
    $date_approved = get_post_meta($post_id, 'date_approved', true);
    $date_approved = date('F j, Y', strtotime($date_approved));
    $registering_as = get_post_meta($post_id, 'registering_as', true);

    // icons
    $approved_icon = '/wp-content/uploads/2025/01/approved.png';
    $pending_icon = '/wp-content/uploads/2025/01/pending.png';
    $declined_icon = '/wp-content/uploads/2025/01/declined.png';

    // add icons
    $application_output = $application_status == 'approved' ? '<img src="' . esc_url($approved_icon) . '" alt="Approved"> Approved' : ($application_status == 'pending' ? '<img src="' . esc_url($pending_icon) . '" alt="Pending"> Pending' : '<img src="' . esc_url($declined_icon) . '" alt="Declined"> Declined');

    // Output
    $output = '<div class="profile-content applicant">';
    $output .= '<div class="application-status">';
    $output .= '<p>Registering As: '. $registering_as .'</p>';
    $output .= '<p>Status: ' . $application_output . '</p>';
    $output .= '<p>Date Applied: ' . $date_created . '</p>';
    $output .= $application_status == 'approved' ? '<p>Date Approved: ' . $date_approved . '</p>' : '';
    $output .= '</div>';
    $output .= '<h3 class="com-acc">Community Account</h3>';
    $output .= '<div class="background"><h4>Background</h4><p>' . esc_html($background ? $background : 'N/A') . '</p></div>';
    $output .= '<div class="fun-facts"><h4>Fun Facts</h4><p>' . esc_html($fun_facts ? $fun_facts : 'N/A') . '</p></div>';
    $output .= '<div class="group-content">';
    $output .= '<div class="mobile-number"><h4>Mobile Number</h4><p>' . esc_html($mobile_number ? $mobile_number : 'N/A') . '</p></div>';
    $output .= '<div class="email"><h4>Email</h4><p>' . esc_html($email ? $email : 'N/A') . '</p></div>';
    $output .= '</div></div>';

    if ($application_status == 'pending') {
        $output .= '<div class="approve-decline-buttons">';
        $output .= do_shortcode('[jet_fb_form form_id="2283" submit_type="reload" required_mark="*" fields_layout="column" fields_label_tag="div" enable_progress="" clear=""]');
        $output .= do_shortcode('[jet_fb_form form_id="2318" submit_type="reload" required_mark="*" fields_layout="column" fields_label_tag="div" enable_progress="" clear=""]');
        $output .= '</div>';
    }

    return $output;
}
add_shortcode('single_applicant_content', 'display_single_applicant_content');
?>
