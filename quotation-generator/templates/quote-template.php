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
		body { font-family: DejaVu Sans, sans-serif; color:#0b1220; }
		.wrap { width:100%; }
		.header { justify-content: space-between; align-items:flex-start; }
		.left-head h1 { margin:0; font-size:24px; font-weight:700; }
		.left-head .slogan { font-style:italic; font-size:12px; margin-top:2px; }
		.addr-line { font-size:12px; line-height:1.4; }
		.right-head { text-align:right; font-size:12px; }
		.right-head .logo { font-size:46px; font-weight:700; color:#9ca3af; }
		.right-head table.meta td { padding:2px 4px; }
		.section-title { font-weight:600; margin:14px 0 6px; }
		.quote-for { font-size:12px; line-height:1.4; }
		.items th { text-transform:uppercase; font-size:12px; }
		.totals-box { border:1px solid #e5e7eb; border-collapse:collapse; font-size:12px; }
		.totals-box td { padding:6px 10px; border:1px solid #e5e7eb; text-align:right; }
		.totals-box tr td:first-child { text-align:left; }
		.thanks { text-align:center; font-weight:600; margin-top:40px; }
	</style>
</head>
<body>
	<div class="wrap">
		<div class="header">
			<div class="left-head">
				<h1><?php echo esc($data['by_business'] ?: 'Your Company Name'); ?></h1>
				<div class="slogan"><?php echo esc($data['company_slogan'] ?? 'Your Company Slogan'); ?></div>
				<?php if (!empty($data['by_address'])): ?><div class="addr-line"><?php echo esc($data['by_address']); ?></div><?php endif; ?>
				<?php if (!empty($data['by_city']) || !empty($data['by_state'])): ?><div class="addr-line"><?php echo esc($data['by_city'] . ', ' . $data['by_state']); ?></div><?php endif; ?>
				<?php if (!empty($data['by_contact'])): ?><div class="addr-line">Phone: <?php echo esc($data['by_contact']); ?></div><?php endif; ?>
			</div>
			<div class="right-head">
				<div class="logo">LOGO</div>
				<table class="meta" style="margin-left:auto;">
					<tr><td><strong>DATE</strong></td><td><?php echo esc($data['quotation_date']); ?></td></tr>
					<tr><td><strong>Quotation #</strong></td><td><?php echo esc($data['quotation_no']); ?></td></tr>
					<?php if(!empty($data['customer_id'])): ?><tr><td><strong>Customer ID</strong></td><td><?php echo esc($data['customer_id']); ?></td></tr><?php endif; ?>
				</table>
			</div>
		</div>

		<div style="margin-top:18px;" class="quote-for-block">
			<div class="section-title">Quotation For:</div>
			<div class="quote-for">
				<div><?php echo esc($data['to_business'] ?: $data['customer_name']); ?></div>
				<?php if (!empty($data['to_address'])): ?><div><?php echo esc($data['to_address']); ?></div><?php endif; ?>
				<?php if (!empty($data['to_city']) || !empty($data['to_state'])): ?><div><?php echo esc($data['to_city'] . ', ' . $data['to_state']); ?></div><?php endif; ?>
				<?php if (!empty($data['to_contact'])): ?><div><?php echo esc($data['to_contact']); ?></div><?php endif; ?>
			</div>
		</div>

		<table class="table">
			<thead>
				<tr>
					<th style="width:60px;">QUANTITY</th>
					<th>DESCRIPTION</th>
					<th style="width:100px;">UNIT PRICE</th>
					<th style="width:80px;">TAXABLE?</th>
					<th style="width:120px;">AMOUNT</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach (($data['items']['name'] ?? []) as $i => $name):
				$qty = (float)($data['items']['qty'][$i] ?? 0);
				$price = (float)($data['items']['price'][$i] ?? 0);
				$tax = (float)($data['items']['tax'][$i] ?? 0);
				$base = $qty * $price;
				$taxAmt = $base * ($tax/100);
				$lineTotal = $base + $taxAmt;
			?>
				<tr>
					<td><?php echo money($qty); ?></td>
					<td><?php echo esc($name); ?></td>
					<td><?php echo $currencySymbol . ' ' . money($price); ?></td>
					<td><?php echo ($tax > 0) ? 'Yes' : 'No'; ?></td>
					<td><?php echo $currencySymbol . ' ' . money($lineTotal); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<table class="totals-box" style="width:240px; margin-left:auto; margin-top:12px;">
			<tr><td>SUBTOTAL</td><td><?php echo $currencySymbol . ' ' . money($subtotal); ?></td></tr>
			<tr><td>TAX RATE</td><td><?php echo money($gstPercent) . '%'; ?></td></tr>
			<tr><td>SALES TAX</td><td><?php echo $currencySymbol . ' ' . money($taxtotal); ?></td></tr>
			<tr><td>OTHER</td><td><?php echo ($additional>0) ? $currencySymbol . ' ' . money($additional) : '-'; ?></td></tr>
			<tr><td><strong>TOTAL</strong></td><td><strong><?php echo $currencySymbol . ' ' . money($grand); ?></strong></td></tr>
		</table>

		<div class="thanks">THANK YOU FOR YOUR BUSINESS!</div>

		<?php if (!empty($data['terms'])): ?>
			<div class="terms">
				<strong>Terms & Conditions</strong>
				<ol>
					<?php foreach ($data['terms'] as $t): if (trim((string)$t) === '') continue; ?>
						<li><?php echo esc($t); ?></li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>

		<div class="signature">
			<?php if ($signaturePath && is_file($signaturePath)): ?>
				<img src="<?php echo esc($signaturePath); ?>" style="height:50px;" />
				<div>Authorized Signature</div>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>

