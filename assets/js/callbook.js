jQuery(document).ready(function($) {
    console.log('Callbook JS loaded');

    // Klick auf Tabellenzeile im Frontend
    $('.view-row').click(function() {
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
                $('#view_prcall').text(data.prcall || '');
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
                $('#view_bemerkung').text(data.bemerkung || '');
                $('#view_regdate').text(data.regdate || '');
                $('#view_lastupdate').text(data.lastupdate || '');
                $('#viewModal').modal('show');
            }
        });
    });
});