<?php
// Modern Asset Generator for Goyaa

function createLogo($path) {
    $width = 500;
    $height = 200;
    $image = imagecreatetruecolor($width, $height);

    // Colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $primary = imagecolorallocate($image, 40, 167, 69); // Professional Green
    $dark = imagecolorallocate($image, 33, 37, 41);

    imagefill($image, 0, 0, $white);

    // Draw a modern icon (a stylized 'G')
    imagefilledellipse($image, 80, 100, 100, 100, $primary);
    imagefilledellipse($image, 80, 100, 60, 60, $white);
    imagefilledrectangle($image, 80, 90, 130, 110, $white);
    imagefilledrectangle($image, 100, 90, 120, 130, $primary);

    // Note: Since we don't have custom TTF fonts reliably on Alpine, we use high-res built-in fonts
    $text = "Goyaa";
    $font = 5; // Largest built-in font
    
    // Scale text by drawing it multiple times or using a custom routine
    // But for a professional look on a server, we just use clear positioning
    imagestring($image, 5, 150, 85, $text, $dark);
    imagestring($image, 3, 150, 110, "Artisans for Hire", $primary);

    imagepng($image, $path);
    imagedestroy($image);
}

function downloadThematicImage($keyword, $path) {
    $url = "https://loremflickr.com/800/600/" . urlencode($keyword);
    $content = @file_get_contents($url);
    if ($content) {
        file_put_contents($path, $content);
        return true;
    }
    return false;
}

// 1. Generate Logo
$logoDir = "/var/www/storage/app/public/business";
if (!file_exists($logoDir)) mkdir($logoDir, 0777, true);
$logoPath = $logoDir . "/goyaa_logo_modern.png";
createLogo($logoPath);
echo "Logo generated at $logoPath\n";

// 2. Fetch Category Images
$categories = [
    'Plumbing' => 'plumber,tools',
    'Electrical' => 'electrician,wiring',
    'Carpentry' => 'carpenter,wood',
    'Cleaning' => 'cleaning,house',
    'Masonry' => 'construction,bricks',
    'Painting' => 'painting,wall',
    'AC Repair' => 'airconditioner,repair'
];

$catDir = "/var/www/storage/app/public/category";
if (!file_exists($catDir)) mkdir($catDir, 0777, true);

foreach ($categories as $name => $keyword) {
    $filename = "cat_" . strtolower(str_replace(' ', '_', $name)) . ".png";
    if (downloadThematicImage($keyword, $catDir . "/" . $filename)) {
        echo "Downloaded image for $name\n";
    }
}

// 3. Fetch Service Images
$serviceDir = "/var/www/storage/app/public/service";
if (!file_exists($serviceDir)) mkdir($serviceDir, 0777, true);

$services = ['Plumbing', 'Electrical', 'Cleaning', 'AC Repair'];
foreach ($services as $s) {
    $filename = "srv_" . strtolower(str_replace(' ', '_', $s)) . ".png";
    downloadThematicImage($s . ",worker", $serviceDir . "/" . $filename);
}

echo "Asset generation complete.\n";
?>
