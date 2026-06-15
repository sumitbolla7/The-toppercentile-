(function ($) {
	'use strict';

	function openMediaFrame($wrap) {
		var frame = wp.media({
			title: 'Enrol Now card image',
			button: { text: 'Use this image' },
			multiple: false,
			library: { type: 'image' },
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			if (!attachment || !attachment.id) {
				return;
			}
			$wrap.find('input[name="_ttp_enroll_card_image_id"]').val(attachment.id);
			var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
			$wrap.find('.ttp-enroll-image-preview').html(
				'<img src="' + url + '" alt="" style="max-width:280px;height:auto;border-radius:8px;" />'
			);
			$wrap.find('.ttp-enroll-image-remove').show();
		});

		frame.open();
	}

	$(function () {
		var $wrap = $('.ttp-enroll-card-image-field');
		if (!$wrap.length) {
			return;
		}

		$wrap.on('click', '.ttp-enroll-image-upload', function (e) {
			e.preventDefault();
			openMediaFrame($wrap);
		});

		$wrap.on('click', '.ttp-enroll-image-remove', function (e) {
			e.preventDefault();
			$wrap.find('input[name="_ttp_enroll_card_image_id"]').val('');
			$wrap.find('.ttp-enroll-image-preview').empty();
			$(this).hide();
		});
	});
})(jQuery);
