jQuery(document).ready(function($) {
    // Funktion zum Ausblenden von Nachrichten nach 5 Sekunden
    function hideMessages() {
        if ($('.alert').length) {
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    // Nachrichten beim Laden der Seite ausblenden
    hideMessages();

    // Klick auf Zeile in der Admin-Tabelle
    $(document).on('click', '.edit-row', function() {
        var id = $(this).data('id');
        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'callbook_get_row',
                id: id
            },
            success: function(response) {
                var data = JSON.parse(response);
                $('#edit_id').val(data.id);
                $('#prcall').val(data.prcall);
                $('#name').val(data.name || '');
                $('#qth').val(data.qth || '');
                $('#locator').val(data.locator || '');
                $('#mybbs').val(data.mybbs || '');
                $('#route').val(data.route || '');
                $('#email').val(data.email || '');
                $('#website').val(data.website || '');
                $('#prmail').val(data.prmail || '');
                $('#bundesland').val(data.bundesland || '');
                $('#land').val(data.land || '');
                $('#regdate').val(data.regdate || '');
                $('#bemerkung').val(data.bemerkung || '');
                $('#activ').prop('checked', data.activ == 1);
                $('#editModal').modal('show');
            }
        });
    });

    // Pagination Klick-Handler
    $(document).on('click', '.pagination a.page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'callbook_get_page',
                page: page,
                is_admin: true
            },
            success: function(response) {
                $('#admin-callbook-table').html(response);
                // Nachrichten nach AJAX-Update ausblenden
                hideMessages();
            }
        });
    });
});