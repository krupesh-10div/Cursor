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

// Apply rounding on server if requested
if (!empty($data['rounding'])) {
	if ($data['rounding'] === 'up') { $grand = ceil($grand); }
	elseif ($data['rounding'] === 'down') { $grand = floor($grand); }
}

// Convert total to words if requested (basic English, INR oriented)
function number_to_words_int($num) {
	$words = [
		0=>'zero',1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve',13=>'thirteen',14=>'fourteen',15=>'fifteen',16=>'sixteen',17=>'seventeen',18=>'eighteen',19=>'nineteen',20=>'twenty',30=>'thirty',40=>'forty',50=>'fifty',60=>'sixty',70=>'seventy',80=>'eighty',90=>'ninety'];
	if ($num < 21) return $words[$num];
	if ($num < 100) { $t = (int)($num/10)*10; $r = $num%10; return $words[$t] . ($r?'-'.$words[$r]:''); }
	if ($num < 1000) { $h=(int)($num/100); $r=$num%100; return $words[$h].' hundred' . ($r?' and '.number_to_words_int($r):''); }
	$units=['',' thousand',' lakh',' crore']; $res=''; $i=0; $n=$num;
	while ($n>0 && $i<count($units)){
		$div = ($i==0?1000:($i==1?100:$i==2?100:$i==3?100:100)); // placeholder, handled via steps below
		if ($i==0) { $chunk=$n%1000; $n=(int)($n/1000); }
		else { $chunk=$n%100; $n=(int)($n/100); }
		if ($chunk) { $res = number_to_words_int($chunk) . $units[$i] . ($res?' '.$res:''); }
		$i++;
	}
	return trim($res);
}

function amount_in_words_inr($amount) {
	$rupees = (int)floor($amount);
	$paise = (int)round(($amount - $rupees) * 100);
	$txt = '';
	if ($rupees > 0) { $txt .= ucfirst(number_to_words_int($rupees)) . ' rupees'; }
	if ($paise > 0) { $txt .= ($txt?' and ':'') . number_to_words_int($paise) . ' paise'; }
	return $txt ?: 'Zero';
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
				<div class="small"><?php echo esc($data['company_email'] ?? ''); ?><?php echo $data['company_phone']? ' · '.esc($data['company_phone']) : '' ?></div>
				<?php if (!empty($data['company_gstin'])): ?><div class="small">GSTIN: <?php echo esc($data['company_gstin']); ?></div><?php endif; ?>
				<?php if (!empty($data['company_address'])): ?><div class="small"><?php echo esc($data['company_address']); ?><?php echo $data['company_city']? ', '.esc($data['company_city']) : '' ?><?php echo $data['company_state']? ', '.esc($data['company_state']) : '' ?><?php echo $data['company_zip']? ' - '.esc($data['company_zip']) : '' ?></div><?php endif; ?>
			</div>
		</div>

		<div class="flex" style="margin-top:16px;">
			<div class="block" style="flex:1;">
				<div class="small">Billed To</div>
				<div><strong><?php echo esc($data['customer_name'] ?? ''); ?></strong></div>
				<div class="small"><?php echo esc($data['customer_email'] ?? ''); ?><?php echo $data['customer_phone']? ' · '.esc($data['customer_phone']) : '' ?></div>
				<?php if (!empty($data['customer_gstin'])): ?><div class="small">GSTIN: <?php echo esc($data['customer_gstin']); ?></div><?php endif; ?>
				<?php if (!empty($data['customer_address'])): ?><div class="small"><?php echo esc($data['customer_address']); ?><?php echo $data['customer_city']? ', '.esc($data['customer_city']) : '' ?><?php echo $data['customer_state']? ', '.esc($data['customer_state']) : '' ?><?php echo $data['customer_zip']? ' - '.esc($data['customer_zip']) : '' ?></div><?php endif; ?>
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
				<td style="text-align:right;">Tax<?php echo !empty($data['gst_included']) ? ' (GST '.money((float)($data['gst_percent'] ?? 0)).'%)' : '' ?></td>
				<td style="text-align:right; width:140px;"><?php echo $currencySymbol . ' ' . money($taxtotal); ?></td>
			</tr>
			<tr>
				<td style="text-align:right;"><strong>Grand Total</strong></td>
				<td style="text-align:right; width:140px;"><strong><?php echo $currencySymbol . ' ' . money($grand); ?></strong></td>
			</tr>
			<?php if (!empty($data['total_in_words'])): ?>
			<tr>
				<td style="text-align:right;">Amount in Words</td>
				<td style="text-align:right; width:140px;"><?php echo esc(ucfirst(amount_in_words_inr($grand))); ?></td>
			</tr>
			<?php endif; ?>
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

		<?php if (!empty($data['notes'])): ?>
			<div class="terms"><strong>Notes</strong><div><?php echo nl2br(esc($data['notes'])); ?></div></div>
		<?php endif; ?>
		<?php if (!empty($data['validity'])): ?>
			<div class="terms"><strong>Validity</strong><div><?php echo esc($data['validity']); ?></div></div>
		<?php endif; ?>
		<?php if (!empty($data['footer'])): ?>
			<div class="terms"><strong>Footer</strong><div><?php echo nl2br(esc($data['footer'])); ?></div></div>
		<?php endif; ?>

		<div class="signature">
			<?php if ($signaturePath && is_file($signaturePath)): ?>
				<img src="<?php echo esc($signaturePath); ?>" style="height:50px;" />
				<div>Authorized Signature</div>
			<?php endif; ?>
			<?php if (!empty($data['signature_name'])): ?><div><?php echo esc($data['signature_name']); ?></div><?php endif; ?>
		</div>
	</div>
</body>
</html>

