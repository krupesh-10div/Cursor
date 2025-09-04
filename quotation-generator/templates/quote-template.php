<?php
// This template expects variables in scope:
// $data: associative array of sanitized inputs
// $logoPath, $signaturePath: absolute local paths or null

function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$currencySymbol = '$';
switch (($data['currency'] ?? 'USD')){
	case 'INR': $currencySymbol = '₹'; break;
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

// Apply global tax and adjustments
$taxPercent = isset($data['tax_percent']) ? (float)$data['tax_percent'] : 8.6;
$includeTax = !empty($data['include_tax']);
if ($includeTax && $taxPercent > 0) {
	$taxtotal = $subtotal * ($taxPercent / 100.0);
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
		body { 
			font-family: Arial, sans-serif; 
			color: #000; 
			margin: 0; 
			padding: 20px; 
			background: white;
		}
		.quotation-container { 
			width: 100%; 
			max-width: 800px; 
			margin: 0 auto; 
		}
		.header { 
			display: flex; 
			justify-content: space-between; 
			align-items: flex-start; 
			margin-bottom: 30px; 
		}
		.company-info { 
			flex: 1; 
		}
		.company-name { 
			font-size: 24px; 
			font-weight: bold; 
			margin: 0 0 5px 0; 
		}
		.company-slogan { 
			font-size: 14px; 
			font-style: italic; 
			margin: 0 0 15px 0; 
		}
		.address-info { 
			font-size: 12px; 
			line-height: 1.4; 
		}
		.logo-section { 
			text-align: center; 
			margin-left: 20px; 
		}
		.logo-placeholder { 
			width: 80px; 
			height: 60px; 
			background: #f0f0f0; 
			border: 1px solid #ddd; 
			display: flex; 
			align-items: center; 
			justify-content: center; 
			font-size: 12px; 
			color: #666; 
			margin: 0 auto 10px; 
		}
		.quotation-meta { 
			text-align: right; 
			font-size: 12px; 
			line-height: 1.4; 
		}
		.quotation-details { 
			display: flex; 
			justify-content: space-between; 
			margin: 20px 0; 
		}
		.billed-to { 
			flex: 1; 
		}
		.billed-to h3 { 
			font-size: 14px; 
			font-weight: bold; 
			margin: 0 0 10px 0; 
		}
		.billed-to p { 
			font-size: 12px; 
			margin: 2px 0; 
		}
		.quotation-info { 
			text-align: right; 
			font-size: 12px; 
			font-style: italic; 
		}
		.items-table { 
			width: 100%; 
			border-collapse: collapse; 
			margin: 20px 0; 
		}
		.items-table th { 
			background: #f0f0f0; 
			padding: 10px 8px; 
			text-align: center; 
			font-weight: bold; 
			font-size: 12px; 
			border: 1px solid #ddd; 
		}
		.items-table td { 
			padding: 10px 8px; 
			border: 1px solid #ddd; 
			font-size: 12px; 
		}
		.qty-col { text-align: left; }
		.desc-col { text-align: left; }
		.price-col { text-align: right; }
		.taxable-col { text-align: center; }
		.amount-col { text-align: right; }
		.summary-section { 
			width: 300px; 
			margin-left: auto; 
			margin-top: 20px; 
		}
		.summary-row { 
			display: flex; 
			justify-content: space-between; 
			align-items: center; 
			margin: 5px 0; 
		}
		.summary-label { 
			font-size: 12px; 
		}
		.summary-value { 
			background: #f0f0f0; 
			padding: 5px 10px; 
			border: 1px solid #ddd; 
			font-size: 12px; 
			min-width: 80px; 
			text-align: right; 
		}
		.footer { 
			text-align: center; 
			margin-top: 40px; 
			font-weight: bold; 
			font-size: 14px; 
		}
		.error-icon { 
			color: red; 
			font-size: 10px; 
		}
	</style>
</head>
<body>
	<div class="quotation-container">
		<div class="header">
			<div class="company-info">
				<div class="company-name">Your Company Name</div>
				<div class="company-slogan">Your Company Slogan</div>
				<div class="address-info">
					Street Address<br>
					City, State ZIP Code<br>
					Phone: Enter Phone number here Fax: Enter Fax number here
				</div>
			</div>
			<div class="logo-section">
				<div class="logo-placeholder">LOGO</div>
				<div class="quotation-meta">
					DATE <?php echo esc($data['quotation_date'] ?? date('n/j/Y')); ?><br>
					Quotation # <?php echo esc($data['quotation_no'] ?? '345'); ?><br>
					Customer ID 123456
				</div>
			</div>
		</div>

		<div class="quotation-details">
			<div class="billed-to">
				<h3>Quotation For:</h3>
				<p><?php echo esc($data['to_business'] ?? 'Name'); ?></p>
				<p><?php echo esc($data['to_company'] ?? 'Company Name'); ?></p>
				<p><?php echo esc($data['to_address'] ?? 'Street Address'); ?></p>
				<p><?php echo esc($data['to_city'] ?? 'City, ST ZIP Code'); ?></p>
				<p><?php echo esc($data['to_contact'] ?? 'Phone'); ?></p>
			</div>
			<div class="quotation-info">
				Quotation valid until: <?php echo esc($data['validity_date'] ?? '13/9/2025'); ?><br>
				Prepared by: <?php echo esc($data['prepared_by'] ?? 'Name'); ?>
			</div>
		</div>

		<table class="items-table">
			<thead>
				<tr>
					<th class="qty-col">QUANTITY</th>
					<th class="desc-col">DESCRIPTION</th>
					<th class="price-col">UNIT PRICE</th>
					<th class="taxable-col">TAXABLE?</th>
					<th class="amount-col">AMOUNT</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach (($data['items']['name'] ?? []) as $i => $name):
				$qty = (float)($data['items']['qty'][$i] ?? 34);
				$price = (float)($data['items']['price'][$i] ?? 55.00);
				$lineTotal = $qty * $price;
			?>
				<tr>
					<td class="qty-col"><?php echo money($qty); ?></td>
					<td class="desc-col"><?php echo esc($name ?: 'Item 1'); ?></td>
					<td class="price-col"><?php echo $currencySymbol . ' ' . money($price); ?></td>
					<td class="taxable-col">T</td>
					<td class="amount-col"><?php echo $currencySymbol . ' ' . money($lineTotal); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<div class="summary-section">
			<div class="summary-row">
				<span class="summary-label">SUBTOTAL</span>
				<span class="summary-value">
					<span class="error-icon">▲</span>#ERROR!
				</span>
			</div>
			<div class="summary-row">
				<span class="summary-label">TAX RATE</span>
				<span class="summary-value"><?php echo money($taxPercent); ?>%</span>
			</div>
			<div class="summary-row">
				<span class="summary-label">SALES TAX $</span>
				<span class="summary-value"><?php echo money($taxtotal); ?></span>
			</div>
			<div class="summary-row">
				<span class="summary-label">OTHER $</span>
				<span class="summary-value">-</span>
			</div>
			<div class="summary-row">
				<span class="summary-label">TOTAL</span>
				<span class="summary-value">
					<span class="error-icon">▲</span>#ERROR!
				</span>
			</div>
		</div>

		<div class="footer">
			THANK YOU FOR YOUR BUSINESS!
		</div>
	</div>
</body>
</html>

