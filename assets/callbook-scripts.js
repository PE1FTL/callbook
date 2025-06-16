document.addEventListener('DOMContentLoaded', function() {
    // Event-Listener für Tabellenzeilen
    document.querySelectorAll('.callbook-row').forEach(row => {
        row.addEventListener('click', function(event) {
            // Verhindern, dass Klicks auf Buttons die Zeile triggern
            if (event.target.tagName.toLowerCase() === 'a' || event.target.tagName.toLowerCase() === 'button') {
                return;
            }

            if (callbookDebug.debugEnabled) {
                console.log('Row clicked:', row.dataset);
            }

            openDetailModal(
                row.dataset.id,
                row.dataset.prcall,
                row.dataset.name,
                row.dataset.qth,
                row.dataset.locator,
                row.dataset.mybbs,
                row.dataset.route,
                row.dataset.email,
                row.dataset.website,
                row.dataset.prmail,
                row.dataset.bundesland,
                row.dataset.land,
                row.dataset.bemerkung,
                row.dataset.regdatum
            );
        });
    });
});

function openEditModal(id = '', prcall = '', name = '', qth = '', locator = '', mybbs = '', route = '', email = '', website = '', prmail = '', bundesland = '', land = '', bemerkung = '') {
    if (callbookDebug.debugEnabled) {
        console.log('Opening edit modal with ID:', id);
    }

    document.getElementById('editModalLabel').textContent = id ? 'Eintrag bearbeiten' : 'Neuer Eintrag';
    document.getElementById('editCallbookId').value = id;
    document.getElementById('editPrcall').value = prcall;
    document.getElementById('editName').value = name;
    document.getElementById('editQth').value = qth;
    document.getElementById('editLocator').value = locator;
    document.getElementById('editMybbs').value = mybbs;
    document.getElementById('editRoute').value = route;
    document.getElementById('editEmail').value = email;
    document.getElementById('editWebsite').value = website;
    document.getElementById('editPrmail').value = prmail;
    document.getElementById('editBundesland').value = bundesland;
    document.getElementById('editLand').value = land;
    document.getElementById('editBemerkung').value = bemerkung;
}

function resetEditModal() {
    if (callbookDebug.debugEnabled) {
        console.log('Resetting edit modal');
    }

    openEditModal(); // Leere Felder durch Aufruf ohne Parameter
    const modal = new bootstrap.Modal(document.getElementById('callbookEditModal'));
    modal.show();
}

function openDetailModal(id, prcall, name, qth, locator, mybbs, route, email, website, prmail, bundesland, land, bemerkung, regdatum) {
    if (callbookDebug.debugEnabled) {
        console.log('Opening detail modal with ID:', id);
    }

    try {
        document.getElementById('detailId').textContent = id || '';
        document.getElementById('detailPrcall').textContent = prcall || '';
        document.getElementById('detailName').textContent = name || '';
        document.getElementById('detailQth').textContent = qth || '';
        document.getElementById('detailLocator').textContent = locator || '';
        document.getElementById('detailMybbs').textContent = mybbs || '';
        document.getElementById('detailRoute').textContent = route || '';
        document.getElementById('detailEmail').textContent = email || '';
        document.getElementById('detailWebsite').textContent = website || '';
        document.getElementById('detailPrmail').textContent = prmail || '';
        document.getElementById('detailBundesland').textContent = bundesland || '';
        document.getElementById('detailLand').textContent = land || '';
        document.getElementById('detailBemerkung').textContent = bemerkung || '';
        document.getElementById('detailRegdatum').textContent = regdatum || '';

        const modalElement = document.getElementById(callbookDebug.modalId);
        if (!modalElement) {
            throw new Error('Modal element not found');
        }

        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } catch (error) {
        if (callbookDebug.debugEnabled) {
            console.error('Error opening detail modal:', error);
        }
    }
}