(function() {
	function qs(root, sel) { return (root || document).querySelector(sel); }
	function qsa(root, sel) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

	function hideElement(el) {
		if (!el) return;
		el.style.display = 'none';
		el.setAttribute('aria-hidden', 'true');
		el.disabled = true;
	}

	function escapeHtml(str) {
		if (str == null) return '';
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function getFormIdFromForm(form) {
		if (!form || !form.id) return '';
		var id = form.id;
		if (id.indexOf('wpforms-form-') === 0) {
			return id.replace('wpforms-form-', '');
		}
		return '';
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
		var safeValuation = escapeHtml(valuationText || '');
		container.innerHTML = "<p>Estimated valuation for your watch is " + safeValuation + ". To get in touch with our valuation expert <a style='color:#0073aa; text-decoration:underline;'>click here</a>.</p>";
		var a = qs(container, 'a');
		if (a) {
			a.addEventListener('click', function(e) {
				e.preventDefault();
				if (submitBtn) submitBtn.click();
			});
		}
	}

	function estimate(form, estimateBtn) {
		var formId = (window.WPWV && WPWV.formId) ? WPWV.formId : getFormIdFromForm(form);
		var brand     = getFieldValue('#wpforms-' + formId + '-field_1');
		var model     = getFieldValue('#wpforms-' + formId + '-field_2');
		var reference = getFieldValue('#wpforms-' + formId + '-field_12');
		var year      = getFieldValue('#wpforms-' + formId + '-field_13');
		var box       = getCheckboxValues('#wpforms-' + formId + '-field_14');
		var papers    = getCheckboxValues('#wpforms-' + formId + '-field_15');
		var age       = getFieldValue('#wpforms-' + formId + '-field_16');
		var condition = getFieldValue('#wpforms-' + formId + '-field_4');
		var source    = getFieldValue('#wpforms-' + formId + '-field_18');

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

		var submitBtn = qs(form, '#wpforms-submit-' + formId);

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
			// Store valuation in a hidden input so it is included in email submissions
			var hiddenInput = qs(form, '#wpwv-valuation-input');
			if (!hiddenInput) {
				hiddenInput = document.createElement('input');
				hiddenInput.type = 'hidden';
				hiddenInput.id = 'wpwv-valuation-input';
				hiddenInput.name = 'estimated_valuation';
				form.appendChild(hiddenInput);
			}
			hiddenInput.value = json.data && json.data.valuation ? json.data.valuation : '';
			// Also populate the existing WPForms hidden field so it shows in {all_fields}
			var wpformsHidden = qs(form, '#wpforms-' + formId + '-field_20');
			if (wpformsHidden) {
				wpformsHidden.value = hiddenInput.value;
			}
			// Hide the estimate button once we have a valuation
			hideElement(estimateBtn);
		})
		.catch(function() {
			if (container) container.textContent = 'Unable to calculate estimate right now.';
		});
	}

	function init() {
		var formId = (window.WPWV && WPWV.formId) ? WPWV.formId : '';
		var form = null;
		if (formId) {
			form = qs(document, '#wpforms-form-' + formId);
		}
		if (!form) {
			form = qs(document, 'form.wpforms-form');
			formId = getFormIdFromForm(form);
			if (formId) {
				window.WPWV = window.WPWV || {};
				WPWV.formId = formId;
			}
		}
		if (!form || !formId) return;

		// Hide any existing Start/Submit buttons
		var originalSubmit = qs(form, '#wpforms-submit-' + WPWV.formId);
		var customStartBtn = qs(form, '#wpwv-start-btn');
		var startBtnClasses = 'wpwv-estimate-btn elementor-button elementor-size-sm wpforms-page-button';
		if (customStartBtn && customStartBtn.className) {
			startBtnClasses = customStartBtn.className + ' wpwv-estimate-btn';
		}
		if (originalSubmit) {
			originalSubmit.style.display = 'none';
			originalSubmit.setAttribute('aria-hidden', 'true');
		}
		hideElement(customStartBtn);

		// Ensure a valuation container exists just above the submit container
		var submitContainer = qs(form, '.wpforms-submit-container');
		if (!submitContainer) return;
		var valuationContainer = qs(form, '#wpwv-valuation-container');
		if (!valuationContainer) {
			valuationContainer = document.createElement('div');
			valuationContainer.id = 'wpwv-valuation-container';
			if (submitContainer.parentNode) {
				submitContainer.parentNode.insertBefore(valuationContainer, submitContainer);
			} else {
				form.appendChild(valuationContainer);
			}
		}

		// Insert Estimate Valuation button (match Start Valuation styles)
		var estimateBtn = createButton('Estimate Valuation', startBtnClasses);
		submitContainer.appendChild(estimateBtn);

		estimateBtn.addEventListener('click', function() {
			estimate(form, estimateBtn);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
