<?php
require_once __DIR__ . '/../simple_html_dom.php';
require_once __DIR__ . '/../Api/ApiSender.php';
require_once __DIR__ . '/../Helpers/ScraperHelpers.php';

class HolidayHomesSpain {
    private string $baseUrl = "https://holiday-homes-spain.com";
    private string $foldername = "HolidayHomesSpain";
    private string $filename = "Properties2.json";
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
            $url = $this->baseUrl . "/property-search-results/?mls_search_performed=1&query_id=b1e104b4-a9a8-11f0-9ebd-02a3ded47a2d&page_num={$page}&p_sorttype=3";
            
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
            echo "URL ".$countLinks++." 🔍 Scraping: $url\n";
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
        
        foreach ($html->find('.mls-pro-list-wrapper .mls-property-box .mls-pyc-title h2 a') as $a) {
            $href = $a->href ?? '';
            if (strpos($href, '/property-details/') !== false) {
                // Decode HTML entities
                $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                $fullUrl = strpos($href, 'http') === 0 ? $href : $this->baseUrl . $href;
                $this->propertyLinks[] = $fullUrl;
            }
        }
        $this->propertyLinks = array_unique($this->propertyLinks);
    }

    private function scrapePropertyDetails(simple_html_dom $html, $url): void {
        // Find all the property highlight sections
        $sections = $html->find('.mls-prj-section .ltst-pst .ltst');

        $listing_id = '';
        $property_type = [];
        $city = '';
        $state = '';
        $country = '';

        foreach ($sections as $section) {
            $h4 = $section->find('h4', 0);
            $p = $section->find('p', 0);
            
            if ($h4 && $p) {
                $title = trim($h4->plaintext);
                $value = trim($p->plaintext);
                
                // Extract Reference ID
                if ($title === 'Reference ID') {
                    $listing_id = $value;
                    if($listing_id && $this->enableUpload) {
                        $IDH_ID = 'IDHI-MLH-'.$listing_id;
                        $res = $this->apiSender->getPropertyById($IDH_ID);
                        if ($res['success']) {
                            $property = $res['property'];
                            echo "❌ Skipping property already exists in the database\n ";
                            return;
                        } else {
                            echo "Error: " . $res['error']. "\n";
                        }
                    }
                }
                
                // Extract Location
                if ($title === 'Location') {
                    $city = $value;
                }
                
                // Extract Area
                if ($title === 'Area') {
                    $state = $value;
                }
                
                // Extract Country
                if ($title === 'Country') {
                    $country = $value;
                }
                
                // Extract Property Type
                if ($title === 'Property type') {
                    $allowedType = $this->helpers->allowedPropertyType($value);
                    if($allowedType) {
                        $property_type[] = $allowedType;
                    } else {
                        echo "❌ Skipping property with invalid property type: {$value}\n";
                        return;
                    }
                }
            }
        }

        $property_title = trim($html->find('.mls-prj-title h2', 0)->plaintext ?? '');
        if(empty($title)) {
            echo "❌ Skipping property with invalid setup of html\n ";
            // $this->helpers->updatePostToDraft($url);
            return; 
        }
        
        // Extract the description
        $descriptionElement = $html->find('.mls-prj-section.mls-prj-lay .mls-prj-content', 0);

        // Initialize description as an empty string
        $descriptionHtml = '';

        if ($descriptionElement) {
            // Create new element with same tag but no classes
            $tagName = $descriptionElement->tag;
            $innerContent = $descriptionElement->innertext;
            $descriptionHtml = "<{$tagName}>{$innerContent}</{$tagName}>";
        }

        // Property excerpt
        $plainText = strip_tags($descriptionHtml);
        // Remove excessive whitespace, newlines, and tabs
        $cleanText = preg_replace('/\s+/', ' ', $plainText);
        // Trim leading and trailing whitespace
        $cleanText = trim($cleanText);
        // Create excerpt
        $translatedExcerpt = substr($cleanText, 0, 300);
        
        
         // Price and Currency
        $priceElement = $html->find('.mls-prj-price h3', 0);
        $price = 0;
        $currency = 'EUR';
        if ($priceElement) {
            // Get the price text using innertext to include all content
            $priceText = $priceElement->innertext;
            // Remove HTML tags but keep the text content
            $priceText = strip_tags($priceText);
            $priceText = trim($priceText);
            
            echo "🔍 Debug - Full price text: '$priceText'\n";
            
            // Determine currency
            if (strpos($priceText, '€') !== false) {
                $currency = 'EUR';
            } elseif (strpos($priceText, '$') !== false) {
                $currency = 'USD';
            } elseif (strpos($priceText, '£') !== false) {
                $currency = 'GBP';
            }
            
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
        } else {
            echo "⚠️ Price element not found\n";
        }
        // Check if price extraction failed or resulted in zero/invalid price
        if (empty($price) || !is_numeric($price) || (int)$price <= 0) {
            echo "❌ Skipping property with invalid price. Extracted value: '$price'\n";
            $this->helpers->updatePostToDraft($url);
            return; 
        }

        $bedrooms = 0;
        $bathrooms = 0;
        $size = '';
        $size_prefix = '';

        // Find all feature sections
        $featureElements = $html->find('.mls-prj-section .mls-prj-feature > div');

        foreach ($featureElements as $element) {
            $titleElement = $element->find('.mls-pycf-t', 0);
            $valueElement = $element->find('.mls-pycf-c span', 0);
            
            if ($titleElement && $valueElement) {
                $title = trim($titleElement->plaintext);
                $value = trim($valueElement->plaintext);
                
                // Extract bedrooms
                if (strtolower($title) === 'bedrooms') {
                    $bedrooms = (int)$value;
                }
                
                // Extract bathrooms
                if (strtolower($title) === 'bathrooms') {
                    $bathrooms = (int)$value;
                }
                
                // Extract built area (90 m²)
                if (strtolower($title) === 'built area') {
                    if (preg_match('/(\d+)\s*m/', $value, $matches)) {
                        $size = $matches[1]; // "90"
                        $size_prefix = 'sqm';
                    }
                }
            }
        }

        $property_status = [];
        $status = trim($html->find('.mls-prj-title div.mls-pyc-left', 0)->plaintext ?? '');
        $allowedStatus = $this->helpers->allowedPropertyStatus($status);
        if($allowedStatus) {
            $property_status[] = $status;
        } else {
            echo "❌ Skipping property with invalid property type: {$status}\n";
            return;
        }

        $video_url = '';
        // Extract video URL from iframe
        $iframe = $html->find('#open-video-tour-pop iframe', 0);
        if ($iframe) {
            $src = trim($iframe->getAttribute('src'));
            if ($src) {
                // Extract video ID and convert to standard YouTube format
                if (preg_match('/(?:youtube\.com\/(?:embed\/|shorts\/)|youtu\.be\/)([^?\s&]+)/', $src, $matches)) {
                    $videoId = $matches[1];
                    $video_url = 'https://www.youtube.com/watch?v=' . $videoId;
                    echo "🎥 Video URL extracted: $video_url\n";
                }
            }
        }

        $features = [];

        // Extract features from amenities section
        $amenitiesSection = $html->find('.mls-prj-amenities', 0);

        if ($amenitiesSection) {
            // Find all amenity items
            $amenityItems = $amenitiesSection->find('.mls-prj-amen');
            
            foreach ($amenityItems as $item) {
                // Get the span text (feature name)
                $featureSpans = $item->find('span');
                
                // The second span contains the feature text
                if (count($featureSpans) >= 2) {
                    $featureText = trim($featureSpans[1]->plaintext);
                    
                    if (!empty($featureText)) {
                        $features[] = $featureText;
                    }
                }
            }
        }

        $images = [];

        // Find the image gallery using the lightgallery ID
        $gallery = $html->find('#lightgallery .mls-project-li-wrapper img');

        if ($gallery && count($gallery) > 0) {
            foreach ($gallery as $index => $imgTag) {
                $imageUrl = $imgTag->getAttribute('src');
                
                // Remove URL parameters (everything after ?)
                $imageUrl = preg_replace('/\?.*$/', '', $imageUrl);
                
                // If image URL is valid, add it to the images array
                if (!empty($imageUrl)) {
                    $images[] = $imageUrl;
                }
                
                // Stop after collecting 10 images
                if (count($images) >= 10) {
                    break;
                }
            }
        }

        // Check if we found any images
        if (empty($images)) {
            echo "❌ Skipping property with no images\n";
            return; // Exit the function without scraping
        }


        // Combine into address variable, filtering out empty values
        $addressParts = array_filter([$city, $state, $country]);
        $address = implode(', ', $addressParts);

        $location = '';
        $latitude = '';
        $longitude = '';

        if ($address) {
            $coordsData = $this->helpers->getCoordinatesFromQuery($address);

            if ($coordsData) {
                $location = $coordsData['location'];          // String: "lat, lng"
                $latitude = $coordsData['latitude'];         // Float: latitude
                $longitude = $coordsData['longitude'];       // Float: longitude

                if($country !== $coordsData['country']) {
                    echo "❌ Skipping property with invalid Coordinates, FROM SITE: {$country}, FROM API: {$coordsData['country']} \n Trying another way.....";

                    $addressParts2 = array_filter([$city, $country]);
                    $address2 = implode(', ', $addressParts2);
                    $coordsData2 = $this->helpers->getCoordinatesFromQuery($address2);

                    if ($coordsData2) {
                        $location = $coordsData2['location'];          // String: "lat, lng"
                        $latitude = $coordsData2['latitude'];         // Float: latitude
                        $longitude = $coordsData2['longitude']; 
                        if($country !== $coordsData2['country']) { 
                            echo "❌ Skipping again property with invalid Coordinates, FROM SITE: {$country}, FROM API: {$coordsData2['country']} \n";
                            $this->helpers->updatePostToDraft($url);
                            return;
                        }
                    }
                   
                }
            }
        }
        

        $this->scrapedData[] = [
            "property_title" => $property_title,
            "property_description" => $this->helpers->translateHtmlPreservingTags($descriptionHtml),
            "property_excerpt" => $translatedExcerpt,
            "price" => $price,
            "currency" => $currency,
            "price_postfix" => "",
            "price_prefix" => "",
            "location" => $location,
            "bedrooms" => $bedrooms,
            "bathrooms" => $bathrooms,
            "size" => $size,
            "size_prefix" => $size_prefix,
            "property_type" => $property_type,
            "property_status" => $property_status,
            "property_address" => $address,
            "property_area" => "",
            "city" => $city,
            "state" => $state,
            "country" => $country,
            "zip_code" => '',
            "latitude" => $latitude,
            "longitude" => $longitude,
            "listing_id" => 'HHS-'.$listing_id,
            "agent_id" => "150",
            "agent_display_option" => "agent_info",
            "mls_id" => "",
            "office_name" => "",
            "video_url" => $video_url,
            "virtual_tour" => "",
            "images" => $images,
            "property_map" => "1",
            "property_year" => "",
            "additional_features" => $features,
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
                "Owned By" => "Holiday Homes Spain",
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

