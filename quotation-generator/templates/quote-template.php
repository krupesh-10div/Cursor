<?php
// This template expects variables in scope:
// $data: associative array of sanitized inputs
// $logoPath, $signaturePath: absolute local paths or null

function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$currencySymbol = '₹';
switch (($data['currency'] ?? 'INR')){
	case 'USD': $currencySymbol = '$'; break;
	case 'EUR': $currencySymbol = '€'; break;
	case 'GBP': $currencySymbol = '£'; break;
}

function money($n){ return number_format((float)$n, 2, '.', ','); }
function qtyfmt($n){
	$n = (float)$n;
	$asTwo = number_format($n, 2, '.', ',');
	return rtrim(rtrim($asTwo, '0'), '.');
}

// Compute totals (server-side safety)
$subtotal = 0.0; $taxtotal = 0.0; $grand = 0.0;
foreach (($data['items']['name'] ?? []) as $i => $name){
	$qty = (float)($data['items']['qty'][$i] ?? 0);
	$price = (float)($data['items']['price'][$i] ?? 0);
	$base = $qty * $price;
	$subtotal += $base; $grand += $base;
}

// Apply global GST and adjustments
$gstPercent = isset($data['gst_percent']) ? (float)$data['gst_percent'] : 0.0;
$includeGst = !empty($data['include_gst']);
if ($includeGst && $gstPercent > 0) {
	$taxtotal = $subtotal * ($gstPercent / 100.0);
}
$discount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0.0;
$additional = isset($data['additional_amount']) ? (float)$data['additional_amount'] : 0.0;
$other = $additional - $discount; // to mirror "OTHER" row in the reference
$grand = $subtotal + $taxtotal + $other;
$rounding = $data['rounding'] ?? 'none';
$roundAdj = 0.0;
if ($rounding === 'up') { $roundAdj = ceil($grand) - $grand; }
elseif ($rounding === 'down') { $roundAdj = floor($grand) - $grand; }
$grand = $grand + $roundAdj;

// Header variables
$companyName = trim((string)($data['by_business'] ?: ($data['company_name'] ?? 'Your Company Name')));
$companySlogan = trim((string)($data['company_slogan'] ?? 'Your Company Slogan'));
$byAddress = trim((string)($data['by_address'] ?? 'Street Address'));
$byCity = trim((string)($data['by_city'] ?? 'City'));
$byState = trim((string)($data['by_state'] ?? 'State'));
$byPostal = trim((string)($data['by_postal'] ?? 'ZIP Code'));
$byPhone = trim((string)($data['by_contact'] ?? 'Enter Phone number here'));
$byFax = trim((string)($data['by_fax'] ?? 'Enter Fax number here'));

$customerId = trim((string)($data['customer_id'] ?? ''));
$preparedBy = trim((string)($data['prepared_by'] ?? ($data['by_business'] ?? '')));
$validUntil = trim((string)($data['valid_until'] ?? ''));
if ($validUntil === '' && !empty($data['quotation_date'])){
	$ts = strtotime($data['quotation_date']);
	if ($ts) { $validUntil = date('j/n/Y', strtotime('+10 days', $ts)); }
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<style>
		body { font-family: DejaVu Sans, sans-serif; color:#0b1220; font-size:12px; }
		.header { display:flex; justify-content: space-between; align-items:flex-start; }
		.h-left { max-width: 62%; }
		.brand-name { font-size:24px; font-weight:700; }
		.brand-slogan { font-style: italic; color:#374151; margin-top:2px; }
		.addr { line-height:1.6; margin-top:12px; }
		.h-right { text-align:right; }
		.logo { font-size:42px; color:#9ca3af; letter-spacing:2px; }
		.kv { margin-top: 10px; font-size:12px; }
		.kv div { margin: 2px 0; }
		.k { font-weight:700; }
		.section-title { margin: 16px 0 6px; font-weight:700; }
		.table { width:100%; border-collapse: collapse; margin-top: 10px; }
		.table th, .table td { border: 1px solid #e5e7eb; padding: 8px; }
		.table th { background:#f3f4f6; text-align:left; font-weight:600; }
		.center { text-align:center; }
		.num { text-align:right; }
		.totals { width: 280px; margin-left:auto; border: 1px solid #e5e7eb; margin-top: 14px; }
		.totals td { padding: 8px; border-bottom:1px solid #e5e7eb; }
		.totals tr:last-child td { border-bottom:0; font-weight:700; }
		.footer-msg { text-align:center; margin-top: 48px; font-weight:700; }
	</style>
</head>
<body>
	<div class="header">
		<div class="h-left">
			<div class="brand-name"><?= esc($companyName) ?></div>
			<div class="brand-slogan"><?= esc($companySlogan) ?></div>
			<div class="addr">
				<div><?= esc($byAddress) ?></div>
				<div><?= esc($byCity) ?>, <?= esc($byState) ?> <?= esc($byPostal) ?></div>
				<div>Phone: <?= esc($byPhone) ?>
					&nbsp;&nbsp;Fax: <?= esc($byFax) ?></div>
			</div>
		</div>
		<div class="h-right">
			<?php if ($logoPath && is_file($logoPath)): ?>
				<img src="<?= esc($logoPath) ?>" style="height:64px;" />
			<?php else: ?>
				<div class="logo">LOGO</div>
			<?php endif; ?>
			<div class="kv">
				<div><span class="k">DATE</span>&nbsp; <?= esc($data['quotation_date'] ?? '') ?></div>
				<div><span class="k">Quotation #</span>&nbsp; <?= esc($data['quotation_no'] ?? '') ?></div>
				<?php if ($customerId !== ''): ?><div><span class="k">Customer ID</span>&nbsp; <?= esc($customerId) ?></div><?php endif; ?>
			</div>
		</div>
	</div>

	<div style="display:flex; justify-content:space-between; margin-top:14px;">
		<div></div>
		<div style="text-align:right;">
			<?php if ($validUntil !== ''): ?><div>Quotation valid until:&nbsp; <?= esc($validUntil) ?></div><?php endif; ?>
			<?php if ($preparedBy !== ''): ?><div>Prepared by:&nbsp; <?= esc($preparedBy) ?></div><?php endif; ?>
		</div>
	</div>

	<div class="section-title">Quotation For:</div>
	<div class="addr">
		<div><?= esc($data['to_business'] ?? ($data['customer_name'] ?? 'Name')) ?></div>
		<?php if (!empty($data['to_address'])): ?><div><?= esc($data['to_address']) ?></div><?php endif; ?>
		<div>
			<?= esc($data['to_city'] ?? 'City') ?>, <?= esc($data['to_state'] ?? 'ST') ?>
			&nbsp; <?= esc($data['to_postal'] ?? 'ZIP Code') ?>
		</div>
		<?php if (!empty($data['to_contact'])): ?><div>Phone: <?= esc($data['to_contact']) ?></div><?php endif; ?>
	</div>

	<table class="table" style="margin-top:18px;">
		<thead>
			<tr>
				<th style="width:100px;" class="center">QUANTITY</th>
				<th>DESCRIPTION</th>
				<th style="width:120px;" class="center">UNIT PRICE</th>
				<th style="width:90px;" class="center">TAXABLE?</th>
				<th style="width:140px;" class="center">AMOUNT</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach (($data['items']['name'] ?? []) as $i => $name):
				$qty = (float)($data['items']['qty'][$i] ?? 0);
				$price = (float)($data['items']['price'][$i] ?? 0);
				$base = $qty * $price;
				$taxableFlag = $includeGst ? 'T' : '';
			?>
			<tr>
				<td class="center"><?= qtyfmt($qty) ?></td>
				<td><?= esc($name) ?></td>
				<td class="center"><?= $currencySymbol . ' ' . money($price) ?></td>
				<td class="center"><?= $taxableFlag ?></td>
				<td class="num"><?= $currencySymbol . ' ' . money($base) ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<table class="totals">
		<tr>
			<td>SUBTOTAL</td>
			<td class="num"><?= $currencySymbol . ' ' . money($subtotal) ?></td>
		</tr>
		<tr>
			<td>TAX RATE</td>
			<td class="center"><?= money($gstPercent) ?>%</td>
		</tr>
		<tr>
			<td>SALES TAX</td>
			<td class="num"><?= $currencySymbol . ' ' . money($taxtotal) ?></td>
		</tr>
		<tr>
			<td>OTHER</td>
			<td class="num"><?= ($other == 0.0 ? '-' : $currencySymbol . ' ' . money($other)) ?></td>
		</tr>
		<tr>
			<td>TOTAL</td>
			<td class="num"><?= $currencySymbol . ' ' . money($grand) ?></td>
		</tr>
	</table>

	<?php if (!empty($data['terms'])): ?>
		<div style="margin-top:18px;">
			<strong>Terms & Conditions</strong>
			<ol style="margin:6px 0 0 16px;">
				<?php foreach ($data['terms'] as $t): if (trim((string)$t) === '') continue; ?>
					<li><?= esc($t) ?></li>
				<?php endforeach; ?>
			</ol>
		</div>
	<?php endif; ?>

	<?php if ($signaturePath && is_file($signaturePath)): ?>
		<div style="text-align:right; margin-top:24px;">
			<img src="<?= esc($signaturePath) ?>" style="height:50px;" />
			<div>Authorized Signature</div>
		</div>
	<?php endif; ?>

	<div class="footer-msg">THANK YOU FOR YOUR BUSINESS!</div>
</body>
</html>

