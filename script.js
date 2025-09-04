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
    const grand = subtotal + gstAmount;
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

      const subtotalText = document.getElementById('subtotal_amount')?.textContent || '₹0.00';
      const totalText = document.getElementById('grand_total')?.textContent || '₹0.00';
      const gstState = getGSTState();

      const summary = [
        `Items: ${items.length}`,
        `Subtotal: ${subtotalText}`,
        gstState.isIncluded ? `GST (${gstState.percent}%): ${document.getElementById('gst_value')?.textContent || '₹0.00'}` : null,
        `Total: ${totalText}`
      ].filter(Boolean).join('\n');
      alert(summary);
    });
  }

  function initExistingRows() {
    getRows().forEach((row) => attachRowInteractions(row));
    recomputeTotals();
  }

  document.addEventListener('DOMContentLoaded', () => {
    initExistingRows();
    initGST();
    initAddRow();
    initSubmit();
  });
})();

