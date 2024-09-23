<?php
header('Access-Control-Allow-Origin: *');
// Set the content type to image/png
header('Content-Type: image/png');

$lightMode = isset($_GET['light']) && $_GET['light'] === 'true';

// Set the background image based on the mode
if ($lightMode) {
    $backgroundImagePath = 'image2.jpg';
} else {
    $backgroundImagePath = 'image.jpg';
}


if (isset($_GET['bgid']) && $_GET['bgid'] !== '') {
    $bgid = htmlspecialchars($_GET['bgid']); // Sanitize input if necessary
    $imageUrl = 'bgs/' . $bgid;
    if (file_exists($imageUrl)) {
        $backgroundImage = imagecreatefromjpeg($imageUrl);
    } else {
        $backgroundImage = imagecreatefromjpeg($backgroundImagePath);
    }
} else {
    $backgroundImage = imagecreatefromjpeg($backgroundImagePath);
}

// Get the dimensions of the background image
$imageWidth = imagesx($backgroundImage);
$imageHeight = imagesy($backgroundImage);

// Create a blank true color image with the same dimensions as the background
$image = imagecreatetruecolor($imageWidth, $imageHeight);

// Copy the background image onto the blank image
imagecopy($image, $backgroundImage, 0, 0, 0, 0, $imageWidth, $imageHeight);

// Set the text color to white
if ($lightMode) {
$textColor = imagecolorallocate($image, 0, 0, 0);
} else {
$textColor = imagecolorallocate($image, 255, 255, 255);
}

// Define the text to be drawn
$text = $_GET['text'];

// Use a default font size and path
$fontSize = 120; // Adjusted for better readability
$fontPath = __DIR__ . '/font.ttf'; // Make sure this font file is available in the same directory
$boldFontPath = __DIR__ . '/bold-font.ttf'; // Provide a bold version of the font

// Function to apply text style based on markdown-like syntax
function getStyledText($line, $defaultFontPath, $boldFontPath) {
    $styledSegments = [];
    $tokens = preg_split('/(\*.*?\*)/', $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    foreach ($tokens as $token) {
        if (preg_match('/^\*(.*?)\*$/', $token, $matches)) {
            $styledSegments[] = ['text' => $matches[1], 'font' => $boldFontPath];
        } else {
            $styledSegments[] = ['text' => $token, 'font' => $defaultFontPath];
        }
    }

    return $styledSegments;
}

// Word-wrap the text to 24 characters per line
$wrappedText = wordwrap($text, 30, "\n", true);

// Split the text into lines
$lines = explode("\n", $wrappedText);

// Calculate the height of the text block with line spacing
$lineHeight = abs(imagettfbbox($fontSize, 0, $fontPath, 'A')[5] - imagettfbbox($fontSize, 0, $fontPath, 'A')[1]);
$lineSpacing = 2; // Adjust this value to set the line spacing multiplier
$textBlockHeight = count($lines) * $lineHeight * $lineSpacing - ($lineSpacing - 1) * $lineHeight;

// Calculate the coordinates to center the text block vertically
$y = ($imageHeight / 2) - ($textBlockHeight / 2);

// Draw each line of text
foreach ($lines as $line) {
    $styledSegments = getStyledText($line, $fontPath, $boldFontPath);

    // Calculate the total width of the line
    $totalWidth = 0;
    foreach ($styledSegments as $segment) {
        $bbox = imagettfbbox($fontSize, 0, $segment['font'], $segment['text']);
        $totalWidth += abs($bbox[4] - $bbox[0]);
    }

    // Calculate the starting x-coordinate to center the text horizontally
   // $x = ($imageWidth / 2) - ($totalWidth / 2);
$x = 400;
    // Draw each segment
    foreach ($styledSegments as $segment) {
        $bbox = imagettfbbox($fontSize, 0, $segment['font'], $segment['text']);
        $segmentWidth = abs($bbox[4] - $bbox[0]);
        imagettftext($image, $fontSize, 0, $x, $y + $lineHeight, $textColor, $segment['font'], $segment['text']);
        $x += $segmentWidth;
    }

    $y += $lineHeight * $lineSpacing; // Move to the next line, with line spacing
}

// Output the image
imagepng($image);

// Free up memory
imagedestroy($image);
imagedestroy($backgroundImage);
?>
this is my code but the background im providing is solid black, and i want light theme also , so for light ill be provoiding image2.jpg, & text will be black, & to get light mode simply ?light=true, then light mode else if not passed then dark as usual