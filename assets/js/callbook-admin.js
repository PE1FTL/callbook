jQuery(document).ready(function($) {
    $('.edit-row').click(function() {
        var id = $(this).data('id');
        $.ajax({
            url: ajax_object.ajaxurl,
            method: 'POST',
            data: {
                action: 'callbook_get_row',
                id: id
            },
            success: function(response) {
                var data = JSON.parse(response);
                $('#edit_id').val(data.id);
                $('#prcall').val(data.prcall);
                $('#name').val(data.name);
                $('#qth').val(data.qth);
                $('#locator').val(data.locator);
                $('#mybbs').val(data.mybbs);
                $('#route').val(data.route);
                $('#email').val(data.email);
                $('#website').val(data.website);
                $('#prmail').val(data.prmail);
                $('#bundesland').val(data.bundesland);
                $('#land').val(data.land);
                $('#bemerkung').val(data.bemerkung);
                $('#regdate').val(data.regdate);
                $('#editModal').modal('show');
            }
        });
    });

    // Prüfen auf Updates alle 24 Stunden
    setInterval(function() {
        $.ajax({
            url: ajax_object.ajaxurl,
            method: 'POST',
            data: {
                action: 'check_github_update'
            },
            success: function(response) {
                if (response === 'update_available') {
                    alert('Ein neues Update für das Callbook-Plugin ist verfügbar!');
                }
            }
        });
    }, 86400000);
});