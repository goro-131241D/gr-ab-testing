jQuery(document).ready(function($) {
    $('form.gr-abtesting-data-delete').on('submit', function(e) {
        if (!confirm('May I delete the log data?')) {
            e.preventDefault();
        }
    });
});