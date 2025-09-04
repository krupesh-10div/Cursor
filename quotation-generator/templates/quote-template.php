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

// Compute totals (server-side safety)
$subtotal = 0.0; $taxtotal = 0.0; $grand = 0.0;
foreach (($data['items']['name'] ?? []) as $i => $name){
	$qty = (float)($data['items']['qty'][$i] ?? 0);
	$price = (float)($data['items']['price'][$i] ?? 0);
	$base = $qty * $price;
	$lineTotal = $base;
	$subtotal += $base; $grand += $lineTotal;
}

// Apply global GST and adjustments
$gstPercent = isset($data['gst_percent']) ? (float)$data['gst_percent'] : 0.0;
$includeGst = !empty($data['include_gst']);
if ($includeGst && $gstPercent > 0) {
	$taxtotal = $subtotal * ($gstPercent / 100.0);
}
$discount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0.0;
$additional = isset($data['additional_amount']) ? (float)$data['additional_amount'] : 0.0;
$grand = $subtotal + $taxtotal - $discount + $additional;
$rounding = $data['rounding'] ?? 'none';
$roundAdj = 0.0;
if ($rounding === 'up') { $roundAdj = ceil($grand) - $grand; }
elseif ($rounding === 'down') { $roundAdj = floor($grand) - $grand; }
$grand = $grand + $roundAdj;
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<style>
		body { font-family: DejaVu Sans, sans-serif; color:#0b1220; font-size: 12px; }
		.header { width:100%; }
		.h-top { display:flex; justify-content:space-between; align-items:flex-start; }
		.company-name { font-size: 24px; font-weight: 700; }
		.company-slogan { font-style: italic; margin-top: 4px; }
		.logo { text-align:right; font-size: 36px; color:#9ca3af; }
		.meta { margin-top: 8px; margin-left:auto; width: 260px; }
		.meta td { padding: 3px 6px; }
		.small { color:#374151; }
		.divider { height: 14px; }
		.block-title { font-weight: 700; margin: 12px 0 6px; }
		.address-block { line-height: 1.5; }
		.table { width:100%; border-collapse: collapse; margin-top: 18px; }
		.table th, .table td { border:1px solid #e5e7eb; padding:8px; }
		.table th { background:#f3f4f6; text-align:left; }
		.right { text-align:right; }
		.center { text-align:center; }
		.totals { width: 300px; margin-left:auto; margin-top: 12px; border-collapse: collapse; }
		.totals td { border:1px solid #e5e7eb; padding:8px; }
		.footer-msg { margin-top: 40px; text-align:center; font-weight: 700; }
	</style>
</head>
<body>
	<div class="header">
		<div class="h-top">
			<div>
				<div class="company-name"><?php echo esc($data['by_business'] ?: ($data['company_name'] ?? 'Your Company Name')); ?></div>
				<div class="company-slogan"><?php echo esc($data['slogan'] ?? 'Your Company Slogan'); ?></div>
			</div>
			<div class="logo">
				<?php if ($logoPath && is_file($logoPath)): ?>
					<img src="<?php echo esc($logoPath); ?>" style="height:60px;" />
				<?php else: ?>
					LOGO
				<?php endif; ?>
			</div>
		</div>
		<table class="meta" align="right">
			<tr><td style="width:120px;"><strong>DATE</strong></td><td><?php echo esc($data['quotation_date'] ?? ''); ?></td></tr>
			<tr><td><strong>Quotation #</strong></td><td><?php echo esc($data['quotation_no'] ?? ''); ?></td></tr>
			<tr><td><strong>Customer ID</strong></td><td><?php echo esc($data['customer_id'] ?? ''); ?></td></tr>
		</table>
	</div>

	<div class="divider"></div>

	<table style="width:100%;">
		<tr>
			<td style="width:55%; vertical-align:top;">
				<div class="address-block small">
					<div>Street Address</div>
					<div>City, State ZIP Code</div>
					<div>Phone: <?php echo esc($data['by_contact'] ?? ''); ?>
						&nbsp;&nbsp;&nbsp; Fax: Enter Fax number here
					</div>
				</div>
			</td>
			<td style="text-align:right; vertical-align:top;" class="small">
				<div><em>Quotation valid until:</em> <?php echo esc($data['validity'] ?? ''); ?></div>
				<div style="margin-top:8px;"><em>Prepared by:</em> <?php echo esc($data['by_business'] ?? ''); ?></div>
			</td>
		</tr>
	</table>

	<div class="block-title">Quotation For:</div>
	<div class="address-block small">
		<div><?php echo esc($data['to_business'] ?? ($data['customer_name'] ?? '')); ?></div>
		<div>Company Name</div>
		<div><?php echo esc($data['to_address'] ?? ''); ?></div>
		<div><?php echo esc(trim(($data['to_city'] ?? '').', '.($data['to_state'] ?? '').'  '.($data['to_postal'] ?? ''))); ?></div>
		<div><?php echo esc($data['to_contact'] ?? ''); ?></div>
	</div>

	<table class="table">
		<thead>
			<tr>
				<th style="width:110px;">QUANTITY</th>
				<th>DESCRIPTION</th>
				<th style="width:120px;">UNIT PRICE</th>
				<th style="width:90px;">TAXABLE?</th>
				<th style="width:140px;">AMOUNT</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach (($data['items']['name'] ?? []) as $i => $name):
			$qty = (float)($data['items']['qty'][$i] ?? 0);
			$price = (float)($data['items']['price'][$i] ?? 0);
			$base = $qty * $price;
		?>
			<tr>
				<td class="center"><?php echo money($qty); ?></td>
				<td><?php echo esc($name); ?></td>
				<td class="right"><?php echo $currencySymbol . ' ' . money($price); ?></td>
				<td class="center"><?php echo (!empty($includeGst) && ($gstPercent ?? 0) > 0) ? 'T' : ''; ?></td>
				<td class="right"><?php echo $currencySymbol . ' ' . money($base); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<table class="totals">
		<tr>
			<td>SUBTOTAL</td>
			<td class="right"><?php echo $currencySymbol . ' ' . money($subtotal); ?></td>
		</tr>
		<tr>
			<td>TAX RATE</td>
			<td class="right"><?php echo money($includeGst ? $gstPercent : 0); ?>%</td>
		</tr>
		<tr>
			<td>SALES TAX</td>
			<td class="right"><?php echo $currencySymbol . ' ' . money($taxtotal); ?></td>
		</tr>
		<?php $other = ($additional ?? 0) - ($discount ?? 0); ?>
		<tr>
			<td>OTHER</td>
			<td class="right"><?php echo $other === 0.0 ? '-' : $currencySymbol . ' ' . money($other); ?></td>
		</tr>
		<tr>
			<td><strong>TOTAL</strong></td>
			<td class="right"><strong><?php echo $currencySymbol . ' ' . money($grand); ?></strong></td>
		</tr>
	</table>

	<?php if (!empty($data['terms'])): ?>
		<div style="margin-top:18px;">
			<strong>Terms & Conditions</strong>
			<ol>
				<?php foreach ($data['terms'] as $t): if (trim((string)$t) === '') continue; ?>
					<li><?php echo esc($t); ?></li>
				<?php endforeach; ?>
			</ol>
		</div>
	<?php endif; ?>

	<?php if ($signaturePath && is_file($signaturePath)): ?>
		<div style="margin-top: 30px; text-align:right;">
			<img src="<?php echo esc($signaturePath); ?>" style="height:50px;" />
			<div>Authorized Signature</div>
		</div>
	<?php endif; ?>

	<div class="footer-msg">THANK YOU FOR YOUR BUSINESS!</div>
</body>
</html>

