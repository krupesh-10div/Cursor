/* Quotation Builder Interactions and Calculations */

(function () {
  function formatCurrencyINR(amountNumber) {
    const formatter = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 2 });
    return formatter.format(isFinite(amountNumber) ? amountNumber : 0);
  }

  function parseNumber(value, fallback = 0) {
    if (value === null || value === undefined) return fallback;
    const parsed = Number(String(value).toString().replace(/[^0-9.\-]/g, ''));
    return isNaN(parsed) ? fallback : parsed;
  }

  function getTableBody() {
    return document.querySelector('.table');
  }

  function getRows() {
    const table = getTableBody();
    if (!table) return [];
    return Array.from(table.querySelectorAll('.table__row'));
  }

  function updateSerialNumbers() {
    getRows().forEach((row, index) => {
      const serialCell = row.querySelector('.td.td--sm');
      if (serialCell) {
        serialCell.textContent = String(index + 1);
      }
    });
  }

  function computeRowTotal(rowElement) {
    const qtyInput = rowElement.querySelector('.input-qty');
    const rateInput = rowElement.querySelector('.input-rate');
    const totalInput = rowElement.querySelector('.input-total');
    const quantity = parseNumber(qtyInput && qtyInput.value, 0);
    const rate = parseNumber(rateInput && rateInput.value, 0);
    const total = quantity * rate;
    if (totalInput) {
      totalInput.value = formatCurrencyINR(total);
    }
    return total;
  }

  function computeSubtotal() {
    return getRows().reduce((sum, row) => sum + computeRowTotal(row), 0);
  }

  function getGSTState() {
    const included = document.getElementById('gst_included');
    const percentInput = document.getElementById('gst_percent');
    const isIncluded = !!(included && included.checked);
    const percent = isIncluded ? Math.max(0, parseNumber(percentInput && percentInput.value, 0)) : 0;
    return { isIncluded, percent };
  }

  function showGSTControls(show) {
    const percentWrap = document.getElementById('gst_percent_wrap');
    const gstRow = document.getElementById('gst_total_row');
    if (percentWrap) percentWrap.classList.toggle('hidden', !show);
    if (gstRow) gstRow.classList.toggle('hidden', !show);
  }

  function recomputeTotals() {
    const subtotal = computeSubtotal();
    const { isIncluded, percent } = getGSTState();

    const subtotalNode = document.getElementById('subtotal_amount');
    if (subtotalNode) subtotalNode.textContent = formatCurrencyINR(subtotal);

    let gstAmount = 0;
    if (isIncluded && percent > 0) {
      gstAmount = subtotal * (percent / 100);
    }

    const gstLabel = document.getElementById('gst_label');
    const gstValue = document.getElementById('gst_value');
    if (gstLabel) gstLabel.textContent = `GST (${percent}%)`;
    if (gstValue) gstValue.textContent = formatCurrencyINR(gstAmount);

    const grandTotalNode = document.getElementById('grand_total');
    let grand = subtotal + gstAmount;

    // Apply rounding mode if selected
    const roundChecked = document.querySelector('input[name="round"]:checked');
    const roundingMode = roundChecked && roundChecked.value ? String(roundChecked.value) : '';
    if (roundingMode === 'up') {
      grand = Math.ceil(grand);
    } else if (roundingMode === 'down') {
      grand = Math.floor(grand);
    }

    if (grandTotalNode) grandTotalNode.textContent = formatCurrencyINR(grand);
  }

  function attachRowInteractions(row) {
    const qty = row.querySelector('.input-qty');
    const rate = row.querySelector('.input-rate');
    const descToggle = row.querySelector('.input-desc-toggle');
    // Use row-level event delegation for action buttons to avoid selecting the wrong `.row` container

    function onValueChanged() {
      computeRowTotal(row);
      recomputeTotals();
    }

    if (qty) qty.addEventListener('input', onValueChanged);
    if (rate) rate.addEventListener('input', onValueChanged);

    if (descToggle) {
      descToggle.addEventListener('change', () => {
        let area = row.querySelector('textarea');
        if (descToggle.checked) {
          if (!area) {
            area = document.createElement('textarea');
            area.placeholder = 'Add description...';
            const nameCell = row.querySelector('.td');
            if (nameCell) nameCell.appendChild(area);
          }
        } else if (area) {
          area.remove();
        }
      });
    }

    row.addEventListener('click', (ev) => {
      const target = ev.target;
      if (!(target instanceof HTMLElement)) return;
      const actionBtn = target.closest('button[data-action]');
      if (!actionBtn || !row.contains(actionBtn)) return;
      const action = actionBtn.getAttribute('data-action');
      if (!action) return;
      if (action === 'delete') {
        row.remove();
        updateSerialNumbers();
        recomputeTotals();
      } else if (action === 'duplicate') {
        const newRow = duplicateRow(row);
        row.insertAdjacentElement('afterend', newRow);
        updateSerialNumbers();
        recomputeTotals();
      } else if (action === 'move') {
        const prev = row.previousElementSibling;
        if (prev && prev.classList.contains('table__row')) {
          row.parentElement.insertBefore(row, prev);
          updateSerialNumbers();
        }
      }
    });
  }

  function buildEmptyRow() {
    const templateRow = getRows()[0];
    const row = templateRow.cloneNode(true);

    const name = row.querySelector('.input-name');
    const qty = row.querySelector('.input-qty');
    const rate = row.querySelector('.input-rate');
    const total = row.querySelector('.input-total');
    const descToggle = row.querySelector('.input-desc-toggle');
    const textarea = row.querySelector('textarea');

    if (name) name.value = '';
    if (qty) qty.value = '0';
    if (rate) rate.value = '0';
    if (total) total.value = formatCurrencyINR(0);
    if (descToggle) descToggle.checked = false;
    if (textarea) textarea.remove();

    attachRowInteractions(row);
    return row;
  }

  function duplicateRow(sourceRow) {
    const clone = sourceRow.cloneNode(true);
    attachRowInteractions(clone);
    return clone;
  }

  function initGST() {
    const checkbox = document.getElementById('gst_included');
    const percentInput = document.getElementById('gst_percent');

    if (checkbox) {
      checkbox.addEventListener('change', () => {
        showGSTControls(checkbox.checked);
        recomputeTotals();
      });
    }

    if (percentInput) {
      percentInput.addEventListener('input', () => {
        const val = parseNumber(percentInput.value, 0);
        if (val < 0) percentInput.value = '0';
        recomputeTotals();
      });
    }
  }

  function initRounding() {
    const radios = Array.from(document.querySelectorAll('input[name="round"]'));
    if (radios.length === 0) return;
    radios.forEach((r) => r.addEventListener('change', () => {
      recomputeTotals();
    }));
  }

  function normalizeButtonText(el) {
    return (el && el.textContent || '').trim().toLowerCase();
  }

  function ensureSection(container, id, titleText, fieldBuilder) {
    let section = container.querySelector('#' + id);
    if (section) {
      section.remove();
      return;
    }
    section = document.createElement('div');
    section.className = 'field field--full';
    section.id = id;
    const label = document.createElement('label');
    label.className = 'label';
    label.textContent = titleText;
    section.appendChild(label);
    const fieldNode = fieldBuilder();
    section.appendChild(fieldNode);
    container.appendChild(section);
  }

  function buildTextarea(placeholder) {
    const area = document.createElement('textarea');
    area.placeholder = placeholder;
    return area;
  }

  function buildTextInput(placeholder) {
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'input';
    input.placeholder = placeholder;
    return input;
  }

  function handleChipActions(targetButton) {
    const btnText = normalizeButtonText(targetButton);
    const cardBody = targetButton.closest('.card__body');
    if (!cardBody) return;
    if (btnText.includes('terms')) {
      ensureSection(cardBody, 'terms_section', 'Terms & Conditions', () => buildTextarea('Add terms and conditions...'));
      return;
    }
    if (btnText.includes('notes')) {
      ensureSection(cardBody, 'notes_section', 'Notes', () => buildTextarea('Add notes...'));
      return;
    }
    if (btnText.includes('validity')) {
      ensureSection(cardBody, 'validity_section', 'Validity', () => buildTextInput('e.g., Valid for 30 days'));
      return;
    }
    if (btnText.includes('footer')) {
      ensureSection(cardBody, 'footer_section', 'Footer', () => buildTextarea('Add footer text...'));
      return;
    }
    if (btnText.includes('signature')) {
      ensureSection(cardBody, 'signature_section', 'Authorized Signature', () => buildTextInput('Signatory name or draw signature'));
    }
  }

  function addCustomFieldToForm(buttonEl) {
    const form = buttonEl.closest('.form');
    if (!form) return false;
    const wrapper = document.createElement('div');
    wrapper.className = 'field field--full';
    const label = document.createElement('label');
    label.className = 'label';
    label.textContent = 'Custom Field';
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'input';
    input.placeholder = 'Enter value';
    wrapper.appendChild(label);
    wrapper.appendChild(input);

    // Insert before the button's field row if possible
    const buttonField = buttonEl.closest('.field');
    if (buttonField && buttonField.parentElement === form) {
      form.insertBefore(wrapper, buttonField);
    } else {
      form.appendChild(wrapper);
    }
    return true;
  }

  function addCustomFieldToTotals(buttonEl) {
    const totals = buttonEl.closest('.totals');
    if (!totals) return false;
    const row = document.createElement('div');
    row.className = 'totals__row';
    const label = document.createElement('div');
    label.className = 'totals__label';
    label.textContent = 'Custom';
    const value = document.createElement('div');
    value.className = 'totals__value';
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'input';
    input.placeholder = 'Enter value';
    value.appendChild(input);
    row.appendChild(label);
    row.appendChild(value);

    const emphRow = totals.querySelector('.totals__row--emph');
    if (emphRow && emphRow.parentElement === totals) {
      totals.insertBefore(row, emphRow);
    } else {
      totals.appendChild(row);
    }
    return true;
  }

  function addOtherFieldToRow(row) {
    const nameCell = row.querySelector('.td');
    if (!nameCell) return;
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'input input-other';
    input.placeholder = 'Other field';
    nameCell.appendChild(input);
  }

  function initGlobalClicks() {
    document.addEventListener('click', (ev) => {
      const target = ev.target;
      if (!(target instanceof HTMLElement)) return;

      const button = target.closest('button');
      if (!button) return;

      const text = normalizeButtonText(button);

      // Chips: Terms, Notes, Validity, Footer, Signature
      if (button.classList.contains('chip')) {
        handleChipActions(button);
        return;
      }

      // Add Other Field inside line items
      if (text.includes('add other field')) {
        const row = button.closest('.table__row');
        if (row) addOtherFieldToRow(row);
        return;
      }

      // Add More/Custom Fields within forms
      if (text.includes('add more fields') || text.includes('add custom fields')) {
        if (addCustomFieldToForm(button)) return;
        if (addCustomFieldToTotals(button)) return;
      }
    });
  }

  function initAddRow() {
    const addBtn = document.getElementById('add_row');
    if (!addBtn) return;
    addBtn.addEventListener('click', () => {
      const newRow = buildEmptyRow();
      const table = getTableBody();
      const footer = table && table.querySelector('.table__footer');
      if (footer && footer.parentElement) {
        footer.parentElement.insertBefore(newRow, footer);
      }
      updateSerialNumbers();
      recomputeTotals();
    });
  }

  function initSubmit() {
    const submitBtn = document.getElementById('generate_quote');
    if (!submitBtn) return;
    submitBtn.addEventListener('click', () => {
      const rows = getRows();
      const items = rows.map((row) => {
        return {
          name: (row.querySelector('.input-name') || { value: '' }).value || '',
          quantity: parseNumber((row.querySelector('.input-qty') || { value: 0 }).value, 0),
          rate: parseNumber((row.querySelector('.input-rate') || { value: 0 }).value, 0),
          amount: parseNumber((row.querySelector('.input-total') || { value: '0' }).value, 0)
        };
      });
      const invalid = items.find(i => !i.name.trim());
      if (invalid) {
        alert('Please fill Product Name for all line items before generating the quote.');
        return;
      }

      // Build FormData for backend PDF generation
      const fd = new FormData();
      const qno = document.getElementById('qno');
      const qdate = document.getElementById('qdate');
      const byBusiness = document.getElementById('by_business');
      const byEmail = document.getElementById('by_email');
      const toBusiness = document.getElementById('to_business');
      const toEmail = document.getElementById('to_email');

      fd.append('quotation_no', (qno && qno.value) || '');
      fd.append('quotation_date', (qdate && qdate.value) || '');
      fd.append('currency', 'INR');
      fd.append('company_name', (byBusiness && byBusiness.value) || '');
      fd.append('company_email', (byEmail && byEmail.value) || '');
      fd.append('customer_name', (toBusiness && toBusiness.value) || '');
      fd.append('customer_email', (toEmail && toEmail.value) || '');

      // Global GST -> apply same tax percent to each item for server-side totals
      const { isIncluded, percent } = getGSTState();
      const taxPercent = isIncluded ? percent : 0;

      items.forEach((it) => {
        fd.append('items[name][]', it.name);
        fd.append('items[qty][]', String(it.quantity));
        fd.append('items[price][]', String(it.rate));
        fd.append('items[tax][]', String(taxPercent));
      });

      // Optional: collect Terms if chip section added
      const termsSection = document.getElementById('terms_section');
      const termsText = termsSection ? (termsSection.querySelector('textarea')?.value || '') : '';
      if (termsText.trim()) {
        termsText.split('\n').map(s => s.trim()).filter(Boolean).forEach((line) => {
          fd.append('terms[]', line);
        });
      }

      // Disable button while generating
      submitBtn.disabled = true;
      submitBtn.textContent = 'Generating...';

      fetch('generate.php', { method: 'POST', body: fd })
        .then(async (res) => {
          if (!res.ok) {
            const text = await res.text().catch(() => '');
            throw new Error(text || 'Failed to generate PDF');
          }
          const blob = await res.blob();
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          const filename = res.headers.get('X-Filename') || 'quotation.pdf';
          a.href = url;
          a.download = filename;
          document.body.appendChild(a);
          a.click();
          a.remove();
          window.URL.revokeObjectURL(url);
        })
        .catch((err) => {
          alert(err.message || 'Something went wrong while generating the PDF.');
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Generate Quote';
        });
    });
  }

  function initExistingRows() {
    getRows().forEach((row) => attachRowInteractions(row));
    recomputeTotals();
  }

  document.addEventListener('DOMContentLoaded', () => {
    initExistingRows();
    initGST();
    initRounding();
    initGlobalClicks();
    initAddRow();
    initSubmit();
  });
})();

