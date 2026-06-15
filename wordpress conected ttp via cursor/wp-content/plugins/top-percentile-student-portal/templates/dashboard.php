<?php
/**
 * Dashboard wrapper template.
 *
 * @var TPSP_Dashboard $context
 */

if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
?>
<div class="tpsp-dashboard-wrap">
    <aside class="tpsp-sidebar">
        <h3><i class="fa-solid fa-user-graduate"></i> <?php esc_html_e('Student Portal', 'tpsp'); ?></h3>
        <nav>
            <a href="<?php echo esc_url(tpsp_get_account_endpoint_url('dashboard')); ?>"><i class="fa-solid fa-gauge"></i> <?php esc_html_e('Dashboard', 'tpsp'); ?></a>
            <a href="<?php echo esc_url(tpsp_get_account_endpoint_url('my-courses')); ?>"><i class="fa-solid fa-book-open-reader"></i> <?php esc_html_e('My Courses', 'tpsp'); ?></a>
            <a href="<?php echo esc_url(tpsp_get_account_endpoint_url('orders')); ?>"><i class="fa-solid fa-receipt"></i> <?php esc_html_e('Orders', 'tpsp'); ?></a>
            <a href="<?php echo esc_url(wc_logout_url()); ?>"><i class="fa-solid fa-right-from-bracket"></i> <?php esc_html_e('Logout', 'tpsp'); ?></a>
        </nav>
    </aside>

    <main class="tpsp-main-panel">
        <div class="tpsp-card tpsp-welcome">
            <h2><?php echo esc_html(sprintf(__('Welcome back, %s', 'tpsp'), $user->display_name)); ?></h2>
            <p><?php esc_html_e('Track your courses, test performance, and account details from one place.', 'tpsp'); ?></p>
            <?php echo do_shortcode('[tpsp_verification_status]'); ?>
        </div>
        <div class="tpsp-grid">
            <div class="tpsp-card">
                <h4><?php esc_html_e('Quick Actions', 'tpsp'); ?></h4>
                <a class="tpsp-btn" href="<?php echo esc_url(tpsp_get_account_endpoint_url('my-courses')); ?>"><?php esc_html_e('View Courses', 'tpsp'); ?></a>
            </div>
            <div class="tpsp-card">
                <h4><?php esc_html_e('Recent Orders', 'tpsp'); ?></h4>
                <?php
                $orders = wc_get_orders(['customer_id' => get_current_user_id(), 'limit' => 3]);
                if (!empty($orders)) :
                    foreach ($orders as $order) :
                        ?>
                        <div class="tpsp-inline-row">
                            <span>#<?php echo esc_html($order->get_order_number()); ?></span>
                            <strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong>
                        </div>
                        <?php
                    endforeach;
                else :
                    ?>
                    <p><?php esc_html_e('No orders yet.', 'tpsp'); ?></p>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </main>
</div>
