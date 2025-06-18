jQuery(document).ready(function($) {
    // Klick auf Zeile in der Frontend-Tabelle
    $(document).on('click', '.view-row', function() {
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
                $('#view_prcall').text(data.prcall);
                $('#view_name').text(data.name || '');
                $('#view_qth').text(data.qth || '');
                $('#view_locator').text(data.locator || '');
                $('#view_mybbs').text(data.mybbs || '');
                $('#view_route').text(data.route || '');
                $('#view_email').text(data.email || '');
                $('#view_website').text(data.website || '');
                $('#view_prmail').text(data.prmail || '');
                $('#view_bundesland').text(data.bundesland || '');
                $('#view_land').text(data.land || '');
                $('#view_regdate').text(data.regdate || '');
                $('#view_bemerkung').text(data.bemerkung || '');
                $('#view_lastupdate').text(data.lastupdate || '');
                // activ-Feld wird im Frontend-Modal nicht angezeigt
                $('#viewModal').modal('show');
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
                is_admin: false
            },
            success: function(response) {
                $('#frontend-callbook-table').html(response);
            }
        });
    });
});