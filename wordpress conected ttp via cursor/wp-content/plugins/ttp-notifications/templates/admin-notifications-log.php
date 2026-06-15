<?php
if (!defined('ABSPATH')) {
    exit;
}

$shown_count = is_array($items) ? count($items) : 0;
$total_count = isset($total) ? (int) $total : $shown_count;
$has_filters = !empty($search) || !empty($user_id);
?>
<div class="wrap ttpn-wrap">
    <h1><?php esc_html_e('All Notifications', 'ttp-notifications'); ?></h1>

    <?php if (!empty($_GET['deleted'])) : ?>
        <div class="notice notice-success"><p><?php esc_html_e('Notification deleted.', 'ttp-notifications'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($_GET['bulk_deleted'])) : ?>
        <div class="notice notice-success"><p>
            <?php
            printf(
                esc_html__('%d notification(s) deleted.', 'ttp-notifications'),
                (int) $_GET['bulk_deleted']
            );
            ?>
        </p></div>
    <?php endif; ?>
    <?php if (!empty($_GET['all_deleted'])) : ?>
        <div class="notice notice-success"><p>
            <?php
            printf(
                esc_html__('%d notification(s) deleted from the log.', 'ttp-notifications'),
                (int) $_GET['all_deleted']
            );
            ?>
        </p></div>
    <?php endif; ?>

    <p class="description">
        <?php
        if ($total_count > $shown_count) {
            printf(
                esc_html__('Showing %1$d of %2$d notifications (newest first). Use “Delete all” to remove every row in one click—not just the visible page.', 'ttp-notifications'),
                $shown_count,
                $total_count
            );
        } else {
            printf(
                esc_html__('%d notification(s) in the log.', 'ttp-notifications'),
                $total_count
            );
        }
        ?>
    </p>

    <form method="get" class="ttpn-filter-form">
        <input type="hidden" name="page" value="ttp-notifications-log" />
        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search title or message…', 'ttp-notifications'); ?>" />
        <input type="number" name="user_id" value="<?php echo esc_attr($user_id ?: ''); ?>" placeholder="<?php esc_attr_e('User ID', 'ttp-notifications'); ?>" />
        <button class="button"><?php esc_html_e('Filter', 'ttp-notifications'); ?></button>
    </form>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="ttpn-bulk-form">
        <?php wp_nonce_field('ttpn_bulk_delete_notifications'); ?>
        <input type="hidden" name="action" value="ttpn_bulk_delete_notifications" />

        <div class="ttpn-bulk-bar">
            <button type="submit" class="button button-secondary" id="ttpn-bulk-delete-btn">
                <?php esc_html_e('Delete selected', 'ttp-notifications'); ?>
            </button>
            <span class="ttpn-bulk-count" id="ttpn-selected-count"></span>
        </div>

        <table class="widefat striped ttpn-table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="ttpn-select-all"><?php esc_html_e('Select all', 'ttp-notifications'); ?></label>
                        <input id="ttpn-select-all" type="checkbox" />
                    </td>
                    <th><?php esc_html_e('ID', 'ttp-notifications'); ?></th>
                    <th><?php esc_html_e('User', 'ttp-notifications'); ?></th>
                    <th><?php esc_html_e('Type', 'ttp-notifications'); ?></th>
                    <th><?php esc_html_e('Title', 'ttp-notifications'); ?></th>
                    <th><?php esc_html_e('Read', 'ttp-notifications'); ?></th>
                    <th><?php esc_html_e('Push', 'ttp-notifications'); ?></th>
                    <th><?php esc_html_e('Date', 'ttp-notifications'); ?></th>
                    <th><?php esc_html_e('Actions', 'ttp-notifications'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr><td colspan="9"><?php esc_html_e('No notifications found.', 'ttp-notifications'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($items as $item) : ?>
                        <?php $user = get_userdata($item['user_id']); ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" class="ttpn-row-check" name="notification_ids[]" value="<?php echo esc_attr($item['id']); ?>" />
                            </th>
                            <td><?php echo esc_html($item['id']); ?></td>
                            <td><?php echo esc_html($user ? $user->display_name : '#' . $item['user_id']); ?></td>
                            <td><?php echo esc_html($item['type']); ?></td>
                            <td>
                                <strong><?php echo esc_html($item['title']); ?></strong><br />
                                <small><?php echo esc_html(wp_strip_all_tags($item['message'])); ?></small>
                            </td>
                            <td><?php echo $item['is_read'] ? esc_html__('Yes', 'ttp-notifications') : esc_html__('No', 'ttp-notifications'); ?></td>
                            <td><?php echo $item['push_sent'] ? esc_html__('Sent', 'ttp-notifications') : esc_html__('Pending', 'ttp-notifications'); ?></td>
                            <td><?php echo esc_html($item['created_at']); ?></td>
                            <td>
                                <a class="submitdelete" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=ttpn_delete_notification&id=' . (int) $item['id']), 'ttpn_delete_notification_' . (int) $item['id'])); ?>" onclick="return confirm('<?php echo esc_js(__('Delete this notification permanently?', 'ttp-notifications')); ?>');">
                                    <?php esc_html_e('Delete', 'ttp-notifications'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>

    <?php if ($total_count > 0) : ?>
        <div class="ttpn-delete-all-bar" style="margin-top:16px;padding-top:16px;border-top:1px solid #dcdcde;">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="ttpn-delete-all-form">
                <?php wp_nonce_field('ttpn_delete_all_notifications'); ?>
                <input type="hidden" name="action" value="ttpn_delete_all_notifications" />
                <input type="hidden" name="filter_search" value="<?php echo esc_attr($search); ?>" />
                <input type="hidden" name="filter_user_id" value="<?php echo esc_attr($user_id ?: ''); ?>" />

                <?php if ($has_filters) : ?>
                    <input type="hidden" name="delete_scope" value="filtered" />
                    <button type="submit" class="button button-link-delete" id="ttpn-delete-filtered-btn">
                        <?php
                        printf(
                            esc_html__('Delete all %d matching notifications', 'ttp-notifications'),
                            $total_count
                        );
                        ?>
                    </button>
                    <span class="description" style="margin-left:8px;">
                        <?php esc_html_e('Removes every row that matches your current filter—not just the 100 shown above.', 'ttp-notifications'); ?>
                    </span>
                <?php else : ?>
                    <input type="hidden" name="delete_scope" value="all" />
                    <button type="submit" class="button button-link-delete" id="ttpn-delete-all-btn">
                        <?php
                        printf(
                            esc_html__('Delete ALL %d notifications', 'ttp-notifications'),
                            $total_count
                        );
                        ?>
                    </button>
                    <span class="description" style="margin-left:8px;">
                        <?php esc_html_e('Permanently clears the entire notification log in one click.', 'ttp-notifications'); ?>
                    </span>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var selectAll = document.getElementById('ttpn-select-all');
    var bulkForm = document.getElementById('ttpn-bulk-form');
    var bulkBtn = document.getElementById('ttpn-bulk-delete-btn');
    var countEl = document.getElementById('ttpn-selected-count');
    var deleteAllForm = document.getElementById('ttpn-delete-all-form');

    function rowChecks() {
        return bulkForm ? bulkForm.querySelectorAll('.ttpn-row-check') : [];
    }

    function updateCount() {
        if (!countEl) {
            return;
        }
        var checked = 0;
        rowChecks().forEach(function (box) {
            if (box.checked) {
                checked++;
            }
        });
        countEl.textContent = checked ? (checked + ' selected') : '';
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowChecks().forEach(function (box) {
                box.checked = selectAll.checked;
            });
            updateCount();
        });
    }

    rowChecks().forEach(function (box) {
        box.addEventListener('change', function () {
            if (!selectAll) {
                updateCount();
                return;
            }
            var all = rowChecks();
            var checkedCount = 0;
            all.forEach(function (item) {
                if (item.checked) {
                    checkedCount++;
                }
            });
            selectAll.checked = all.length > 0 && checkedCount === all.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < all.length;
            updateCount();
        });
    });

    if (bulkForm && bulkBtn) {
        bulkForm.addEventListener('submit', function (e) {
            var checked = 0;
            rowChecks().forEach(function (box) {
                if (box.checked) {
                    checked++;
                }
            });
            if (!checked) {
                e.preventDefault();
                window.alert('<?php echo esc_js(__('Select at least one notification to delete.', 'ttp-notifications')); ?>');
                return;
            }
            if (!window.confirm('<?php echo esc_js(__('Delete selected notifications permanently?', 'ttp-notifications')); ?>')) {
                e.preventDefault();
            }
        });
    }

    if (deleteAllForm) {
        deleteAllForm.addEventListener('submit', function (e) {
            var msg = <?php echo wp_json_encode(
                $has_filters
                    ? sprintf(
                        /* translators: %d: notification count */
                        __('Delete ALL %d notifications matching the current filter? This cannot be undone.', 'ttp-notifications'),
                        $total_count
                    )
                    : sprintf(
                        /* translators: %d: notification count */
                        __('Delete ALL %d notifications from the log? This cannot be undone.', 'ttp-notifications'),
                        $total_count
                    )
            ); ?>;
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    }
})();
</script>
