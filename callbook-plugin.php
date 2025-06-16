<?php
/*
Plugin Name: Callbook CRUD
Description: Ein WordPress-Plugin für ein Callbook mit CRUD-Funktionen, seitenweiser Darstellung mit Buttons, modalem Eingabefenster, SQL-Import für Admins und öffentlicher Anzeige mit modaler Detailansicht, gestylt mit Bootstrap. Unterstützt Updates von GitHub-Releases.
Version: 1.8
Author: Grok
GitHub Plugin URI: https://github.com/PE1FTL/callbook-crud
*/

// Sicherheitsprüfung
if (!defined('ABSPATH')) {
    exit;
}

// Datenbanktabelle bei Aktivierung erstellen
function callbook_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'callbook';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        prcall varchar(50) NOT NULL,
        name varchar(255) NOT NULL,
        qth varchar(255) DEFAULT '' NOT NULL,
        locator varchar(10) DEFAULT '' NOT NULL,
        mybbs varchar(50) DEFAULT '' NOT NULL,
        route text DEFAULT '' NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        website varchar(255) DEFAULT '' NOT NULL,
        prmail varchar(100) DEFAULT '' NOT NULL,
        bundesland varchar(100) DEFAULT '' NOT NULL,
        land varchar(100) DEFAULT '' NOT NULL,
        bemerkung text DEFAULT '' NOT NULL,
        regdatum datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'callbook_install');

// Admin-Menü hinzufügen
function callbook_menu() {
    add_menu_page('Callbook', 'Callbook', 'manage_options', 'callbook', 'callbook_admin_page', 'dashicons-book');
}
add_action('admin_menu', 'callbook_menu');

// Ressourcen einbinden (CSS, JS, Bootstrap)
function callbook_enqueue_assets() {
    // Bootstrap CSS
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3');
    // Plugin CSS
    wp_enqueue_style('callbook-styles', plugin_dir_url(__FILE__) . 'assets/callbook-styles.css', ['bootstrap'], '1.8');
    // Bootstrap JS
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [], '5.3.3', true);
    // Plugin JS
    wp_enqueue_script('callbook-scripts', plugin_dir_url(__FILE__) . 'assets/callbook-scripts.js', ['bootstrap'], '1.8', true);

    // Inline-Skript für Debugging
    wp_localize_script('callbook-scripts', 'callbookDebug', [
        'modalId' => 'callbookDetailModal',
        'debugEnabled' => WP_DEBUG,
    ]);
}
add_action('admin_enqueue_scripts', 'callbook_enqueue_assets');
add_action('wp_enqueue_scripts', 'callbook_enqueue_assets');

// SQL-Import-Funktion
function callbook_import_sql() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'callbook';
    
    if (isset($_POST['callbook_import']) && check_admin_referer('callbook_import_nonce')) {
        if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['sql_file']['tmp_name'];
            $file_name = $_FILES['sql_file']['name'];
            
            if (pathinfo($file_name, PATHINFO_EXTENSION) !== 'sql') {
                echo '<div class="alert alert-danger">Nur .sql-Dateien sind erlaubt.</div>';
                return;
            }
            
            $sql_content = file_get_contents($file);
            if ($sql_content === false) {
                echo '<div class="alert alert-danger">Fehler beim Lesen der Datei.</div>';
                return;
            }
            
            $statements = array_filter(array_map('trim', explode(';', $sql_content)), function($stmt) {
                return !empty($stmt) && strpos($stmt, 'INSERT INTO') !== false;
            });
            
            if (empty($statements)) {
                echo '<div class="alert alert-danger">Keine gültigen INSERT-Statements gefunden.</div>';
                return;
            }
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($statements as $statement) {
                if (strpos($statement, '`wp_callbook`') === false) {
                    continue;
                }
                
                preg_match("/INSERT INTO `wp_callbook` \(`id`, `prcall`, `name`, `qth`, `locator`, `mybbs`, `route`, `email`, `website`, `prmail`, `bundesland`, `land`, `bemerkung`, `regdatum`\) VALUES\s*\((.*?)\)/", $statement, $matches);
                if (isset($matches[1])) {
                    $values = str_getcsv($matches[1], ',', "'");
                    if (count($values) === 14) {
                        $data = [
                            'id' => trim($values[0]) === 'NULL' ? null : intval($values[0]),
                            'prcall' => sanitize_text_field(trim($values[1])),
                            'name' => sanitize_text_field(trim($values[2])),
                            'qth' => sanitize_text_field(trim($values[3])),
                            'locator' => sanitize_text_field(trim($values[4])),
                            'mybbs' => sanitize_text_field(trim($values[5])),
                            'route' => sanitize_textarea_field(trim($values[6])),
                            'email' => sanitize_email(trim($values[7])),
                            'website' => esc_url_raw(trim($values[8]) === 'NULL' ? '' : trim($values[8])),
                            'prmail' => sanitize_text_field(trim($values[9]) === 'NULL' ? '' : trim($values[9])),
                            'bundesland' => sanitize_text_field(trim($values[10]) === 'NULL' ? '' : trim($values[10])),
                            'land' => sanitize_text_field(trim($values[11]) === 'NULL' ? '' : trim($values[11])),
                            'bemerkung' => sanitize_textarea_field(trim($values[12]) === 'NULL' ? '' : trim($values[12])),
                            'regdatum' => trim($values[13]) === 'NULL' ? current_time('mysql') : trim($values[13])
                        ];
                        
                        unset($data['id']);
                        
                        $result = $wpdb->insert($table_name, $data, ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);
                        if ($result !== false) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    }
                }
            }
            
            if ($success_count > 0) {
                echo '<div class="alert alert-success">' . $success_count . ' Einträge erfolgreich importiert.</div>';
            }
            if ($error_count > 0) {
                echo '<div class="alert alert-danger">' . $error_count . ' Einträge konnten nicht importiert werden.</div>';
            }
            if ($success_count === 0 && $error_count === 0) {
                echo '<div class="alert alert-danger">Keine gültigen Daten zum Importieren gefunden.</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Keine Datei hochgeladen oder ein Fehler ist aufgetreten.</div>';
        }
    }
}

// Shortcode für öffentliche Ansicht
function callbook_public_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'callbook';
    
    $per_page = 10;
    $current_page = isset($_GET['callbook_page']) ? max(1, intval($_GET['callbook_page'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);
    
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY id ASC LIMIT %d OFFSET %d", $per_page, $offset));
    
    ob_start();
    ?>
    <div class="callbook-public container my-4">
        <h2>Callbook</h2>
        <p>Gesamtanzahl der Einträge: <?php echo $total_items; ?></p>
        <div class="table-responsive">
            <table class="callbook-table table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>PR-Call</th>
                        <th>Name</th>
                        <th>QTH</th>
                        <th>Locator</th>
                        <th>MyBBS</th>
                        <th>Route</th>
                        <th>E-Mail</th>
                        <th>Website</th>
                        <th>PR-Mail</th>
                        <th>Bundesland</th>
                        <th>Land</th>
                        <th>Bemerkung</th>
                        <th>Registrierungsdatum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                    <tr class="callbook-row" data-id="<?php echo esc_attr($row->id); ?>" 
                        data-prcall="<?php echo esc_attr($row->prcall); ?>" 
                        data-name="<?php echo esc_attr($row->name); ?>" 
                        data-qth="<?php echo esc_attr($row->qth); ?>" 
                        data-locator="<?php echo esc_attr($row->locator); ?>" 
                        data-mybbs="<?php echo esc_attr($row->mybbs); ?>" 
                        data-route="<?php echo esc_attr($row->route); ?>" 
                        data-email="<?php echo esc_attr($row->email); ?>" 
                        data-website="<?php echo esc_attr($row->website); ?>" 
                        data-prmail="<?php echo esc_attr($row->prmail); ?>" 
                        data-bundesland="<?php echo esc_attr($row->bundesland); ?>" 
                        data-land="<?php echo esc_attr($row->land); ?>" 
                        data-bemerkung="<?php echo esc_attr($row->bemerkung); ?>" 
                        data-regdatum="<?php echo esc_attr($row->regdatum); ?>">
                        <td><?php echo esc_html($row->id); ?></td>
                        <td><?php echo esc_html($row->prcall); ?></td>
                        <td><?php echo esc_html($row->name); ?></td>
                        <td><?php echo esc_html($row->qth); ?></td>
                        <td><?php echo esc_html($row->locator); ?></td>
                        <td><?php echo esc_html($row->mybbs); ?></td>
                        <td><?php echo esc_html($row->route); ?></td>
                        <td><?php echo esc_html($row->email); ?></td>
                        <td><?php echo esc_html($row->website); ?></td>
                        <td><?php echo esc_html($row->prmail); ?></td>
                        <td><?php echo esc_html($row->bundesland); ?></td>
                        <td><?php echo esc_html($row->land); ?></td>
                        <td><?php echo esc_html($row->bemerkung); ?></td>
                        <td><?php echo esc_html($row->regdatum); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Benutzerdefinierte Pagination -->
        <div class="callbook-pagination mt-3 d-flex justify-content-center">
            <?php if ($current_page > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('callbook_page', $current_page - 1)); ?>" class="btn btn-outline-primary mx-1">« Zurück</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="<?php echo esc_url(add_query_arg('callbook_page', $i)); ?>" class="btn btn-<?php echo $i === $current_page ? 'primary' : 'outline-primary'; ?> mx-1"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg('callbook_page', $current_page + 1)); ?>" class="btn btn-outline-primary mx-1">Weiter »</a>
            <?php endif; ?>
        </div>
        
        <!-- Detail-Modal -->
        <div class="modal fade" id="callbookDetailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailModalLabel">Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6"><p><strong>ID:</strong> <span id="detailId"></span></p></div>
                            <div class="col-md-6"><p><strong>PR-Call:</strong> <span id="detailPrcall"></span></p></div>
                            <div class="col-md-6"><p><strong>Name:</strong> <span id="detailName"></span></p></div>
                            <div class="col-md-6"><p><strong>QTH:</strong> <span id="detailQth"></span></p></div>
                            <div class="col-md-6"><p><strong>Locator:</strong> <span id="detailLocator"></span></p></div>
                            <div class="col-md-6"><p><strong>MyBBS:</strong> <span id="detailMybbs"></span></p></div>
                            <div class="col-md-6"><p><strong>Route:</strong> <span id="detailRoute"></span></p></div>
                            <div class="col-md-6"><p><strong>E-Mail:</strong> <span id="detailEmail"></span></p></div>
                            <div class="col-md-6"><p><strong>Website:</strong> <span id="detailWebsite"></span></p></div>
                            <div class="col-md-6"><p><strong>PR-Mail:</strong> <span id="detailPrmail"></span></p></div>
                            <div class="col-md-6"><p><strong>Bundesland:</strong> <span id="detailBundesland"></span></p></div>
                            <div class="col-md-6"><p><strong>Land:</strong> <span id="detailLand"></span></p></div>
                            <div class="col-12"><p><strong>Bemerkung:</strong> <span id="detailBemerkung"></span></p></div>
                            <div class="col-md-6"><p><strong>Registrierungsdatum:</strong> <span id="detailRegdatum"></span></p></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('callbook', 'callbook_public_shortcode');

// Admin-Seite rendern
function callbook_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'callbook';
    
    callbook_import_sql();
    
    $per_page = 10;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);
    
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY id ASC LIMIT %d OFFSET %d", $per_page, $offset));
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        check_admin_referer('callbook_delete_' . $_GET['id']);
        $wpdb->delete($table_name, ['id' => intval($_GET['id'])], ['%d']);
        echo '<div class="alert alert-success">Eintrag gelöscht.</div>';
    }
    
    if (isset($_POST['callbook_submit']) && check_admin_referer('callbook_save')) {
        $data = [
            'prcall' => sanitize_text_field($_POST['prcall']),
            'name' => sanitize_text_field($_POST['name']),
            'qth' => sanitize_text_field($_POST['qth']),
            'locator' => sanitize_text_field($_POST['locator']),
            'mybbs' => sanitize_text_field($_POST['mybbs']),
            'route' => sanitize_textarea_field($_POST['route']),
            'email' => sanitize_email($_POST['email']),
            'website' => esc_url_raw($_POST['website']),
            'prmail' => sanitize_text_field($_POST['prmail']),
            'bundesland' => sanitize_text_field($_POST['bundesland']),
            'land' => sanitize_text_field($_POST['land']),
            'bemerkung' => sanitize_textarea_field($_POST['bemerkung']),
            'regdatum' => current_time('mysql')
        ];
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $wpdb->update($table_name, $data, ['id' => intval($_POST['id'])], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'], ['%d']);
            echo '<div class="alert alert-success">Eintrag aktualisiert.</div>';
        } else {
            $wpdb->insert($table_name, $data, ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);
            echo '<div class="alert alert-success">Eintrag hinzugefügt.</div>';
        }
    }
?>
<div class="wrap container my-4">
    <h1>Callbook</h1>
    <p>Gesamtanzahl der Einträge: <?php echo $total_items; ?></p>
    
    <h2>SQL-Datei importieren</h2>
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <?php wp_nonce_field('callbook_import_nonce'); ?>
        <div class="mb-3">
            <label for="sql_file" class="form-label">SQL-Datei auswählen (.sql):</label>
            <input type="file" name="sql_file" id="sql_file" accept=".sql" class="form-control" required>
        </div>
        <button type="submit" name="callbook_import" class="btn btn-secondary">Importieren</button>
    </form>
    
    <button class="btn btn-primary mb-3" onclick="resetEditModal()">Neuer Eintrag</button>
    
    <div class="table-responsive">
        <table class="wp-list-table table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>PR-Call</th>
                    <th>Name</th>
                    <th>QTH</th>
                    <th>Locator</th>
                    <th>MyBBS</th>
                    <th>Route</th>
                    <th>E-Mail</th>
                    <th>Website</th>
                    <th>PR-Mail</th>
                    <th>Bundesland</th>
                    <th>Land</th>
                    <th>Bemerkung</th>
                    <th>Registrierungsdatum</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                <tr class="callbook-row" data-id="<?php echo esc_attr($row->id); ?>" 
                    data-prcall="<?php echo esc_attr($row->prcall); ?>" 
                    data-name="<?php echo esc_attr($row->name); ?>" 
                    data-qth="<?php echo esc_attr($row->qth); ?>" 
                    data-locator="<?php echo esc_attr($row->locator); ?>" 
                    data-mybbs="<?php echo esc_attr($row->mybbs); ?>" 
                    data-route="<?php echo esc_attr($row->route); ?>" 
                    data-email="<?php echo esc_attr($row->email); ?>" 
                    data-website="<?php echo esc_attr($row->website); ?>" 
                    data-prmail="<?php echo esc_attr($row->prmail); ?>" 
                    data-bundesland="<?php echo esc_attr($row->bundesland); ?>" 
                    data-land="<?php echo esc_attr($row->land); ?>" 
                    data-bemerkung="<?php echo esc_attr($row->bemerkung); ?>" 
                    data-regdatum="<?php echo esc_attr($row->regdatum); ?>">
                    <td><?php echo esc_html($row->id); ?></td>
                    <td><?php echo esc_html($row->prcall); ?></td>
                    <td><?php echo esc_html($row->name); ?></td>
                    <td><?php echo esc_html($row->qth); ?></td>
                    <td><?php echo esc_html($row->locator); ?></td>
                    <td><?php echo esc_html($row->mybbs); ?></td>
                    <td><?php echo esc_html($row->route); ?></td>
                    <td><?php echo esc_html($row->email); ?></td>
                    <td><?php echo esc_html($row->website); ?></td>
                    <td><?php echo esc_html($row->prmail); ?></td>
                    <td><?php echo esc_html($row->bundesland); ?></td>
                    <td><?php echo esc_html($row->land); ?></td>
                    <td><?php echo esc_html($row->bemerkung); ?></td>
                    <td><?php echo esc_html($row->regdatum); ?></td>
                    <td>
                        <a href="#" class="btn btn-sm btn-primary" onclick="event.stopPropagation(); openEditModal('<?php echo esc_js($row->id); ?>', '<?php echo esc_js($row->prcall); ?>', '<?php echo esc_js($row->name); ?>', '<?php echo esc_js($row->qth); ?>', '<?php echo esc_js($row->locator); ?>', '<?php echo esc_js($row->mybbs); ?>', '<?php echo esc_js($row->route); ?>', '<?php echo esc_js($row->email); ?>', '<?php echo esc_js($row->website); ?>', '<?php echo esc_js($row->prmail); ?>', '<?php echo esc_js($row->bundesland); ?>', '<?php echo esc_js($row->land); ?>', '<?php echo esc_js($row->bemerkung); ?>')" data-bs-toggle="modal" data-bs-target="#callbookEditModal">Editieren</a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=callbook&action=delete&id=' . $row->id), 'callbook_delete_' . $row->id); ?>" class="btn btn-sm btn-danger" onclick="event.stopPropagation(); return confirm('Sicher, dass Sie diesen Eintrag löschen möchten?')">Löschen</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Benutzerdefinierte Pagination -->
    <div class="tablenav mt-3 d-flex justify-content-center">
        <?php if ($current_page > 1): ?>
            <a href="<?php echo esc_url(add_query_arg('paged', $current_page - 1)); ?>" class="btn btn-outline-primary mx-1">« Zurück</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo esc_url(add_query_arg('paged', $i)); ?>" class="btn btn-<?php echo $i === $current_page ? 'primary' : 'outline-primary'; ?> mx-1"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo esc_url(add_query_arg('paged', $current_page + 1)); ?>" class="btn btn-outline-primary mx-1">Weiter »</a>
        <?php endif; ?>
    </div>
    
    <!-- Edit-Modal -->
    <div class="modal fade" id="callbookEditModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Neuer Eintrag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="callbookForm">
                        <?php wp_nonce_field('callbook_save'); ?>
                        <input type="hidden" name="id" id="editCallbookId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editPrcall" class="form-label">PR-Call:</label>
                                <input type="text" name="prcall" id="editPrcall" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editName" class="form-label">Name:</label>
                                <input type="text" name="name" id="editName" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editQth" class="form-label">QTH:</label>
                                <input type="text" name="qth" id="editQth" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editLocator" class="form-label">Locator:</label>
                                <input type="text" name="locator" id="editLocator" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editMybbs" class="form-label">MyBBS:</label>
                                <input type="text" name="mybbs" id="editMybbs" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editRoute" class="form-label">Route:</label>
                                <textarea name="route" id="editRoute" class="form-control" rows="4"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">E-Mail:</label>
                                <input type="email" name="email" id="editEmail" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editWebsite" class="form-label">Website:</label>
                                <input type="url" name="website" id="editWebsite" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editPrmail" class="form-label">PR-Mail:</label>
                                <input type="text" name="prmail" id="editPrmail" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editBundesland" class="form-label">Bundesland:</label>
                                <input type="text" name="bundesland" id="editBundesland" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editLand" class="form-label">Land:</label>
                                <input type="text" name="land" id="editLand" class="form-control">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="editBemerkung" class="form-label">Bemerkung:</label>
                                <textarea name="bemerkung" id="editBemerkung" class="form-control" rows="4"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="callbook_submit" class="btn btn-primary">Speichern</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detail-Modal -->
    <div class="modal fade" id="callbookDetailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6"><p><strong>ID:</strong> <span id="detailId"></span></p></div>
                        <div class="col-md-6"><p><strong>PR-Call:</strong> <span id="detailPrcall"></span></p></div>
                        <div class="col-md-6"><p><strong>Name:</strong> <span id="detailName"></span></p></div>
                        <div class="col-md-6"><p><strong>QTH:</strong> <span id="detailQth"></span></p></div>
                        <div class="col-md-6"><p><strong>Locator:</strong> <span id="detailLocator"></span></p></div>
                        <div class="col-md-6"><p><strong>MyBBS:</strong> <span id="detailMybbs"></span></p></div>
                        <div class="col-md-6"><p><strong>Route:</strong> <span id="detailRoute"></span></p></div>
                        <div class="col-md-6"><p><strong>E-Mail:</strong> <span id="detailEmail"></span></p></div>
                        <div class="col-md-6"><p><strong>Website:</strong> <span id="detailWebsite"></span></p></div>
                        <div class="col-md-6"><p><strong>PR-Mail:</strong> <span id="detailPrmail"></span></p></div>
                        <div class="col-md-6"><p><strong>Bundesland:</strong> <span id="detailBundesland"></span></p></div>
                        <div class="col-md-6"><p><strong>Land:</strong> <span id="detailLand"></span></p></div>
                        <div class="col-12"><p><strong>Bemerkung:</strong> <span id="detailBemerkung"></span></p></div>
                        <div class="col-md-6"><p><strong>Registrierungsdatum:</strong> <span id="detailRegdatum"></span></p></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}

// Admin-only Zugriff für Admin-Seite
function callbook_admin_only() {
    if (!current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'callbook') {
        wp_die('Zugriff verweigert.');
    }
}
add_action('admin_init', 'callbook_admin_only');

// Update-Logik für GitHub-Releases
class CallbookPluginUpdater {
    private $plugin_file;
    private $plugin_data;
    private $basename;
    private $github_repo;
    private $transient_key = 'callbook_update';

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->basename = plugin_basename($plugin_file);
        $this->github_repo = $this->get_github_repo();

        if (!$this->github_repo) {
            return;
        }

        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        // Plugin-Daten laden
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $this->plugin_data = get_plugin_data($this->plugin_file);

        // Hooks für Updates
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        add_filter('upgrader_pre_download', [$this, 'pre_download'], 10, 3);
    }

    private function get_github_repo() {
        $headers = get_file_data($this->plugin_file, ['GitHub Plugin URI' => 'GitHub Plugin URI']);
        $uri = $headers['GitHub Plugin URI'];
        if (empty($uri)) {
            return false;
        }

        // Extrahiere owner/repo aus URI (z. B. https://github.com/<owner>/<repo> oder <owner>/<repo>)
        $uri = str_replace('https://github.com/', '', rtrim($uri, '/'));
        if (strpos($uri, '/') === false) {
            return false;
        }

        return $uri;
    }

    public function check_for_update($transient) {
        if (empty($transient) || !is_object($transient)) {
            $transient = new stdClass();
        }

        // Transient prüfen
        $update = get_transient($this->transient_key);
        if (false === $update) {
            $update = $this->fetch_github_release();
            set_transient($this->transient_key, $update, HOUR_IN_SECONDS * 12);
        }

        if (!empty($update) && version_compare($this->plugin_data['Version'], $update['version'], '<')) {
            $transient->response[$this->basename] = (object) [
                'slug' => dirname($this->basename),
                'new_version' => $update['version'],
                'url' => $this->plugin_data['PluginURI'],
                'package' => $update['zip_url'],
                'tested' => '6.6',
                'requires' => '5.0',
            ];
        }

        return $transient;
    }

    private function fetch_github_release() {
        $response = wp_remote_get(
            "https://api.github.com/repos/{$this->github_repo}/releases/latest",
            [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                ],
                'timeout' => 15,
            ]
        );

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['tag_name']) || empty($data['assets'])) {
            return false;
        }

        $zip_url = '';
        foreach ($data['assets'] as $asset) {
            if (pathinfo($asset['name'], PATHINFO_EXTENSION) === 'zip') {
                $zip_url = $asset['browser_download_url'];
                break;
            }
        }

        if (empty($zip_url)) {
            return false;
        }

        return [
            'version' => $data['tag_name'],
            'zip_url' => $zip_url,
            'published_at' => $data['published_at'],
            'changelog' => $data['body'],
        ];
    }

    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== dirname($this->basename)) {
            return $result;
        }

        $update = get_transient($this->transient_key);
        if (false === $update) {
            $update = $this->fetch_github_release();
            set_transient($this->transient_key, $update, HOUR_IN_SECONDS * 12);
        }

        if (empty($update)) {
            return $result;
        }

        return (object) [
            'name' => $this->plugin_data['Name'],
            'slug' => dirname($this->basename),
            'version' => $update['version'],
            'author' => $this->plugin_data['Author'],
            'homepage' => $this->plugin_data['PluginURI'],
            'requires' => '5.0',
            'tested' => '6.6',
            'last_updated' => $update['published_at'],
            'download_link' => $update['zip_url'],
            'sections' => [
                'description' => $this->plugin_data['Description'],
                'changelog' => $update['changelog'],
            ],
        ];
    }

    public function pre_download($reply, $package, $upgrader) {
        if (strpos($package, 'github.com') === false) {
            return $reply;
        }

        // Sicherstellen, dass WP_Filesystem geladen ist
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        WP_Filesystem();

        global $wp_filesystem;
        if (!$wp_filesystem->is_writable(WP_PLUGIN_DIR)) {
            return new WP_Error('fs_unwritable', __('Plugin-Verzeichnis ist nicht beschreibbar.', 'callbook-crud'));
        }

        return $reply;
    }
}

// Updater initialisieren
new CallbookPluginUpdater(__FILE__);
?>