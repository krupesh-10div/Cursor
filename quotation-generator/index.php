<?php
// Simple standalone entry for the Quotation Generator tool.
// Drop this folder under your web root or inside a WordPress theme directory
// and access this file from a browser.
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Quotation Generator</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/style.css" />
</head>
<body>
	<div class="container">
		<h1 class="page-title">Generate Perfect Quotes</h1>
		<form id="quoteForm" enctype="multipart/form-data">
			<section class="card">
				<h2 class="card-title">Quotation Details</h2>
				<div class="grid grid-3">
					<label class="field">
						<span>Quotation No</span>
						<input type="text" name="quotation_no" placeholder="Q0001" required />
					</label>
					<label class="field">
						<span>Quotation Date</span>
						<input type="date" name="quotation_date" value="<?php echo date('Y-m-d'); ?>" required />
					</label>
					<label class="field">
						<span>Currency</span>
						<select name="currency" aria-label="Currency">
							<option value="INR" selected>INR (₹)</option>
							<option value="USD">USD ($)</option>
							<option value="EUR">EUR (€)</option>
							<option value="GBP">GBP (£)</option>
						</select>
					</label>
				</div>

				<div class="grid grid-2">
					<label class="field">
						<span>Your Company Name</span>
						<input type="text" name="company_name" placeholder="Acme Pvt Ltd" required />
					</label>
					<label class="field">
						<span>Company Email</span>
						<input type="email" name="company_email" placeholder="hello@company.com" />
					</label>
				</div>
				<div class="grid grid-2">
					<label class="field">
						<span>Customer Name</span>
						<input type="text" name="customer_name" placeholder="Customer / Client" required />
					</label>
					<label class="field">
						<span>Customer Email</span>
						<input type="email" name="customer_email" placeholder="client@email.com" />
					</label>
				</div>

				<div class="grid grid-2">
					<label class="field file">
						<span>Company Logo (PNG/JPG, optional)</span>
						<input type="file" name="company_logo" accept="image/png,image/jpeg" />
					</label>
					<label class="field file">
						<span>Digital Signature (PNG/JPG, optional)</span>
						<input type="file" name="signature" accept="image/png,image/jpeg" />
					</label>
				</div>
			</section>

			<section class="card">
				<h2 class="card-title">Items</h2>
				<div class="table-wrap">
					<table class="items" id="itemsTable">
						<thead>
							<tr>
								<th>Product / Service</th>
								<th class="w-100">Qty</th>
								<th class="w-160">Unit Price</th>
								<th class="w-120">Tax %</th>
								<th class="w-160">Total</th>
								<th class="w-80"></th>
							</tr>
						</thead>
						<tbody id="itemRows">
							<tr class="item-row">
								<td>
									<input type="text" name="items[name][]" placeholder="Product name" required />
								</td>
								<td>
									<input type="number" min="0" step="1" name="items[qty][]" value="1" class="qty" required />
								</td>
								<td>
									<input type="number" min="0" step="0.01" name="items[price][]" value="0" class="price" required />
								</td>
								<td>
									<input type="number" min="0" step="0.01" name="items[tax][]" value="0" class="tax" />
								</td>
								<td class="row-total">0.00</td>
								<td class="actions"><button type="button" class="btn btn-icon remove-row" title="Remove">×</button></td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="table-actions">
					<button type="button" id="addRow" class="btn">Add Row</button>
				</div>

				<div class="totals">
					<div class="totals-line"><span class="label">Subtotal</span><span class="value" id="subtotal">0.00</span></div>
					<div class="totals-line"><span class="label">Tax</span><span class="value" id="taxtotal">0.00</span></div>
					<div class="totals-line grand"><span class="label">Grand Total</span><span class="value" id="grandtotal">0.00</span></div>
				</div>
			</section>

			<section class="card">
				<h2 class="card-title">Terms & Conditions</h2>
				<div id="termsWrap" class="terms-wrap">
					<div class="term-row">
						<input type="text" name="terms[]" placeholder="Enter a term" />
						<button type="button" class="btn btn-icon remove-term">×</button>
					</div>
				</div>
				<button type="button" id="addTerm" class="btn">Add Term</button>
			</section>

			<div class="actions footer-actions">
				<button type="submit" id="generateBtn" class="btn btn-primary">Generate Quotation</button>
				<div id="loading" class="loading" hidden>
					<span class="spinner"></span>
					<span>Generating PDF…</span>
				</div>
			</div>
		</form>
	</div>

	<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
	<script src="assets/script.js"></script>
</body>
</html>

