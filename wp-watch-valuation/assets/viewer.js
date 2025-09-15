(function($){
	function initViewer($root){
		var $input = $root.find('.wpwv-input');
		var $btn = $root.find('.wpwv-run');
		var $out = $root.find('.wpwv-output');
		var nonce = $root.data('nonce');

		$btn.on('click', function(){
			var text = $input.val();
			$out.html('<em>Rendering…</em>');
			$.ajax({
				url: WPWV_Viewer.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wpwv_text_to_html',
					text: text,
					nonce: nonce
				}
			}).done(function(resp){
				if(resp && resp.success && resp.data && resp.data.html){
					$out.html(resp.data.html);
				}else{
					$out.text('No output');
				}
			}).fail(function(){
				$out.text('Failed to render');
			});
		});
	}

	$(function(){
		$('.wpwv-viewer').each(function(){
			initViewer($(this));
		});
	});
})(jQuery);

