<?php
/*
Plugin Name: Callbook
Description: Ein Plugin zur Verwaltung und Anzeige einer Callbook-Datenbank mit CRUD-Operationen und GitHub-Auto-Updates.
Version: 1.0.0
Author: Grok
License: GPL2
*/

define('CALLBOOK_VERSION', '1.0.0');

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Initialisierung
class Callbook_Plugin {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'callbook';
        
        // Registriere Hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_callbook_crud', [$this, 'handle_crud_operations']);
        add_action('wp_ajax_nopriv_callbook_crud', [$this, 'handle_crud_operations']);
        
        // Shortcode für Frontend-Anzeige
        add_shortcode('callbook_table', [$this, 'render_callbook_table']);
        
        // GitHub-Auto-Update
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_github_update']);
    }

    // Admin-Menü hinzufügen
    public function add_admin_menu() {
        add_menu_page(
            'Callbook',
            'Callbook',
            'manage_options',
            'callbook',
            [$this, 'render_admin_page'],
            'dashicons-book',
            20
        );
    }

    // Scripts und Styles einbinden
    public function enqueue_scripts() {
        wp_enqueue_style('callbook-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css');
        wp_enqueue_script('callbook-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', [], false, true);
        wp_enqueue_style('callbook-style', plugins_url('assets/style.css', __FILE__));
        wp_enqueue_script('callbook-script', plugins_url('assets/script.js', __FILE__), ['jquery'], false, true);
        
        wp_localize_script('callbook-script', 'callbookAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('callbook_nonce')
        ]);
    }

    // Admin-Seite rendern
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Callbook-Verwaltung</h1>
            <?php $this->render_callbook_table(true); ?>
        </div>
        <?php
    }

    // Callbook-Tabelle rendern
    public function render_callbook_table($is_admin = false) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$this->table_name}");
        
        ob_start();
        ?>
        <div class="callbook-container">
            <?php if (current_user_can('manage_options') || !$is_admin): ?>
                <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#callbookModal">
                    Neuer Eintrag
                </button>
            <?php endif; ?>
            
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Call</th>
                        <th>Name</th>
                        <th>QTH</th>
                        <th>Locator</th>
                        <th>BBS</th>
                        <th>Route</th>
                        <th>Email</th>
                        <th>Website</th>
                        <th>PR Mail</th>
                        <th>Bundesland</th>
                        <th>Land</th>
                        <th>Bemerkung</th>
                        <th>Registrierungsdatum</th>
                        <?php if ($is_admin && current_user_can('manage_options')): ?>
                            <th>Aktionen</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
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
                            <?php if ($is_admin && current_user_can('manage_options')): ?>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-entry" data-id="<?php echo $row->id; ?>" data-bs-toggle="modal" data-bs-target="#callbookModal">Bearbeiten</button>
                                    <button class="btn btn-sm btn-danger delete-entry" data-id="<?php echo $row->id; ?>">Löschen</button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Modal -->
            <div class="modal fade" id="callbookModal" tabindex="-1" aria-labelledby="callbookModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="callbookModalLabel">Callbook-Eintrag hinzufügen/bearbeiten</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="callbook-form">
                                <input type="hidden" name="id" id="entry-id">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="prcall" class="form-label">Call</label>
                                        <input type="text" class="form-control" name="prcall" id="prcall" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" name="name" id="name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="qth" class="form-label">QTH</label>
                                        <input type="text" class="form-control" name="qth" id="qth">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="locator" class="form-label">Locator</label>
                                        <input type="text" class="form-control" name="locator" id="locator">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="mybbs" class="form-label">BBS</label>
                                        <input type="text" class="form-control" name="mybbs" id="mybbs">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="route" class="form-label">Route</label>
                                        <input type="text" class="form-control" name="route" id="route">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" id="email">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="website" class="form-label">Website</label>
                                        <input type="url" class="form-control" name="website" id="website">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="prmail" class="form-label">PR Mail</label>
                                        <input type="text" class="form-control" name="prmail" id="prmail">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="bundesland" class="form-label">Bundesland</label>
                                        <input type="text" class="form-control" name="bundesland" id="bundesland">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="land" class="form-label">Land</label>
                                        <input type="text" class="form-control" name="land" id="land">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label for="bemerkung" class="form-label">Bemerkung</label>
                                        <textarea class="form-control" name="bemerkung" id="bemerkung" rows="4"></textarea>
                                    </div>
                                </div>
                                <input type="hidden" name="action" value="callbook_crud">
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('callbook_nonce'); ?>">
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                            <button type="button" class="btn btn-primary" id="save-entry">Änderungen speichern</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // CRUD-Operationen verarbeiten
    public function handle_crud_operations() {
        global $wpdb;
        
        check_ajax_referer('callbook_nonce', 'nonce');
        
        $action = isset($_POST['operation']) ? sanitize_text_field($_POST['operation']) : '';
        $data = [
            'prcall' => sanitize_text_field($_POST['prcall']),
            'name' => sanitize_text_field($_POST['name']),
            'qth' => sanitize_text_field($_POST['qth']),
            'locator' => sanitize_text_field($_POST['locator']),
            'mybbs' => sanitize_text_field($_POST['mybbs']),
            'route' => sanitize_text_field($_POST['route']),
            'email' => sanitize_email($_POST['email']),
            'website' => esc_url_raw($_POST['website']),
            'prmail' => sanitize_text_field($_POST['prmail']),
            'bundesland' => sanitize_text_field($_POST['bundesland']),
            'land' => sanitize_text_field($_POST['land']),
            'bemerkung' => sanitize_textarea_field($_POST['bemerkung']),
            'regdatum' => current_time('mysql')
        ];
        
        if ($action === 'create') {
            $result = $wpdb->insert($this->table_name, $data);
            wp_send_json_success(['message' => 'Eintrag erfolgreich erstellt']);
        } elseif ($action === 'update' && current_user_can('manage_options')) {
            $id = intval($_POST['id']);
            $result = $wpdb->update($this->table_name, $data, ['id' => $id]);
            wp_send_json_success(['message' => 'Eintrag erfolgreich aktualisiert']);
        } elseif ($action === 'delete' && current_user_can('manage_options')) {
            $id = intval($_POST['id']);
            $result = $wpdb->delete($this->table_name, ['id' => $id]);
            wp_send_json_success(['message' => 'Eintrag erfolgreich gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Ungültige Operation oder Berechtigung']);
        }
    }

    // GitHub-Updates prüfen
    public function check_github_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = wp_remote_get('https://api.github.com/repos/pe1ftl/callbook/releases/latest');
        
        if (is_wp_error($remote) || wp_remote_retrieve_response_code($remote) !== 200) {
            return $transient;
        }

        $remote = json_decode(wp_remote_retrieve_body($remote));
        $remote_version = ltrim($remote->tag_name, 'v');
        
        if (version_compare($remote_version, CALLBOOK_VERSION, '>')) {
            $transient->response['callbook/callbook.php'] = [
                'slug' => 'callbook',
                'new_version' => $remote_version,
                'url' => 'https://github.com/pe1ftl/callbook',
                'package' => $remote->zipball_url
            ];
        }
        
        return $transient;
    }
}

// Plugin initialisieren
new Callbook_Plugin();

// Aktivierungs-Hook zur Sicherstellung der Tabellenexistenz
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'callbook';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        prcall varchar(10) DEFAULT NULL,
        name varchar(100) DEFAULT NULL,
        qth varchar(100) DEFAULT NULL,
        locator varchar(10) DEFAULT NULL,
        mybbs varchar(50) DEFAULT NULL,
        route varchar(255) DEFAULT NULL,
        email varchar(100) DEFAULT NULL,
        website varchar(255) DEFAULT NULL,
        prmail varchar(100) DEFAULT NULL,
        bundesland varchar(100) DEFAULT NULL,
        land varchar(100) DEFAULT NULL,
        bemerkung text DEFAULT NULL,
        regdatum datetime DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});
