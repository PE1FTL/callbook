jQuery(document).ready(function($) {
    // Funktion zum Binden von Klick-Events für edit-row
    function bindEditRowEvents() {
        $('.edit-row').off('click').on('click', function() {
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
    }

    // Initiale Bindung der Events
    bindEditRowEvents();

    // Pagination Klick-Event
    $(document).on('click', '#admin-callbook-table .page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (!page || $(this).parent().hasClass('disabled')) return;

        $.ajax({
            url: ajax_object.ajaxurl,
            method: 'POST',
            data: {
                action: 'callbook_get_page',
                page: page,
                is_admin: true
            },
            success: function(response) {
                $('#admin-callbook-table').html(response);
                bindEditRowEvents();
            }
        });
    });
});