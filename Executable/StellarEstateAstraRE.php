<?php
require_once __DIR__ . '/../simple_html_dom.php';
require_once __DIR__ . '/../Api/ApiSender.php';
require_once __DIR__ . '/../Helpers/ScraperHelpers.php';

class StellarEstateAstraRE {
    private string $baseUrl = "https://astrarealestate.me";
    private string $foldername = "StellarEstateAstraRE";
    private string $filename = "Properties.json";
    private array $propertyLinks = [];
    private array $scrapedData = [];
    private ApiSender $apiSender;
    private ScraperHelpers $helpers;
    private int $successCreated;
    private int $successUpdated;
    private bool $enableUpload = true;
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
            $url = $this->baseUrl . "/properties-for-sale/page/{$page}/";
            
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
        
        foreach ($html->find('.properties-wrapper.items-wrapper .col-sm-6 h2.property-title a') as $a) {
            $href = $a->href ?? '';
            if (strpos($href, '/property/') !== false) {
                // Decode HTML entities
                $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                $fullUrl = strpos($href, 'http') === 0 ? $href : $this->baseUrl . $href;
                $this->propertyLinks[] = $fullUrl;
            }
        }
        $this->propertyLinks = array_unique($this->propertyLinks);
    }

    private function scrapePropertyDetails(simple_html_dom $html, $url): void {
        $property_status[] = "For Sale";

        $title = trim($html->find('h1.single-property-title', 0)->plaintext ?? '');
        if(empty($title)) {
            echo "❌ Skipping property with invalid setup of html\n ";
            $this->helpers->updatePostToDraft($url);
            return; 
        }

        $idElement = $html->find('.id-property', 0);
        if ($idElement) {
            $text = $idElement->plaintext;
            if (preg_match('/Property ID:\s*(\d+)/', $text, $matches)) {
                $listing_id = trim($matches[1]);
            }
        }


        // Extract the property description
        $descriptionElement = $html->find('.description-inner', 0);

        $descriptionHtml = '';
        if ($descriptionElement) {
            $descriptionHtml = $descriptionElement->innertext;
            
            // Optional: Clean up any unwanted attributes if needed
            // $descriptionHtml = preg_replace('/<([^>]+)\s+class="[^"]*"([^>]*)>/', '<$1$2>', $descriptionHtml);
        }

        // For excerpt, get clean text
        $plainText = trim(strip_tags($descriptionHtml));
        $translatedExcerpt = substr($plainText, 0, 300);
        

        // Price and Currency
        $priceElement = $html->find('.property-price div', 0);
        // Alternative: $priceElement = $html->find('h4.property-price .property-price', 0);

        $price = '';
        $currency = '';

        if ($priceElement) {
            try {
                // Get the price text and ensure UTF-8 encoding
                $priceText = trim($priceElement->plaintext);
                
                // Get last character using multibyte function (since € is at the end)
                $lastChar = mb_substr($priceText, -1, 1, 'UTF-8');
                
                // Determine currency based on last character
                if ($lastChar === '€') {
                    $currency = 'EUR';
                } elseif ($lastChar === '$') {
                    $currency = 'USD';
                } else {
                    // Fallback: check if it contains euro symbol anywhere
                    if (mb_strpos($priceText, '€', 0, 'UTF-8') !== false) {
                        $currency = 'EUR';
                    } else {
                        $currency = 'EUR'; // Default to EUR
                    }
                }

                // Extract numeric value - handle "from" prefix
                $numericPrice = preg_replace('/[^0-9,]/', '', $priceText);
                $price = (int)str_replace(',', '', $numericPrice);
                
                // echo "✅ Price extracted: $price $currency (from text: $priceText)\n";
                
            } catch (Exception $e) {
                echo "Error extracting price: " . $e->getMessage() . "\n";
                $price = 0;
                $currency = 'EUR';
            }
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

        // Find the detail-metas-top container
        $detailMetas = $html->find('.detail-metas-top', 0);

        if ($detailMetas) {
            // Find all property-meta divs
            $metaElements = $detailMetas->find('.property-meta');
            
            foreach ($metaElements as $element) {
                $text = trim($element->plaintext);
                
                // Extract bedrooms (looking for "Bedrooms 3")
                if (preg_match('/Bedrooms\s+(\d+)/i', $text, $matches)) {
                    $bedrooms = (int)$matches[1];
                }
                
                // Extract bathrooms (looking for "Baths 2")
                if (preg_match('/Baths?\s+(\d+)/i', $text, $matches)) {
                    $bathrooms = (int)$matches[1];
                }
                
                // Extract size (looking for "133.09 m2")
                if (preg_match('/([\d.]+)\s*m2?/i', $text, $matches)) {
                    $size = (int)$matches[1]; 
                    $size_prefix = 'sqm';
                }
            }
        }
        

        $features = [];
        // Extract features using the specific ul class
        $featuresList = $html->find('ul.columns-gap.list-check li', 0);

        if ($featuresList) {
            $featureItems = $html->find('ul.columns-gap.list-check li');
            
            foreach ($featureItems as $item) {
                $featureText = trim($item->plaintext);
                
                if (!empty($featureText)) {
                    $features[] = $featureText;
                }
            }
        }

        $images = [];
        // Find the image gallery using the property-detail-gallery class
        $gallery = $html->find('.property-detail-gallery .gallery-grid a');
        if ($gallery && count($gallery) > 0) {
            foreach ($gallery as $index => $linkTag) {
                $imageUrl = $linkTag->getAttribute('href');
                
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
            $this->helpers->updatePostToDraft($url);
            return; // Exit the function without scraping
        }

        $latitude = '';
        $longitude = '';
        $location = '';
        // $address_data = [];

        // Extract latitude and longitude from the HTML divs
        $latElement = $html->find('.latitude', 0);
        $lngElement = $html->find('.longitude', 0);

        if ($latElement && $lngElement) {
            $latitude = (float)trim($latElement->plaintext);
            $longitude = (float)trim($lngElement->plaintext);
            
            if ($latitude && $longitude) {
                $location = $latitude . ', ' . $longitude;
                // $address_data = $this->helpers->getLocationDataByCoords($lat, $lng) ?? [];
            }
        }

        $property_type = [];
        $foundValidType = false;

        // First, check the entire title
        $allowedType = $this->helpers->allowedPropertyType($title);
        if ($allowedType) {
            $property_type[] = $allowedType;
            $foundValidType = true;
        }

        // Then check individual words
        $words = preg_split('/\s+/', $title);
        foreach ($words as $word) {
            $word = trim($word, '.,!?;:-'); // Remove punctuation
            
            if (!empty($word)) {
                $allowedType = $this->helpers->allowedPropertyType($word);
                
                if ($allowedType && !in_array($allowedType, $property_type)) {
                    $property_type[] = $allowedType;
                    $foundValidType = true;
                }
            }
        }

        // Check if we found at least one valid property type
        if (!$foundValidType) {
            echo "❌ Skipping property with invalid property type. Title: '{$title}'\n";
            $this->helpers->updatePostToDraft($url);
            return;
        }


        $city = '';
        $area = '';
        $country = '';

        // Find the property location element
        $locationElement = $html->find('h3.property-location', 0);

        if ($locationElement) {
            // Extract area and city from the links
            $locationLinks = $locationElement->find('.property-location a');
            
            if (count($locationLinks) >= 2) {
                $area = trim($locationLinks[0]->plaintext); // Bečići
                $city = trim($locationLinks[1]->plaintext); // Budva
            } elseif (count($locationLinks) == 1) {
                $city = trim($locationLinks[0]->plaintext);
            }
            
            // Extract country from the span
            $countryElement = $locationElement->find('.country-location', 0);
            if ($countryElement) {
                $country = trim($countryElement->plaintext) ?? "Montenegro"; // Montenegro
            }
        }

        // Create address by imploding non-empty values
        $addressParts = array_filter([$area, $city, $country]);
        $address = implode(', ', $addressParts);

        $this->scrapedData[] = [
            "property_title" => $title,
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
            "property_area" => $area,
            "city" => $city,
            "state" => "",
            "country" => $country,
            "zip_code" => '',
            "latitude" => $latitude,
            "longitude" => $longitude,
            "listing_id" => 'SEARE-'.$listing_id,
            "agent_id" => "150",
            "agent_display_option" => "agent_info",
            "mls_id" => "",
            "office_name" => "",
            "video_url" => "",
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
                "Owned By" => "Stellar Estate (Astra Real Estate)",
                "Contact Person" => "Igor Brković",
                "Phone" => "+382 67 209 469",
                "Email" => "igor.brkovic@astrarealestate.me"
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

