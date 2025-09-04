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
		<h1 class="page-title">Generate Perfect Quotes with Munim Right Away!</h1>
		<form id="quoteForm" enctype="multipart/form-data">
			<div class="grid grid-2 gap-16">
				<section class="card">
					<h2 class="card-title">Quotation Details</h2>
					<div class="grid grid-2">
						<label class="field">
							<span>Quotation No</span>
							<input type="text" name="quotation_no" placeholder="A0001" required />
						</label>
						<label class="field">
							<span>Quotation Date</span>
							<input type="date" name="quotation_date" value="<?php echo date('Y-m-d'); ?>" required />
						</label>
					</div>
					<div class="notice">
						<button type="button" class="btn btn-outline">Add More Fields</button>
					</div>
				</section>

				<section class="card upload-card">
					<div class="upload">
						<div class="upload-icon">📎</div>
						<div class="upload-title">Add Company Logo Here</div>
						<div class="upload-sub">Resolution up to 1080x1080px, PNG or JPEG file.</div>
						<label class="upload-drop">
							<input type="file" name="company_logo" accept="image/png,image/jpeg" />
							<span>Drop files to upload</span>
						</label>
					</div>
				</section>
			</div>

			<!-- GST include notice to mirror the reference design -->
			<section class="card info notice-row">
				<label class="checkbox">
					<input type="checkbox" id="include_gst" name="include_gst" />
					Does Your Estimate include GST? If yes, then tick the checkbox.
				</label>
				<div id="gstField" class="gst-field" hidden>
					<label class="field compact">
						<span>GST Percent</span>
						<input type="number" step="0.01" min="0" name="gst_percent" value="0" />
					</label>
				</div>
			</section>

			<div class="grid grid-2 gap-16">
				<section class="card">
					<h2 class="card-title">Billed By <span class="muted">(Your Details)</span></h2>
					<div class="grid grid-1">
						<label class="field"><span>Your Business Name <b>*</b></span><input type="text" name="by_business" placeholder="Enter Your Business Name" required /></label>
					</div>
					<div class="grid grid-3">
						<label class="field"><span>Contact Number</span><input type="text" name="by_contact" placeholder="+91 98765 43210" /></label>
						<label class="field"><span>Email ID</span><input type="email" name="by_email" placeholder="you@company.com" /></label>
						<label class="field"><span>Your GSTIN</span><input type="text" name="by_gstin" placeholder="Enter Your GSTIN Number" /></label>
					</div>
					<div class="grid grid-1"><label class="field"><span>Address</span><input type="text" name="by_address" placeholder="Enter Your Address" /></label></div>
					<div class="grid grid-3">
						<label class="field"><span>City</span><input type="text" name="by_city" placeholder="Enter City" /></label>
						<label class="field"><span>Postal Code/Zip Code</span><input type="text" name="by_postal" placeholder="Enter Postal Code/Zip Code" /></label>
						<label class="field"><span>State</span><input type="text" name="by_state" placeholder="Select State" /></label>
					</div>
					<div><button type="button" class="btn btn-outline">Add Custom Fields</button></div>
				</section>

				<section class="card">
					<h2 class="card-title">Billed To <span class="muted">(Client Details)</span></h2>
					<div class="grid grid-1">
						<label class="field"><span>Client's Business Name <b>*</b></span><input type="text" name="to_business" placeholder="Enter Client's Business Name" required /></label>
					</div>
					<div class="grid grid-3">
						<label class="field"><span>Contact Number</span><input type="text" name="to_contact" placeholder="+91 98765 43210" /></label>
						<label class="field"><span>Email ID</span><input type="email" name="to_email" placeholder="client@company.com" /></label>
						<label class="field"><span>Client's GSTIN</span><input type="text" name="to_gstin" placeholder="Enter Client's GSTIN Number" /></label>
					</div>
					<div class="grid grid-1"><label class="field"><span>Address</span><input type="text" name="to_address" placeholder="Enter Client's Address" /></label></div>
					<div class="grid grid-3">
						<label class="field"><span>City</span><input type="text" name="to_city" placeholder="Enter City" /></label>
						<label class="field"><span>Postal Code/Zip Code</span><input type="text" name="to_postal" placeholder="Enter Postal Code/Zip Code" /></label>
						<label class="field"><span>State</span><input type="text" name="to_state" placeholder="Select State" /></label>
					</div>
					<div><button type="button" class="btn btn-outline">Add Custom Fields</button></div>
				</section>
			</div>

			<section class="card">
				<h2 class="card-title">Invoice Details</h2>
				<div class="table-wrap">
					<table class="items" id="itemsTable">
						<thead>
							<tr>
								<th class="w-60">Sr. No</th>
								<th>Product Name</th>
								<th class="w-120">Quantity</th>
								<th class="w-140">Rate</th>
								<th class="w-160">Total Amount</th>
								<th class="w-80">Actions</th>
							</tr>
						</thead>
						<tbody id="itemRows">
							<tr class="item-row">
								<td class="sr">1</td>
								<td>
									<input type="text" name="items[name][]" placeholder="Product Name" required />
								</td>
								<td><input type="number" min="0" step="1" name="items[qty][]" value="1" class="qty" required /></td>
								<td><input type="number" min="0" step="0.01" name="items[price][]" value="0" class="price" required /></td>
								<td class="row-total">0.00</td>
								<td class="actions">
									<div class="row-actions">
										<button type="button" class="btn btn-icon remove-row" title="Remove">🗑</button>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="table-actions">
					<div class="left">
						<button type="button" id="addRow" class="btn">Add New Line</button>
					</div>
					<div class="right"></div>
				</div>
			</section>

			<div class="grid grid-2 gap-16">
				<section class="card">
					<div class="pill-actions">
						<button type="button" class="btn btn-outline" id="addTerms">Add Terms & Conditions</button>
						<button type="button" class="btn btn-outline" id="addNotes">Add Notes</button>
						<button type="button" class="btn btn-outline" id="addValidity">Add Validity</button>
						<button type="button" class="btn btn-outline" id="addFooter">Add your footer</button>
					</div>
					<div id="termsWrap" class="terms-wrap" hidden>
						<div class="term-row">
							<input type="text" name="terms[]" placeholder="Enter a term" />
							<button type="button" class="btn btn-icon remove-term">×</button>
						</div>
					</div>
					<div id="notesWrap" class="notes-wrap" hidden>
						<label class="field"><span>Notes</span><textarea name="notes" rows="3" placeholder="Write notes..."></textarea></label>
					</div>
					<div id="validityWrap" class="notes-wrap" hidden>
						<label class="field"><span>Validity</span><input type="text" name="validity" placeholder="e.g., Valid for 7 days" /></label>
					</div>
					<div id="footerWrap" class="notes-wrap" hidden>
						<label class="field"><span>Footer</span><input type="text" name="footer_text" placeholder="Thank you for your business" /></label>
					</div>
				</section>

				<section class="card amount-card">
					<h3 class="card-title">Amount</h3>
					<div class="addon">
						<label class="checkbox"><input type="checkbox" id="toggleDiscounts" checked /> Add Discounts/Additional Charges</label>
						<div class="addon-grid" id="discountsWrap">
							<label class="field"><span>Discount</span><input type="number" step="0.01" min="0" name="discount_amount" value="0" /></label>
							<label class="field"><span>Additional Charges</span><input type="number" step="0.01" min="0" name="additional_amount" value="0" /></label>
						</div>
					</div>
					<label class="checkbox"><input type="checkbox" id="sumQty" name="sum_qty" /> Summarise Total Quantity</label>
					<div class="rounding">
						<label class="radio"><input type="radio" name="rounding" value="none" checked /> No Rounding</label>
						<label class="radio"><input type="radio" name="rounding" value="up" /> Round Up</label>
						<label class="radio"><input type="radio" name="rounding" value="down" /> Round Down</label>
					</div>
					<div class="totals light">
						<div class="totals-line"><span class="label">Subtotal</span><span class="value" id="subtotal">0.00</span></div>
						<div class="totals-line gst" id="gstLine" hidden><span class="label">GST</span><span class="value" id="taxtotal">0.00</span></div>
						<div class="totals-line" id="discountLine" hidden><span class="label">Discount</span><span class="value" id="discountval">0.00</span></div>
						<div class="totals-line" id="additionalLine" hidden><span class="label">Additional Charges</span><span class="value" id="additionalval">0.00</span></div>
						<div class="totals-line" id="roundLine" hidden><span class="label">Rounding Adj.</span><span class="value" id="roundval">0.00</span></div>
						<div class="totals-line grand"><span class="label">Total (INR)</span><span class="value" id="grandtotal">0.00</span></div>
					</div>
					<div class="controls inline">
						<button type="button" class="btn btn-outline">Add More Fields</button>
					</div>
					<div class="controls">
						<label class="checkbox"><input type="checkbox" id="showWords" /> Show Total In Words</label>
						<div id="wordsOut" class="words" hidden></div>
					</div>
					<div class="sign-upload">
						<label class="field file"><span>Digital Signature</span><input type="file" name="signature" accept="image/png,image/jpeg" /></label>
					</div>
				</section>
			</div>

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

