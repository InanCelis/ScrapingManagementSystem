<?php
require_once __DIR__ . '/ExecutableXML/ThaiProperty1.php';

// Test the ThaiProperty1 scraper
$scraper = new ThaiProperty1();

// Enable testing mode to save debug files
$scraper->setTestingMode(true);

// Optional: Enable upload to send data to API
// $scraper->enableUpload(true);

// Optional: Customize configuration
// $scraper->setConfig([
//     'listing_id_prefix' => 'CUSTOM-',
//     'website_url' => 'https://custom-website.com'
// ]);

// Run the scraper with the XML feed URL
// Limit to 5 properties for testing
echo "Starting ThaiProperty1 scraper...\n\n";

$xmlUrl = 'https://thaiproperty1.com/api/lA1bwo/feeds/nestopa-feed';

// Optional: Pass confidential info dynamically
$confidentialInfo = [
    'Owned by' => 'Thai Property 1',
    'Contact Person' => 'John Doe',
    'Phone' => '+66 (0) 38 412 122',
    'Email' => 'info@thaiproperty1.com',
    // 'listing_id_prefix' => 'CUSTOM-' // Can also set prefix here
];

$success = $scraper->run($xmlUrl, 5, $confidentialInfo);

if ($success) {
    echo "\n✅ Test completed successfully!\n";
} else {
    echo "\n❌ Test failed!\n";
}

?>
