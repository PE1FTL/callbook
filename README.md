# callbook
Änderungen und Erläuterungen
Plugin-Änderungen (callbook.php):
Die Update-Prüfung wurde von der GitHub-API (releases/latest) auf eine benutzerdefinierte update.json-Datei umgestellt, die im Repository liegt (https://raw.githubusercontent.com/pe1ftl/callbook/main/update.json).
Neue Methoden check_for_update und plugin_info wurden hinzugefügt, um die WordPress-Update-API zu unterstützen. Diese Methoden prüfen die update.json-Datei und stellen Plugin-Informationen bereit.
Die AJAX-Update-Prüfung (check_github_update) wurde entfernt, da die WordPress-Update-API nun verwendet wird.
Die Admin-JS-Datei wurde entsprechend angepasst, um die entfernte AJAX-Prüfung zu berücksichtigen.
Assets-Dateien:
Die CSS- und JS-Dateien (callbook.css, callbook.js) bleiben unverändert.
Die callbook-admin.js wurde angepasst, um die entfernte Update-Prüfung zu reflektieren, behält aber die Logik für die Datensatzbearbeitung bei.
GitHub-Workflow (version.yml):
Ein neuer Schritt erstellt die update.json-Datei mit den Feldern version, package, changelog, requires (minimale WordPress-Version) und tested (getestete WordPress-Version).
Das Plugin wird als ZIP-Datei (callbook.zip) verpackt, die den Ordner callbook mit callbook.php und dem assets-Verzeichnis enthält.
Die ZIP-Datei wird als Release-Asset hochgeladen, sodass sie über den in update.json angegebenen Download-Link verfügbar ist.
Die update.json-Datei wird zusammen mit callbook.php committed und gepusht.
Verzeichnisstruktur: Die Verzeichnisstruktur des Plugins bleibt wie folgt:
text

Einklappen

Zeilenumbruch

Kopieren
callbook/
├── callbook.php
├── update.json (wird vom Workflow generiert)
├── assets/
│   ├── css/
│   │   └── callbook.css
│   └── js/
│       ├── callbook.js
│       └── callbook-admin.js
Installation und Nutzung:
Laden Sie die Dateien in das WordPress-Plugin-Verzeichnis (wp-content/plugins/callbook).
Aktivieren Sie das Plugin im WordPress-Admin-Bereich.
Verwenden Sie den Shortcode [callbook] für die Frontend-Anzeige.
Importieren Sie die SQL-Datei im Admin-Bereich.
WordPress prüft automatisch auf Updates, indem es die update.json-Datei abruft. Bei verfügbaren Updates wird dies im Plugin-Bereich des WordPress-Dashboards angezeigt.
GitHub-Setup:
Erstellen Sie ein Repository unter pe1ftl/callbook.
Fügen Sie die Dateien hinzu und aktivieren Sie GitHub Actions.
Der Workflow erstellt bei jedem Push eine neue Version, aktualisiert update.json, verpackt das Plugin als ZIP und erstellt ein Release mit dem ZIP-Asset.
Stellen Sie sicher, dass das Repository öffentlich ist oder ein gültiger GitHub-Token für private Repositories konfiguriert ist.
Beispiel für update.json
Die generierte update.json-Datei sieht beispielsweise so aus:

json

Einklappen

Zeilenumbruch

Kopieren
{
  "version": "0.0.1-0001",
  "package": "https://github.com/pe1ftl/callbook/releases/download/0.0.1-0001/callbook.zip",
  "changelog": "Version 0.0.1-0001 released on 2025-06-17",
  "requires": "5.0",
  "tested": "6.5"
}
Fazit
Der angepasste Workflow erstellt eine update.json-Datei und ein ZIP-Archiv für das Plugin, das als Release-Asset hochgeladen wird. Das Plugin unterstützt nun automatische Updates über die WordPress-Update-API, und alle Anforderungen (Datenbankverwaltung, Bootstrap 5, assets-Verzeichnis, Versionserhöhung) bleiben erfüllt. Das lastupdate-Feld wird weiterhin bei jeder Bearbeitung automatisch aktualisiert.
