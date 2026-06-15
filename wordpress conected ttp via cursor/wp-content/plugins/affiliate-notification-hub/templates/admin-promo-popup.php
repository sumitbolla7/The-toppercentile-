<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap anh-wrap">
    <h1><?php esc_html_e('Site Popup (Flash Sale)', 'anh-hub'); ?></h1>

    <?php if (!empty($_GET['updated'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Popup settings saved.', 'anh-hub'); ?></p></div>
    <?php endif; ?>

    <p><?php esc_html_e('Customize the promotional popup visitors see on your site — text, image, button link, colors, and live countdown timer.', 'anh-hub'); ?></p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="anh-promo-settings-form">
        <?php wp_nonce_field('anh_save_promo_popup'); ?>
        <input type="hidden" name="action" value="anh_save_promo_popup" />

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Enable popup', 'anh-hub'); ?></th>
                <td>
                    <input type="hidden" name="enabled_sent" value="1" />
                    <label><input type="checkbox" name="enabled" value="1" <?php checked(!empty($settings['enabled'])); ?> /> <?php esc_html_e('Show popup on the website', 'anh-hub'); ?></label>
                    <?php if (empty($settings['enabled'])) : ?>
                        <p class="description"><?php esc_html_e('Popup is currently OFF. Save settings after any change, then hard-refresh the site (Ctrl+F5).', 'anh-hub'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-title"><?php esc_html_e('Top label', 'anh-hub'); ?></label></th>
                <td><input type="text" class="regular-text" id="anh-promo-title" name="title" value="<?php echo esc_attr($settings['title']); ?>" placeholder="Flash Sale" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-headline"><?php esc_html_e('Main headline', 'anh-hub'); ?></label></th>
                <td><input type="text" class="regular-text" id="anh-promo-headline" name="headline" value="<?php echo esc_attr($settings['headline']); ?>" placeholder="50% OFF" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-subtitle"><?php esc_html_e('Subtitle', 'anh-hub'); ?></label></th>
                <td><input type="text" class="regular-text" id="anh-promo-subtitle" name="subtitle" value="<?php echo esc_attr($settings['subtitle']); ?>" placeholder="ON ENTIRE ORDER" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-description"><?php esc_html_e('Countdown label', 'anh-hub'); ?></label></th>
                <td><input type="text" class="regular-text" id="anh-promo-description" name="description" value="<?php echo esc_attr($settings['description']); ?>" placeholder="LIMITED-TIME OFFER! SALE ENDS IN" /></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Live countdown', 'anh-hub'); ?></th>
                <td>
                    <label><input type="checkbox" name="show_countdown" value="1" <?php checked($settings['show_countdown']); ?> /> <?php esc_html_e('Show countdown timer', 'anh-hub'); ?></label>
                    <p>
                        <label for="anh-promo-countdown-end"><?php esc_html_e('Sale ends at', 'anh-hub'); ?></label><br>
                        <input type="datetime-local" id="anh-promo-countdown-end" name="countdown_end" value="<?php echo esc_attr($settings['countdown_end'] ? wp_date('Y-m-d\TH:i', strtotime($settings['countdown_end'])) : ''); ?>" />
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-image"><?php esc_html_e('Promo image', 'anh-hub'); ?></label></th>
                <td>
                    <input type="url" class="regular-text anh-promo-image-url" id="anh-promo-image" name="image_url" value="<?php echo esc_attr($settings['image_url']); ?>" />
                    <button type="button" class="button anh-promo-upload-image"><?php esc_html_e('Select image', 'anh-hub'); ?></button>
                    <?php if (!empty($settings['image_url'])) : ?>
                        <p><img src="<?php echo esc_url($settings['image_url']); ?>" alt="" style="max-width:200px;height:auto;border-radius:8px;" /></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-button-text"><?php esc_html_e('Button text', 'anh-hub'); ?></label></th>
                <td><input type="text" class="regular-text" id="anh-promo-button-text" name="button_text" value="<?php echo esc_attr($settings['button_text']); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-button-url"><?php esc_html_e('Button URL', 'anh-hub'); ?></label></th>
                <td><input type="url" class="regular-text" id="anh-promo-button-url" name="button_url" value="<?php echo esc_attr($settings['button_url']); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-dismiss"><?php esc_html_e('Dismiss text', 'anh-hub'); ?></label></th>
                <td><input type="text" class="regular-text" id="anh-promo-dismiss" name="dismiss_text" value="<?php echo esc_attr($settings['dismiss_text']); ?>" placeholder="NO, THANKS!" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-accent"><?php esc_html_e('Accent color', 'anh-hub'); ?></label></th>
                <td><input type="text" class="anh-color-picker" id="anh-promo-accent" name="accent_color" value="<?php echo esc_attr($settings['accent_color']); ?>" data-default-color="#e91e63" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-panel"><?php esc_html_e('Image panel color', 'anh-hub'); ?></label></th>
                <td><input type="text" class="anh-color-picker" id="anh-promo-panel" name="panel_color" value="<?php echo esc_attr($settings['panel_color']); ?>" data-default-color="#fce4ec" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="anh-promo-delay"><?php esc_html_e('Delay (seconds)', 'anh-hub'); ?></label></th>
                <td><input type="number" min="0" max="60" id="anh-promo-delay" name="delay_seconds" value="<?php echo esc_attr((string) $settings['delay_seconds']); ?>" class="small-text" /></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Display rules', 'anh-hub'); ?></th>
                <td>
                    <label><input type="checkbox" name="show_once_session" value="1" <?php checked($settings['show_once_session']); ?> /> <?php esc_html_e('Show once per browser session', 'anh-hub'); ?></label><br>
                    <label><input type="checkbox" name="show_logged_in" value="1" <?php checked($settings['show_logged_in']); ?> /> <?php esc_html_e('Show to logged-in users', 'anh-hub'); ?></label><br>
                    <label><input type="checkbox" name="show_guests" value="1" <?php checked($settings['show_guests']); ?> /> <?php esc_html_e('Show to guests', 'anh-hub'); ?></label>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save popup settings', 'anh-hub')); ?>
    </form>
</div>
