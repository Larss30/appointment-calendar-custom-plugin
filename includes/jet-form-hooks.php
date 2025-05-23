<?php

// add new caregiver from jet form builder with hook name 'create_new_caregiver'
add_action('jet-form-builder/custom-action/create_new_caregiver', function ($request){

    $caregiver_name = sanitize_text_field($request['first_name'] . ' ' . $request['last_name']);

    $post_type = 'applicants';

    // $applicant_exists = post_exists($caregiver_name, $post_type);

        $post_id = wp_insert_post(array(
            'post_title' => $caregiver_name,
            'post_type' => $post_type,
            'post_status' => 'publish',
            'meta_input' => array(
                'email' => sanitize_email($request['email_address']),
                'date_created' => $request['date_created'],
                'application_status' => 'pending',
                'user_profile_picture' => $request['profile_photo'],
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'address' => $request['address'],
                'state' => $request['state'],
                'zip_code' => $request['zip_code'],
                'background'  => $request['background'],
                'phone_number' => $request['phone_number'],
                'description' => $request['bio'],
                'fun_facts' => $request['fun_facts'],
                'registering_as' => 'Caregiver',
            )
        ));

        wp_redirect(home_url('/account/applicant-details/?id=' . $post_id));

        exit;
});

// add new caregiver from jet form builder with hook name 'create_new_care_recipient'
add_action('jet-form-builder/custom-action/create_new_care_recipient', function ($request){

    $care_recipient_name = sanitize_text_field($request['first_name'] . ' ' . $request['last_name']);

    $taxonomy = 'care_recipient';
    $taxonomy_front_name = 'Care Recipient';

    $caregiver_exists = term_exists($care_recipient_name, $taxonomy);

    if ($caregiver_exists == 0 || $caregiver_exists == null) {
        $term = wp_insert_term($care_recipient_name, $taxonomy);
        // update_term_meta($term['term_id'], 'email', sanitize_email($request['email_address']));
        $term_id = $term['term_id'];
        
        
        create_user_on_new_customer_taxonomy($term_id);
        $user = get_user_by_term($term_id);

        update_user_meta($user->ID, 'first_name', $request['first_name']);
        update_user_meta($user->ID, 'last_name', $request['last_name']);
        wp_update_user(array(
            'ID' => $user->ID,
            'user_email' => $request['email_address']
        ));
        update_user_meta($user->ID, 'address', $request['address']);
        update_user_meta($user->ID, 'state', $request['state']);
        update_user_meta($user->ID, 'zip_code', $request['post_code']);
        update_user_meta($user->ID, 'phone_number', $request['phone_number']);
        update_user_meta($user->ID, 'description', $request['bio']);
        update_user_meta($user->ID, 'user_profile_picture', $request['profile_photo']);
        update_user_meta($user->ID, 'user_files', $request['upload_files']);
        update_user_meta($user->ID, 'fun_facts', $request['fun_facts']);
        update_user_meta($user->ID, 'alerts_risks', $request['alerts_risks']);
        update_user_meta($user->ID, 'personal_preferences', $request['personal_preferences']);
        update_user_meta($user->ID, 'medical_info', $request['medical_info']);

        $date_today = date('Ymd');
        update_term_meta($term_id, 'temporary_password', 'klsplaceh0ld3rp4ass' . $term_id . $date_today);
        update_term_meta($term_id, 'message_sent', 'false');

        // Redirect to the edit profile page with the term ID as a query parameter
        wp_redirect(home_url('/account/care-recipient-details/?id=' . $term_id));

        exit;


    } else{
        wp_redirect(home_url('/account/add-care-recipient/?error=caregiver_exists'));
        exit;
    }
});

// approve applicant from jet form builder with hook name 'approve_applicant'
add_action('jet-form-builder/custom-action/approve_applicant', function ($request){
    $post_id = intval($request['applicant_id']);

    $post = get_post($post_id);;

    // update applicant post meta
    update_post_meta($post->ID, 'application_status', 'approved');
    update_post_meta($post->ID, 'date_approved', $request['date_approved']);
    $registering_as = get_post_meta($post->ID, 'registering_as', true);

    if($registering_as == 'Caregiver'){
        $caregiver_name = get_the_title($post_id);
        $taxonomy = 'caregiver';
        $caregiver_exists = term_exists($caregiver_name, $taxonomy);

        // add term and user and their details
        if ($caregiver_exists == 0 || $caregiver_exists == null) {
            $term = wp_insert_term($caregiver_name, $taxonomy);
            $term_id = $term['term_id'];
            
            create_user_on_new_caregiver_taxonomy($term_id);
            $user = get_user_by_term($term_id);
            
            update_user_meta($user->ID, 'date_created', get_post_meta($post_id, 'date_created', true));
            update_user_meta($user->ID, 'fun_facts', get_post_meta($post_id, 'fun_facts', true));
            update_user_meta($user->ID, 'user_profile_picture', get_post_meta($post_id, 'user_profile_picture', true));
            update_user_meta($user->ID, 'first_name', get_post_meta($post_id, 'first_name', true));
            update_user_meta($user->ID, 'last_name', get_post_meta($post_id, 'last_name', true));
            wp_update_user(array(
                'ID' => $user->ID,
                'user_email' => get_post_meta($post_id, 'email', true)
            ));
            update_user_meta($user->ID, 'address', get_post_meta($post_id, 'address', true));
            update_user_meta($user->ID, 'state', get_post_meta($post_id, 'state', true));
            update_user_meta($user->ID, 'zip_code', get_post_meta($post_id, 'zip_code', true));
            update_user_meta($user->ID, 'phone_number', get_post_meta($post_id, 'phone_number', true));
            update_user_meta($user->ID, 'description', get_post_meta($post_id, 'description', true));
            update_user_meta($user->ID, 'background', get_post_meta($post_id, 'background', true));

            $date_today = date('Ymd');
            update_term_meta($term_id, 'temporary_password', 'klsplaceh0ld3rp4ass' . $term_id . $date_today);
            update_term_meta($term_id, 'message_sent', 'false');

            // Redirect to the edit profile page with the term ID as a query parameter
            wp_redirect(home_url('/account/caregiver-details/?id=' . $term_id));

            exit;


        } else{
            wp_redirect(home_url('/account/applicants/?error=caregiver_already_exists/'));
            update_post_meta($post->ID, 'application_status', 'declined');
            exit;
        }
    } else if ($registering_as == 'Care Recipient'){
        $care_recipient_name = get_the_title($post_id);
        $taxonomy = 'care_recipient';
        $care_recipient_exists = term_exists($care_recipient_name, $taxonomy);

        if ($care_recipient_exists == 0 || $care_recipient_exists == null) {
            $term = wp_insert_term($care_recipient_name, $taxonomy);
            $term_id = $term['term_id'];

            create_user_on_new_customer_taxonomy($term_id);
            $user = get_user_by_term($term_id);
            wp_update_user(array(
                'ID' => $user->ID,
                'user_email' => get_post_meta($post_id, 'email', true)
            ));
            update_user_meta($user->ID, 'first_name', get_post_meta($post_id, 'first_name', true));
            update_user_meta($user->ID, 'last_name', get_post_meta($post_id, 'last_name', true));
            update_user_meta($user->ID, 'date_created', get_post_meta($post_id, 'date_created', true));

            $date_today = date('Ymd');
            update_term_meta($term_id, 'temporary_password', 'klsplaceh0ld3rp4ass' . $term_id . $date_today);
            update_term_meta($term_id, 'message_sent', 'false');

        } else{
            wp_redirect(home_url('/account/applicants/?error=care_recipient_already_exists/'));
            update_post_meta($post->ID, 'application_status', 'declined');
            exit;
        }
    }
    

});

// decline applicant from jet form builder with hook name 'decline_applicant'
add_action('jet-form-builder/custom-action/decline_applicant', function ($request){
    $post_id = intval($request['applicant_id']);

    // $post_type = 'applicants';

    $post = get_post($post_id);

    update_post_meta($post->ID, 'application_status', 'declined');

    wp_redirect(home_url('/account/applicants/'));
    exit;
});

// add availability from jet form builder with hook name 'add_availability'
add_action('jet-form-builder/custom-action/add_availability', function ($request){

    $caregiver = isset($_POST['caregivers']) ? sanitize_text_field($_POST['caregivers']) : get_term_by_user($request['author_id'], 'caregiver')->term_id;
    $availability_status = isset($_POST['availability_status']) ? sanitize_text_field($_POST['availability_status']) : '';
    $caregiver_name = get_term($caregiver)->name;
    $availability_status_name = get_term($availability_status)->name;

    if (($request['start_time'] >= $request['end_time']) && $request['whole_day'] != 'enabled'){
        wp_redirect(home_url('/account/availability-date-range-invalid/'));
        exit();
        return;
    }

    wp_insert_post(array(
        'post_title' => $caregiver_name . ' - ' .  ($request['whole_day'] == 'enabled' ? $request['whole_day_date'] : $request['start_time']),
        'post_type' => 'availability',
        'post_status' => 'publish',
        'post_author' => 1,
        'meta_input' => array(
            'start_time' => $request['whole_day'] == 'enabled' ? $request['whole_day_date'] : $request['start_time'],
            'end_time' => $request['whole_day'] == 'enabled' ? ($request['whole_day_date'] + 86399) : $request['end_time'],
            'notes' => $request['notes'],
            'whole_day' => $request['whole_day'] == 'enabled' ? true : false,
        ),
        'tax_input' => array(
            'caregiver' => array($caregiver_name),
            'availability-status' => array($availability_status_name),
        )));
});

// add availability from jet form builder with hook name 'custom_new_appointment'
add_action('jet-form-builder/custom-action/custom_new_appointment', function ($request){

    $care_recipient = $request['care_recipient'] ? $request['care_recipient'] : '';
    $caregiver = $request['caregiver'] ? $request['caregiver'] : '';
    $inserted_availability = $request['inserted_availability'] ? $request['inserted_availability'] : '';
    $inserted_care_appointments = $request['inserted_care-appointments'] ? $request['inserted_care-appointments'] : '';
    $care_recipient_user = get_user_by_term($care_recipient);
    $care_recipient_name = get_term($care_recipient)->name;
    $caregiver_name = get_term($caregiver)->name;

    $is_recurring = $request['is_recurring'] ? $request['is_recurring'] : '';
    if ($is_recurring == 'yes'){
        $recurring_start_date = $request['recurring_start_date'] ? $request['recurring_start_date'] : '';
        $recurring_repeat = $request['recurring_repeat'] ? $request['recurring_repeat'] : '';
        
        $recurring_end = $request['recurring_end'] ? $request['recurring_end'] : '';
        $recurring_iterations = $request['recurring_iterations'] ? $request['recurring_iterations'] : '';
        $recurring_end_date = $request['recurring_end_date'] ? $request['recurring_end_date'] : '';

        $recurring_start_time = $request['recurring_start_time'] ? $request['recurring_start_time'] : '';
        $recurring_end_time = $request['recurring_end_time'] ? $request['recurring_end_time'] : '';

        $start_date_object = new DateTime($recurring_start_date);
        $recurring_dates = strtotime($start_date_object->format('Y-m-d'));
        update_post_meta($inserted_availability, 'is_recurring', true);
        update_post_meta($inserted_care_appointments, 'is_recurring', true);
        

        if ($recurring_repeat == 'daily'){
            $recurring_day_frequency = $request['recurring_day_frequency'] ? $request['recurring_day_frequency'] : '';

            if($recurring_end == 'after'){
                for ($i = 1; $i < $recurring_iterations; $i++){
                    $start_date_object->modify('+' . $recurring_day_frequency . ' day');
                    $recurring_dates .= ' ' . strtotime($start_date_object->format('Y-m-d'));
                }
                
            } elseif ($recurring_end == 'on_date'){
                $end_date_object = new DateTime($recurring_end_date);

                while (strtotime($start_date_object->format('Y-m-d')) < strtotime($end_date_object->format('Y-m-d'))){
                    $start_date_object->modify('+' . $recurring_day_frequency . ' day');
                    if (strtotime($start_date_object->format('Y-m-d')) > strtotime($end_date_object->format('Y-m-d'))) {
                        break;
                    }
                    $recurring_dates .= ' ' . strtotime($start_date_object->format('Y-m-d'));
                }
                
            }

        } elseif ($recurring_repeat == 'weekly'){

            $recurring_week_days = $request['recurring_week_days'] ? $request['recurring_week_days'] : [];

            $start_day_of_week = $start_date_object->format('w');

            $days_of_week = [
                'Sunday' => 0,
                'Monday' => 1,
                'Tuesday' => 2,
                'Wednesday' => 3,
                'Thursday' => 4,
                'Friday' => 5,
                'Saturday' => 6
            ];

            function getDayDifference($start_day, $target_day) {
                $diff = ($target_day - $start_day + 7) % 7; // Ensures all values are positive
                return $diff === 0 ? 7 : $diff; // Moves the same day to the next week
            }

            usort($recurring_week_days, function ($a, $b) use ($start_day_of_week, $days_of_week) {
                $diff_a = getDayDifference($start_day_of_week, $days_of_week[$a]);
                $diff_b = getDayDifference($start_day_of_week, $days_of_week[$b]);
                return $diff_a - $diff_b; // Sort by day difference
            });


            if($recurring_end == 'after'){
                for($i = 1; $i < $recurring_iterations;){
                    foreach ($recurring_week_days as $day) {
                        if ($i >= $recurring_iterations) {
                            break 2;
                        }
                        $start_date_object->modify('next ' . $day);
                        $recurring_dates .= ' ' . strtotime($start_date_object->format('Y-m-d'));
                        $i++;
                    }
                }
            } elseif ($recurring_end == 'on_date'){
                $end_date_object = new DateTime($recurring_end_date);
                while (strtotime($start_date_object->format('Y-m-d')) <= strtotime($end_date_object->format('Y-m-d'))) {
                    foreach ($recurring_week_days as $day) {
                        $start_date_object->modify('next ' . $day);
                        if (strtotime($start_date_object->format('Y-m-d')) > strtotime($end_date_object->format('Y-m-d'))) {
                            break 2; // Break out of both loops
                        }
                        $recurring_dates .= ' ' . strtotime($start_date_object->format('Y-m-d'));
                    }
                }
            }
        };
        $recurring_dates_array = explode(' ', trim($recurring_dates));
        $first_recurring_date = new DateTime('@' . $recurring_dates_array[0]);
        $last_recurring_date = new DateTime('@' . end($recurring_dates_array));
        $recurring_start_datetime = strtotime($first_recurring_date->format('Y-m-d') . ' ' . $recurring_start_time);
        $recurring_end_datettime = strtotime($last_recurring_date->format('Y-m-d') . ' ' . $recurring_end_time);

        if ($inserted_care_appointments) {
            wp_insert_post(array(
                'ID' => $inserted_care_appointments,
                'post_title' => $caregiver_name . ' - ' . $request['recurring_start_date'] . ' (Recurring)',
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => 'care-appointments',
                'post_author'   => 1,
                'meta_input' => array(
                    'shift_details' => $request['shift_details'],
                    'appointment_address' => $request['address'],
                    'availability_edit_url' => get_edit_post_link($inserted_availability),
                    'recurring_dates' => $recurring_dates,
                    'start_time' => $recurring_start_datetime,
                    'end_time' => $recurring_end_datettime,
                ),
            ));
        }

        if ($inserted_availability) {
            wp_insert_post(array(
                'ID' => $inserted_availability,
                'post_title' => $caregiver_name . ' - ' . $request['recurring_start_date'] . ' (Recurring)',
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => 'availability',
                'post_author'   => 1,
                'meta_input' => array(
                    'notes' => 'In an appointment with ' . $care_recipient_name,
                    'appointment_id' => $inserted_care_appointments,
                    'recurring_dates' => $recurring_dates,
                    'start_time' => $recurring_start_datetime,
                    'end_time' => $recurring_end_datettime,

                ),
            ));
        }

        update_user_meta($care_recipient_user->ID, 'shift_details', $request['shift_details']);
        update_user_meta($care_recipient_user->ID, 'address', $request['address']);
        update_user_meta($care_recipient_user->ID, 'start_time', $recurring_start_datetime);
        update_user_meta($care_recipient_user->ID, 'end_time', $recurring_end_datettime);

        wp_redirect(home_url(). '/account/schedule/');
        exit();

    } else {
        if ($inserted_availability) {
            wp_insert_post(array(
                'ID' => $inserted_availability,
                'post_title' => $caregiver_name . ' - ' . date('Y-m-d', $request['start_datetime']),
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => 'availability',
                'post_author'   => 1,
                'meta_input' => array(
                    'notes' => 'In an appointment with ' . $care_recipient_name,
                    'appointment_id' => $inserted_care_appointments,
                ),
            ));
    
            update_user_meta($care_recipient_user->ID, 'shift_details', $request['shift_details']);
            update_user_meta($care_recipient_user->ID, 'address', $request['address']);
            update_user_meta($care_recipient_user->ID, 'start_time', $request['start_datetime']);
            update_user_meta($care_recipient_user->ID, 'end_time', $request['end_datetime']);
    
        }
    
        if ($inserted_care_appointments) {
            wp_insert_post(array(
                'ID' => $inserted_care_appointments,
                'post_title' => $caregiver_name . ' - ' . date('Y-m-d', $request['start_datetime']),
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => 'care-appointments',
                'post_author'   => 1,
                'meta_input' => array(
                    'shift_details' => $request['shift_details'],
                    'appointment_address' => $request['address'],
                    'availability_edit_url' => get_edit_post_link($inserted_availability),
                ),
            ));
        }
    }


    wp_redirect(home_url(). '/account/schedule/');
    exit();
});

// add availability from jet form builder with hook name  'new_user_message_sent'
add_action('jet-form-builder/custom-action/new_user_message_sent', function ($request){

    $term_id = $request['user_term_id'];
    update_term_meta($term_id, 'message_sent', 'true');

    wp_redirect($_SERVER['HTTP_REFERER']);
    exit();

});

// change term name from jet form builder with hook name 'edit_care_recipient_user'
add_action('jet-form-builder/custom-action/edit_care_recipient_user', function ($request){
    $care_recipient_term_id = get_term_by_user($request['caregiver_user_id'], 'care_recipient')->term_id;
    
    wp_update_term($care_recipient_term_id, 'care_recipient', array(
        'name' => sanitize_text_field($request['first_name'] . ' ' . $request['last_name']),
    ));

    $user_files = get_user_meta($request['caregiver_user_id'], 'user_files', true) ? get_user_meta($request['caregiver_user_id'], 'user_files', true) : '';

    if($request['user_files']){

        if(!$user_files){
            update_user_meta($request['caregiver_user_id'], 'user_files', $request['user_files']);
        } else {
            update_user_meta($request['caregiver_user_id'], 'user_files', $user_files . ',' . $request['user_files']);
        }
    }
    if ($request['remove_files'] == 'yes'){
        update_user_meta($request['caregiver_user_id'], 'user_files', '');
    }
});

// change term name from jet form builder with hook name 'edit_caregiver_user'
add_action('jet-form-builder/custom-action/edit_caregiver_user', function ($request){
    $caregiver_term_id = get_term_by_user($request['caregiver_user_id'], 'caregiver')->term_id;
    
    wp_update_term($caregiver_term_id, 'caregiver', array(
        'name' => sanitize_text_field($request['first_name'] . ' ' . $request['last_name']),
    ));
});

// change term name from jet form builder with hook name 'edit_caregiver_profile'
add_action('jet-form-builder/custom-action/edit_caregiver_profile', function ($request){
    $caregiver_term_id = get_term_by_user($request['caregiver_user_id'], 'caregiver')->term_id;
    
    wp_update_term($caregiver_term_id, 'caregiver', array(
        'name' => sanitize_text_field($request['first_name'] . ' ' . $request['last_name']),
    ));
});

// change term name from jet form builder with hook name 'edit_care_recipient_profile'
add_action('jet-form-builder/custom-action/edit_care_recipient_profile', function ($request){
    $care_recipient_term_id = get_term_by_user($request['caregiver_user_id'], 'care_recipient')->term_id;

    wp_update_term($care_recipient_term_id, 'care_recipient', array(
        'name' => sanitize_text_field($request['first_name'] . ' ' . $request['last_name']),
    ));

    $user_files = get_user_meta($request['caregiver_user_id'], 'user_files', true) ? get_user_meta($request['caregiver_user_id'], 'user_files', true) : '';

    if($request['user_files']){
        if(!$user_files){
            update_user_meta($request['caregiver_user_id'], 'user_files', $request['user_files']);
        } else {
            update_user_meta($request['caregiver_user_id'], 'user_files', $user_files . ',' . $request['user_files']);
        }
    }
    if ($request['remove_files'] == 'yes'){
        update_user_meta($request['caregiver_user_id'], 'user_files', '');
    }
});

// add notification numbers in ticket support with hook name 'add_ticket_reply'
add_action('jet-engine-booking/add_ticket_reply', function ($data){
    
    $author_id = $data['author_id'];
    $post_id = $data['post_id'];
    $user_replies = get_post_meta($post_id, 'user_replies', true);
    $admin_replies = get_post_meta($post_id, 'admin_replies', true);

    if (user_can($author_id, 'administrator')) {
        update_post_meta($post_id, 'user_replies', 0);
        update_post_meta($post_id, 'admin_replies', $admin_replies + 1);
    } else {
        update_post_meta($post_id, 'user_replies', $user_replies + 1);
        update_post_meta($post_id, 'admin_replies', 0);
    }

});


// change term name from jet form builder with hook name 'applicant_post_title'
add_action('jet-form-builder/custom-action/applicant_post_title', function ($request){
    $inserted_post_id = $request['inserted_post_id'];
    $last_name = $request['last_name'];
    $first_name = $request['first_name'];

    wp_update_post(array(
        'ID' => $inserted_post_id,
        'post_title' => sanitize_text_field($first_name . ' ' . $last_name),
    ));
});