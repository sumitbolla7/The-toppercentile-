<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap anh-wrap">
    <h1><?php esc_html_e('Create Notification', 'anh-hub'); ?></h1>

    <?php if (!empty($_GET['sent'])) : ?>
        <div class="notice notice-success"><p>
            <?php
            printf(
                /* translators: %d: number of notifications sent */
                esc_html__('Notification sent to %d user(s).', 'anh-hub'),
                (int) $_GET['sent']
            );
            ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ttp-notifications-log')); ?>"><?php esc_html_e('View or delete notifications', 'anh-hub'); ?></a>
        </p></div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])) : ?>
        <div class="notice notice-error"><p><?php esc_html_e('Could not send notification. Check title and message.', 'anh-hub'); ?></p></div>
    <?php endif; ?>

    <?php if (!empty($_GET['csv_error'])) : ?>
        <div class="notice notice-error"><p><?php esc_html_e('CSV upload failed or no matching users were found. Use a .csv file with one email per row (or a column named email).', 'anh-hub'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('anh_create_notification'); ?>
        <input type="hidden" name="action" value="anh_create_notification" />

        <table class="form-table">
            <tr>
                <th><label for="anh-title"><?php esc_html_e('Title', 'anh-hub'); ?></label></th>
                <td><input type="text" id="anh-title" name="title" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="anh-message"><?php esc_html_e('Message', 'anh-hub'); ?></label></th>
                <td><textarea id="anh-message" name="message" rows="5" class="large-text" required></textarea></td>
            </tr>
            <tr>
                <th><label for="anh-type"><?php esc_html_e('Type', 'anh-hub'); ?></label></th>
                <td>
                    <select id="anh-type" name="type">
                        <option value="general"><?php esc_html_e('General', 'anh-hub'); ?></option>
                        <option value="referral"><?php esc_html_e('Referral', 'anh-hub'); ?></option>
                        <option value="commission"><?php esc_html_e('Commission', 'anh-hub'); ?></option>
                        <option value="order"><?php esc_html_e('Order', 'anh-hub'); ?></option>
                        <option value="payout"><?php esc_html_e('Payout', 'anh-hub'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="anh-link"><?php esc_html_e('Link (optional)', 'anh-hub'); ?></label></th>
                <td><input type="url" id="anh-link" name="link" class="regular-text" placeholder="https://" /></td>
            </tr>
            <tr>
                <th><label for="anh-duration"><?php esc_html_e('Display duration', 'anh-hub'); ?></label></th>
                <td>
                    <input type="number" id="anh-duration" name="duration_seconds" class="small-text" min="3" max="120" step="1" value="5" />
                    <?php esc_html_e('seconds', 'anh-hub'); ?>
                    <p class="description"><?php esc_html_e('How long the popup stays visible on screen (3–120 seconds).', 'anh-hub'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="anh-target"><?php esc_html_e('Send to', 'anh-hub'); ?></label></th>
                <td>
                    <select id="anh-target" name="target">
                        <option value="single"><?php esc_html_e('Single user', 'anh-hub'); ?></option>
                        <option value="csv"><?php esc_html_e('CSV file (emails)', 'anh-hub'); ?></option>
                        <option value="all"><?php esc_html_e('All users', 'anh-hub'); ?></option>
                        <option value="affiliates"><?php esc_html_e('All affiliates', 'anh-hub'); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="anh-target-single">
                <th><label for="anh-user"><?php esc_html_e('User', 'anh-hub'); ?></label></th>
                <td>
                    <select id="anh-user" name="user_id" class="anh-user-search" data-search-placeholder="<?php esc_attr_e('Search by name or email…', 'anh-hub'); ?>">
                        <option value=""><?php esc_html_e('Select user…', 'anh-hub'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Start typing a name or email — matching users load as you search.', 'anh-hub'); ?></p>
                </td>
            </tr>
            <tr class="anh-target-csv" style="display:none;">
                <th><label for="anh-csv-file"><?php esc_html_e('CSV file', 'anh-hub'); ?></label></th>
                <td>
                    <input type="file" id="anh-csv-file" name="csv_file" accept=".csv,text/csv" />
                    <p class="description">
                        <?php esc_html_e('Upload a .csv with one email per row, or a header row with an "email" column. Optional "user_id" column is also supported.', 'anh-hub'); ?>
                        <br />
                        <code>email</code><br />
                        <code>student@example.com</code><br />
                        <code>sumit@example.com</code>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Push notification', 'anh-hub'); ?></th>
                <td>
                    <label><input type="checkbox" name="send_push" value="1" checked /> <?php esc_html_e('Also send browser push (if enabled)', 'anh-hub'); ?></label>
                    <p class="description"><?php esc_html_e('Bulk sends (all users / affiliates / CSV) queue push in the background so the page does not time out.', 'anh-hub'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Send notification', 'anh-hub')); ?>
    </form>
</div>
