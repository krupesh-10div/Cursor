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
	$tax = (float)($data['items']['tax'][$i] ?? 0);
	$base = $qty * $price;
	$taxAmt = $base * ($tax/100);
	$lineTotal = $base + $taxAmt;
	$subtotal += $base; $taxtotal += $taxAmt; $grand += $lineTotal;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<style>
		body { font-family: DejaVu Sans, sans-serif; color:#0b1220; }
		.wrap { width:100%; }
		.header { display:flex; justify-content: space-between; align-items:center; }
		.company { font-size:14px; }
		.title { font-size:26px; font-weight:700; letter-spacing: .5px; }
		.meta { margin-top: 12px; font-size:12px; color:#555; }
		.table { width:100%; border-collapse: collapse; margin-top: 18px; }
		.table th, .table td { border: 1px solid #e5e7eb; padding: 8px; }
		.table th { background:#f7f7fb; text-align:left; font-weight:600; }
		.totals { margin-top: 10px; width: 320px; margin-left: auto; }
		.totals td { padding: 6px 8px; }
		.terms { margin-top: 20px; font-size: 12px; }
		.flex { display:flex; gap:24px; }
		.block { border: 1px solid #e5e7eb; padding:12px; border-radius:8px; }
		.small { font-size: 12px; color:#444; }
		.signature { margin-top: 40px; text-align:right; font-size: 12px; }
	</style>
</head>
<body>
	<div class="wrap">
		<div class="header">
			<div>
				<div class="title">Quotation</div>
				<div class="meta">#<?php echo esc($data['quotation_no'] ?? ''); ?> · <?php echo esc($data['quotation_date'] ?? ''); ?></div>
			</div>
			<div class="company">
				<?php if ($logoPath && is_file($logoPath)): ?>
					<img src="<?php echo esc($logoPath); ?>" style="height:60px;" />
				<?php endif; ?>
				<div><strong><?php echo esc($data['company_name'] ?? ''); ?></strong></div>
				<div class="small"><?php echo esc($data['company_email'] ?? ''); ?></div>
			</div>
		</div>

		<div class="flex" style="margin-top:16px;">
			<div class="block" style="flex:1;">
				<div class="small">Billed To</div>
				<div><strong><?php echo esc($data['customer_name'] ?? ''); ?></strong></div>
				<div class="small"><?php echo esc($data['customer_email'] ?? ''); ?></div>
			</div>
		</div>

		<table class="table">
			<thead>
				<tr>
					<th>Product / Service</th>
					<th style="width:80px;">Qty</th>
					<th style="width:120px;">Unit Price</th>
					<th style="width:80px;">Tax %</th>
					<th style="width:140px;">Total</th>
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
					<td><?php echo esc($name); ?></td>
					<td><?php echo money($qty); ?></td>
					<td><?php echo $currencySymbol . ' ' . money($price); ?></td>
					<td><?php echo money($tax); ?></td>
					<td><?php echo $currencySymbol . ' ' . money($lineTotal); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<table class="totals">
			<tr>
				<td style="text-align:right;">Subtotal</td>
				<td style="text-align:right; width:140px;"><?php echo $currencySymbol . ' ' . money($subtotal); ?></td>
			</tr>
			<tr>
				<td style="text-align:right;">Tax</td>
				<td style="text-align:right; width:140px;"><?php echo $currencySymbol . ' ' . money($taxtotal); ?></td>
			</tr>
			<tr>
				<td style="text-align:right;"><strong>Grand Total</strong></td>
				<td style="text-align:right; width:140px;"><strong><?php echo $currencySymbol . ' ' . money($grand); ?></strong></td>
			</tr>
		</table>

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

