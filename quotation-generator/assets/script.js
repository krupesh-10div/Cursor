/* global $ */
(function(){
	const $form = $('#quoteForm');
	const $itemsBody = $('#itemRows');
	const $addRow = $('#addRow');
	const $addTerm = $('#addTerm');
	const $termsWrap = $('#termsWrap');
	const $loading = $('#loading');
	const $btn = $('#generateBtn');

	function formatNumber(n){
		return (Math.round((Number(n)||0) * 100) / 100).toFixed(2);
	}

	function computeRowTotal($row){
		const qty = parseFloat($row.find('input.qty').val()) || 0;
		const price = parseFloat($row.find('input.price').val()) || 0;
		const tax = parseFloat($row.find('input.tax').val()) || 0;
		const base = qty * price;
		const taxAmt = base * (tax / 100);
		const total = base + taxAmt;
		$row.find('.row-total').text(formatNumber(total));
		return { base, taxAmt, total };
	}

	function recomputeTotals(){
		let subtotal = 0; let taxTotal = 0; let grand = 0;
		$itemsBody.find('tr.item-row').each(function(){
			const {base, taxAmt, total} = computeRowTotal($(this));
			subtotal += base; taxTotal += taxAmt; grand += total;
		});
		$('#subtotal').text(formatNumber(subtotal));
		$('#taxtotal').text(formatNumber(taxTotal));
		$('#grandtotal').text(formatNumber(grand));
	}

	// Initial compute
	recomputeTotals();

	$itemsBody.on('input', 'input', function(){
		recomputeTotals();
	});

	$itemsBody.on('click', '.remove-row', function(){
		if ($itemsBody.find('tr.item-row').length === 1){
			// keep one row
			$(this).closest('tr').find('input').val('');
			recomputeTotals();
			return;
		}
		$(this).closest('tr').remove();
		recomputeTotals();
	});

	$addRow.on('click', function(){
		const $row = $itemsBody.find('tr.item-row').first().clone();
		$row.find('input').val('');
		$row.find('.row-total').text('0.00');
		$itemsBody.append($row);
	});

	$termsWrap.on('click', '.remove-term', function(){
		if ($termsWrap.find('.term-row').length === 1){
			$(this).closest('.term-row').find('input').val('');
			return;
		}
		$(this).closest('.term-row').remove();
	});

	$addTerm.on('click', function(){
		const $row = $termsWrap.find('.term-row').first().clone();
		$row.find('input').val('');
		$termsWrap.append($row);
	});

	$form.on('submit', function(e){
		e.preventDefault();
		$btn.prop('disabled', true);
		$loading.removeAttr('hidden');
		const formData = new FormData(this);
		$.ajax({
			url: 'generate_quote.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			xhrFields: { responseType: 'blob' },
			success: function(data, status, xhr){
				const filename = xhr.getResponseHeader('X-Filename') || 'quotation.pdf';
				const blob = new Blob([data], { type: 'application/pdf' });
				const link = document.createElement('a');
				link.href = URL.createObjectURL(blob);
				link.download = filename;
				document.body.appendChild(link);
				link.click();
				link.remove();
			},
			error: function(xhr){
				alert('Failed to generate PDF. Please try again.');
				console.error(xhr.responseText);
			},
			complete: function(){
				$btn.prop('disabled', false);
				$loading.attr('hidden', 'hidden');
			}
		});
	});
})();

