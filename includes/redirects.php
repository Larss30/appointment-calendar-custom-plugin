<?php 

    //redirect unauthorized users from single ticket page 
    add_action('template_redirect', 'redirect_single_ticket_page');

    function redirect_single_ticket_page() {
        global $post;
        $current_user = wp_get_current_user();
        if (is_singular('tickets') && !in_array('administrator', (array) $current_user->roles) && $post->post_author != get_current_user_id()) {
            wp_redirect(home_url('/account/ticket-support/'));
            exit();
        }
        if (is_singular('tickets') && !is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit();
        }
    }

    add_action('template_redirect', 'redirect_care_appointments_page');

    function redirect_care_appointments_page() {

        if (is_singular('care-appointments') && !is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit();
        }

        $current_user = wp_get_current_user();
        if (is_singular('care-appointments') && in_array('customer', (array) $current_user->roles)) {
            wp_redirect(home_url('/account/schedule/'));
            exit();
        }
        if (is_singular('care-appointments') && in_array('care_giver',  (array) $current_user->roles)) {

            global $post;

            $terms = get_the_terms($post->ID, 'caregiver');
            $caregiver_id = $terms ? $terms[0]->term_id : null;

            $caregiver_user_id = get_user_by_term($caregiver_id)->ID;

            if ($caregiver_user_id != get_current_user_id()) {
                wp_redirect(home_url('/account/schedule/'));
                exit();
            }
        }
    }

    add_action('template_redirect', 'redirect_care_avaiability_page');

    function redirect_care_avaiability_page() {

        if (is_singular('availability') && !is_user_logged_in()) {
            wp_redirect(home_url('/account/'));
            exit();
        }
    }


?>