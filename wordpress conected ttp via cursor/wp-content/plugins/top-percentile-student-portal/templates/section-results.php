<?php
/**
 * Results section.
 */
?>
<div class="tpsp-card">
    <h3><i class="fa-solid fa-chart-line"></i> <?php esc_html_e('Results & Performance', 'tpsp'); ?></h3>
    <p><?php esc_html_e('Exam attempts, marks, percentile, and badges are supported through user meta and ready for LMS/testing API integration.', 'tpsp'); ?></p>
    <div class="tpsp-grid">
        <div class="tpsp-card tpsp-sub-card">
            <strong>0</strong>
            <span><?php esc_html_e('Exam Attempts', 'tpsp'); ?></span>
        </div>
        <div class="tpsp-card tpsp-sub-card">
            <strong>0%</strong>
            <span><?php esc_html_e('Average Score', 'tpsp'); ?></span>
        </div>
        <div class="tpsp-card tpsp-sub-card">
            <strong>0</strong>
            <span><?php esc_html_e('Badges Earned', 'tpsp'); ?></span>
        </div>
    </div>
    <p><?php esc_html_e('Need actual score cards? Store data in user meta key: tpsp_exam_results (JSON structure).', 'tpsp'); ?></p>
</div>
