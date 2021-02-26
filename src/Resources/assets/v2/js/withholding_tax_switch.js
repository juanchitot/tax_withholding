$(document).ready(function() {
    bindSwitch();
});

function bindSwitch() {
    $('#section-switch').on('change', function() {
        if (this.checked) {
            window.location = $(this).data('href-on');
        } else {
            window.location = $(this).data('href-off');
        }
    });
}