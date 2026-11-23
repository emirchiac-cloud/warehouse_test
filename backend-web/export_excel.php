<?php
// backend-web/export_excel.php
// Dynamic Excel Spreadsheet Export with Native Excel Formulas

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pdo = getDBConnection();
$filterCategory = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        INNER JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";
$params = [];
if ($filterCategory > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $filterCategory;
}
$sql .= " ORDER BY c.name ASC, p.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$filename = "Inventory_Valuation_Report_" . date('Y-m-d_His') . ".xls";

// Set Headers for Excel Download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author><?= htmlspecialchars($currentUser['full_name']) ?></Author>
  <Created><?= date('Y-m-d\TH:i:s\Z') ?></Created>
  <Company>Centro Escolar México Junior College</Company>
 </DocumentProperties>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <!-- Main Title Style -->
  <Style ss:ID="ReportTitle">
   <Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#1B263B"/>
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
  </Style>
  <!-- Subtitle Style -->
  <Style ss:ID="ReportSubtitle">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Italic="1" ss:Color="#495057"/>
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
  </Style>
  <!-- Table Header: Styled bold header row with background fill -->
  <Style ss:ID="HeaderRow">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#1B263B" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#0D1B2A"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0D1B2A"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0D1B2A"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0D1B2A"/>
   </Borders>
  </Style>
  <!-- Normal Data Cells -->
  <Style ss:ID="CellText">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
   </Borders>
  </Style>
  <Style ss:ID="CellCenter">
   <Alignment ss:Horizontal="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
   </Borders>
  </Style>
  <!-- Currency Format $#,##0.00 -->
  <Style ss:ID="CellCurrency">
   <NumberFormat ss:Format="&quot;$&quot;#,##0.00"/>
   <Alignment ss:Horizontal="Right"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
   </Borders>
  </Style>
  <!-- Status Styles -->
  <Style ss:ID="StatusOk">
   <Alignment ss:Horizontal="Center"/>
   <Font ss:Bold="1" ss:Color="#2E7D32"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
   </Borders>
  </Style>
  <Style ss:ID="StatusLowStock">
   <Alignment ss:Horizontal="Center"/>
   <Font ss:Bold="1" ss:Color="#C62828"/>
   <Interior ss:Color="#FFEBEE" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
   </Borders>
  </Style>
  <!-- Grand Total Footer -->
  <Style ss:ID="TotalFooter">
   <Font ss:Bold="1" ss:Color="#000000"/>
   <Interior ss:Color="#E9ECEF" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/>
    <Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#000000"/>
   </Borders>
  </Style>
  <Style ss:ID="TotalFooterCenter">
   <Font ss:Bold="1" ss:Color="#000000"/>
   <Interior ss:Color="#E9ECEF" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/>
    <Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#000000"/>
   </Borders>
  </Style>
  <Style ss:ID="TotalFooterCurrency">
   <Font ss:Bold="1" ss:Color="#1B263B"/>
   <Interior ss:Color="#E9ECEF" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="&quot;$&quot;#,##0.00"/>
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/>
    <Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#000000"/>
   </Borders>
  </Style>
 </Styles>

 <Worksheet ss:Name="Inventory Valuation">
  <Table ss:ExpandedColumnCount="8" x:FullColumns="1" x:FullRows="1" ss:DefaultRowHeight="20">
   <Column ss:Width="90"/>   <!-- SKU -->
   <Column ss:Width="230"/>  <!-- Product Name -->
   <Column ss:Width="130"/>  <!-- Category -->
   <Column ss:Width="90"/>   <!-- Unit Price -->
   <Column ss:Width="70"/>   <!-- Quantity -->
   <Column ss:Width="90"/>   <!-- Min Threshold -->
   <Column ss:Width="100"/>  <!-- Status -->
   <Column ss:Width="110"/>  <!-- Total Value -->

   <!-- Document Header Titles -->
   <Row ss:Height="26">
    <Cell ss:MergeAcross="7" ss:StyleID="ReportTitle"><Data ss:Type="String">Centro Escolar México Junior College - IT Department</Data></Cell>
   </Row>
   <Row ss:Height="20">
    <Cell ss:MergeAcross="7" ss:StyleID="ReportSubtitle"><Data ss:Type="String">Unified Inventory Valuation &amp; Stock Threshold Audit Report</Data></Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:MergeAcross="7" ss:StyleID="ReportSubtitle"><Data ss:Type="String">Generated: <?= date('Y-m-d H:i:s') ?> by <?= htmlspecialchars($currentUser['full_name']) ?></Data></Cell>
   </Row>
   <Row ss:Height="10"></Row>

   <!-- Table Header Row (Row 5) -->
   <Row ss:Height="24">
    <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">SKU</Data></Cell>
    <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">Product Name</Data></Cell>
    <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">Category</Data></Cell>
    <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">Unit Price</Data></Cell>
    <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">Quantity</Data></Cell>
    <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">Min Threshold</Data></Cell>
    <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">Status</Data></Cell>
    <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">Total Value</Data></Cell>
   </Row>

   <!-- Product Data Rows (Starting at row 6) -->
   <?php 
   $startRow = 6;
   $currentRow = $startRow;
   foreach ($products as $p): 
       $isLow = ((int)$p['quantity'] < (int)$p['min_stock_threshold']);
       $status = $isLow ? 'LOW STOCK' : 'OK';
       $statusStyle = $isLow ? 'StatusLowStock' : 'StatusOk';
   ?>
   <Row ss:Height="22">
    <Cell ss:StyleID="CellCenter"><Data ss:Type="String"><?= htmlspecialchars($p['sku']) ?></Data></Cell>
    <Cell ss:StyleID="CellText"><Data ss:Type="String"><?= htmlspecialchars($p['name']) ?></Data></Cell>
    <Cell ss:StyleID="CellText"><Data ss:Type="String"><?= htmlspecialchars($p['category_name']) ?></Data></Cell>
    <Cell ss:StyleID="CellCurrency"><Data ss:Type="Number"><?= (float)$p['unit_price'] ?></Data></Cell>
    <Cell ss:StyleID="CellCenter"><Data ss:Type="Number"><?= (int)$p['quantity'] ?></Data></Cell>
    <Cell ss:StyleID="CellCenter"><Data ss:Type="Number"><?= (int)$p['min_stock_threshold'] ?></Data></Cell>
    <Cell ss:StyleID="<?= $statusStyle ?>"><Data ss:Type="String"><?= $status ?></Data></Cell>
    <!-- Native Excel formula: Unit Price (Col D) * Quantity (Col E) -->
    <Cell ss:StyleID="CellCurrency" ss:Formula="=RC[-4]*RC[-3]"><Data ss:Type="Number"><?= (float)$p['unit_price'] * (int)$p['quantity'] ?></Data></Cell>
   </Row>
   <?php 
       $currentRow++;
   endforeach; 
   $lastDataRow = $currentRow - 1;
   ?>

   <!-- Grand Total Footer Row with Native SUM Formulas -->
   <Row ss:Height="24">
    <Cell ss:MergeAcross="3" ss:StyleID="TotalFooter"><Data ss:Type="String">GRAND TOTALS:</Data></Cell>
    <!-- Total Quantity =SUM(E6:E...) -->
    <Cell ss:StyleID="TotalFooterCenter" ss:Formula="=SUM(R<?= $startRow ?>C:R<?= $lastDataRow ?>C)"><Data ss:Type="Number">0</Data></Cell>
    <Cell ss:MergeAcross="1" ss:StyleID="TotalFooter"><Data ss:Type="String"></Data></Cell>
    <!-- Grand Total Valuation =SUM(H6:H...) -->
    <Cell ss:StyleID="TotalFooterCurrency" ss:Formula="=SUM(R<?= $startRow ?>C:R<?= $lastDataRow ?>C)"><Data ss:Type="Number">0</Data></Cell>
   </Row>

  </Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <Selected/>
   <Panes>
    <Pane>
     <Number>3</Number>
     <ActiveRow>5</ActiveRow>
    </Pane>
   </Panes>
   <ProtectObjects>False</ProtectObjects>
   <ProtectScenarios>False</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>
</Workbook>
