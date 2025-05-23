<?php
// to display: create div with id="availability-calendar" and id="availability-calendar-nav"

function display_availability_calendar() {


    $args = array(
        'post_type' => 'availability',
        'posts_per_page' => -1,
    );

    $current_user = wp_get_current_user();
    switch (true) {
        case current_user_can('administrator'):
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
        $availability_start_time = date('g:i A', intval(get_post_meta($event_id, 'start_time', true)));
        $availability_end_time = date('g:i A', intval(get_post_meta($event_id, 'end_time', true)));
        $event_speakers = wp_get_post_terms($event_id, 'caregiver', array('fields' => 'names'));
        $avalability_status = wp_get_post_terms($event_id, 'availability-status', array('fields' => 'names'));
        $notes = get_post_meta($event_id, 'notes', true);
        $whole_day = get_post_meta($event_id, 'whole_day', true);


        $availability_data[] = array(
            'id' => $event_id,
            'caregiver' => $event_speakers,
            'start_time' => $availability_start_time,
            'end_time' => $availability_end_time,
            'avalability_status' => $avalability_status,
            'notes' => $notes,
            'whole_day' => $whole_day
        );
    }
    
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        let availabilitydata = [];
        availabilitydata = <?php echo json_encode($availability_data); ?>;
        
        let availabilityCalendar = new EventCalendar(document.getElementById('availability-calendar'), {
            eventContent: function (info) {
                    let output = '';
                    availabilitydata.forEach(function (availability) {
                        if (availability.id == info.event.id) {
                            output += '<div class="availability-info">';
                            output += '<p class="caregiver"><strong>' + availability.caregiver + '</strong></p>';
                            output += (availability.whole_day == 'true' || availability.whole_day == 1 || availability.whole_day == true) ? '<p class="whole-day">Whole Day</p><br>' : '<p class="availability-time">' + availability.start_time + '</p>';
                            output += (availability.whole_day == 'true' || availability.whole_day == 1 || availability.whole_day == true) ? '' : '<p class="availability-time">' + availability.end_time + '</p><br>';
                            output += '<p class="notes-header"><strong>Notes</strong></p>';
                            output += '<p class="notes">' + availability.notes + '</p>';
                            output += '</div>';
                        }
                    });
                    return {html : output};
            },
            view: 'timeGridDay',
            slotHeight: 120,
            slotMinTime: '08:00:00',
            slotMaxTime: '21:00:00',
            dayMaxEvents: true,
            headerToolbar: {start: '', center: '', end: ''},
            eventBackgroundColor: '#B80000',
            allDaySlot: false,
            events: [
                <?php 
                    $query = new WP_Query($args);

                    while ($query->have_posts()) {
                        $query->the_post();
                        $event_id = get_the_ID();
                        $availability_start_time = date('Y-m-d H:i:s', intval(get_post_meta($event_id, 'start_time', true)));
                        $availability_end_time = date('Y-m-d H:i:s', intval(get_post_meta($event_id, 'end_time', true)));
                        $event_speakers = wp_get_post_terms($event_id, 'caregiver', array('fields' => 'ids'));
                        $whole_day = get_post_meta($event_id, 'whole_day', true);
                        $backgroundColor = '#B80000';
                        $recurring_dates = explode(' ', get_post_meta($event_id, 'recurring_dates', true));
                        $is_recurring = get_post_meta($event_id, 'is_recurring', true);

                        foreach ($event_speakers as $speaker) {
                            if($is_recurring == true || $is_recurring == 'true'){
                                foreach ($recurring_dates as $timestamp) {
                                    $recurring_start = date('Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'start_time', true));
                                    $recurring_end = date('Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'end_time', true));
                                    echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($recurring_start) . "', end: '" . esc_js($recurring_end) . "' },";
                                }
                            } else {
                                echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($availability_start_time) . "', end: '" . esc_js($availability_end_time) . "'},";
                            }
                        }
                    }
                ?>
            ],

        });

        
        let availabilityCalendarNav = new EventCalendar(document.getElementById('availability-calendar-nav'), {
            view: 'dayGridMonth',
            views: {
                // change the date in the main calendar when user clicks prev or next in dayGridMonth view
                timeGridDay:{
                    datesSet: function (info) {
                        availabilityCalendar.setOption('date', info.startStr.split('T')[0]);
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
                        availabilityCalendar.setOption('date', info.dateStr.split('T')[0]);
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
                    }
                },
                dayGridMonth:{
                    dayHeaderFormat: function(date){
                        let output = '<div class="weekday-initial">' + date.toLocaleDateString('en-US', { weekday: 'short' }).charAt(0) + '</div>';
                        return {html: output};
                    },
                    eventContent: function (info) {
                            let output = '';
                            output += '<div class="ec-marker"></div>';
                            return {html : output};
                        
                    },
                }
                
            },
            headerToolbar: {start: 'prev', center: 'timeGridDay,timeGridWeek,dayGridMonth title', end: 'next'},
            eventBackgroundColor: '#ffffff00',
            allDaySlot: false,
            dateClick: function (info) {
                availabilityCalendar.setOption('date', info.dateStr.split('T')[0]);
                document.querySelectorAll('.day-active').forEach(function (el) {
                    el.classList.remove('day-active');
                });
                document.querySelectorAll('.ec-today').forEach(function (el) {
                    el.classList.remove('ec-today');
                });
                info.dayEl.classList.add('day-active');
            },
            events: [
                <?php 
                    $query = new WP_Query($args);

                    while ($query->have_posts()) {
                        $query->the_post();
                        $event_id = get_the_ID();
                        $availability_start_time = date('Y-m-d H:i:s', intval(get_post_meta($event_id, 'start_time', true)));
                        $availability_end_time = date('Y-m-d H:i:s', intval(get_post_meta($event_id, 'end_time', true)));
                        $event_speakers = wp_get_post_terms($event_id, 'caregiver', array('fields' => 'ids'));
                        $whole_day = get_post_meta($event_id, 'whole_day', true);
                        $backgroundColor = '#ffffff00';
                        $recurring_dates = explode(' ', get_post_meta($event_id, 'recurring_dates', true));
                        $is_recurring = get_post_meta($event_id, 'is_recurring', true);

                        foreach ($event_speakers as $speaker) {
                            if($is_recurring == true || $is_recurring == 'true'){
                                foreach ($recurring_dates as $timestamp) {
                                    $recurring_start = date('Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'start_time', true));
                                    $recurring_end = date('Y-m-d', intval($timestamp)) . ' ' . date( 'H:i:s', get_post_meta($event_id, 'end_time', true));
                                    echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($recurring_start) . "', end: '" . esc_js($recurring_end) . "', backgroundColor: '" . esc_js($backgroundColor) . "'},";
                                }
                            } else {
                                echo "{id: '" . esc_js($event_id) . "', title: '" . esc_js(get_the_title()) . "', start: '" . esc_js($availability_start_time) . "', end: '" . esc_js($availability_end_time) . "'},";
                            }
                        }
                    }
                ?>
            ],
            });

    });

    </script>

    <style>
        #availability-calendar-nav .ec-day-view .ec-body, #availability-calendar-nav .ec-day-view .ec-header {
            display:none;
        }
        #availability-calendar .ec, #availability-calendar-nav .ec{
            max-width: 600px;
            margin: 0 auto 20px auto;
            width: 100%;
        }
        #availability-calendar-nav .ec-day {
            cursor: pointer;
        }
        #availability-calendar-nav {
            padding-top: 40px;
        }
        #availability-calendar-nav .ec-button-group {
            position: absolute;
            width: 100%;
            max-width: 600px;
            left:0;
            top: -50px;
            justify-content: space-between;
        }
        #availability-calendar-nav .ec-button-group > *{
            flex: 0 0 30%;
            max-width: 30%;
            border: none;
            color: #fff;
            text-transform:uppercase;
            font-weight: 700
        }
        #availability-calendar-nav .ec-button-group{
            background: #D6D8CA;
            height: 40px;
            border-radius: 12px;
        }
        #availability-calendar-nav .ec-button {
            border-radius: 12px;
            margin: 5px 0;
            line-height: 1em;
            margin: 5px;
        }
        #availability-calendar-nav .ec-button.ec-active, #availability-calendar-nav .ec-button:hover, #availability-calendar-nav .ec-button:focus {
            background:#CCB490;
        }
        #availability-calendar-nav nav.ec-toolbar {
            position:relative
        }
        #availability-calendar .availability-info h6, #availability-calendar .availability-info p{
            color: #ffffff !important;
            margin-bottom: 0px;
        }
        #availability-calendar .availability-info{
            padding: 20px;
        }
        #availability-calendar .ec-event{
            border-radius: 5px;
            transition: .3s ease;
        }
        #availability-calendar .ec-time-grid .ec-time, #availability-calendar .ec-time-grid .ec-line {
            height: 120px;  /* override this value */
        }
        #availability-calendar .ec-time-grid .ec-time{
            padding-right: 2px;
        }
        #availability-calendar .ec-event:hover{
            background-color: #ffffff20
        }
        #availability-calendar .ec-extra {
            background: #f3f4f9
        }
        #availability-calendar .ec-body {
            border: none;
        }
        #availability-calendar-nav .ec-day-grid .ec-body .ec-day{
            min-height: unset;
        }

        #availability-calendar-nav .ec-day-view .ec-toolbar {
            margin: 0;
        }
        #availability-calendar-nav .ec-week-view .ec-body {
            max-height: 50px;
            margin-top: -50px;
            opacity: 0;
            margin-right: -20px;
        }
        #availability-calendar-nav .ec-week-view  .ec-hidden-scroll{
            display: none;
        }
        #availability-calendar-nav .ec-toolbar .ec-button{
            border:none;
            color: #fff
        }
        #availability-calendar-nav .ec-toolbar .ec-title {
            color: #fff;
            font-size: 20px;
        }
        #availability-calendar-nav .ec-header, #availability-calendar-nav .ec-day{
            border: none;
        }
        #availability-calendar-nav .ec-week-view .ec-header {
            min-height: 40px;
        }
        #availability-calendar-nav .ec-week-view .ec-sidebar {
            display:none
        }
        #availability-calendar-nav .ec-week-view .ec-lines {
            width: 0 !important;; 
        }
        #availability-calendar .ec-event-body{
            box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
        }
        #availability-calendar .ec-event:hover{
            z-index:999 !important;
        }
        #availability-calendar h6.availability-time strong{
            font-weight: 500;
        }
        #availability-calendar-nav .ec-month-view .ec-day-head {
            justify-content:center;
        }
        #availability-calendar .ec-header, .ec-day {
            border: none !important;;
        }
        #availability-calendar .ec-header .ec-day{
            margin-bottom: 10px;
        }
        #availability-calendar .ec-header .ec-sidebar{
            display:none
        }
        #availability-calendar .ec-time-grid .ec-lines{
            width: 0
        }
        #availability-calendar .ec-header time {
            font-size: 24px;;
            font-weight: 500
        }
        #availability-calendar-nav .ec-day{
            color: #fff;
            font-weight: 700
        }
        #availability-calendar-nav .ec-days, #availability-calendar-nav .ec-body {
            border: none;
        }
        #availability-calendar .ec-event-body{
            justify-content: flex-start;
        }
        #availability-calendar-nav .ec-day.ec-today{
            background: none !important;
        }
        #availability-calendar-nav .ec-day-head time{
            padding: 2px 7px;
            border-radius: 8px;
        }
        #availability-calendar-nav .ec-day.ec-today .ec-day-head time, .day-active .ec-day-head time{
            background: #D6D8CA !important
        }
        #availability-calendar-nav .ec-week-view .ec-header time {
            display:flex;
            justify-content:center;
        }
        #availability-calendar-nav .ec-week-view .ec-header .day-active .weekly-header-wrapper{
            background: #D6D8CA !important;
        }
        #availability-calendar-nav .ec-week-view .ec-header .weekly-header-wrapper{
            padding: 5px;
            border-radius:8px;
        }
        #availability-calendar-nav .ec-body{
            scrollbar-width: none;
            overflow: hidden;
        }
        #availability-calendar-nav button.ec-button.ec-next:focus, #availability-calendar-nav button.ec-button.ec-prev:focus {
            background: none;
        }
        #availability-calendar-nav .ec-marker{
            width: 5px;
            height: 5px;
            background: #fff;
            position:absolute;
            bottom: 5px;
            transform: translateX(3px);
            border-radius: 100%;
        }
        #availability-calendar-nav .ec-events{
            display:flex;
            justify-content:center;
            
        }
        #availability-calendar-nav .ec-event{
            width:5px !important;
        }
        #availability-calendar-nav .ec-event:not(:first-child){
            display:none;
        }
    </style>

    <?php
}
add_shortcode('availability_calendar', 'display_availability_calendar');