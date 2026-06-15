jQuery(document).ready(function($){
    function buildExamLoginUrl(pid){
        var checkoutBase = (typeof ttp_ajax !== 'undefined' && ttp_ajax.checkout_url) ? ttp_ajax.checkout_url : '/checkout/';
        var loginBase = (typeof ttp_ajax !== 'undefined' && ttp_ajax.login_url) ? ttp_ajax.login_url : '/login/';
        var checkout = checkoutBase;
        if(pid){
            var sep = checkoutBase.indexOf('?') === -1 ? '?' : '&';
            checkout = checkoutBase + sep + 'add-to-cart=' + encodeURIComponent(pid);
        }
        var loginSep = loginBase.indexOf('?') === -1 ? '?' : '&';
        return loginBase + loginSep + 'redirect_to=' + encodeURIComponent(checkout);
    }

    function redirectToBuyFlow(pid, buttonEl, options){
        options = options || {};
        if(!pid){
            return;
        }

        var onExam = window.location.pathname.indexOf('/exam/') !== -1;
        var isLoggedIn = (typeof ttp_ajax !== 'undefined' && parseInt(ttp_ajax.is_logged_in, 10)) || $('body').hasClass('logged-in');
        if (!isLoggedIn && typeof window.tpspSessionLoggedIn !== 'undefined') {
            isLoggedIn = !!window.tpspSessionLoggedIn;
        }
        if(onExam && !isLoggedIn){
            window.location.href = buildExamLoginUrl(pid);
            return;
        }

        var btn = buttonEl ? $(buttonEl) : $();
        if(btn.length){
            btn.html('⏳ Please wait...').prop('disabled', true);
        }

        var postData = {action:'ttp_buy_now', nonce:ttp_ajax.nonce, product_id:pid};
        if(onExam){
            postData.exam_page = 1;
        }

        $.post(
            ttp_ajax.ajax_url,
            postData,
            function(r){
                if(r && r.success && r.data && r.data.redirect_url){
                    window.location.href = r.data.redirect_url;
                    return;
                }
                alert((r && r.data && r.data.message) ? r.data.message : 'Something went wrong.');
                if(btn.length){
                    btn.html('⚡ Enroll Now →').prop('disabled', false);
                }
            }
        );
    }

    function enforceExamLoginCheckoutFlow(){
        if(window.location.pathname.indexOf('/exam/') === -1){
            return;
        }

        // Hard-disable modal if it exists in page markup.
        var overlay = document.getElementById('ttpSignupOverlay');
        if(overlay){
            overlay.style.display = 'none';
            overlay.classList.remove('open');
        }

        // Capture click before page-level popup handlers run.
        document.addEventListener('click', function(evt){
            var trigger = evt.target.closest('.ttp-open-signup, .ttp-btn-enroll, .ttp-buy-now-btn, .ttp-btn-buy, .ttp-plan-card__btn-cart, .ttp-course-card__buy');
            if(!trigger){
                return;
            }
            var pid = trigger.getAttribute('data-product-id');
            if(!pid){
                return;
            }
            evt.preventDefault();
            evt.stopPropagation();
            if(typeof evt.stopImmediatePropagation === 'function'){
                evt.stopImmediatePropagation();
            }
            redirectToBuyFlow(pid, trigger);
        }, true);
    }

    enforceExamLoginCheckoutFlow();

    function forceExamBuyNowUi(){
        if(window.location.pathname.indexOf('/exam/') === -1){
            return;
        }

        $('.ttp-plan-card').each(function(){
            var card = $(this);
            var buyBtn = card.find('.ttp-btn-enroll, .ttp-open-signup').first();
            var viewBtn = card.find('.ttp-btn-cart').first();

            if(!buyBtn.length){
                return;
            }

            buyBtn
                .show()
                .removeAttr('hidden')
                .text('Buy Now')
                .css({
                    display: 'block',
                    width: '100%',
                    marginBottom: '10px',
                    background: '#101010',
                    color: '#fff',
                    border: '1px solid #101010',
                    fontWeight: '700'
                });

            if(viewBtn.length){
                buyBtn.insertBefore(viewBtn);
            }
        });
    }

    // Run now and once again after lazy UI scripts settle.
    forceExamBuyNowUi();
    setTimeout(forceExamBuyNowUi, 1200);

    // BUY NOW
    $(document).on('click','.ttp-buy-now-btn, .ttp-plan-card__btn-cart, .ttp-course-card__buy',function(){
        var btn=$(this),pid=btn.data('product-id');
        redirectToBuyFlow(pid, btn);
    });
    // REGISTRATION
    $('#ttp-submit-reg').on('click',function(){
        var btn=$(this),msg=$('#ttp-reg-message');
        var fn=$('#ttp_full_name').val().trim(),em=$('#ttp_email').val().trim(),
            mo=$('#ttp_mobile').val().trim(),un=$('#ttp_username').val().trim(),
            pw=$('#ttp_password').val(),pid=$('#ttp_product_id').val();
        msg.hide().removeClass('success error');
        if(!fn||!em||!mo||!un||!pw){msg.addClass('error').text('Please fill in all fields.').show();return;}
        if(mo.length!==10||!/^\d+$/.test(mo)){msg.addClass('error').text('Please enter a valid 10-digit mobile number.').show();return;}
        if(pw.length<6){msg.addClass('error').text('Password must be at least 6 characters.').show();return;}
        btn.html('⏳ Creating Account...').prop('disabled',true);
        $.post(ttp_ajax.ajax_url,{action:'ttp_register_student',nonce:ttp_ajax.nonce,full_name:fn,email:em,mobile:mo,username:un,password:pw,product_id:pid},function(r){
            if(r.success){msg.addClass('success').text('✅ '+r.data.message).show();setTimeout(function(){window.location.href=r.data.redirect_url;},1500);}
            else{msg.addClass('error').text('❌ '+r.data.message).show();btn.html('Create Account & Proceed to Payment →').prop('disabled',false);}
        });
    });
    // TCY LOGIN
    $(document).on('click','.ttp-access-btn',function(){
        var btn=$(this),oid=btn.data('order-id'),nc=btn.data('nonce');
        var key = '';
        try {
            key = new URLSearchParams(window.location.search || '').get('key') || '';
        } catch (e) {}
        if (!btn.data('ttp-orig-html')) {
            btn.data('ttp-orig-html', btn.html());
        }
        btn.siblings('.ttp-inline-msg').remove();
        btn.html('⏳ Connecting...').prop('disabled',true);
        $.post(ttp_ajax.ajax_url,{action:'ttp_tcy_login',nonce:nc,order_id:oid,order_key:key},function(r){
            if(r.success){
                if(r.data && r.data.login_url){
                    window.location.href = r.data.login_url;
                    return;
                }
                btn.after('<p class="ttp-inline-msg" role="alert">Could not load course link.</p>');
            } else {
                if (r.data && r.data.silent) {
                    btn.after('<p class="ttp-inline-msg" role="status">Connecting… please wait a moment and try again.</p>');
                } else {
                    var msg = (r.data && r.data.message) ? r.data.message : 'Could not connect. Please contact support.';
                    btn.after('<p class="ttp-inline-msg" role="alert"></p>');
                    btn.next('.ttp-inline-msg').text(msg);
                }
            }
            btn.html(btn.data('ttp-orig-html')).prop('disabled', false);
        });
    });
    // COUNTDOWN TIMER
    $('.ttp-countdown').each(function(){
        var el=$(this),s=parseInt(el.data('minutes'),10)*60;
        if(isNaN(s)||s<=0)return;
        var iv=setInterval(function(){
            if(s<=0){clearInterval(iv);el.closest('.ttp-timer-wrap').html('<span style="color:#e8540a;font-weight:800;">⏰ Offer Expired!</span>');return;}
            s--;var m=Math.floor(s/60),sec=s%60;
            el.text((m<10?'0':'')+m+':'+(sec<10?'0':'')+sec);
        },1000);
    });
    // ADMIN RETRY
    $(document).on('click','.ttp-retry-btn',function(){
        var btn=$(this),oid=btn.data('order-id'),nc=btn.data('nonce');
        if(!confirm('Retry TCY API for Order #'+oid+'?'))return;
        btn.text('Retrying...').prop('disabled',true);
        $.post(ajaxurl,{action:'ttp_retry_api',nonce:nc,order_id:oid},function(r){
            if(r.success){alert('✅ '+r.data.message);location.reload();}
            else{alert('❌ Retry failed. Check API credentials.');}
            btn.text('Retry').prop('disabled',false);
        });
    });
});
