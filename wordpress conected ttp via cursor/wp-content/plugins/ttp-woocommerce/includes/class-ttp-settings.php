<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TTP_Settings {

    public function __construct() {
        add_action( 'admin_menu',  [ $this, 'add_menu' ] );
        add_action( 'admin_init',  [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_ttp_test_connection', [ $this, 'ajax_test' ] );
    }

    public function add_menu() {
        add_menu_page( 'TTP Dashboard', 'TTP Dashboard', 'manage_options', 'ttp-dashboard', [ $this, 'dashboard' ], 'dashicons-welcome-learn-more', 56 );
        add_submenu_page( 'ttp-dashboard', 'API Settings', 'API Settings', 'manage_options', 'ttp-settings', [ $this, 'settings_page' ] );
        add_submenu_page( 'ttp-dashboard', 'API Logs',     'API Logs',     'manage_options', 'ttp-api-logs', [ $this, 'logs_page' ] );
        add_submenu_page( 'ttp-dashboard', 'Study Redirect Logs', 'Study Redirect Logs', 'manage_options', 'ttp-study-redirect-logs', [ $this, 'study_redirect_logs_page' ] );
        add_submenu_page( 'ttp-dashboard', 'Students',     'Students',     'manage_options', 'ttp-students', [ $this, 'students_page' ] );
        add_submenu_page( 'ttp-dashboard', 'TCY Courses',  'TCY Courses',  'manage_options', 'ttp-courses',  [ $this, 'courses_page' ] );
        add_submenu_page( 'ttp-dashboard', 'MBA CET 2027 Catalog', 'MBA CET 2027 Catalog', 'manage_options', 'ttp-course-catalog', [ $this, 'catalog_seed_page' ] );
    }

    public function register_settings() {
        register_setting( 'ttp_settings_group', 'ttp_tcy_client_id',     [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'ttp_settings_group', 'ttp_tcy_security_code', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'ttp_settings_group', 'ttp_study_portal_base_url', [ 'sanitize_callback' => 'esc_url_raw' ] );
        register_setting( 'ttp_settings_group', 'ttp_redirect_thankyou_to_study', [ 'sanitize_callback' => [ $this, 'sanitize_yes_no' ] ] );
        register_setting( 'ttp_settings_group', 'ttp_tcy_enable_online_tab', [ 'sanitize_callback' => 'absint' ] );
        register_setting( 'ttp_settings_group', 'ttp_tcy_send_sub_cat', [ 'sanitize_callback' => 'absint' ] );
        register_setting( 'ttp_settings_group', 'ttp_tcy_remove_siblings', [ 'sanitize_callback' => 'absint' ] );
    }

    /**
     * @param mixed $v Raw value.
     * @return string 'yes' or 'no'.
     */
    public function sanitize_yes_no( $v ) {
        return ( ! empty( $v ) && 'yes' === $v ) ? 'yes' : 'no';
    }

    public function dashboard() {
        global $wpdb;
        $students = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ttp_students" );
        $mapped   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ttp_students WHERE tcy_user_id IS NOT NULL" );
        $failed   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ttp_api_logs WHERE status='failed'" );
        $cid      = get_option( 'ttp_tcy_client_id', '' );
        ?>
        <div class="wrap">
            <h1>TTP Dashboard</h1>
            <?php if ( empty( $cid ) ): ?>
                <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:14px;margin:12px 0;">
                    ⚠️ TCY credentials not set. <a href="<?php echo admin_url('admin.php?page=ttp-settings'); ?>">Add them here →</a>
                </div>
            <?php else: ?>
                <div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:14px;margin:12px 0;">
                    ✅ TCY Client ID: <strong><?php echo esc_html($cid); ?></strong>
                </div>
            <?php endif; ?>
            <div style="display:flex;gap:16px;flex-wrap:wrap;margin:20px 0;">
                <?php foreach ( [ ['Students', $students, '#1a56db'], ['TCY Mapped', $mapped, '#0a7a55'], ['API Failures', $failed, '#dc3545'] ] as [$label, $val, $color] ): ?>
                <div style="background:<?php echo $color; ?>;color:#fff;padding:20px 32px;border-radius:10px;text-align:center;">
                    <div style="font-size:30px;font-weight:700;"><?php echo $val; ?></div>
                    <div style="font-size:13px;margin-top:4px;"><?php echo $label; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <p>
                <a href="<?php echo admin_url('admin.php?page=ttp-settings'); ?>" class="button button-primary">API Settings</a>
                <a href="<?php echo admin_url('admin.php?page=ttp-api-logs'); ?>"  class="button">API Logs</a>
                <a href="<?php echo admin_url('admin.php?page=ttp-students'); ?>"  class="button">Students</a>
                <a href="<?php echo admin_url('admin.php?page=ttp-courses'); ?>"   class="button">TCY Courses</a>
            </p>
        </div>
        <?php
    }

    public function settings_page() {
        if ( isset( $_POST['ttp_save'] ) && check_admin_referer( 'ttp_save_settings' ) ) {
            update_option( 'ttp_tcy_client_id', function_exists( 'ttp_normalize_tcy_client_id' )
                ? ttp_normalize_tcy_client_id( wp_unslash( $_POST['ttp_tcy_client_id'] ?? '' ) )
                : preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['ttp_tcy_client_id'] ?? '' ) ) );
            update_option( 'ttp_tcy_security_code', sanitize_text_field( wp_unslash( $_POST['ttp_tcy_security_code'] ?? '' ) ) );
            update_option( 'ttp_study_portal_base_url', esc_url_raw( wp_unslash( $_POST['ttp_study_portal_base_url'] ?? '' ) ) ?: 'https://study.thetoppercentile.co.in' );
            update_option( 'ttp_redirect_thankyou_to_study', ! empty( $_POST['ttp_redirect_thankyou_to_study'] ) ? 'yes' : 'no' );
            update_option( 'ttp_tcy_enable_online_tab', isset( $_POST['ttp_tcy_enable_online_tab'] ) ? 1 : 0 );
            update_option( 'ttp_tcy_send_sub_cat', isset( $_POST['ttp_tcy_send_sub_cat'] ) ? 1 : 0 );
            update_option( 'ttp_tcy_remove_siblings', isset( $_POST['ttp_tcy_remove_siblings'] ) ? 1 : 0 );
            echo '<div class="notice notice-success is-dismissible"><p>✅ Settings saved!</p></div>';
        }
        $cid = get_option( 'ttp_tcy_client_id', '' );
        $sec = get_option( 'ttp_tcy_security_code', '' );
        $study_base = get_option( 'ttp_study_portal_base_url', 'https://study.thetoppercentile.co.in' );
        $redirect_thankyou = get_option( 'ttp_redirect_thankyou_to_study', 'no' );
        $enable_online_tab = (int) get_option( 'ttp_tcy_enable_online_tab', 0 );
        $send_sub_cat      = (int) get_option( 'ttp_tcy_send_sub_cat', 1 );
        $remove_siblings   = (int) get_option( 'ttp_tcy_remove_siblings', 0 );
        ?>
        <div class="wrap"><h1>TCY API Settings</h1>
        <form method="post">
            <?php wp_nonce_field( 'ttp_save_settings' ); ?>
            <table class="form-table" style="max-width:720px;">
                <tr><th><label>TCY Client ID</label></th><td><input type="text" name="ttp_tcy_client_id" value="<?php echo esc_attr($cid); ?>" class="regular-text" placeholder="e.g. 7716"/></td></tr>
                <tr><th><label>TCY Security Code</label></th><td><input type="text" name="ttp_tcy_security_code" value="<?php echo esc_attr($sec); ?>" class="regular-text"/></td></tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'enable_online_tab', 'ttp-woocommerce' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="ttp_tcy_enable_online_tab" value="1" <?php checked( 1, $enable_online_tab ); ?>/>
                        <?php esc_html_e( 'Send enable_online_tab=1 on register (uncheck = 0, recommended)', 'ttp-woocommerce' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'add_course sub_cat', 'ttp-woocommerce' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="ttp_tcy_send_sub_cat" value="1" <?php checked( 1, $send_sub_cat ); ?>/>
                        <?php esc_html_e( 'Send sub_cat pack id (33599 CET / 33605 NMAT) on add_course — required by TCY API', 'ttp-woocommerce' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Remove sibling courses', 'ttp-woocommerce' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="ttp_tcy_remove_siblings" value="1" <?php checked( 1, $remove_siblings ); ?>/>
                        <?php esc_html_e( 'Before add_course, call remove_course for other tiers in the same pack (legacy 604 workaround — leave OFF when TCY allows multiple CET/NMAT plans on one account)', 'ttp-woocommerce' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ttp_study_portal_base_url"><?php esc_html_e( 'Study portal URL', 'ttp-woocommerce' ); ?></label></th>
                    <td>
                        <input type="url" id="ttp_study_portal_base_url" name="ttp_study_portal_base_url" value="<?php echo esc_attr( $study_base ); ?>" class="large-text" placeholder="https://study.thetoppercentile.co.in"/>
                        <p class="description"><?php esc_html_e( 'White-label TCY host (no trailing slash). Magic login links from the API are rewritten to this domain.', 'ttp-woocommerce' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'After purchase', 'ttp-woocommerce' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ttp_redirect_thankyou_to_study" value="yes" <?php checked( $redirect_thankyou, 'yes' ); ?>/>
                            <?php esc_html_e( 'Redirect order thank-you page to the study portal (auto login)', 'ttp-woocommerce' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'When enabled, the browser is sent straight to the study portal after a successful purchase (skips this thank-you page). Default is off so customers see “Thank you” and the study portal button here first.', 'ttp-woocommerce' ); ?></p>
                    </td>
                </tr>
            </table>
            <p><input type="submit" name="ttp_save" class="button button-primary button-large" value="Save Settings"/></p>
        </form>
        <?php if ( $cid ): ?>
        <hr/>
        <h2>Test Connection</h2>
        <button class="button button-secondary" id="ttp-test">Test TCY Connection</button>
        <div id="ttp-test-result" style="margin-top:14px;padding:14px;border-radius:8px;display:none;"></div>
        <script>
        document.getElementById('ttp-test').onclick = function(){
            var btn = this; btn.textContent = 'Testing...'; btn.disabled = true;
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'action=ttp_test_connection&nonce=<?php echo wp_create_nonce('ttp_nonce'); ?>'
            }).then(r=>r.json()).then(d=>{
                var el = document.getElementById('ttp-test-result'); el.style.display='block';
                if(d.success==1){
                    el.style.background='#d4edda'; el.style.border='1px solid #c3e6cb';
                    el.innerHTML='<strong style="color:green">✅ Connected! Courses found: '+(d.data?d.data.length:0)+'</strong>';
                } else {
                    el.style.background='#f8d7da'; el.style.border='1px solid #f5c6cb';
                    el.innerHTML='<strong style="color:red">❌ Failed</strong><pre>'+JSON.stringify(d,null,2)+'</pre>';
                }
                btn.textContent='Test TCY Connection'; btn.disabled=false;
            });
        };
        </script>
        <?php endif; ?>
        </div>
        <?php
    }

    public function study_redirect_logs_page() {
        if ( isset( $_POST['ttp_clear_study_redirect_logs'] ) && check_admin_referer( 'ttp_clear_study_redirect_logs' ) ) {
            if ( class_exists( 'TTP_Study_Redirect_Log', false ) ) {
                TTP_Study_Redirect_Log::clear();
            }
            echo '<div class="notice notice-success is-dismissible"><p>Study redirect logs cleared.</p></div>';
        }

        $max  = class_exists( 'TTP_Study_Redirect_Log', false ) ? TTP_Study_Redirect_Log::MAX_LOGS : 10;
        $logs = class_exists( 'TTP_Study_Redirect_Log', false ) ? TTP_Study_Redirect_Log::get_all() : array();
        ?>
        <div class="wrap">
            <h1>Study portal redirect logs (last <?php echo (int) $max; ?>)</h1>
            <p>Recorded when a customer is sent to the TCY study portal.</p>
            <form method="post" style="margin:12px 0;">
                <?php wp_nonce_field( 'ttp_clear_study_redirect_logs' ); ?>
                <input type="submit" name="ttp_clear_study_redirect_logs" class="button" value="Clear logs"/>
            </form>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Source</th>
                        <th>Order</th>
                        <th>User</th>
                        <th>TCY user ID</th>
                        <th>Final URL</th>
                        <th>Raw URL</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $logs ) ) : ?>
                    <tr><td colspan="8">No redirects logged yet.</td></tr>
                <?php else : ?>
                    <?php foreach ( $logs as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row['time'] ?? '' ); ?></td>
                            <td><code><?php echo esc_html( $row['source'] ?? '' ); ?></code></td>
                            <td>
                                <?php
                                if ( ! empty( $row['order_id'] ) ) {
                                    $oid = (int) $row['order_id'];
                                    echo '<a href="' . esc_url( admin_url( 'post.php?post=' . $oid . '&action=edit' ) ) . '">#' . $oid . '</a>';
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td><?php echo ! empty( $row['user_id'] ) ? (int) $row['user_id'] : '—'; ?></td>
                            <td><?php echo esc_html( $row['tcy_user_id'] ?? '' ); ?></td>
                            <td style="max-width:280px;word-break:break-all;"><a href="<?php echo esc_url( $row['final_url'] ?? '' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row['final_url'] ?? '' ); ?></a></td>
                            <td style="max-width:220px;word-break:break-all;"><small><?php echo esc_html( $row['raw_url'] ?? '' ); ?></small></td>
                            <td><?php echo esc_html( $row['note'] ?? '' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function logs_page() {
        global $wpdb;
        $logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ttp_api_logs ORDER BY created_at DESC LIMIT 100" );
        echo '<div class="wrap"><h1>API Logs</h1><p class="description">' . esc_html__( 'Filter add_course rows to see why a course_id failed (course_id, sub_cat, user_id in request_data).', 'ttp-woocommerce' ) . '</p>';
        echo '<table class="widefat"><thead><tr><th>ID</th><th>Action</th><th>Status</th><th>Order</th><th>Time</th><th>Request / Response</th></tr></thead><tbody>';
        foreach ( $logs as $l ) {
            $color = $l->status === 'success' ? 'green' : 'red';
            $req   = json_decode( (string) $l->request_data, true );
            $res   = json_decode( (string) $l->response_data, true );
            $snippet = '';
            if ( is_array( $req ) ) {
                $snippet .= 'course_id=' . esc_html( $req['course_id'] ?? '' ) . ', user_id=' . esc_html( $req['user_id'] ?? '' );
                if ( ! empty( $req['sub_cat'] ) ) {
                    $snippet .= ', sub_cat=' . esc_html( $req['sub_cat'] );
                }
            }
            if ( is_array( $res ) ) {
                $snippet .= ' → ' . esc_html( wp_json_encode( $res ) );
            }
            echo '<tr><td>' . (int) $l->id . '</td><td>' . esc_html( $l->action ) . '</td><td style="color:' . esc_attr( $color ) . ';font-weight:600;">' . esc_html( $l->status ) . '</td><td>' . esc_html( (string) $l->order_id ) . '</td><td>' . esc_html( $l->created_at ) . '</td><td style="max-width:480px;word-break:break-all;font-size:11px;">' . $snippet . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function students_page() {
        global $wpdb;

        if ( isset( $_POST['ttp_resync_tcy_courses'] ) && check_admin_referer( 'ttp_resync_tcy_courses' ) ) {
            $email  = sanitize_email( wp_unslash( $_POST['ttp_resync_email'] ?? '' ) );
            $wp_uid = 0;
            if ( $email !== '' ) {
                $u = get_user_by( 'email', $email );
                if ( $u ) {
                    $wp_uid = (int) $u->ID;
                }
            }
            if ( function_exists( 'ttp_tcy_loop_add_all_courses_for_user_id' ) && $email !== '' ) {
                $result = ttp_tcy_loop_add_all_courses_for_user_id( '', $wp_uid, $email );
                $failed_n = (int) ( $result['failed'] ?? 0 );
                $class    = $failed_n > 0 ? 'notice-warning' : 'notice-success';
                echo '<div class="notice ' . esc_attr( $class ) . '"><p><strong>' . esc_html__( 'add_course loop finished', 'ttp-woocommerce' ) . '</strong></p>';
                echo '<p>' . esc_html(
                    sprintf(
                        __( 'TCY user_id (login): %1$s — newly added: %2$d — already on account: %3$d — failed: %4$d (of %5$d API runs)', 'ttp-woocommerce' ),
                        $result['tcy_user_id'] ?? '—',
                        (int) ( $result['added'] ?? 0 ),
                        (int) ( $result['already'] ?? 0 ),
                        $failed_n,
                        (int) ( $result['total'] ?? 0 )
                    )
                ) . '</p>';
                if ( (int) ( $result['added'] ?? 0 ) === 0 && (int) ( $result['already'] ?? 0 ) > 0 && $failed_n === 0 ) {
                    echo '<p class="description">' . esc_html__( '“Already on account” means TCY already has those course_ids — login to ViewClientCourses to confirm tabs. Run sync again after fixing any failed row below.', 'ttp-woocommerce' ) . '</p>';
                }
                $skipped_n = (int) ( $result['skipped'] ?? 0 );
                if ( $skipped_n > 0 ) {
                    echo '<p class="description">' . esc_html(
                        sprintf(
                            __( 'Skipped: %d (invalid Product_id stored as course_id — fix Woo product TCY Course ID).', 'ttp-woocommerce' ),
                            $skipped_n
                        )
                    ) . '</p>';
                }
                if ( $failed_n > 0 && ! empty( $result['details'] ) && is_array( $result['details'] ) ) {
                    foreach ( $result['details'] as $row ) {
                        if ( ( $row['status'] ?? '' ) !== 'failed' ) {
                            continue;
                        }
                        echo '<p style="color:#b91c1c;"><strong>' . esc_html__( 'Failed course', 'ttp-woocommerce' ) . ':</strong> ';
                        echo esc_html( 'course_id=' . ( $row['course_id'] ?? '?' ) );
                        if ( ! empty( $row['stored_course_id'] ) ) {
                            echo esc_html( ' (Woo meta was ' . $row['stored_course_id'] . ')' );
                        }
                        if ( ! empty( $row['line_name'] ) ) {
                            echo ' — ' . esc_html( (string) $row['line_name'] );
                        }
                        if ( ! empty( $row['error'] ) ) {
                            echo ' — ' . esc_html( (string) $row['error'] );
                        }
                        echo '</p>';
                        break;
                    }
                }
                if ( ! empty( $result['split_account'] ) && ! empty( $result['all_tcy_ids'] ) ) {
                    echo '<p class="description" style="color:#b45309;"><strong>' . esc_html__( 'Split TCY accounts detected', 'ttp-woocommerce' ) . ':</strong> ';
                    echo esc_html( implode( ', ', array_map( 'strval', (array) $result['all_tcy_ids'] ) ) );
                    echo ' — ' . esc_html__( 'courses were pushed to every id; canonical id is used for login.', 'ttp-woocommerce' ) . '</p>';
                }
                if ( ! empty( $result['error'] ) ) {
                    echo '<p>' . esc_html( (string) $result['error'] ) . '</p>';
                }
                if ( ! empty( $result['pack_note'] ) ) {
                    echo '<p class="description" style="max-width:720px;"><strong>' . esc_html__( 'TCY product pack limit', 'ttp-woocommerce' ) . ':</strong> ';
                    echo esc_html( (string) $result['pack_note'] );
                    if ( ! empty( $result['pairs_purchased'] ) ) {
                        echo ' ';
                        echo esc_html(
                            sprintf(
                                __( '(Purchased: %1$d course_ids → TCY can sync: %2$d)', 'ttp-woocommerce' ),
                                (int) $result['pairs_purchased'],
                                (int) ( $result['pairs_tcy_sync'] ?? 0 )
                            )
                        );
                    }
                    echo '</p>';
                }
                if ( ! empty( $result['diagnosis'] ) && is_array( $result['diagnosis'] ) ) {
                    $d = $result['diagnosis'];
                    echo '<p><strong>' . esc_html__( 'WooCommerce diagnosis', 'ttp-woocommerce' ) . '</strong> — ';
                    echo esc_html(
                        sprintf(
                            __( '%1$d distinct course_id(s) purchased: %2$s', 'ttp-woocommerce' ),
                            count( $d['distinct_course'] ?? [] ),
                            ! empty( $d['distinct_course'] ) ? implode( ', ', array_map( 'strval', $d['distinct_course'] ) ) : '—'
                        )
                    );
                    if ( ! empty( $d['invalid_course_ids'] ) ) {
                        echo '<br/><span class="description" style="color:#b45309;"><strong>' . esc_html__( 'Stale Woo course_id(s) (not 90069–90073):', 'ttp-woocommerce' ) . '</strong> ';
                        echo esc_html( implode( ', ', array_map( 'strval', (array) $d['invalid_course_ids'] ) ) );
                        echo ' — ' . esc_html__( 'sync remaps these from the order line title (e.g. Combo Pack → 90071, Test Series → 90070).', 'ttp-woocommerce' ) . '</span>';
                    }
                    if ( ! empty( $d['pairs_tcy_sync'] ) ) {
                        $sync_ids = array_map(
                            static function ( $row ) {
                                return is_array( $row ) && ! empty( $row['course_id'] ) ? (string) $row['course_id'] : '';
                            },
                            (array) $d['pairs_tcy_sync']
                        );
                        $sync_ids = array_values( array_unique( array_filter( $sync_ids ) ) );
                        $sync_label = function_exists( 'ttp_tcy_should_limit_one_course_per_pack' ) && ttp_tcy_should_limit_one_course_per_pack()
                            ? __( 'TCY will receive (one per pack): ', 'ttp-woocommerce' )
                            : __( 'add_course loop will sync: ', 'ttp-woocommerce' );
                        echo '<br/><span class="description">' . esc_html( $sync_label ) . esc_html( implode( ', ', $sync_ids ) ) . '</span>';
                    }
                    echo '</p>';
                    if ( ! empty( $d['orders'] ) ) {
                        echo '<table class="widefat" style="margin-top:8px;"><thead><tr><th>Order</th><th>Status</th><th>TCY on order</th><th>Lines → course_id</th></tr></thead><tbody>';
                        foreach ( $d['orders'] as $o ) {
                            $line_txt = [];
                            foreach ( (array) ( $o['lines'] ?? [] ) as $ln ) {
                                $line_txt[] = ( $ln['name'] ?? '' ) . ' → ' . ( $ln['course_id'] ?? '?' );
                            }
                            echo '<tr><td>' . esc_html( (string) ( $o['order_id'] ?? '' ) ) . '</td>';
                            echo '<td>' . esc_html( (string) ( $o['status'] ?? '' ) ) . '</td>';
                            echo '<td><code>' . esc_html( (string) ( $o['tcy_user_id'] ?? '' ) ) . '</code></td>';
                            echo '<td style="font-size:12px;">' . esc_html( implode( ' | ', $line_txt ) ) . '</td></tr>';
                        }
                        echo '</tbody></table>';
                    }
                }
                if ( ! empty( $result['details'] ) && is_array( $result['details'] ) ) {
                    echo '<table class="widefat" style="margin-top:12px;"><thead><tr><th>TCY user</th><th>course_id</th><th>Was (meta)</th><th>Payload</th><th>sub_cat</th><th>Order</th><th>Course</th><th>Status</th><th>TCY response</th></tr></thead><tbody>';
                    foreach ( $result['details'] as $row ) {
                        $st    = isset( $row['status'] ) ? (string) $row['status'] : '';
                        $color = 'failed' === $st ? 'red' : ( 'skipped' === $st ? '#6b7280' : ( in_array( $st, [ 'already', 'pack_conflict' ], true ) ? '#b45309' : 'green' ) );
                        echo '<tr><td><code>' . esc_html( $row['tcy_user_id'] ?? '' ) . '</code></td>';
                        echo '<td>' . esc_html( $row['course_id'] ?? '' ) . '</td>';
                        echo '<td>' . esc_html( ! empty( $row['stored_course_id'] ) ? (string) $row['stored_course_id'] : '—' ) . '</td>';
                        echo '<td>' . esc_html( $row['variant'] ?? '' ) . '</td>';
                        echo '<td>' . esc_html( $row['sub_cat'] ?? '' ) . '</td>';
                        echo '<td>' . esc_html( isset( $row['order_id'] ) ? (string) $row['order_id'] : '' ) . '</td>';
                        echo '<td>' . esc_html( $row['line_name'] ?? '' ) . '</td>';
                        echo '<td style="font-weight:700;color:' . esc_attr( $color ) . ';">' . esc_html( $st ) . '</td>';
                        $err = $row['error'] ?? '';
                        if ( $st === 'pack_conflict' && $err === '' ) {
                            $err = '604 — another course in this product pack is already assigned on TCY';
                        }
                        echo '<td style="font-size:11px;max-width:360px;word-break:break-all;">' . esc_html( $err ) . '</td></tr>';
                    }
                    echo '</tbody></table>';
                }
                echo '</div>';
            }
        }

        $students = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ttp_students ORDER BY created_at DESC LIMIT 100" );
        echo '<div class="wrap"><h1>Students</h1>';
        echo '<form method="post" style="margin:16px 0;padding:16px;background:#fff;border:1px solid #ccc;border-radius:8px;max-width:520px;">';
        wp_nonce_field( 'ttp_resync_tcy_courses' );
        echo '<p><strong>' . esc_html__( 'Re-sync all purchased courses to TCY', 'ttp-woocommerce' ) . '</strong></p>';
        echo '<p><label>' . esc_html__( 'Customer email', 'ttp-woocommerce' ) . '</label><br/>';
        echo '<input type="email" name="ttp_resync_email" class="regular-text" required placeholder="student@example.com"/></p>';
        echo '<p><input type="submit" name="ttp_resync_tcy_courses" class="button button-primary" value="' . esc_attr__( 'Run add_course loop', 'ttp-woocommerce' ) . '"/></p>';
        echo '<p class="description">' . esc_html__( 'Runs add_course for every purchased course_id (category_id 100000 + sub_cat). Check API Logs if the 3rd+ row fails — status pack_conflict means 604; success on mba+sub_cat means TCY accepted it.', 'ttp-woocommerce' ) . '</p></form>';
        echo '<table class="widefat"><thead><tr><th>Name</th><th>Email</th><th>Mobile</th><th>TCY ID</th><th>Registered</th></tr></thead><tbody>';
        foreach ( $students as $s ) {
            echo "<tr><td>{$s->full_name}</td><td>{$s->email}</td><td>{$s->mobile}</td><td>" . ( $s->tcy_user_id ?: '—' ) . "</td><td>{$s->created_at}</td></tr>";
        }
        echo '</tbody></table></div>';
    }

    public function courses_page() {
        echo '<div class="wrap"><h1>TCY Courses</h1>';
        if ( isset( $_POST['fetch_courses'] ) ) {
            $api    = new TTP_TCY_API();
            $result = $api->get_courses();
            echo '<pre style="background:#f1f1f1;padding:16px;border-radius:8px;overflow:auto;">' . esc_html( json_encode( $result, JSON_PRETTY_PRINT ) ) . '</pre>';
        } else {
            echo '<form method="post"><input type="submit" name="fetch_courses" class="button button-primary" value="Fetch Courses from TCY"/></form>';
        }
        echo '</div>';
    }

    /**
     * Create/update WooCommerce products + TCY IDs (editable under Products).
     */
    public function catalog_seed_page() {
        if ( ! class_exists( 'TTP_Catalog_Seed' ) ) {
            echo '<div class="wrap"><p>Catalog module not loaded.</p></div>';
            return;
        }

        if ( isset( $_POST['ttp_seed_catalog'] ) && check_admin_referer( 'ttp_seed_catalog' ) ) {
            $full = ! empty( $_POST['ttp_seed_full'] );
            $r     = TTP_Catalog_Seed::seed( $full );
            $fixed  = TTP_Catalog_Seed::repair_all_tcy_meta();
            $display = TTP_Catalog_Seed::repair_all_product_display_content();
            echo '<div class="notice notice-success is-dismissible"><p><strong>Done.</strong> '
                . sprintf(
                    /* translators: 1: created count, 2: updated count */
                    esc_html__( 'Created %1$d new products, updated %2$d. TCY Course / Product IDs are saved on each product (General → TCY).', 'ttp-woocommerce' ),
                    (int) $r['created'],
                    (int) $r['updated']
                );
            if ( ! empty( $fixed ) ) {
                echo ' ' . esc_html( sprintf( __( 'Corrected TCY mapping on %d product(s).', 'ttp-woocommerce' ), count( $fixed ) ) );
            }
            if ( ! empty( $display ) ) {
                echo ' ' . esc_html( sprintf( __( 'Refreshed unique course content on %d product(s).', 'ttp-woocommerce' ), (int) $display ) );
            }
            echo '</p></div>';
        }

        $defs = TTP_Catalog_Seed::get_definitions();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'MBA CET 2027 — WooCommerce catalog', 'ttp-woocommerce' ); ?></h1>
            <p><?php esc_html_e( 'Each plan uses course_id 90069–90073, category_id 100000 (MBA Entrance), and sub_cat 33599 (CET) or 33605 (NMAT). After TCY lifted the one-course-per-pack limit, keep “Remove sibling courses” off in API Settings so the add_course loop can assign a 3rd+ tier.', 'ttp-woocommerce' ); ?></p>
            <ul style="list-style:disc;padding-left:22px;max-width:920px;">
                <?php foreach ( $defs as $row ) :
                    $api = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::resolve_tcy_api_ids( $row ) : [];
                    ?>
                    <li><strong><?php echo esc_html( $row['name'] ); ?></strong>
                        — <?php esc_html_e( 'Course', 'ttp-woocommerce' ); ?> <code><?php echo esc_html( $api['course_id'] ?? '' ); ?></code>,
                        <?php esc_html_e( 'Category', 'ttp-woocommerce' ); ?> <code><?php echo esc_html( $api['category_id'] ?? '' ); ?></code>,
                        <?php esc_html_e( 'Pack', 'ttp-woocommerce' ); ?> <code><?php echo esc_html( $api['product_pack_id'] ?? '' ); ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><?php esc_html_e( 'Client ID 7716 — set under TTP → Settings. Do not paste Security Code in chat; save it only in Settings.', 'ttp-woocommerce' ); ?></p>
            <p><?php esc_html_e( 'Pack lines use “(12 Months)” at the end. After purchase, checkout still triggers TCY registration and the “Login & Access” flow.', 'ttp-woocommerce' ); ?></p>

            <form method="post" style="margin:20px 0;">
                <?php wp_nonce_field( 'ttp_seed_catalog' ); ?>
                <p>
                    <button type="submit" name="ttp_seed_catalog" class="button button-primary" value="1">
                        <?php esc_html_e( 'Sync TCY IDs only (safe)', 'ttp-woocommerce' ); ?>
                    </button>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="ttp_seed_full" value="1"/>
                        <?php esc_html_e( 'Full reset from catalog: overwrite titles, prices, descriptions, SKU from plugin definitions', 'ttp-woocommerce' ); ?>
                    </label>
                </p>
            </form>

            <h2><?php esc_html_e( 'Live mapping check (what the site will send to TCY)', 'ttp-woocommerce' ); ?></h2>
            <p><?php esc_html_e( 'Each plan must have a different course_id. API category_id must be 100000 for all plans. Run Sync after updating the plugin.', 'ttp-woocommerce' ); ?></p>
            <table class="widefat striped" style="max-width:1100px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Plan', 'ttp-woocommerce' ); ?></th>
                        <th><?php esc_html_e( 'WC product', 'ttp-woocommerce' ); ?></th>
                        <th><?php esc_html_e( 'WC ID', 'ttp-woocommerce' ); ?></th>
                        <th><?php esc_html_e( 'SKU', 'ttp-woocommerce' ); ?></th>
                        <th><?php esc_html_e( 'TCY Course', 'ttp-woocommerce' ); ?></th>
                        <th><?php esc_html_e( 'TCY category_id (API)', 'ttp-woocommerce' ); ?></th>
                        <th><?php esc_html_e( 'Pack ref.', 'ttp-woocommerce' ); ?></th>
                        <th><?php esc_html_e( 'Match', 'ttp-woocommerce' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ( $defs as $row ) :
                    $pid     = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::get_product_id_for_definition( $row ) : 0;
                    $product = $pid ? wc_get_product( $pid ) : null;
                    $ids     = $product && class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::get_tcy_ids_for_product( $product ) : [ 'course_id' => '', 'product_id' => '' ];
                    $api     = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::resolve_tcy_api_ids( $row ) : [];
                    $expect_c = (string) ( $api['course_id'] ?? '' );
                    $expect_cat = (string) ( $api['category_id'] ?? '' );
                    $ok       = $ids['course_id'] === $expect_c && $ids['product_id'] === $expect_cat;
                    ?>
                    <tr>
                        <td><?php echo esc_html( $row['name'] ); ?></td>
                        <td><?php echo $product ? esc_html( $product->get_name() ) : '<span style="color:#b45309;">' . esc_html__( 'Not found', 'ttp-woocommerce' ) . '</span>'; ?></td>
                        <td><?php echo $pid ? (int) $pid : '—'; ?></td>
                        <td><code><?php echo esc_html( $row['sku'] ); ?></code></td>
                        <td><code><?php echo esc_html( $ids['course_id'] ); ?></code></td>
                        <td><code><?php echo esc_html( $ids['product_id'] ); ?></code></td>
                        <td><code><?php echo esc_html( $api['product_pack_id'] ?? '' ); ?></code></td>
                        <td><?php echo $ok ? '✅' : '❌'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Slugs (for reference)', 'ttp-woocommerce' ); ?></h2>
            <table class="widefat striped" style="max-width:720px;">
                <thead><tr><th><?php esc_html_e( 'Product', 'ttp-woocommerce' ); ?></th><th><?php esc_html_e( 'Slug', 'ttp-woocommerce' ); ?></th></tr></thead>
                <tbody>
                <?php foreach ( $defs as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row['name'] ); ?></td>
                        <td><code><?php echo esc_html( $row['slug'] ); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function ajax_test() {
        check_ajax_referer( 'ttp_nonce', 'nonce' );
        $api = new TTP_TCY_API();
        wp_send_json( $api->test_connection() );
    }
}
