jQuery(document).ready(function($){

    // initialize and show the popup
    function showPopup(id, name, caregiver_name, customer_name, start_datetime, end_datetime, location, link) {
        var $dynamicPopup = $('<div id="event-popup-' + id + '" class="dynamic-popup">This is a popup with ID: ' + id + '.<br>Name: ' + name + '<br>Caregiver: ' + caregiver_name +  '<br>Customer: ' + customer_name + '<br>Start Time: ' + start_datetime + '<br>End Time: ' + end_datetime + '<br>Location: ' + location + '<br><a href="'+ link +'">View more details</a>.</div>' ) ;
        $('body').append($dynamicPopup);
       
        $('body').addClass('popup-open');
        $dynamicPopup.show();
    }

    // hide the popup
    function hidePopup() {
        $('.dynamic-popup').hide();
        $('body').removeClass('popup-open');
    }

    // show the popup when a button is clicked
    $('.showPopupButton').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var caregiver_name = $(this).data('caregiver_name');
        var customer_name = $(this).data('customer_name');
        var start_datetime = $(this).data('start_datetime');
        var end_datetime = $(this).data('end_datetime');
        var location = $(this).data('location');
        var link = $(this).data('link');

        showPopup(id, name, caregiver_name, customer_name, start_datetime, end_datetime, location, link);
    });

    // hide the popup when clicking outside of it
    $(document).mouseup(function(e) {
        var $popup = $('.dynamic-popup');
        if (!$popup.is(e.target) && $popup.has(e.target).length === 0) {
            hidePopup();
        }
    });
});