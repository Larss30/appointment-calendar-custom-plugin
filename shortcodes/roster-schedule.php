<?php
//to be fixed: display recurring events in the calendar, go to eventContent option


// to display: create two div elements with id="appointment-calendar" and id="appointment-calendar-nav"

function display_appointments_calendar() {

    $appointments_data = array();
    $args = array(
        'post_type' => 'care-appointments',
        'posts_per_page' => -1,
    );

    //filter by user login
    $current_user = wp_get_current_user();
    switch (true) {
        case current_user_can('administrator'):
            break;
        case in_array('customer', $current_user->roles):
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'care_recipient',
                    'field' => 'term_id',
                    'terms' => get_term_by_user($current_user->ID, 'care_recipient')->term_id,
                )
            );
            break;
        case in_array('care_giver', $current_user->roles):
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'caregiver',
                    'field' => 'term_id',
                    'terms' => get_term_by_user($current_user->ID, 'caregiver')->term_id,
                )
            );
            break;
    }
    
    $query = new WP_Query($args);

    while ($query->have_posts()) {
        $query->the_post();
        $event_id = get_the_ID();
        $appointment_start_time = date('g:i A', get_post_meta($event_id, 'start_time', true));
        $appointment_end_time = date('g:i A', get_post_meta($event_id, 'end_time', true));
        $shift_details = get_post_meta($event_id, 'shift_details', true);
        $appointment_link = get_permalink($event_id);
        $event_speakers = wp_get_post_terms($event_id, 'caregiver', array('fields' => 'names'));
        $event_organizers = wp_get_post_terms($event_id, 'care_recipient', array('fields' => 'names'));

        $appointments_data[] = array(
            'id' => $event_id,
            'caregiver' => $event_speakers,
            'care_recipient' => $event_organizers,
            'start_time' => $appointment_start_time,
            'end_time' => $appointment_end_time,
            'link' => $appointment_link,
            'shift_details' => $shift_details,
            'user_role' => $current_user->roles[0],

        );
    }

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        
        let appointmentdata = [];
        // pass the php array to javascript
        appointmentdata = <?php echo json_encode($appointments_data); ?>;
        
        let appointmentCalendarNav = new EventCalendar(document.getElementById('appointment-calendar-nav'), {
            view: 'dayGridMonth',
            views: {
                // change the date in the main calendar when user clicks prev or next in dayGridMonth view
                timeGridDay:{
                    datesSet: function (info) {
                        appointmentCalendar.setOption('date', info.startStr.split('T')[0]);
                    }
                },
                timeGridWeek:{
                    dayHeaderFormat: function(date){
                        let output = '<div class="weekly-header-wrapper">';
                        output += '<div class="weekday-initial">' + date.toLocaleDateString('en-US', { weekday: 'short' }).charAt(0) + '</div>';
                        output += '<div class="day-num">' + date.toLocaleDateString('en-US', {day: 'numeric' }) + '</div>';
                        output += '</div>'
                        return {html: output};

                    },
                    dateClick: function (info) {
                        appointmentCalendar.setOption('date', info.dateStr.split('T')[0]);
                        document.querySelectorAll('.day-active').forEach(function (el) {
                            el.classList.remove('day-active');
                        });
                        document.querySelectorAll('.ec-today').forEach(function (el) {
                            el.classList.remove('ec-today');
                        });
                        let dayClass = info.dayEl.classList[1]; // Get the class like ec-sun, ec-mon, etc.
                        document.querySelectorAll('.' + dayClass).forEach(function (el) {
                            el.classList.add('day-active');
                        });
                        info.dayEl.classList.add('day-active');
                        console.log(info.dayEl)
                    },
                },
                dayGridMonth:{
                    dayHeaderFormat: function(date){
                        let output = '<div class="weekday-initial">' + date.toLocaleDateString('en-US', { weekday: 'short' }).charAt(0) + '</div>';
                        return {html: output};
                    },
                    eventContent: function (info) {
                        let output = '';
                        appointmentdata.forEach(function (appointment) {
                                if (appointment.id == info.event.id) {
                                    output += '<div class="ec-marker">';
                                    output += '</div>';
                                }
                        });
                        return {html : output};
                    },
                }
            },
            events: [
                <?php 

                    while ($query->have_posts()) {
                        $query->the_post();
                        $event_id = get_the_ID();
                        $appointment_start_time = date('Y-m-d H:i:s', get_post_meta($event_id, 'start_time', true));
                        $appointment_end_time = date('Y-m-d H:i:s', get_post_meta($event_id, 'end_time', true));
                        $event_speakers = wp_get_post_terms($event_id, 'caregiver', array('fields' => 'ids'));
                        $recurring_dates = explode(' ', get_post_meta($event_id, 'recurring_dates', true));
                        $is_recurring = get_post_meta($event_id, 'is_recurring', true);

                        foreach ($event_speakers as $speaker) {
                            if ($is_recurring == true || $is_recurring == 'true') {
                                foreach ($recurring_dates as $timestamp) {
                                    $recurring_start = date( 'Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'start_time', true));
                                    $recurring_end = date('Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'end_time', true));
                                    echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($recurring_start) . "', end: '" . esc_js($recurring_end) . "' },";
                                }
                            } else {
                                echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($appointment_start_time) . "', end: '" . esc_js($appointment_end_time) . "'},";
                            }
                        }
                    }
                ?>
            ],
            headerToolbar: {start: 'prev', center: 'timeGridDay,timeGridWeek,dayGridMonth title', end: 'next'},
            eventBackgroundColor: '#D9DFCC',
            allDaySlot: false,
            dateClick: function (info) {
                appointmentCalendar.setOption('date', info.dateStr.split('T')[0]);
                document.querySelectorAll('.day-active').forEach(function (el) {
                    el.classList.remove('day-active');
                });
                document.querySelectorAll('.ec-today').forEach(function (el) {
                    el.classList.remove('ec-today');
                });
                info.dayEl.classList.add('day-active');
            },
            eventBackgroundColor: '#ffffff00'
        });

        let appointmentCalendar = new EventCalendar(document.getElementById('appointment-calendar'), {
            view: 'timeGridDay',
            eventContent: function (info) {
                    let output = '';
                    appointmentdata.forEach(function (appointment) {
                            if (appointment.id == info.event.id) {
                                output += '<div class="appointment-info">';
                                output += '<p class="caregiver"' + '/"><strong>' + appointment.caregiver + '</strong></a><br>';
                                output += '<p class="appointment-time">' + appointment.start_time + ' - ' + appointment.end_time + '</p><br>';
                                output += '<p>Client:</p>';
                                output += appointment.user_role != 'customer' ? '<a class="care-recipient admin-and-caregiver-only" href="'+ appointment.link + '"><strong>' + appointment.care_recipient + '</strong></a>' : '';
                                output += appointment.user_role == 'customer' ? '<p class="care-recipient users-only"/"><strong>' + appointment.care_recipient + '</strong></p>' : '';
                                output += '<p class="shift-details"' + '/">' + appointment.shift_details + '</a><br>';
                                output += '</div>';
                            }
                    });
                    return {html : output};
            },
            slotMinTime: '08:00:00',
            slotMaxTime: '21:00:00',
            slotHeight: 120,
            flexibleSlotTimeLimits: true,
            longPressDelay: 999999,
            editable: false,
            dayMaxEvents: true,
            events: [
                <?php 
                    while ($query->have_posts()) {
                        $query->the_post();
                        $event_id = get_the_ID();
                        $appointment_start_time = date('Y-m-d H:i:s', get_post_meta($event_id, 'start_time', true));
                        $appointment_end_time = date('Y-m-d H:i:s', get_post_meta($event_id, 'end_time', true));
                        $event_speakers = wp_get_post_terms($event_id, 'caregiver', array('fields' => 'ids'));
                        $recurring_dates = explode(' ', get_post_meta($event_id, 'recurring_dates', true));
                        $is_recurring = get_post_meta($event_id, 'is_recurring', true);

                        foreach ($event_speakers as $speaker) {
                            if ($is_recurring == true || $is_recurring == 'true') {
                                foreach ($recurring_dates as $timestamp) {
                                    $recurring_start = date( 'Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'start_time', true));
                                    $recurring_end = date('Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'end_time', true));
                                    echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($recurring_start) . "', end: '" . esc_js($recurring_end) . "' },";
                                }
                            } else {
                                echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($appointment_start_time) . "', end: '" . esc_js($appointment_end_time) . "'},";
                            }
                        }
                    }
                ?>
            ],
            headerToolbar: {start: '', center: '', end: ''},
            eventBackgroundColor: '#D9DFCC',
            allDaySlot: false,
        });

    });
    </script>

    <style>
        #appointment-calendar-nav .ec-day-view .ec-body, #appointment-calendar-nav .ec-day-view .ec-header {
            display:none;
        }
        #appointment-calendar .ec, #appointment-calendar-nav .ec{
            max-width: 600px;
            margin: 0 auto 20px auto;
            width: 100%;
        }
        #appointment-calendar-nav .ec-day {
            cursor: pointer;
        }
        #appointment-calendar-nav {
            padding-top: 40px;
        }
        #appointment-calendar-nav .ec-button-group {
            position: absolute;
            width: 100%;
            max-width: 600px;
            left:0;
            top: -50px;
            justify-content: space-between;
        }
        #appointment-calendar-nav .ec-button-group > *{
            flex: 0 0 30%;
            max-width: 30%;
            border: none;
            color: #fff;
            text-transform:uppercase;
            font-weight: 700
        }
        #appointment-calendar-nav .ec-button-group{
            background: #D6D8CA;
            height: 40px;
            border-radius: 12px;
        }
        #appointment-calendar-nav .ec-button {
            border-radius: 12px;
            margin: 5px 0;
            line-height: 1em;
            margin: 5px;
        }
        #appointment-calendar-nav .ec-button.ec-active, #appointment-calendar-nav .ec-button:hover {
            background:#BBC2AE;
        }
        #appointment-calendar-nav nav.ec-toolbar {
            position:relative
        }
        #appointment-calendar .appointment-info h6, #appointment-calendar .appointment-info p{
            color: #545454 !important;
            margin-bottom: 0px;
        }
        #appointment-calendar .appointment-info{
            padding: 20px;
        }
        #appointment-calendar .ec-event{
            border-radius: 5px;
            transition: .3s ease;
        }
        #appointment-calendar .ec-time-grid .ec-time, #appointment-calendar .ec-time-grid .ec-line {
            height: 120px;  /* override this value */
        }
        #appointment-calendar .ec-time-grid .ec-time{
            padding-right: 2px;
        }
        #appointment-calendar .ec-event:hover{
            background-color:rgb(200, 218, 161) !important;
        }
        #appointment-calendar .ec-extra {
            background: #f3f4f9
        }
        #appointment-calendar .ec-body {
            border: none;
        }
        #appointment-calendar-nav .ec-day-grid .ec-body .ec-day{
            min-height: unset;
        }

        #appointment-calendar-nav .ec-day-view .ec-toolbar {
            margin: 0;
        }
        #appointment-calendar-nav .ec-week-view .ec-body {
            max-height: 50px;
            margin-top: -50px;
            opacity: 0;
            margin-right: -20px;
        }
        #appointment-calendar-nav .ec-week-view  .ec-hidden-scroll{
            display: none;
        }
        #appointment-calendar-nav .ec-toolbar .ec-button{
            border:none;
            color: #fff
        }
        #appointment-calendar-nav .ec-toolbar .ec-title {
            color: #fff;
            font-size: 20px;
        }
        #appointment-calendar-nav .ec-header, #appointment-calendar-nav .ec-day{
            border: none;
        }
        #appointment-calendar-nav .ec-week-view .ec-header {
            min-height: 40px;
        }
        #appointment-calendar-nav .ec-week-view .ec-sidebar {
            display:none
        }
        #appointment-calendar-nav .ec-week-view .ec-lines {
            width: 0 !important;; 
        }
        #appointment-calendar .ec-event-body{
            box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
        }
        #appointment-calendar .ec-event:hover{
            z-index:999 !important;
        }
        #appointment-calendar h6.appointment-time strong{
            font-weight: 500;
        }
        #appointment-calendar-nav .ec-month-view .ec-day-head {
            justify-content:center;
        }
        #appointment-calendar .ec-header, .ec-day {
            border: none !important;;
        }
        #appointment-calendar .ec-header .ec-day{
            margin-bottom: 10px;
        }
        #appointment-calendar .ec-header .ec-sidebar{
            display:none
        }
        #appointment-calendar .ec-time-grid .ec-lines{
            width: 100%;
        position: absolute;
        z-index: 1;
        }
        #appointment-calendar .ec-header time {
            font-size: 24px;
            font-weight: 500
        }
        #appointment-calendar-nav .ec-day{
            color: #fff;
            font-weight: 700
        }
        #appointment-calendar-nav .ec-days, #appointment-calendar-nav .ec-body {
            border: none;
        }
        #appointment-calendar .ec-event-body{
            justify-content: flex-start;
        }
        #appointment-calendar-nav .ec-day.ec-today{
            background: none !important;
        }
        #appointment-calendar-nav .ec-day-head time{
            padding: 2px 7px;
            border-radius: 8px;
        }
        #appointment-calendar-nav .ec-day.ec-today .ec-day-head time, .day-active .ec-day-head time{
            background: #D6D8CA !important
        }
        #appointment-calendar-nav .ec-week-view .ec-header time {
            display:flex;
            justify-content:center;
        }
        #appointment-calendar-nav .ec-week-view .ec-header .day-active .weekly-header-wrapper{
            background: #D6D8CA !important;
        }
        #appointment-calendar-nav .ec-week-view .ec-header .weekly-header-wrapper{
            padding: 5px;
            border-radius:8px;
        }
        #appointment-calendar-nav .ec-body{
            scrollbar-width: none;
            overflow: hidden;
        }
        #appointment-calendar-nav button.ec-button.ec-next:focus, #appointment-calendar-nav button.ec-button.ec-prev:focus {
            background: none;
        }
        #appointment-calendar-nav .ec-marker{
            width: 5px;
            height: 5px;
            background: #fff;
            position:absolute;
            bottom: 5px;
            transform: translateX(3px);
            border-radius: 100%;
        }
        #appointment-calendar-nav .ec-events{
            display:flex;
            justify-content:center;
            max-height: 0;
        },
        #appointment-calendar-nav .ec-event{
            background-color: transparent !important;
        }
        #appointment-calendar-nav .ec-event{
            width:5px !important;
        }
        #appointment-calendar-nav .ec-event:not(:first-child){
            display:none;
        }
    </style>

    <?php
}
add_shortcode('appointments_calendar', 'display_appointments_calendar');

function display_appointments_timeline() {

    $terms = get_terms(array(
        'taxonomy' => 'caregiver',
        'hide_empty' => false,
    ));

    $args = array(
        'post_type' => 'care-appointments',
        'posts_per_page' => -1,
    );

    $appointments_data = array();

    $query = new WP_Query($args);

    while ($query->have_posts()) {
        $query->the_post();
        $event_id = get_the_ID();
        $appointment_start_time = date('M j, y (g:i A)', get_post_meta($event_id, 'start_time', true));
        $appointment_end_time = date('M j, y (g:i A)', get_post_meta($event_id, 'end_time', true));
        $event_speakers = wp_get_post_terms($event_id, 'caregiver', array('fields' => 'names'));
        $event_organizers = wp_get_post_terms($event_id, 'care_recipient', array('fields' => 'names'));
        $appointment_link = get_permalink($event_id);
        $appointments_data[] = array(
            'id' => $event_id,
            'caregiver' => $event_speakers,
            'care_recipient' => $event_organizers,
            'start_time' => $appointment_start_time,
            'end_time' => $appointment_end_time,
            'appointment_link' => $appointment_link,
        );
    }

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            
            let appointmentdata = [];
            appointmentdata = <?php echo json_encode($appointments_data); ?>;

            let appointmentMonthTimeline = new EventCalendar(document.getElementById('appointment-month-timeline'), {
                view: 'resourceTimelineDay',
                slotMinTime: '08:00:00',
                slotMaxTime: '22:00:00',
                eventContent: function (info) {
                    let output = '';
                    appointmentdata.forEach(function (appointment) {
                            if (appointment.id == info.event.id) {
                                output += '<div class="appointment-info">';
                                output += '<h6 class="appointment-time"><strong>' + appointment.start_time + ' - ' + appointment.end_time + '</strong></h6><br>';
                                output += '<p>Care Recipient</p>';
                                output += '<a class="care-recipient" href="'+ appointment.appointment_link +'/"><strong>' + appointment.care_recipient + '</strong></a>';
								
                                output += '</div>';
                            }
                    });
                    return {html : output};
                },
                eventBackgroundColor: '#D9DFCC',
                slotWidth : 150,
                resources: [
                    <?php 
                        foreach ($terms as $term) {
                            echo "{id:" . esc_js($term->term_id) . ", title: '" . esc_js($term->name) . "'},";
                        }
                    ?>
                ],
                events: [
                    <?php 

                        while ($query->have_posts()) {
                            $query->the_post();
                            $event_id = get_the_ID();
                            $appointment_start_time = date('Y-m-d H:i:s', get_post_meta($event_id, 'start_time', true));
                            $appointment_end_time = date('Y-m-d H:i:s', get_post_meta($event_id, 'end_time', true));
                            $event_speakers = wp_get_post_terms($event_id, 'caregiver', array('fields' => 'ids'));
                            $recurring_dates = explode(' ', get_post_meta($event_id, 'recurring_dates', true));
                            $is_recurring = get_post_meta($event_id, 'is_recurring', true);

                            foreach ($event_speakers as $speaker) {
                                if ($is_recurring == true || $is_recurring == 'true') {
                                    foreach ($recurring_dates as $timestamp) {
                                        $recurring_start = date( 'Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'start_time', true));
                                        $recurring_end = date('Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'end_time', true));
                                        echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($recurring_start) . "', end: '" . esc_js($recurring_end) . "', resourceId:" . esc_js($speaker) . " },";
                                    }
                                } else {
                                    echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($appointment_start_time) . "', end: '" . esc_js($appointment_end_time) . "', resourceId:" . esc_js($speaker) . " },";
                                }
                            }
                        }
                    ?>
                ]
            });
        });
    </script>
    <style>
        #appointment-month-timeline .appointment-info h6, #appointment-month-timeline .appointment-info p{
            color: #545454 !important;
            margin-bottom: 0px;
        }
        #appointment-month-timeline .appointment-info{
            padding: 20px;
        }
        #appointment-month-timeline .ec-event{
            border-radius: 5px;
            transition: .3s ease;
        }
        #appointment-month-timeline .ec-event:hover{
            background-color:rgb(200, 218, 161) !important;
        } 
        #appointment-month-timeline .ec-event-body{
            box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
        } 
        #appointment-month-timeline .ec-timeline .ec-time, #appointment-month-timeline .ec-timeline .ec-line {
            width: 150px;  /* override this value */
        }
    </style>
    <?php
}
add_shortcode('appointments_timeline', 'display_appointments_timeline'); 