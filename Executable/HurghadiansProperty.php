<?php
require_once __DIR__ . '/../simple_html_dom.php';
require_once __DIR__ . '/../Api/ApiSender.php';
require_once __DIR__ . '/../Helpers/ScraperHelpers.php';

class HurghadiansProperty {
    private string $baseUrl = "https://hurghadiansproperty.com";
    private string $foldername = "HurghadiansProperty";
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
            $url = $this->baseUrl . "/buy-a-home/page/{$page}/";
            
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
        
        foreach ($html->find('.rh-ultra-half-layout-list .rh-ultra-property-card a.rh-permalink') as $a) {
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
        $title = trim($html->find('h1.property-title', 0)->plaintext ?? '');
        if(empty($title)) {
            echo "❌ Skipping property with invalid setup of html\n ";
            $this->helpers->updatePostToDraft($url);
            return; 
        }

        // Extract and clean property description
        $result = $this->extractPropertyDescription($html);
        $descriptionHtml = $result['html'];
        $translatedExcerpt = $result['excerpt'];

        $priceElement = $html->find('span.rh-ultra-price', 0);
        $price = 0;
        $currency = 'EUR';

        if ($priceElement) {
            $priceText = trim($priceElement->plaintext);
            
            // Get currency
            if (strpos($priceText, '€') !== false) $currency = 'EUR';
            elseif (strpos($priceText, '$') !== false) $currency = 'USD';
            elseif (strpos($priceText, '£') !== false) $currency = 'GBP';
            
            // Get price number (remove everything except digits, then convert to int to remove decimals)
            $priceNumbers = preg_replace('/[^\d.]/', '', $priceText);
            $price = (int)floatval($priceNumbers);
        }

         // Check if price extraction failed or resulted in zero/invalid price
        if (empty($price) || !is_numeric($price) || (int)$price <= 0) {
            echo "❌ Skipping property with invalid price. Extracted value: '$price'\n";
            $this->helpers->updatePostToDraft($url);
            return; 
        }

        $propertyIdElement = $html->find('div.rh-property-id span', 1);
        $listing_id = '';

        if ($propertyIdElement) {
            $propertyIdText = trim($propertyIdElement->plaintext);
            // Remove "-property" suffix
            $listing_id = str_replace('-property', '', $propertyIdText);
        }

        $bedrooms = 0;
        $bathrooms = 0;
        $yearBuilt = '';
        $size = '';
        $size_prefix = '';

        // Get all property meta sections
        $metaSections = $html->find('div.rh_ultra_prop_card__meta');

        foreach ($metaSections as $section) {
            $label = $section->find('span.rh-ultra-meta-label', 0);
            
            if ($label) {
                $labelText = strtolower(trim($label->plaintext));
                $figure = $section->find('span.figure', 0);
                $labelSpan = $section->find('span.label', 0);
                
                if ($labelText === 'bedrooms') {
                    $bedrooms = $figure ? (int)trim($figure->plaintext) : 0;
                }
                
                if ($labelText === 'bathrooms') {
                    $bathrooms = $figure ? (int)trim($figure->plaintext) : 0;
                }
                
                if ($labelText === 'year built') {
                    $yearBuilt = $figure ? trim($figure->plaintext) : '';
                }
                
                if ($labelText === 'area') {
                    $size = $figure ? trim($figure->plaintext) : '';
                    if ($labelSpan) {
                        $labelText = strtolower(trim($labelSpan->plaintext));
                        // Check if it contains square meter variations
                        if (preg_match('/(\d+)\s*m/', $labelText, $matches)) {
                            $size_prefix = 'sqm';
                        } elseif (strpos($labelText, 'sqm') !== false || strpos($labelText, 'm2') !== false || strpos($labelText, 'm²') !== false) {
                            $size_prefix = 'sqm';
                        } elseif (strpos($labelText, 'sqft') !== false || strpos($labelText, 'ft²') !== false) {
                            $size_prefix = 'sqft';
                        } else {
                            $size_prefix = '';
                        }
                    }
                }
            }
        }

        $property_type = [];
        $property_status = [];
        $hasValidType = false;
        $hasValidStatus = false;

        // Get all property tags
        $propertyTags = $html->find('div.rh-ultra-property-tags a');

        foreach ($propertyTags as $tag) {
            $tagText = trim($tag->plaintext);
            $tagClass = $tag->class;
            $href = $tag->href;
            
            // Check if it's a property type (has rh-ultra-type class)
            if (strpos($tagClass, 'rh-ultra-type') !== false) {
                $allowedType = $this->helpers->allowedPropertyType($tagText);
                if($allowedType) {
                    $property_type[] = $allowedType;
                    $hasValidType = true;
                }
            }
            
            // Check if it's a property status (has rh-ultra-status class)
            if (strpos($tagClass, 'rh-ultra-status') !== false) {
                // Check both text and href for status validation
                $allowedStatus = $this->helpers->allowedPropertyStatus($tagText);
                
                // If text fails, check href for "for-sale" pattern
                if (!$allowedStatus && $href) {
                    if (strpos($href, 'for-sale') !== false) {
                        $allowedStatus = 'For Sale';
                    }
                }
                
                if($allowedStatus) {
                    $property_status[] = $tagText;
                    $hasValidStatus = true;
                }
            }
        }

        // Check if we found valid type and status after processing all tags
        if (!$hasValidType) {
            echo "❌ Skipping property - no valid property type found\n";
            $this->helpers->updatePostToDraft($url);
            return;
        }

        if (!$hasValidStatus) {
            echo "❌ Skipping property - no valid property status found\n";
            $this->helpers->updatePostToDraft($url);
            return;
        }

        $video_url = '';

        // Look for iframe with data-lazy-src first (for lazy-loaded videos)
        $iframe = $html->find('figure.wp-block-embed-vimeo iframe[data-lazy-src]', 0);

        if ($iframe) {
            $video_url = $iframe->getAttribute('data-lazy-src');
        } else {
            // Fallback to regular src attribute
            $iframe = $html->find('figure.wp-block-embed-vimeo iframe[src]', 0);
            if ($iframe) {
                $video_url = $iframe->getAttribute('src');
                // Skip if src is placeholder
                if ($video_url === 'about:blank') {
                    $video_url = '';
                }
            }
        }

        // If still no video found, check noscript iframe
        if (empty($video_url)) {
            $noscriptIframe = $html->find('figure.wp-block-embed-vimeo noscript iframe', 0);
            if ($noscriptIframe) {
                $video_url = $noscriptIframe->getAttribute('src');
            }
        }


        $images = [];

        // Find images in the property slider
        $sliderImages = $html->find('.rh-ultra-property-slider a[data-bg]');

        if ($sliderImages && count($sliderImages) > 0) {
            foreach ($sliderImages as $index => $imgLink) {
                $imageUrl = $imgLink->getAttribute('data-bg');
                
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

        // If no images found in slider, try alternative selector
        if (empty($images)) {
            $altImages = $html->find('.rh-ultra-property-slider a[href]');
            
            if ($altImages && count($altImages) > 0) {
                foreach ($altImages as $index => $imgLink) {
                    $imageUrl = $imgLink->getAttribute('href');
                    
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
        }

        // Check if we found any images
        if (empty($images)) {
            echo "❌ Skipping property with no images\n";
            $this->helpers->updatePostToDraft($url);
            return; // Exit the function without scraping
        }

        $features = [];
        // Find all feature items in the features section
        $featureItems = $html->find('.rh_property__features li.rh_property__feature a');

        if ($featureItems && count($featureItems) > 0) {
            foreach ($featureItems as $featureLink) {
                $featureText = trim($featureLink->plaintext);
                
                if (!empty($featureText)) {
                    $features[] = $featureText;
                }
            }
        }

        $latitude = '';
        $longitude = '';
        $location = '';

        // Find the first JSON-LD script tag without a class attribute
        $jsonScripts = $html->find('script[type="application/ld+json"]');

        foreach ($jsonScripts as $script) {
            // Skip if it has a class attribute (like class="aioseo-schema")
            if ($script->hasAttribute('class')) {
                continue;
            }
            
            $jsonContent = $script->innertext;
            
            // Decode the JSON
            $data = json_decode($jsonContent, true);
            
            if ($data && isset($data['geo'])) {
                $geo = $data['geo'];
                
                if (isset($geo['latitude']) && isset($geo['longitude'])) {
                    $latitude = $geo['latitude'];
                    $longitude = $geo['longitude'];
                    break; // Exit after finding the first valid coordinates
                }
            }
        }

        // Break if we found both values
        if ($latitude && $longitude) {
            $location = $latitude.', '.$longitude;
            $address_data = $this->helpers->getLocationDataByCoords($latitude, $longitude) ?? [];
        }

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
            "property_address" => $address_data['address'],
            "property_area" => "",
            "city" => $address_data['city'],
            "state" => $address_data['state'],
            "country" => $address_data['country'],
            "zip_code" => $address_data['postal_code'],
            "latitude" => $latitude,
            "longitude" => $longitude,
            "listing_id" => $listing_id,
            "agent_id" => "150",
            "agent_display_option" => "agent_info",
            "mls_id" => "",
            "office_name" => "",
            "video_url" => $video_url,
            "virtual_tour" => "",
            "images" => $images,
            "property_map" => "1",
            "property_year" => $yearBuilt,
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
                "Owned By" => "Hurghadians Property",
                "Contact Person" => "Akram Amin",
                "Phone" => "+20 128 7182 750",
                "Email" => "akram@hurghadiansproperty.com"
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
    // Clean HTML content by removing unwanted elements
    private function cleanHtmlContent($html) {
        // Remove iframe elements and everything inside them
        $html = preg_replace('/<iframe[^>]*>.*?<\/iframe>/si', '', $html);
        
        // Remove figure elements that contain iframes/images
        $html = preg_replace('/<figure[^>]*>.*?<\/figure>/si', '', $html);
        
        // Remove style blocks
        $html = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html);
        
        // Remove the entire author bio section
        $html = preg_replace('/<div[^>]*class="[^"]*aioseo-author-bio-compact[^"]*"[^>]*>.*?<\/div>\s*<\/div>/si', '', $html);
        
        // Remove noscript tags
        $html = preg_replace('/<noscript[^>]*>.*?<\/noscript>/si', '', $html);
        
        // Remove img tags
        $html = preg_replace('/<img[^>]*>/i', '', $html);
        
        // Remove all class attributes
        $html = preg_replace('/\s*class="[^"]*"/i', '', $html);
        
        // Remove empty title attributes
        $html = preg_replace('/\s*title=""\s*/i', '', $html);
        
        // Clean up extra whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);
        
        // Remove any orphaned closing </div> tags at the very end
        $html = preg_replace('/<\/div>\s*$/i', '', $html);
        
        return $html;
    }

    // Extract and clean property description
    private function extractPropertyDescription($html) {
        // Find the description content
        $descriptionElement = $html->find('.rh-content-wrapper .rh_content', 0);
        
        if (!$descriptionElement) {
            return ['html' => '', 'excerpt' => ''];
        }
        
        // Get innertext
        $content = $descriptionElement->innertext;
        
        // Clean the content
        $cleanContent = $this->cleanHtmlContent($content);
        
        // Use the cleaned content as-is
        $descriptionHtml = $cleanContent;
        
        // Create clean excerpt
        $plainText = strip_tags($cleanContent);
        $cleanText = trim(preg_replace('/\s+/', ' ', $plainText));
        $excerpt = substr($cleanContent, 0, 300);
        
        return [
            'html' => $descriptionHtml,
            'excerpt' => $excerpt
        ];
    }
}

