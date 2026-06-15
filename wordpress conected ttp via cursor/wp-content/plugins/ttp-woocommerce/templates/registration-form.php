<?php if (!defined('ABSPATH')) exit; ?>
<div class="ttp-registration-wrap">
    <?php if ($product): ?>
    <div class="ttp-product-summary">
        <div>
            <div style="font-size:12px;opacity:0.8;margin-bottom:4px;">YOU ARE PURCHASING</div>
            <h3><?php echo esc_html($product->get_name()); ?></h3>
        </div>
        <div class="ttp-price"><?php echo $product->get_price_html(); ?></div>
    </div>
    <?php endif; ?>
    <div class="ttp-reg-box">
        <h2>Create Your Account</h2>
        <p style="color:#7a756d;margin-bottom:24px;">Create an account to proceed to payment and access your course.</p>
        <div id="ttp-reg-message" class="ttp-message" style="display:none;"></div>
        <div class="ttp-field">
            <label>Full Name *</label>
            <input type="text" id="ttp_full_name" placeholder="Enter your full name"/>
        </div>
        <div class="ttp-field">
            <label>Email Address *</label>
            <input type="email" id="ttp_email" placeholder="Enter your email"/>
        </div>
        <div class="ttp-field">
            <label>Mobile Number *</label>
            <input type="tel" id="ttp_mobile" placeholder="10-digit mobile number" maxlength="10"/>
        </div>
        <div class="ttp-field">
            <label>Username *</label>
            <input type="text" id="ttp_username" placeholder="Choose a unique username"/>
        </div>
        <div class="ttp-field">
            <label>Password *</label>
            <input type="password" id="ttp_password" placeholder="Minimum 6 characters"/>
        </div>
        <input type="hidden" id="ttp_product_id" value="<?php echo esc_attr($product_id); ?>"/>
        <button type="button" class="ttp-submit-reg" id="ttp-submit-reg">
            Create Account & Proceed to Payment →
        </button>
        <p class="ttp-login-link">Already have an account? <a href="<?php echo esc_url(function_exists('tpsp_get_login_redirect_url') ? tpsp_get_login_redirect_url(wc_get_checkout_url()) : home_url('/login/')); ?>">Login here</a></p>
    </div>
</div>
