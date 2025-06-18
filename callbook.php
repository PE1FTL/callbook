<?php
/*
Plugin Name: Callbook
Description: Ein WordPress-Plugin zur Verwaltung einer Callbook-Datenbank mit automatischen Updates von GitHub und Bootstrap 5 Integration.
Version: 0.0.7
Author: xAI
License: GPL2
*/

// Sicherstellen, dass das Plugin nicht direkt aufgerufen wird
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Klasse
class CallbookPlugin {
    private $table_name;
    private $version = '0.0.7';
    private $update_url = 'https://raw.githubusercontent.com/pe1ftl/callbook/main/update.json';
    private $items_per_page = 10;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'callbook';
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_callbook_get_row', [$this, 'get_row']);
        add_action('wp_ajax_nopriv_callbook_get_row', [$this, 'get_row']);
        add_action('wp_ajax_callbook_get_page', [$this, 'get_page']);
        add_action('wp_ajax_nopriv_callbook_get_page', [$this, 'get_page']);
        add_shortcode('callbook', [$this, 'display_callbook']);
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
    }

    // Plugin-Aktivierung
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            prcall varchar(25) NOT NULL,
            name varchar(23) DEFAULT NULL,
            qth varchar(27) DEFAULT NULL,
            locator varchar(13) DEFAULT NULL,
            mybbs varchar(27) DEFAULT NULL,
            route varchar(81) DEFAULT NULL,
            email varchar(30) DEFAULT NULL,
            website varchar(33) DEFAULT NULL,
            prmail varchar(36) DEFAULT NULL,
            bundesland varchar(22) DEFAULT NULL,
            land varchar(14) DEFAULT NULL,
            bemerkung varchar(112) DEFAULT NULL,
            regdate varchar(16) DEFAULT NULL,
            lastupdate datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('callbook_version', $this->version);
    }

    // Admin-Menü hinzufügen
    public function add_admin_menu() {
        add_menu_page('Callbook Verwaltung', 'Callbook', 'manage_options', 'callbook', [$this, 'admin_page'], 'dashicons-book-alt');
    }

    // Admin-Seite
    public function admin_page() {
        global $wpdb;
        $message = '';

        // Datenbank-Import
        if (isset($_POST['callbook_import']) && check_admin_referer('callbook_import_nonce')) {
            if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] == 0) {
                $file = $_FILES['sql_file']['tmp_name'];
                $sql = file_get_contents($file);
                $wpdb->query('SET foreign_key_checks = 0');
                $wpdb->query($sql);
                $message = '<div class="alert alert-success">Datenbank erfolgreich importiert.</div>';
            } else {
                $message = '<div class="alert alert-danger">Fehler beim Hochladen der Datei.</div>';
            }
        }

        // Datensatz bearbeiten
        if (isset($_POST['callbook_edit']) && check_admin_referer('callbook_edit_nonce')) {
            $id = intval($_POST['id']);
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
                'bemerkung' => sanitize_text_field($_POST['bemerkung']),
                'regdate' => sanitize_text_field($_POST['regdate']),
                'lastupdate' => current_time('mysql')
            ];
            $wpdb->update($this->table_name, $data, ['id' => $id]);
            $message = '<div class="alert alert-success">Datensatz erfolgreich aktualisiert.</div>';
        }

        // Pagination
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $this->items_per_page;
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
        $total_pages = ceil($total_items / $this->items_per_page);
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->table_name LIMIT %d OFFSET %d", $this->items_per_page, $offset));

        // Bereich für Pagination-Links (±2 Seiten)
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);

        ?>
        <div class="wrap">
            <h1>Callbook Verwaltung</h1>
            <?php echo $message; ?>
            <h2>Datenbank Import</h2>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('callbook_import_nonce'); ?>
                <input type="file" name="sql_file" accept=".sql">
                <input type="submit" name="callbook_import" class="btn btn-primary" value="SQL-Datei importieren">
            </form>
            <h2>Datensätze</h2>
            <div id="admin-callbook-table">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>PR Call</th>
                            <th>Name</th>
                            <th>QTH</th>
                            <th>Locator</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) : ?>
                            <tr class="edit-row" data-id="<?php echo esc_attr($row->id); ?>">
                                <td><?php echo esc_html($row->id); ?></td>
                                <td><?php echo esc_html($row->prcall); ?></td>
                                <td><?php echo esc_html($row->name); ?></td>
                                <td><?php echo esc_html($row->qth); ?></td>
                                <td><?php echo esc_html($row->locator); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Pagination -->
                <?php if ($total_pages > 1) : ?>
                    <nav aria-label="Callbook Pagination">
                        <ul class="pagination">
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=callbook&paged=1" data-page="1"><i class="bi bi-skip-start-fill"></i></a>
                            </li>
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=callbook&paged=<?php echo $current_page - 1; ?>" data-page="<?php echo $current_page - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=callbook&paged=<?php echo $i; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=callbook&paged=<?php echo $current_page + 1; ?>" data-page="<?php echo $current_page + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=callbook&paged=<?php echo $total_pages; ?>" data-page="<?php echo $total_pages; ?>"><i class="bi bi-skip-end-fill"></i></a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
            <!-- Bearbeitungsmodal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="post">
                            <?php wp_nonce_field('callbook_edit_nonce'); ?>
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel">Datensatz bearbeiten</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="edit_id">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="prcall" class="form-label">PR Call</label>
                                            <input type="text" class="form-control" name="prcall" id="prcall" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" class="form-control" name="name" id="name">
                                        </div>
                                        <div class="mb-3">
                                            <label for="qth" class="form-label">QTH</label>
                                            <input type="text" class="form-control" name="qth" id="qth">
                                        </div>
                                        <div class="mb-3">
                                            <label for="locator" class="form-label">Locator</label>
                                            <input type="text" class="form-control" name="locator" id="locator">
                                        </div>
                                        <div class="mb-3">
                                            <label for="mybbs" class="form-label">MyBBS</label>
                                            <input type="text" class="form-control" name="mybbs" id="mybbs">
                                        </div>
                                        <div class="mb-3">
                                            <label for="route" class="form-label">Route</label>
                                            <input type="text" class="form-control" name="route" id="route">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" id="email">
                                        </div>
                                        <div class="mb-3">
                                            <label for="website" class="form-label">Website</label>
                                            <input type="url" class="form-control" name="website" id="website">
                                        </div>
                                        <div class="mb-3">
                                            <label for="prmail" class="form-label">PR Mail</label>
                                            <input type="text" class="form-control" name="prmail" id="prmail">
                                        </div>
                                        <div class="mb-3">
                                            <label for="bundesland" class="form-label">Bundesland</label>
                                            <input type="text" class="form-control" name="bundesland" id="bundesland">
                                        </div>
                                        <div class="mb-3">
                                            <label for="land" class="form-label">Land</label>
                                            <input type="text" class="form-control" name="land" id="land">
                                        </div>
                                        <div class="mb-3">
                                            <label for="regdate" class="form-label">Registrierungsdatum</label>
                                            <input type="text" class="form-control" name="regdate" id="regdate">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="bemerkung" class="form-label">Bemerkung</label>
                                    <textarea class="form-control" name="bemerkung" id="bemerkung" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                                <input type="submit" name="callbook_edit" class="btn btn-primary" value="Speichern">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // AJAX-Handler für Datensatzabfrage
    public function get_row() {
        global $wpdb;
        $id = intval($_POST['id']);
        $row = $wpdb->get_row("SELECT * FROM $this->table_name WHERE id = $id", ARRAY_A);
        echo json_encode($row);
        wp_die();
    }

    // AJAX-Handler für paginierte Tabelle
    public function get_page() {
        global $wpdb;
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === 'true';
        $offset = ($page - 1) * $this->items_per_page;
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->table_name LIMIT %d OFFSET %d", $this->items_per_page, $offset));
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
        $total_pages = ceil($total_items / $this->items_per_page);

        // Bereich für Pagination-Links (±2 Seiten)
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);

        ob_start();
        ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PR Call</th>
                    <th>Name</th>
                    <th>QTH</th>
                    <th>Locator</th>
                    <?php if ($is_admin) : ?>
                        <th></th>
                    <?php else : ?>
                        <th>Email</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                    <tr class="<?php echo $is_admin ? 'edit-row' : 'view-row'; ?>" data-id="<?php echo esc_attr($row->id); ?>">
                        <td><?php echo esc_html($row->id); ?></td>
                        <td><?php echo esc_html($row->prcall); ?></td>
                        <td><?php echo esc_html($row->name); ?></td>
                        <td><?php echo esc_html($row->qth); ?></td>
                        <td><?php echo esc_html($row->locator); ?></td>
                        <?php if (!$is_admin) : ?>
                            <td><?php echo esc_html($row->email); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($total_pages > 1) : ?>
            <nav aria-label="Callbook Pagination">
                <ul class="pagination">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="#" data-page="1"><i class="bi bi-skip-start-fill"></i></a>
                    </li>
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="#" data-page="<?php echo $page - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="#" data-page="<?php echo $page + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="#" data-page="<?php echo $total_pages; ?>"><i class="bi bi-skip-end-fill"></i></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
        <?php
        echo ob_get_clean();
        wp_die();
    }

    // Scripts und Styles einbinden
    public function enqueue_scripts() {
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');
        wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css', [], '1.10.5');
        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0', true);
        wp_enqueue_style('callbook', plugin_dir_url(__FILE__) . 'assets/css/callbook.css', [], $this->version);
        wp_enqueue_script('callbook', plugin_dir_url(__FILE__) . 'assets/js/callbook.js', ['jquery'], $this->version, true);
        wp_localize_script('callbook', 'ajax_object', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }

    public function enqueue_admin_scripts() {
        $this->enqueue_scripts();
        wp_enqueue_script('callbook-admin', plugin_dir_url(__FILE__) . 'assets/js/callbook-admin.js', ['jquery'], $this->version, true);
        wp_localize_script('callbook-admin', 'ajax_object', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }

    // Shortcode für Frontend-Anzeige
    public function display_callbook() {
        global $wpdb;
        $current_page = isset($_GET['callbook_page']) ? max(1, intval($_GET['callbook_page'])) : 1;
        $offset = ($current_page - 1) * $this->items_per_page;
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
        $total_pages = ceil($total_items / $this->items_per_page);
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->table_name LIMIT %d OFFSET %d", $this->items_per_page, $offset));

        // Bereich für Pagination-Links (±2 Seiten)
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);

        ob_start();
        ?>
        <div class="container">
            <h2>Callbook</h2>
            <div id="frontend-callbook-table">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>PR Call</th>
                            <th>Name</th>
                            <th>QTH</th>
                            <th>Locator</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) : ?>
                            <tr class="view-row" data-id="<?php echo esc_attr($row->id); ?>">
                                <td><?php echo esc_html($row->id); ?></td>
                                <td><?php echo esc_html($row->prcall); ?></td>
                                <td><?php echo esc_html($row->name); ?></td>
                                <td><?php echo esc_html($row->qth); ?></td>
                                <td><?php echo esc_html($row->locator); ?></td>
                                <td><?php echo esc_html($row->email); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Pagination -->
                <?php if ($total_pages > 1) : ?>
                    <nav aria-label="Callbook Pagination">
                        <ul class="pagination">
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?callbook_page=1" data-page="1"><i class="bi bi-skip-start-fill"></i></a>
                            </li>
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?callbook_page=<?php echo $current_page - 1; ?>" data-page="<?php echo $current_page - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?callbook_page=<?php echo $i; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?callbook_page=<?php echo $current_page + 1; ?>" data-page="<?php echo $current_page + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?callbook_page=<?php echo $total_pages; ?>" data-page="<?php echo $total_pages; ?>"><i class="bi bi-skip-end-fill"></i></a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
            <!-- Anzeigemodal -->
            <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewModalLabel">Datensatz anzeigen</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">PR Call</label>
                                        <p id="view_prcall" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <p id="view_name" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">QTH</label>
                                        <p id="view_qth" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Locator</label>
                                        <p id="view_locator" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">MyBBS</label>
                                        <p id="view_mybbs" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Route</label>
                                        <p id="view_route" class="form-control-plaintext"></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <p id="view_email" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <p id="view_website" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">PR Mail</label>
                                        <p id="view_prmail" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bundesland</label>
                                        <p id="view_bundesland" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Land</label>
                                        <p id="view_land" class="form-control-plaintext"></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Registrierungsdatum</label>
                                        <p id="view_regdate" class="form-control-plaintext"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bemerkung</label>
                                <p id="view_bemerkung" class="form-control-plaintext"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Letzte Aktualisierung</label>
                                <p id="view_lastupdate" class="form-control-plaintext"></p>
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

    // Update-Logik
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $response = wp_remote_get($this->update_url);
        if (is_wp_error($response)) {
            return $transient;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['version']) || !isset($data['package'])) {
            return $transient;
        }

        if (version_compare($this->version, $data['version'], '<')) {
            $plugin_data = new stdClass();
            $plugin_data->slug = 'callbook';
            $plugin_data->plugin = 'callbook/callbook.php';
            $plugin_data->new_version = $data['version'];
            $plugin_data->url = 'https://github.com/pe1ftl/callbook';
            $plugin_data->package = $data['package'];
            $transient->response['callbook/callbook.php'] = $plugin_data;
        }

        return $transient;
    }

    // Plugin-Informationen für die WordPress-Update-API
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== 'callbook') {
            return $result;
        }

        $response = wp_remote_get($this->update_url);
        if (is_wp_error($response)) {
            return $result;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['version'])) {
            return $result;
        }

        $plugin_info = new stdClass();
        $plugin_info->name = 'Callbook';
        $plugin_info->slug = 'callbook';
        $plugin_info->version = $data['version'];
        $plugin_info->author = 'xAI';
        $plugin_info->homepage = 'https://github.com/pe1ftl/callbook';
        $plugin_info->download_link = $data['package'];
        $plugin_info->sections = [
            'description' => 'Ein WordPress-Plugin zur Verwaltung einer Callbook-Datenbank mit automatischen Updates von GitHub und Bootstrap 5 Integration.',
            'changelog' => isset($data['changelog']) ? $data['changelog'] : 'Keine Änderungsprotokollinformationen verfügbar.'
        ];

        return $plugin_info;
    }

    // Einstellungen registrieren
    public function register_settings() {
        // Bereits durch add_action in __construct registriert
    }
}

new CallbookPlugin();
?>