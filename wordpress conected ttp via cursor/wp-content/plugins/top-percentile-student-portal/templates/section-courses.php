<?php
/**
 * Courses section.
 */
?>
<div class="tpsp-card">
    <h3><i class="fa-solid fa-book-open-reader"></i> <?php esc_html_e('Enrolled Courses', 'tpsp'); ?></h3>
    <?php if (empty($purchased_products)) : ?>
        <p><?php esc_html_e('No purchased courses yet.', 'tpsp'); ?></p>
    <?php else : ?>
        <div class="tpsp-list">
            <?php foreach ($purchased_products as $product_id => $course_data) : ?>
                <?php
                $course_name = is_array($course_data) && isset($course_data['name']) ? $course_data['name'] : $course_data;
                $access_url  = is_array($course_data) && !empty($course_data['access_url']) ? $course_data['access_url'] : get_permalink($product_id);
                ?>
                <div class="tpsp-list-item">
                    <div>
                        <strong><?php echo esc_html($course_name); ?></strong>
                        <small><?php esc_html_e('Access granted after successful purchase.', 'tpsp'); ?></small>
                    </div>
                    <a class="tpsp-btn tpsp-btn-secondary" href="<?php echo esc_url($access_url); ?>"><?php esc_html_e('Open Course', 'tpsp'); ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h4><?php esc_html_e('Course Progress', 'tpsp'); ?></h4>
    <?php if (empty($progress)) : ?>
        <p><?php esc_html_e('Progress tracking is enabled and ready for LMS integration.', 'tpsp'); ?></p>
    <?php else : ?>
        <?php foreach ($progress as $course_id => $percentage) : ?>
            <div class="tpsp-progress">
                <span><?php echo esc_html(get_the_title((int) $course_id)); ?></span>
                <div class="tpsp-progress-bar"><i style="width:<?php echo esc_attr((int) $percentage); ?>%"></i></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
