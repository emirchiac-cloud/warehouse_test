<?php
// backend-web/create_sample_images.php
$dir = __DIR__ . '/uploads/products/';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Generate simple SVGs and fallback PNGs or SVG files
$images = [
    'placeholder.png' => ['Warehouse Item', '#4A5568'],
    'scanner.png'      => ['Barcode Scanner', '#2B6CB0'],
    'terminal.png'     => ['Handheld Terminal', '#2C5282'],
    'printer.png'      => ['Label Printer', '#319795'],
    'labels.png'       => ['Thermal Labels 4x6', '#DD6B20'],
    'tape.png'         => ['Heavy Packing Tape', '#C05621'],
    'markers.png'      => ['Permanent Markers', '#2D3748'],
    'knife.png'        => ['Safety Utility Knife', '#E53E3E'],
    'gloves.png'       => ['Nitrile Work Gloves', '#38A169'],
    'strapping.png'    => ['Pallet Strapping Kit', '#805AD5'],
    'tote.png'         => ['Plastic Tote 60L', '#3182CE'],
    'wire_bin.png'     => ['Wire Shelf Bin', '#4A5568'],
    'stretch_wrap.png' => ['Stretch Film Roll', '#00A3C4'],
];

foreach ($images as $filename => $info) {
    list($label, $color) = $info;
    $width = 300;
    $height = 300;
    
    // Check if GD is available to create genuine PNG
    if (function_exists('imagecreatetruecolor')) {
        $im = imagecreatetruecolor($width, $height);
        
        // Parse hex color
        $hex = ltrim($color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $bgColor = imagecolorallocate($im, $r, $g, $b);
        $textColor = imagecolorallocate($im, 255, 255, 255);
        $accentColor = imagecolorallocate($im, max(0, $r - 30), max(0, $g - 30), max(0, $b - 30));
        
        imagefilledrectangle($im, 0, 0, $width, $height, $bgColor);
        imagefilledellipse($im, 150, 130, 160, 160, $accentColor);
        
        // Draw label text
        $text = $label;
        $fontSize = 4; // built-in font
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $x = ($width - $textWidth) / 2;
        imagestring($im, $fontSize, (int)$x, 240, $text, $textColor);
        
        imagepng($im, $dir . $filename);
        imagedestroy($im);
    } else {
        // Fallback: create SVG
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">
            <rect width="300" height="300" fill="' . $color . '" rx="16"/>
            <circle cx="150" cy="130" r="70" fill="white" opacity="0.25"/>
            <text x="150" y="240" font-family="sans-serif" font-size="16" font-weight="bold" fill="white" text-anchor="middle">' . htmlspecialchars($label) . '</text>
        </svg>';
        file_put_contents($dir . str_replace('.png', '.svg', $filename), $svg);
        file_put_contents($dir . $filename, $svg);
    }
}
echo "Sample images generated successfully in " . $dir . PHP_EOL;
