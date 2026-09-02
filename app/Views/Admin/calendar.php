<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<div class="row">
    <div class="col-md-8 col-xs-12">
        <div id='calendar'></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() 
{
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: '<?= $language;?>',
        headerToolbar: 
        {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: '<?= base_url("Admin/calendar_activities") ?>',
        eventClick: function(info) {
            alert('Activity: ' + info.event.title + '\nHours: ' + info.event.extendedProps.hours + '\nCity: ' + info.event.extendedProps.city);
        }
    });
    calendar.render();
});
</script>
