<?php
require_once __DIR__ . '/../simple_html_dom.php';
require_once __DIR__ . '/../Api/ApiSender.php';
require_once __DIR__ . '/../Helpers/ScraperHelpers.php';

class CasaEspanha {
    private string $baseUrl = "https://casaespanha.com";
    private string $foldername = "CasaEspanha";
    private string $filename = "Properties.json";
    private array $propertyLinks = [];
    private array $scrapedData = [];
    private ApiSender $apiSender;
    private ScraperHelpers $helpers;
    private int $successCreated;
    private int $successUpdated;
    private bool $enableUpload = false;
    private bool $testingMode = false;
    private array $confidentialInfo = [];

    public function __construct() {
        $this->apiSender = new ApiSender(true);
        $this->helpers = new ScraperHelpers();
        $this->successCreated = 0;
        $this->successUpdated = 0;
    }

    public function setConfidentialInfo(array $confidentialInfo): void {
        $this->confidentialInfo = $confidentialInfo;
    }

    public function run(int $pageCount = 1, int $limit = 0): void {
        $folder = __DIR__ . '/../ScrapeFile/'.$this->foldername;
        $outputFile = $folder . '/'.$this->filename;
        if($this->testingMode) {
            $htmlTest =  $folder . '/Test.html';
        }
        
        // Create the folder if it doesn't exist
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        // Start a fresh JSON array
        file_put_contents($outputFile, "[");

        $propertyCounter = 0;
        for ($page = 1; $page <= $pageCount; $page++) {
            $url = $this->baseUrl . "/properties/?location&type&bedrooms&bathrooms&minprice&maxprice=1500000&reference&listing_type&rol_page={$page}";
            
            echo "📄 Fetching page $page: $url\n";

            $html = file_get_html($url);
            if (!$html) {
                echo "⚠️ Failed to load page $page. Skipping...\n";
                continue;
            }
            $this->extractPropertyLinks($html);
        }

        // Deduplicate array of arrays
        $this->propertyLinks = array_map("unserialize", array_unique(array_map("serialize", $this->propertyLinks)));
        if ($limit > 0) {
            $this->propertyLinks = array_slice($this->propertyLinks, 0, $limit);
        }
        $countLinks = 1;
        
        // Get total count of property links
        $totalLinks = count($this->propertyLinks);
        echo "📊 Total properties to scrape: {$totalLinks}\n\n";
        foreach ($this->propertyLinks as $url) {
            echo "\nURL ".$countLinks++." 🔍 Scraping: $url\n";
            $propertyHtml = file_get_html($url);
            if ($propertyHtml) {
                $this->scrapedData = []; // Clear for fresh
                
                if($this->testingMode) {
                    file_put_contents($htmlTest, $propertyHtml);
                    return;
                }

                $this->scrapePropertyDetails($propertyHtml, $url);

                if (!empty($this->scrapedData[0])) {
                    $jsonEntry = json_encode($this->scrapedData[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    file_put_contents($outputFile, ($propertyCounter > 0 ? "," : "") . "\n" . $jsonEntry, FILE_APPEND);
                    $propertyCounter++;

                    // Send the property data via the ApiSender

                    if($this->enableUpload) {
                        $result = $this->apiSender->sendProperty($this->scrapedData[0]);
                        if ($result['success']) {
                            echo "✅ Success after {$result['attempts']} attempt(s)\n";
                            if (count($result['response']['updated_properties']) > 0) {
                                echo "✅ Updated # " . $this->successUpdated++ . "\n";
                            } else {
                                echo "✅ Created # " . $this->successCreated++ . "\n";
                            }
                        } else {
                            echo "❌ Failed after {$result['attempts']} attempts. Last error: {$result['error']}\n";
                            if ($result['http_code']) {
                                echo "⚠️ HTTP Status: {$result['http_code']}\n";
                            }
                        }
                        sleep(1);
                    }
                }
            }
        }

        // Close the JSON array
        file_put_contents($outputFile, "\n]", FILE_APPEND);

        echo "✅ Scraping completed. Output saved to {$outputFile}\n";
        echo "✅ Properties Created: {$this->successCreated}\n";
        echo "✅ Properties Updated: {$this->successUpdated}\n";
    }


    private function extractPropertyLinks(simple_html_dom $html): void {
        if($this->testingMode) {
            // file_put_contents('test.html', $html);
            // return;
        }
        
        foreach ($html->find('.rol-properties-grid div>a') as $a) {
            $href = $a->href ?? '';
            if (strpos($href, '/R') !== false) {
                // Decode HTML entities
                $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                $fullUrl = strpos($href, 'https') === 0 ? $href : $this->baseUrl . $href;
                $this->propertyLinks[] = $fullUrl;
            }
        }
        $this->propertyLinks = array_unique($this->propertyLinks);
    }

    private function scrapePropertyDetails(simple_html_dom $html, $url): void {
        $title = trim($html->find('h1.rol-details-title', 0)->plaintext ?? '');
        if(empty($title)) {
            echo "❌ Skipping property with invalid setup of html\n ";
            // $this->helpers->updatePostToDraft($url);
            return; 
        }


        // Find the property details container
        $propertyDetails = $html->find('.property-details', 0);
        $listing_id = '';
        $price = 0;
        $currency = 'EUR';
        if ($propertyDetails) {
            // Find all divs within the overview-grid
            $overviewItems = $propertyDetails->find('.overview-grid div');
            
            foreach ($overviewItems as $item) {
                $text = trim($item->plaintext);
                
                // Check if this div contains "Property ID:"
                if (strpos($text, 'Property ID:') !== false) {
                    // Extract the ID value after "Property ID:"
                    $listing_id = trim(str_replace('Property ID:', '', $text));
                    if($listing_id) {
                        $IDH_ID = 'IDHI-MLH-'.$listing_id;
                        $res = $this->apiSender->getPropertyById($IDH_ID);
                        if ($res['success']) {
                            $property = $res['property'];
                            echo "❌ Skipping IDH property already exists in the database\n ";
                            return;
                        } else {
                            $HSS_ID = 'HHS-'.$listing_id;
                            $res2 = $this->apiSender->getPropertyById($HSS_ID);
                            if ($res2['success']) {
                                $property = $res2['property'];
                                echo "❌ Skipping HSS property already exists in the database\n ";
                                return;
                            } else {
                                echo "Error: " . $res['error']. "\n";
                            }
                        }
                    }
                }


                 // Check if this div contains "Price:"
                if (strpos($text, 'Price:') !== false) {
                    // Extract the price text after "Price:"
                    $priceText = trim(str_replace('Price:', '', $text));
                    
                    echo "🔍 Debug - Full price text: '$priceText'\n";
                    
                    // Determine currency
                    if (strpos($priceText, '€') !== false) {
                        $currency = 'EUR';
                    } elseif (strpos($priceText, '$') !== false) {
                        $currency = 'USD';
                    } elseif (strpos($priceText, '£') !== false) {
                        $currency = 'GBP';
                    }
                    
                    // Remove currency symbols and extra spaces
                    $priceText = str_replace(['€', '$', '£'], '', $priceText);
                    $priceText = trim($priceText);
                    
                    // Find all number sequences in the text
                    preg_match_all('/\d+(?:\.\d+)*/', $priceText, $allMatches);
                    echo "🔍 Debug - All number matches: " . print_r($allMatches[0], true) . "\n";
                    
                    if (!empty($allMatches[0])) {
                        // Take the first number sequence (before any dash)
                        $firstNumber = $allMatches[0][0];
                        echo "🔍 Debug - First number sequence: '$firstNumber'\n";
                        
                        // Remove dots and convert to integer
                        $price = (int)str_replace('.', '', $firstNumber);
                        echo "🔍 Debug - Final price: $price\n";
                    }
                    
                    echo "✅ Price extracted: $price $currency\n";
                }
                
            }
        }

        $this->scrapedData[] = [
            "property_title" => $title,
            // "property_description" => $this->helpers->translateHtmlPreservingTags($descriptionHtml),
            // "property_excerpt" => $translatedExcerpt,
            "price" => $price,
            "currency" => $currency,
            // "price_postfix" => "",
            // "price_prefix" => "",
            // "location" => $location,
            // "bedrooms" => $bedrooms,
            // "bathrooms" => $bathrooms,
            // "size" => $size,
            // "size_prefix" => $size_prefix,
            // "property_type" => $property_type,
            // "property_status" => $property_status,
            // "property_address" => $address,
            // "property_area" => "",
            // "city" => $city,
            // "state" => $state,
            // "country" => $country,
            // "zip_code" => '',
            // "latitude" => $latitude,
            // "longitude" => $longitude,
            "listing_id" => 'CE-'.$listing_id,
            // "agent_id" => "150",
            // "agent_display_option" => "agent_info",
            // "mls_id" => "",
            // "office_name" => "",
            // "video_url" => $video_url,
            // "virtual_tour" => "",
            // "images" => $images,
            // "property_map" => "1",
            // "property_year" => "",
            // "additional_features" => $features,
            "confidential_info" => $this->buildConfidentialInfo($url)
        ];
    }

    private function buildConfidentialInfo(string $url = ''): array {
        $confidentialInfo = [];

        // Add URL first if available
        if (!empty($url)) {
            $confidentialInfo[] = [
                "fave_additional_feature_title" => "Website",
                "fave_additional_feature_value" => $url
            ];
        }

        // Add dynamic confidential information from config
        foreach ($this->confidentialInfo as $title => $value) {
            if (!empty($value)) {
                $confidentialInfo[] = [
                    "fave_additional_feature_title" => $title,
                    "fave_additional_feature_value" => $value
                ];
            }
        }

        // Fallback to hardcoded defaults if no config provided
        if (empty($this->confidentialInfo)) {
            $defaultInfo = [
                "Owned By" => "Casa Espanha",
                "Contact Person" => "Darren Ashley",
                "Phone" => "+34 722 43 32 94",
                "Email" => "darren@holiday-homes-spain.com"
            ];

            foreach ($defaultInfo as $title => $value) {
                $confidentialInfo[] = [
                    "fave_additional_feature_title" => $title,
                    "fave_additional_feature_value" => $value
                ];
            }
        }

        return $confidentialInfo;
    }

    private function saveToJson(string $filename): void {
        file_put_contents(
            $filename,
            json_encode($this->scrapedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}

