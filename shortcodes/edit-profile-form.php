<?php
    function display_edit_profile_photo_img($atts) {
        if (!isset($_GET['id'])) {
            return 'user not set';
        }

        $user_id = intval($_GET['id']);
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return 'user not found';
        }

        $attachment_id = get_user_meta($user_id, 'user_profile_picture', true);
        ?>

        <style>
            .profile-image {
                height: 100px !important;
                width: 100px !important;
                border-radius: 100% !important;
                margin: auto;
				object-fit: cover;
            }
            .profile-image-not-set {
                background-color: #00000060;
                height: 100px;
                width: 100px;
                border-radius: 100% !important;
                margin: auto;
            }
            .elementor-widget-shortcode{
                text-align: center;
            }
        </style>

        <?php
        if ($attachment_id) {
            $image_url = wp_get_attachment_url($attachment_id);
            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" alt="User Profile Picture" class="profile-image" width=75 height=75>';
            } else {
                echo '<div class="profile-image profile-image-not-set"></div>';
            }
        } else {
            echo '<div class="profile-image profile-image-not-set"></div>';
        }
    }

    add_shortcode('edit_profile_photo_img', 'display_edit_profile_photo_img');

    function display_edit_user_files_img($atts) {

        $user_id = intval($_GET['id']);
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return 'user not found';
        }

        ?>

        <style>
            .attached-user-files img{
                max-width: 100px;
            }
            .attached-user-files p{
                display:flex;
                align-items:center;
                flex-direction: column;
            }
        </style>

        <?php

        $attached_user_files_ids = get_user_meta($user->ID, 'user_files', true);

        if ($attached_user_files_ids) {
            $output = '<div class="attached-user-files"><h4>Attached Files</h4> <p>';
            $attached_user_files_ids = explode(',', $attached_user_files_ids);
            foreach ($attached_user_files_ids as $file_id) {
                $file_url = wp_get_attachment_url($file_id);
                $output .=  '<a href="' . esc_url($file_url) . '">' . basename($file_url) . '</a>';
            }
            $output .=  '</p></div>';
        } else {
            $output = '<div class="attached-user-files"><h4>Attached Files</h4><p>N/A</p></div>';
        }

        return $output;

    }

    add_shortcode('edit_user_files_img', 'display_edit_user_files_img');

    function display_edit_profile_files_img($atts) {

        $user_id = get_current_user_id();
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return 'user not found';
        }

        ?>

        <style>
            .attached-user-files img{
                max-width: 100px;
            }
            .attached-user-files p{
                display:flex;
                align-items:center;
                flex-direction: column;
            }
        </style>

        <?php

        $attached_user_files_ids = get_user_meta($user->ID, 'user_files', true);

        if ($attached_user_files_ids) {
            $output = '<div class="attached-user-files"><h4>Attached Files</h4> <p>';
            $attached_user_files_ids = explode(',', $attached_user_files_ids);
            foreach ($attached_user_files_ids as $file_id) {
                $file_url = wp_get_attachment_url($file_id);
                $output .=  '<a href="' . esc_url($file_url) . '">' . basename($file_url) . '</a>';
            }
            $output .=  '</p></div>';
        } else {
            $output = '<div class="attached-user-files"><h4>Attached Files</h4><p>N/A</p></div>';
        }

        return $output;

    }

    add_shortcode('edit_profile_files_img', 'display_edit_profile_files_img');
?>