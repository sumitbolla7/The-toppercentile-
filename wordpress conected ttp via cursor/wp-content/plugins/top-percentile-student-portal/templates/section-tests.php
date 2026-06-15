<?php
/**
 * Tests section.
 */
?>
<div class="tpsp-card">
    <h3><i class="fa-solid fa-file-pen"></i> <?php esc_html_e('Test Series Access', 'tpsp'); ?></h3>
    <?php if (empty($purchased_products)) : ?>
        <p><?php esc_html_e('Purchase a test series to unlock this area.', 'tpsp'); ?></p>
    <?php else : ?>
        <div class="tpsp-list">
            <?php foreach ($purchased_products as $product_id => $test_name) : ?>
                <div class="tpsp-list-item">
                    <div>
                        <strong><?php echo esc_html($test_name); ?></strong>
                        <small><?php esc_html_e('Ready for future online exam engine integration.', 'tpsp'); ?></small>
                    </div>
                    <a class="tpsp-btn" href="<?php echo esc_url(get_permalink($product_id)); ?>"><?php esc_html_e('Go to Test', 'tpsp'); ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
