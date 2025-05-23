<?php

function caregiver_customer_login_function() {
    
    if ( !is_user_logged_in() ) {
        return wp_login_form( array( 'echo' => false ) );
    }

    $user = wp_get_current_user();
    
    $term_details = [
        // 'taxonomy' => 'user_role'
        'care_recipient' => 'customer',
        'caregiver'   => 'care_giver',
    ];

    $term_keys = array_keys($term_details);

    // Check if user has the customer or care_giver role
    $has_role = false;
    foreach ($term_keys as $key) {
        if (in_array( $term_details[$key], (array) $user->roles)) {
            $has_role = true;
            break;
        }
    }
    if (!$has_role) {
        return 'You need to be logged in as care_giver or customer.';
    }

    // loop through the term details array
    foreach ( $term_details as $taxonomy => $user_role ) {


        $term = get_terms(array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key'   => 'connected_user_id',
                    'value' => $user->ID,
                ),
            ),
        ));

        // if no terms found, skip to the next taxonomy
        if (empty($term)){
            continue;
        }

        echo '<h4>Successfully logged in as ' . $user_role . '. List of events by this ' . $user_role . ':</h4>' ;

        $term_id = !is_wp_error($term) && !empty($term) ? wp_list_pluck($term, 'term_id') : array();

        $args = array(
            'post_type'      => 'mec-events',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ),
            ),
        );

        $query = new WP_Query( $args );

        // start the events loop
        if ( $query->have_posts() ) {

            ?> 
            <?php 
            echo '<ul>';
            while ( $query->have_posts() ) {
                $query->the_post();
                
                // print out meta values for reference and testing
                // echo '<pre>';
                // print_r(get_post_meta(get_the_ID()));
                // echo '</pre>';

                $start_datetime = get_post_meta(get_the_ID(), 'mec_start_datetime', true);
                $end_datetime = get_post_meta(get_the_ID(), 'mec_end_datetime', true);
                $caregiver_name = get_the_terms( get_the_ID(), 'caregiver' )[0]->name ? get_the_terms( get_the_ID(), 'caregiver' )[0]->name : 'No Caregiver Assigned';
                $customer_name = get_the_terms( get_the_ID(), 'care_recipient' )[0]->name ? get_the_terms( get_the_ID(), 'care_recipient' )[0]->name : 'No Customer Assigned';
                $location = get_post_meta(get_the_ID(), 'mec_location_id', true) == '0' ? 'No Location Assigned' : get_the_title(get_post_meta(get_the_ID(), 'mec_location_id', true));

                echo '<li><button class="showPopupButton" data-id="' . get_the_ID() . '" data-name="' . get_the_title() . '" data-caregiver_name="' . $caregiver_name . '" data-customer_name="' . $customer_name . '" data-link="' . get_the_permalink() . '" data-start_datetime="' . $start_datetime . '" data-end_datetime="' . $end_datetime . '" data-location="' . $location . '">' . get_the_title() . '</button></li>';
            }
            echo '</ul>';

        } else {
            echo 'No events found';
        }
    }
    wp_reset_postdata();
}
add_shortcode( 'caregiver_customer_login', 'caregiver_customer_login_function' );


?>