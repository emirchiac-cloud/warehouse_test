<?php
// backend-web/export_pdf.php
// Dynamic Valuation & Low-Stock PDF Generator

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/data/ProductData.php';

$filterCategory = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$items = ProductData::getAll([
    'category_id' => $filterCategory ?: null,
    'sort'        => 'low_stock_first'
]);

$generationTime = date('Y-m-d H:i:s');
$generatedBy = $currentUser['full_name'] . ' (' . ucfirst($currentUser['role']) . ')';

// Calculate Totals
$totalQty = 0;
$totalValuation = 0.0;
$lowStockCount = 0;

foreach ($items as $item) {
    $totalQty += (int)$item['quantity'];
    $totalValuation += (float)$item['unit_price'] * (int)$item['quantity'];
    if ((int)$item['quantity'] < (int)$item['min_stock_threshold']) {
        $lowStockCount++;
    }
}

// Function to get Base64 Data URI for image so Dompdf / Browser can embed without file URL problems
function getImageBase64($relPath) {
    $abs = __DIR__ . '/' . ltrim($relPath ?: 'uploads/products/placeholder.png', '/');
    if (file_exists($abs) && is_file($abs)) {
        $type = pathinfo($abs, PATHINFO_EXTENSION);
        $data = file_get_contents($abs);
        return 'data:image/' . ($type === 'svg' ? 'svg+xml' : $type) . ';base64,' . base64_encode($data);
    }
    return '';
}

// Render HTML layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Valuation &amp; Low-Stock Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #222;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1b263b;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .system-title {
            font-size: 16pt;
            font-weight: bold;
            color: #1b263b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .system-sub {
            font-size: 10pt;
            color: #555;
            font-weight: 600;
        }
        .meta-text {
            font-size: 8.5pt;
            color: #666;
            text-align: right;
        }
        .report-summary-box {
            display: flex;
            justify-content: space-between;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-size: 9.5pt;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item span {
            display: block;
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
        }
        .summary-item strong {
            font-size: 12pt;
            color: #1b263b;
        }
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 15px;
        }
        table.report-table th {
            background-color: #1b263b;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            padding: 7px 6px;
            text-align: left;
            border: 1px solid #1b263b;
        }
        table.report-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        table.report-table tr.low-stock {
            background-color: #ffebee !important;
        }
        .thumb {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            border: 1px solid #ccc;
            display: block;
        }
        .badge-danger {
            background: #d32f2f;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 7pt;
            font-weight: bold;
            display: inline-block;
        }
        .badge-ok {
            background: #2e7d32;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 7pt;
            font-weight: bold;
            display: inline-block;
        }
        .total-footer {
            background: #e9ecef;
            font-weight: bold;
            font-size: 10pt;
        }
        .no-print-bar {
            background: #343a40;
            color: #fff;
            padding: 10px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px;
        }
        .btn-print {
            background: #0d6efd;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            .no-print-bar { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <div>
        <strong>Official PDF Report View</strong> | Ready for saving or printing
    </div>
    <div>
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>
</div>

<table class="header-table">
    <tr>
        <td style="width: 65%;">
            <div class="system-title">Centro Escolar México Junior College</div>
            <div class="system-sub">Information Technology Department — Unified Inventory Management System</div>
            <div style="font-size: 9pt; color: #777; margin-top: 3px;">Official Valuation &amp; Stock Threshold Audit Report</div>
        </td>
        <td style="width: 35%;" class="meta-text">
            <div><strong>Generated:</strong> <?= htmlspecialchars($generationTime) ?></div>
            <div><strong>Generated By:</strong> <?= htmlspecialchars($generatedBy) ?></div>
            <div><strong>Catalog Scope:</strong> <?= $filterCategory ? 'Filtered by Category' : 'Complete Warehouse Inventory' ?></div>
        </td>
    </tr>
</table>

<div class="report-summary-box">
    <div class="summary-item">
        <span>Total SKUs Listed</span>
        <strong><?= count($items) ?></strong>
    </div>
    <div class="summary-item">
        <span>Total Units in Stock</span>
        <strong><?= number_format($totalQty) ?></strong>
    </div>
    <div class="summary-item">
        <span>Low-Stock Alerts</span>
        <strong style="color: #c62828;"><?= $lowStockCount ?> Critical Items</strong>
    </div>
    <div class="summary-item">
        <span>Grand Total Inventory Valuation</span>
        <strong style="color: #1b263b;">$<?= number_format($totalValuation, 2) ?></strong>
    </div>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th style="width: 40px; text-align: center;">Thumb</th>
            <th>SKU</th>
            <th>Product Name</th>
            <th>Category</th>
            <th style="text-align: right;">Unit Price</th>
            <th style="text-align: center;">Qty</th>
            <th style="text-align: center;">Min Safe</th>
            <th style="text-align: center;">Status</th>
            <th style="text-align: right;">Subtotal Valuation</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $row): 
            $isLow = ((int)$row['quantity'] < (int)$row['min_stock_threshold']);
            $subtotal = (float)$row['unit_price'] * (int)$row['quantity'];
            $base64Img = getImageBase64($row['image_path']);
        ?>
            <tr class="<?= $isLow ? 'low-stock' : '' ?>">
                <td style="text-align: center;">
                    <?php if ($base64Img): ?>
                        <img src="<?= $base64Img ?>" class="thumb" alt="">
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><code><?= htmlspecialchars($row['sku']) ?></code></td>
                <td>
                    <strong><?= htmlspecialchars($row['name']) ?></strong>
                </td>
                <td><?= htmlspecialchars($row['category_name']) ?></td>
                <td style="text-align: right;">$<?= number_format($row['unit_price'], 2) ?></td>
                <td style="text-align: center; font-weight: bold;"><?= (int)$row['quantity'] ?></td>
                <td style="text-align: center;"><?= (int)$row['min_stock_threshold'] ?></td>
                <td style="text-align: center;">
                    <?php if ($isLow): ?>
                        <span class="badge-danger">LOW STOCK</span>
                    <?php else: ?>
                        <span class="badge-ok">OK</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right; font-weight: bold;">$<?= number_format($subtotal, 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="total-footer">
            <td colspan="5" style="text-align: right; padding: 10px;">GRAND TOTAL VALUATION:</td>
            <td style="text-align: center;"><?= number_format($totalQty) ?></td>
            <td colspan="2"></td>
            <td style="text-align: right; color: #1b263b;">$<?= number_format($totalValuation, 2) ?></td>
        </tr>
    </tfoot>
</table>

<div style="font-size: 8pt; color: #888; text-align: center; margin-top: 25px; border-top: 1px solid #ddd; padding-top: 8px;">
    Centro Escolar México Junior College • Information Technology Department • Semester 3 Examination Deliverable
</div>

</body>
</html>
