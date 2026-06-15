<?php
if (!defined('ABSPATH')) {
    exit;
}

$accent = esc_attr($settings['accent_color']);
$panel  = esc_attr($settings['panel_color']);
?>
<div id="anh-promo-popup" class="anh-promo-popup" hidden aria-hidden="true" style="--anh-promo-accent:<?php echo $accent; ?>;--anh-promo-panel:<?php echo $panel; ?>;">
    <div class="anh-promo-backdrop" data-anh-promo-close></div>
    <div class="anh-promo-modal" role="dialog" aria-modal="true" aria-labelledby="anh-promo-title">
        <button type="button" class="anh-promo-close" data-anh-promo-close aria-label="<?php esc_attr_e('Close', 'anh-hub'); ?>" title="<?php esc_attr_e('Close', 'anh-hub'); ?>"><span aria-hidden="true">&times;</span></button>
        <div class="anh-promo-grid">
            <div class="anh-promo-content">
                <?php if (!empty($settings['title'])) : ?>
                    <p class="anh-promo-kicker" id="anh-promo-title"><?php echo esc_html($settings['title']); ?></p>
                <?php endif; ?>
                <?php if (!empty($settings['headline'])) : ?>
                    <h2 class="anh-promo-headline"><?php echo esc_html($settings['headline']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($settings['subtitle'])) : ?>
                    <p class="anh-promo-subtitle"><?php echo esc_html($settings['subtitle']); ?></p>
                <?php endif; ?>
                <?php if (!empty($settings['description'])) : ?>
                    <p class="anh-promo-desc"><?php echo esc_html($settings['description']); ?></p>
                <?php endif; ?>

                <?php if (!empty($settings['show_countdown'])) : ?>
                    <div class="anh-promo-countdown" id="anh-promo-countdown" aria-live="polite">
                        <div class="anh-promo-countdown-item"><span class="anh-promo-countdown-num" data-unit="days">00</span><span class="anh-promo-countdown-label"><?php esc_html_e('DAYS', 'anh-hub'); ?></span></div>
                        <div class="anh-promo-countdown-item"><span class="anh-promo-countdown-num" data-unit="hours">00</span><span class="anh-promo-countdown-label"><?php esc_html_e('HRS', 'anh-hub'); ?></span></div>
                        <div class="anh-promo-countdown-item"><span class="anh-promo-countdown-num" data-unit="minutes">00</span><span class="anh-promo-countdown-label"><?php esc_html_e('MIN', 'anh-hub'); ?></span></div>
                        <div class="anh-promo-countdown-item"><span class="anh-promo-countdown-num" data-unit="seconds">00</span><span class="anh-promo-countdown-label"><?php esc_html_e('SEC', 'anh-hub'); ?></span></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($settings['button_text']) && !empty($settings['button_url'])) : ?>
                    <a class="anh-promo-cta" href="<?php echo esc_url($settings['button_url']); ?>"><?php echo esc_html($settings['button_text']); ?></a>
                <?php endif; ?>

                <?php if (!empty($settings['dismiss_text'])) : ?>
                    <button type="button" class="anh-promo-dismiss" data-anh-promo-close><?php echo esc_html($settings['dismiss_text']); ?></button>
                <?php endif; ?>
            </div>
            <div class="anh-promo-visual" style="background-color:<?php echo $panel; ?>;">
                <?php if (!empty($settings['image_url'])) : ?>
                    <img src="<?php echo esc_url($settings['image_url']); ?>" alt="" class="anh-promo-image" />
                <?php else : ?>
                    <div class="anh-promo-image-placeholder"><?php esc_html_e('Add promo image in Affiliate Hub → Site Popup', 'anh-hub'); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
