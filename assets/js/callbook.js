jQuery(document).ready(function($) {
    console.log('Callbook JS loaded');

    // Funktion zum Binden von Klick-Events für view-row
    function bindViewRowEvents() {
        $('.view-row').off('click').on('click', function() {
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
    }

    // Initiale Bindung der Events
    bindViewRowEvents();

    // Pagination Klick-Event
    $(document).on('click', '#frontend-callbook-table .page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (!page || $(this).parent().hasClass('disabled')) return;

        $.ajax({
            url: ajax_object.ajaxurl,
            method: 'POST',
            data: {
                action: 'callbook_get_page',
                page: page,
                is_admin: false
            },
            success: function(response) {
                $('#frontend-callbook-table').html(response);
                bindViewRowEvents();
            }
        });
    });
});