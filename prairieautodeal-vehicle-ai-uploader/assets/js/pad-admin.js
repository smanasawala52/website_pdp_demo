(function($){
	function ajax(action, data){
		data = data || {};
		data.action = action;
		data.nonce = (window.PAD_VAIU && PAD_VAIU.nonce) || '';
		return $.post((window.PAD_VAIU && PAD_VAIU.ajaxUrl) || ajaxurl, data);
	}

	function renderImageList($ul, ids){
		$ul.empty();
		(ids||[]).forEach(function(id){
			var img = wp.media.attachment(id);
			var url = (img && img.attributes && img.attributes.sizes && img.attributes.sizes.thumbnail && img.attributes.sizes.thumbnail.url) || (img && img.get('url')) || '';
			$ul.append('<li class="pad-image-item" data-id="'+id+'"><img src="'+url+'" /></li>');
		});
	}

	$(document).on('click', '#pad-add-images', function(){
		var postId = $(this).data('post');
		var maxImages = (window.PAD_VAIU && PAD_VAIU.maxImages) || 10;
		var frame = wp.media({
			title: 'Select Vehicle Images',
			button: { text: 'Add Images' },
			multiple: true,
			library: { type: 'image' }
		});
		frame.on('select', function(){
			var selection = frame.state().get('selection');
			var ids = [];
			var warnings = [];
			selection.each(function(att){
				var a = att.toJSON();
				if (a && a.width && a.width < 600){ warnings.push(a.filename + ' ('+a.width+'px)'); }
				ids.push(a.id);
			});
			if (ids.length > maxImages){ ids = ids.slice(0, maxImages); }
			ajax('pad_upload_vehicle_images', { post_id: postId, attachment_ids: ids }).done(function(resp){
				if (!resp || !resp.success){ return alert((resp && resp.data && resp.data.message) || 'Upload failed'); }
				renderImageList($('#pad-image-list'), resp.data.images);
				if (warnings.length){ alert('Some images are small (<600px):\n' + warnings.join('\n')); }
			});
		});
		frame.open();
	});

	$(function(){
		var $ul = $('#pad-image-list');
		if ($ul.length){
			$ul.sortable({
				update: function(){
					var order = [];
					$ul.find('.pad-image-item').each(function(){ order.push(parseInt($(this).data('id'),10)); });
					var postId = $('#pad-add-images').data('post');
					ajax('pad_reorder_images', { post_id: postId, order: order });
				}
			});
		}
	});

	$(document).on('click', '#pad-run-ai', function(){
		var $btn = $(this);
		$btn.prop('disabled', true).text('Running AI...');
		var postId = $btn.data('post');
		ajax('pad_run_vehicle_ai', { post_id: postId }).done(function(resp){
			if (resp && resp.success && resp.data && resp.data.data){
				var d = resp.data.data;
				if (d._make !== undefined) $('input[name="pad_make"]').val(d._make);
				if (d._model !== undefined) $('input[name="pad_model"]').val(d._model);
				if (d._year !== undefined) $('input[name="pad_year"]').val(d._year);
				if (d._body_type !== undefined) $('input[name="pad_body_type"]').val(d._body_type);
				if (d._color !== undefined) $('input[name="pad_color"]').val(d._color);
				if (d._mileage !== undefined) $('input[name="pad_mileage"]').val(d._mileage);
				$('.pad-ai-status').text('Completed · ' + (d._ai_extracted_at || ''));
			} else {
				alert((resp && resp.data && resp.data.message) || 'AI run failed');
			}
		}).always(function(){
			$btn.prop('disabled', false).text('Run AI Extraction');
		});
	});

	// Bulk page
	var bulkSelectedIds = [];
	$(document).on('click', '#pad-bulk-select-images', function(){
		var frame = wp.media({
			title: 'Select Images for Bulk',
			button: { text: 'Use Images' },
			multiple: true,
			library: { type: 'image' }
		});
		frame.on('select', function(){
			bulkSelectedIds = [];
			var selection = frame.state().get('selection');
			var smalls = [];
			selection.each(function(att){
				var a = att.toJSON();
				bulkSelectedIds.push(a.id);
				if (a.width && a.width < 600){ smalls.push(a.filename + ' ('+a.width+'px)'); }
			});
			$('#pad-bulk-summary').text(bulkSelectedIds.length + ' images selected');
			$('#pad-bulk-create').prop('disabled', bulkSelectedIds.length === 0);
			if (smalls.length){ alert('Some images are small (<600px):\n' + smalls.join('\n')); }
		});
		frame.open();
	});

	$(document).on('click', '#pad-bulk-create', function(){
		var per = parseInt($('#pad-bulk-images-per-listing').val(), 10) || 1;
		var runAI = $('#pad-bulk-run-ai').is(':checked');
		var groups = [];
		for (var i=0; i<bulkSelectedIds.length; i+=per){ groups.push(bulkSelectedIds.slice(i, i+per)); }
		var $progress = $('#pad-bulk-progress').empty();
		function next(idx){
			if (idx >= groups.length){ return; }
			var g = groups[idx];
			var $li = $('<li/>').text('Creating listing ' + (idx+1) + ' of ' + groups.length + '...').appendTo($progress);
			ajax('pad_bulk_create_listing', { attachment_ids: g, run_ai: runAI ? 1 : 0 }).done(function(resp){
				if (resp && resp.success){
					$li.text('Created listing #' + resp.data.post_id + (resp.data.ai_error ? ' (AI error)' : (runAI ? ' (AI completed)' : '')));
				} else {
					$li.text('Failed to create listing: ' + ((resp && resp.data && resp.data.message) || 'error'));
				}
				next(idx+1);
			}).fail(function(){
				$li.text('Failed to create listing (network error)');
				next(idx+1);
			});
		}
		next(0);
	});
})(jQuery);
