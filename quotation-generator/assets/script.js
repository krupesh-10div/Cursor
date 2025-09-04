/* global $ */
(function(){
	const $form = $('#quoteForm');
	const $itemsBody = $('#itemRows');
	const $addRow = $('#addRow');
	const $termsWrap = $('#termsWrap');
	const $loading = $('#loading');
	const $btn = $('#generateBtn');
	const $includeGst = $('#include_gst');
	const $gstField = $('#gstField');
	const $discountsWrap = $('#discountsWrap');
	const $toggleDiscounts = $('#toggleDiscounts');
	const $showWords = $('#showWords');
	const $wordsOut = $('#wordsOut');
	const $addTermsBtn = $('#addTerms');
	const $addNotesBtn = $('#addNotes');
	const $addValidityBtn = $('#addValidity');
	const $addFooterBtn = $('#addFooter');

	function numberToWordsIndian(num){
		// very lightweight conversion to words (Indian grouping) for demo
		const a = ['','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen'];
		const b = ['','','twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety'];
		function inWords(n){
			if (n < 20) return a[n];
			if (n < 100) return b[Math.floor(n/10)] + (n%10? ' ' + a[n%10] : '');
			if (n < 1000) return a[Math.floor(n/100)] + ' hundred' + (n%100? ' ' + inWords(n%100) : '');
			if (n < 100000) return inWords(Math.floor(n/1000)) + ' thousand' + (n%1000? ' ' + inWords(n%1000) : '');
			if (n < 10000000) return inWords(Math.floor(n/100000)) + ' lakh' + (n%100000? ' ' + inWords(n%100000) : '');
			return inWords(Math.floor(n/10000000)) + ' crore' + (n%10000000? ' ' + inWords(n%10000000) : '');
		}
		const whole = Math.floor(num);
		const paise = Math.round((num - whole) * 100);
		let out = inWords(whole) + ' rupees';
		if (paise) out += ' and ' + inWords(paise) + ' paise';
		return out + ' only';
	}

	function formatNumber(n){
		return (Math.round((Number(n)||0) * 100) / 100).toFixed(2);
	}

	function computeRowTotal($row){
		const qty = parseFloat($row.find('input.qty').val()) || 0;
		const price = parseFloat($row.find('input.price').val()) || 0;
		const base = qty * price;
		const total = base; // per-row tax removed; GST handled globally
		$row.find('.row-total').text(formatNumber(total));
		return { base, total };
	}

	function recomputeTotals(){
		let subtotal = 0; let grand = 0;
		$itemsBody.find('tr.item-row').each(function(){
			const {base, total} = computeRowTotal($(this));
			subtotal += base; grand += total;
		});
		$('#subtotal').text(formatNumber(subtotal));
		let gstAmount = 0;
		if ($includeGst.is(':checked')){
			const gstPercent = parseFloat($('input[name="gst_percent"]').val()) || 0;
			gstAmount = subtotal * gstPercent / 100;
			$('#gstLine').removeAttr('hidden');
			$('#taxtotal').text(formatNumber(gstAmount));
		} else {
			$('#gstLine').attr('hidden','hidden');
		}
		let discount = parseFloat($('input[name="discount_amount"]').val()) || 0;
		let additional = parseFloat($('input[name="additional_amount"]').val()) || 0;
		$('#discountLine').toggle(discount>0);
		$('#additionalLine').toggle(additional>0);
		$('#discountval').text(formatNumber(discount));
		$('#additionalval').text(formatNumber(additional));

		let total = subtotal + gstAmount - discount + additional;
		let roundAdj = 0;
		const rounding = $('input[name="rounding"]:checked').val();
		if (rounding === 'up') roundAdj = Math.ceil(total) - total;
		if (rounding === 'down') roundAdj = Math.floor(total) - total;
		if (rounding !== 'none') {
			$('#roundLine').show();
			$('#roundval').text(formatNumber(roundAdj));
		} else {
			$('#roundLine').hide();
		}
		total = total + roundAdj;
		$('#grandtotal').text(formatNumber(total));
		if ($showWords.is(':checked')){
			$wordsOut.removeAttr('hidden').text(numberToWordsIndian(total));
		} else {
			$wordsOut.attr('hidden','hidden').text('');
		}
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
		$itemsBody.find('.sr').each(function(i){ $(this).text(i+1); });
	});

	$termsWrap.on('click', '.remove-term', function(){
		if ($termsWrap.find('.term-row').length === 1){
			$(this).closest('.term-row').find('input').val('');
			return;
		}
		$(this).closest('.term-row').remove();
	});

	$('#addTerm').on('click', function(){
		const $row = $termsWrap.find('.term-row').first().clone();
		$row.find('input').val('');
		$termsWrap.append($row);
	});

	$includeGst.on('change', function(){
		if (this.checked) { $gstField.removeAttr('hidden'); } else { $gstField.attr('hidden','hidden'); }
		recomputeTotals();
	});
	$('input[name="gst_percent"], input[name="discount_amount"], input[name="additional_amount"], input[name="rounding"]').on('input change', recomputeTotals);
	$toggleDiscounts.on('change', function(){
		if (this.checked) { $discountsWrap.show(); } else { $discountsWrap.hide(); }
	});
	$showWords.on('change', recomputeTotals);

	$addTermsBtn.on('click', function(){ $('#termsWrap').toggle(); });
	$addNotesBtn.on('click', function(){ $('#notesWrap').toggle(); });
	$addValidityBtn.on('click', function(){ $('#validityWrap').toggle(); });
	$addFooterBtn.on('click', function(){ $('#footerWrap').toggle(); });

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

