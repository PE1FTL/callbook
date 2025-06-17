jQuery(document).ready(function($) {
    // Formularübermittlung behandeln
    $('#save-entry').click(function() {
        var formData = $('#callbook-form').serialize();
        formData += '&operation=' + ($('#entry-id').val() ? 'update' : 'create');
        
        $.ajax({
            url: callbookAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            }
        });
    });

    // Bearbeitungs-Button behandeln
    $('.edit-entry').click(function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: callbookAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'callbook_crud',
                operation: 'get',
                id: id,
                nonce: callbookAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#entry-id').val(data.id);
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
                    $('#callbookModalLabel').text('Callbook-Eintrag bearbeiten');
                }
            }
        });
    });

    // Lösch-Button behandeln
    $('.delete-entry').click(function() {
        if (confirm('Möchtest du diesen Eintrag wirklich löschen?')) {
            var id = $(this).data('id');
            
            $.ajax({
                url: callbookAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'callbook_crud',
                    operation: 'delete',
                    id: id,
                    nonce: callbookAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Modal-Formular beim Schließen zurücksetzen
    $('#callbookModal').on('hidden.bs.modal', function() {
        $('#callbook-form')[0].reset();
        $('#entry-id').val('');
        $('#callbookModalLabel').text('Neuer Callbook-Eintrag');
    });
});