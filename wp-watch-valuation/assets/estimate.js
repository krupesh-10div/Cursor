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

	function getFieldInputByNumber(formId, fieldNumber) {
		var input = qs(document, '#wpforms-' + formId + '-field_' + fieldNumber);
		if (input) return input;
		input = qs(document, '[name="wpforms[fields][' + fieldNumber + ']"]');
		if (input) return input;
		input = qs(document, '[name="wpforms[fields][' + fieldNumber + '][]"]');
		return input || null;
	}

	function getFieldValueByNumber(formId, fieldNumber) {
		var input = getFieldInputByNumber(formId, fieldNumber);
		if (!input) return '';
		if (input.tagName === 'SELECT' || input.tagName === 'INPUT' || input.tagName === 'TEXTAREA') {
			return input.value || '';
		}
		return '';
	}

	function getCheckboxValuesByNumber(formId, fieldNumber) {
		var container = qs(document, '#wpforms-' + formId + '-field_' + fieldNumber + '-container');
		var nodes = [];
		if (container) {
			nodes = qsa(container, 'input[type="checkbox"]');
		}
		if (!nodes.length) {
			nodes = qsa(document, 'input[type="checkbox"][name="wpforms[fields][' + fieldNumber + '][]"]');
		}
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

	function getContainerForInput(input) {
		if (!input) return null;
		var byId = qs(document, '#' + input.id + '-container');
		if (byId) return byId;
		var closest = input.closest ? input.closest('.wpforms-field') : null;
		return closest || input.parentNode;
	}

	function removeOurErrors(root) {
		qsa(root, 'em.wpforms-error[data-wpwv="1"]').forEach(function(em){
			if (em && em.parentNode) em.parentNode.removeChild(em);
		});
		qsa(root, '[data-wpwv-marked="1"]').forEach(function(el){
			el.classList.remove('wpforms-error');
			el.removeAttribute('data-wpwv-marked');
			el.setAttribute('aria-invalid', 'false');
			var errId = el.getAttribute('aria-errormessage');
			if (errId && errId.indexOf(el.id + '-error') === 0) {
				el.removeAttribute('aria-errormessage');
			}
			var describedBy = (el.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
			var ours = el.id + '-error';
			var next = describedBy.filter(function(id){ return id !== ours; }).join(' ');
			if (next) el.setAttribute('aria-describedby', next); else el.removeAttribute('aria-describedby');
		});
		qsa(root, '.wpforms-has-error[data-wpwv="1"]').forEach(function(c){
			c.classList.remove('wpforms-has-error');
			c.removeAttribute('data-wpwv');
		});
	}

	function appendError(containerOrAfterEl, input, message) {
		var errId = input.id ? (input.id + '-error') : ('wpwv-error-' + Math.random().toString(36).slice(2));
		var em = document.createElement('em');
		em.id = errId;
		em.className = 'wpforms-error';
		em.setAttribute('role', 'alert');
		em.setAttribute('aria-label', 'Error message');
		em.setAttribute('data-wpwv', '1');
		em.textContent = message || 'This field is required.';
		if (containerOrAfterEl && containerOrAfterEl.classList && containerOrAfterEl.classList.contains('wpforms-field')) {
			containerOrAfterEl.appendChild(em);
		} else if (containerOrAfterEl && containerOrAfterEl.parentNode) {
			containerOrAfterEl.parentNode.insertBefore(em, containerOrAfterEl.nextSibling);
		}
		if (input.id) {
			input.setAttribute('aria-errormessage', errId);
			var describedBy = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
			if (describedBy.indexOf(errId) === -1) {
				describedBy.push(errId);
				input.setAttribute('aria-describedby', describedBy.join(' '));
			}
		}
	}

	function markInputInvalid(input, message) {
		var container = getContainerForInput(input);
		if (container) {
			container.classList.add('wpforms-has-error');
			container.setAttribute('data-wpwv', '1');
		}
		input.classList.add('wpforms-error');
		input.setAttribute('data-wpwv-marked', '1');
		input.setAttribute('aria-invalid', 'true');
		appendError(container || input, input, message);
	}

	function validateGroupContainer(container) {
		var isCheckbox = container.classList.contains('wpforms-field-checkbox');
		var isRadio = container.classList.contains('wpforms-field-radio');
		if (!isCheckbox && !isRadio) return true;
		var inputs = qsa(container, 'input[type="' + (isCheckbox ? 'checkbox' : 'radio') + '"]');
		if (!inputs.length) return true;
		var anyChecked = inputs.some(function(i){ return i.checked; });
		if (anyChecked) return true;
		var target = inputs[0];
		markInputInvalid(target, 'This field is required.');
		return false;
	}

	function validateForm(form, formId) {
		removeOurErrors(form);
		var allValid = true;

		qsa(form, '.wpforms-field.wpforms-field-required').forEach(function(fieldContainer){
			var ok = validateGroupContainer(fieldContainer);
			if (!ok) allValid = false;
		});

		var requiredInputs = qsa(form, 'input[required], select[required], textarea[required]');
		requiredInputs.forEach(function(input){
			if (input.type === 'checkbox' || input.type === 'radio') return;
			var val = (input.value || '').trim();
			if (!val) {
				allValid = false;
				markInputInvalid(input, 'This field is required.');
			}
		});

		return allValid;
	}

	function scrollToFirstError(form) {
		var first = qs(form, '[data-wpwv-marked="1"], .wpforms-has-error[data-wpwv="1"]');
		if (!first) first = qs(form, 'em.wpforms-error[data-wpwv="1"]');
		if (!first) return;
		var target = first;
		if (first.classList && first.classList.contains('wpforms-has-error')) {
			var inside = qs(first, '[data-wpwv-marked="1"], input, select, textarea');
			if (inside) target = inside;
		}
		if (typeof target.scrollIntoView === 'function') {
			target.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
		if (typeof target.focus === 'function') {
			try { target.focus({ preventScroll: true }); } catch (e) { target.focus(); }
		}
	}

	function estimate(form, estimateBtn) {
		var formId = (window.WPWV && WPWV.formId) ? WPWV.formId : getFormIdFromForm(form);

		var isValid = validateForm(form, formId);
		if (!isValid) {
			scrollToFirstError(form);
			return;
		}

		var fields = (window.WPWV && WPWV.fields) ? WPWV.fields : {
			brand: 1, model: 2, reference: 12, year: 13,
			box: 14, papers: 15, age: 16, condition: 4, source: 18, valuationHidden: 20
		};

		var brand     = getFieldValueByNumber(formId, fields.brand);
		var model     = getFieldValueByNumber(formId, fields.model);
		var reference = getFieldValueByNumber(formId, fields.reference);
		var year      = getFieldValueByNumber(formId, fields.year);
		var box       = getCheckboxValuesByNumber(formId, fields.box);
		var papers    = getCheckboxValuesByNumber(formId, fields.papers);
		var age       = getFieldValueByNumber(formId, fields.age);
		var condition = getFieldValueByNumber(formId, fields.condition);
		var source    = getFieldValueByNumber(formId, fields.source);

		var container = qs(form, '#wpwv-valuation-container') || qs(document, '#wpwv-valuation-container');
		if (container) container.textContent = 'Calculating estimate…';

		var formData = new FormData();
		formData.append('action', 'wpwv_estimate_valuation');
		formData.append('nonce', WPWV.nonce);
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

		fetch(WPWV.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		})
		.then(res => res.json())
		.then(json => {
			if (!json.success) {
				container.textContent = json.data?.message || 'Unable to calculate estimate right now.';
				return;
			}
			renderEstimate(container, json.data.valuation, submitBtn);

			var hiddenInput = qs(form, '#wpforms-' + formId + '-field_' + fields.valuationHidden)
			                  || qs(form, '[name="wpforms[fields][' + fields.valuationHidden + ']"]');
			if (hiddenInput) hiddenInput.value = json.data.valuation;

			hideElement(estimateBtn);
		})
		.catch(err => {
			console.error('Estimation AJAX error:', err);
			if (container) container.textContent = 'Unable to calculate estimate right now.';
		});
	}

	function init() {
		var formId = WPWV.formId || '';
		var form = formId ? qs(document, '#wpforms-form-' + formId) : qs(document, 'form.wpforms-form');
		if (!form) return;

		formId = getFormIdFromForm(form);
		WPWV.formId = formId;

		var submitContainer = qs(form, '.wpforms-submit-container');
		if (!submitContainer) return;

		var estimateBtn = createButton('Estimate Valuation', 'wpwv-estimate-btn elementor-button elementor-size-sm wpforms-page-button wpforms-submit');
		submitContainer.appendChild(estimateBtn);

		estimateBtn.addEventListener('click', function() { estimate(form, estimateBtn); });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
