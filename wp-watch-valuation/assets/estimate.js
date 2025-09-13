(function() {
	function qs(root, sel) { return (root || document).querySelector(sel); }
	function qsa(root, sel) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

	function hideElement(el) {
		if (!el) return;
		el.style.display = 'none';
		el.setAttribute('aria-hidden', 'true');
		el.disabled = true;
	}

	function createButton(text, classes) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.textContent = text;
		btn.className = classes || 'wpwv-estimate-btn wpforms-submit';
		return btn;
	}

	function getFieldValue(selector) {
		var el = qs(document, selector);
		if (!el) return '';
		if (el.tagName === 'SELECT' || el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
			return el.value || '';
		}
		return '';
	}

	function getCheckboxValues(selector) {
		var nodes = qsa(document, selector + ' input[type="checkbox"]');
		return nodes.filter(function(n){ return n.checked; }).map(function(n){ return n.value; }).join(', ');
	}

	function renderEstimate(container, valuationText, submitBtn) {
		if (!container) return;
		container.innerHTML = '';
		var p = document.createElement('p');
		var valuation = valuationText || '';
		var span = document.createElement('span');
		span.textContent = 'Estimated valuation for your watch is ' + valuation + '. ';
		var link = document.createElement('a');
		link.href = '#';
		link.textContent = 'click here';
		link.style.color = '#0073aa';
		link.style.textDecoration = 'underline';
		link.addEventListener('click', function(e) {
			e.preventDefault();
			if (submitBtn) {
				// Trigger original WPForms submission
				submitBtn.click();
			}
		});
		var tail = document.createTextNode('To get in touch with our valuation expert ');
		p.appendChild(span);
		p.appendChild(tail);
		p.appendChild(link);
		p.appendChild(document.createTextNode('.'));
		container.appendChild(p);
	}

	function estimate(form) {
		var brand     = getFieldValue('#wpforms-765-field_1');
		var model     = getFieldValue('#wpforms-765-field_2');
		var reference = getFieldValue('#wpforms-765-field_12');
		var year      = getFieldValue('#wpforms-765-field_13');
		var box       = getCheckboxValues('#wpforms-765-field_14');
		var papers    = getCheckboxValues('#wpforms-765-field_15');
		var age       = getFieldValue('#wpforms-765-field_16');
		var condition = getFieldValue('#wpforms-765-field_4');
		var source    = getFieldValue('#wpforms-765-field_18');

		var container = qs(form, '#wpwv-valuation-container') || qs(document, '#wpwv-valuation-container');
		if (container) {
			container.textContent = 'Calculating estimate…';
		}

		var formData = new FormData();
		formData.append('action', 'wpwv_estimate_valuation');
		formData.append('nonce', (window.WPWV && WPWV.nonce) ? WPWV.nonce : '');
		formData.append('brand', brand);
		formData.append('model', model);
		formData.append('reference', reference);
		formData.append('year', year);
		formData.append('box', box);
		formData.append('papers', papers);
		formData.append('age', age);
		formData.append('condition', condition);
		formData.append('source', source);

		var submitBtn = qs(form, '#wpforms-submit-765');

		fetch((window.WPWV && WPWV.ajax_url) ? WPWV.ajax_url : '/wp-admin/admin-ajax.php', {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		})
		.then(function(res) { return res.json(); })
		.then(function(json) {
			if (!json || json.success !== true) {
				if (container) container.textContent = 'Unable to calculate estimate right now.';
				return;
			}
			renderEstimate(container, json.data.valuation, submitBtn);
		})
		.catch(function() {
			if (container) container.textContent = 'Unable to calculate estimate right now.';
		});
	}

	function init() {
		if (!window.WPWV || !WPWV.formId) return;
		var form = qs(document, '#wpforms-form-' + WPWV.formId);
		if (!form) return;

		// Hide any existing Start/Submit buttons
		var originalSubmit = qs(form, '#wpforms-submit-' + WPWV.formId);
		var customStartBtn = qs(form, '#wpwv-start-btn');
		if (originalSubmit) {
			originalSubmit.style.display = 'none';
			originalSubmit.setAttribute('aria-hidden', 'true');
		}
		hideElement(customStartBtn);

		// Insert Estimate Valuation button
		var submitContainer = qs(form, '.wpforms-submit-container');
		if (!submitContainer) return;
		var estimateBtn = createButton('Estimate Valuation', 'wpwv-estimate-btn elementor-button elementor-size-sm');
		submitContainer.appendChild(estimateBtn);

		estimateBtn.addEventListener('click', function() {
			estimate(form);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

