function openEditModal(id = '', prcall = '', name = '', qth = '', locator = '', mybbs = '', route = '', email = '', website = '', prmail = '', bundesland = '', land = '', bemerkung = '') {
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
    openEditModal(); // Leere Felder durch Aufruf ohne Parameter
    const modal = new bootstrap.Modal(document.getElementById('callbookEditModal'));
    modal.show();
}

function openDetailModal(id, prcall, name, qth, locator, mybbs, route, email, website, prmail, bundesland, land, bemerkung, regdatum) {
    document.getElementById('detailId').textContent = id;
    document.getElementById('detailPrcall').textContent = prcall;
    document.getElementById('detailName').textContent = name;
    document.getElementById('detailQth').textContent = qth;
    document.getElementById('detailLocator').textContent = locator;
    document.getElementById('detailMybbs').textContent = mybbs;
    document.getElementById('detailRoute').textContent = route;
    document.getElementById('detailEmail').textContent = email;
    document.getElementById('detailWebsite').textContent = website;
    document.getElementById('detailPrmail').textContent = prmail;
    document.getElementById('detailBundesland').textContent = bundesland;
    document.getElementById('detailLand').textContent = land;
    document.getElementById('detailBemerkung').textContent = bemerkung;
    document.getElementById('detailRegdatum').textContent = regdatum;
}