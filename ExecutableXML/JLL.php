<?php
require_once __DIR__ . '/../Api/ApiSender.php';
require_once __DIR__ . '/../Helpers/ScraperHelpers.php';

class JLL {
    private string $xmlUrl = "https://media.egorealestate.com/XML/1320/Properties/Properties_XML_1320.xml";
    private string $foldername = "JLL";
    private string $filename = "JLLv2.json";
    private array $scrapedData = [];
    private ApiSender $apiSender;
    private ScraperHelpers $helpers;
    private int $successCreated;
    private int $successUpdated;
    private bool $enableUpload = true;
    private bool $testingMode = false;
    private array $confidentialInfo = [];

    public function setTestingMode(bool $mode): void {
        $this->testingMode = $mode;
    }

    public function setConfidentialInfo(array $confidentialInfo): void {
        $this->confidentialInfo = $confidentialInfo;
    }

    public function __construct() {
        $this->apiSender = new ApiSender(true);
        $this->helpers = new ScraperHelpers();
        $this->successCreated = 0;
        $this->successUpdated = 0;
    }

    public function run(int $limit = 0): void {
        $folder = __DIR__ . '/../ScrapeFile/'.$this->foldername;
        $outputFile = $folder . '/'.$this->filename;
        
        // Create the folder if it doesn't exist
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        echo "🌐 Fetching XML feed from: {$this->xmlUrl}\n";

        // Fetch XML content
        $xmlContent = $this->fetchXmlContent();
        if (!$xmlContent) {
            echo "❌ Failed to fetch XML content\n";
            return;
        }

        // Parse XML
        $properties = $this->parseXmlProperties($xmlContent);
        if (empty($properties)) {
            echo "❌ No properties found in XML\n";
            return;
        }

        // Apply limit if specified
        if ($limit > 0) {
            $properties = array_slice($properties, 0, $limit);
        }

        echo "📊 Total properties to process: " . count($properties) . "\n\n";

        // Start a fresh JSON array
        file_put_contents($outputFile, "[");

        $propertyCounter = 0;
        foreach ($properties as $index => $property) {
            $startUpload = 370;
            echo "🏠 Processing property " . ($index + 1) . "/" . count($properties) . "\n";
            
            $propertyData = $this->processProperty($property);
            
            if (!empty($propertyData)) {
                $jsonEntry = json_encode($propertyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                file_put_contents($outputFile, ($propertyCounter > 0 ? "," : "") . "\n" . $jsonEntry, FILE_APPEND);
                $propertyCounter++;

                // Send the property data via the ApiSender
                if ($this->enableUpload && $startUpload <= $index) {
                    $result = $this->apiSender->sendProperty($propertyData);
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

        // Close the JSON array
        file_put_contents($outputFile, "\n]", FILE_APPEND);

        echo "✅ Scraping completed. Output saved to {$outputFile}\n";
        echo "✅ Properties Created: {$this->successCreated}\n";
        echo "✅ Properties Updated: {$this->successUpdated}\n";
    }

    private string $loginUrl = "https://media.egorealestate.com/XML/1320/Properties/login.aspx";
    private string $username = "cobeegoproperties";
    private string $password = "5Cx!propertiestaB!04";
    
    private function fetchXmlContent(): ?string {
        echo "Starting authentication process...\n";
        
        // Get login form data
        $formData = $this->getLoginFormData();
        if (!$formData) {
            echo "Failed to get login form data\n";
            return null;
        }
        
        // Submit login and get XML
        $cookieJar = tempnam(sys_get_temp_dir(), 'ego_cookies');
        $loginSuccess = $this->submitLogin($formData, $cookieJar);
        
        if (!$loginSuccess) {
            echo "Login failed\n";
            unlink($cookieJar);
            return null;
        }
        
        $xmlData = $this->fetchAuthenticatedXml($cookieJar);
        unlink($cookieJar);
        
        return $xmlData;
    }
    
    private function getLoginFormData(): ?array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->loginUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$html) {
            return null;
        }
        
        $formData = [];
        
        // Extract ASP.NET hidden fields
        if (preg_match('/<input[^>]*name="__VIEWSTATE"[^>]*value="([^"]*)"[^>]*>/i', $html, $matches)) {
            $formData['__VIEWSTATE'] = $matches[1];
        }
        if (preg_match('/<input[^>]*name="__VIEWSTATEGENERATOR"[^>]*value="([^"]*)"[^>]*>/i', $html, $matches)) {
            $formData['__VIEWSTATEGENERATOR'] = $matches[1];
        }
        if (preg_match('/<input[^>]*name="__EVENTVALIDATION"[^>]*value="([^"]*)"[^>]*>/i', $html, $matches)) {
            $formData['__EVENTVALIDATION'] = $matches[1];
        }
        
        return $formData;
    }
    
    private function submitLogin(array $formData, string $cookieJar): bool {
        $postData = array_merge($formData, [
            'UserEmail' => $this->username,
            'UserPass' => $this->password,
            'ctl02' => 'Logon'
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->loginUrl . "?ReturnUrl=" . urlencode("/XML/1320/Properties/Properties_XML_1320.xml"),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_COOKIEJAR => $cookieJar,
            CURLOPT_COOKIEFILE => $cookieJar,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: ' . $this->loginUrl
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        
        return ($httpCode === 200 && strpos($finalUrl, 'login.aspx') === false);
    }
    
    private function fetchAuthenticatedXml(string $cookieJar): ?string {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->xmlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $cookieJar,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: application/xml, text/xml, */*',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode === 200) ? $result : null;
    }

    private function fetchXmlWithCurl(): ?string {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->xmlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; PropertyScraper/1.0)',
            CURLOPT_HTTPHEADER => [
                'Accept: application/xml, text/xml, */*',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '', // Let cURL handle compression automatically
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($result === false || $httpCode !== 200) {
            echo "❌ cURL failed. HTTP Code: $httpCode\n";
            if ($contentType) {
                echo "⚠️ Content-Type: $contentType\n";
            }
            return null;
        }

        return $result;
    }

    private function isGzipCompressed(string $content): bool {
        // Check for gzip magic number (1f 8b)
        return substr($content, 0, 2) === "\x1f\x8b";
    }

    private function parseXmlProperties(string $xmlContent): array {
        // Suppress XML parsing errors and handle them manually
        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            echo "❌ XML parsing errors:\n";
            foreach ($errors as $error) {
                echo "  - " . trim($error->message) . "\n";
            }
            return [];
        }

        // Convert SimpleXML to array for easier processing
        $properties = [];
        
        // Handle the specific <Report><Row> structure
        if (isset($xml->Row)) {
            foreach ($xml->Row as $row) {
                $properties[] = $row;
            }
        } else {
            echo "❌ No <Row> elements found in <Report>\n";
            return [];
        }

        echo "📋 Found " . count($properties) . " properties in XML\n";
        
        if ($this->testingMode && count($properties) > 0) {
            // Save first property structure for debugging
            $debugFile = __DIR__ . '/../ScrapeFile/'.$this->foldername.'/xml_structure_debug.txt';
            file_put_contents($debugFile, print_r($properties[0], true));
            echo "🔍 Debug: First property structure saved to xml_structure_debug.txt\n";
        }

        return $properties;
    }

    private function processProperty(SimpleXMLElement $property): ?array {
        try {
            // Extract basic property information using actual XML field names
            
            $description = $this->getXmlValue($property, ['Description_en-gb']);
            $listing_id = $this->getXmlValue($property, ['Reference']);
            
            // // Price extraction
            $price = $this->extractPrice($property);
            $currency = $this->getXmlValue($property, ['Currency']) ?: 'EUR';

            if (empty($price) || !is_numeric($price) || (int)$price <= 0) {
                echo "❌ Skipping property with invalid price. Extracted value: '$price'\n";
                // $this->helpers->updatePostToDraft($url);
                return null;
            }

            // // Property details
            $bedrooms = (int)$this->getXmlValue($property, ['Bedrooms']);
            $bathrooms = (int)$this->getXmlValue($property, ['Bathrooms']);
            $size = $this->extractSize($property);
            $size_prefix = 'sqm';

            // // Property type and status
            $property_type = $this->extractPropertyTypes($property);
            if (empty($property_type)) {
                echo "❌ Skipping property with invalid type\n";
                return null;
            }

            $property_status = $this->extractPropertyStatus($property);
            if (empty($property_status)) {
                $property_status[] = "For Sale";
             }

            // // Location information
            $city = $this->getXmlValue($property, ['City', 'Town']);
            $state = $this->getXmlValue($property, ['State', 'Region', 'Province']);
            $country = $this->getXmlValue($property, ['Country']) ?: 'Portugal'; // Based on zip format
            $area = $this->getXmlValue($property, ['Area', 'District', 'Zone']);
            $zipcode = $this->getXmlValue($property, ['Zipcode', 'PostalCode']);

            // // Build full address
            $addressParts = array_filter([$area, $city, $zipcode, $country]);
            $address = implode(', ', $addressParts);

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
                                return null;
                            }
                        }
                    
                    }
                }
            }

            // Images
            $images = $this->extractImages($property);

            // Video URL
            $video_url = $this->getXmlValue($property, ['VideoLink']);

            // Create excerpt from description
            $plainText = strip_tags($description);
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $plainText = trim($plainText);
            $excerpt = substr($plainText, 0, 300);

            if($bedrooms > 0) {
                $property = $property_type[0];
                $title = "{$bedrooms} Bedroom" . ($bedrooms > 1 ? 's' : '') . " {$property} in " . ($city ?: $area);
            } else {
                $property = $property_type[0];
                $title = "{$property} in " . ($city ?: $area);
            }

            return [
                "property_title" => $title,
                "property_description" => $this->helpers->translateHtmlPreservingTags($description),
                "property_excerpt" => $excerpt,
                "price" => (int)$price,
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
                "state" => $state,
                "country" => $country,
                "zip_code" => $zipcode,
                "latitude" => $latitude,
                "longitude" => $longitude,
                "listing_id" => 'JLL-' . $listing_id,
                "agent_id" => "150",
                "agent_display_option" => "agent_info",
                "mls_id" => "",
                "office_name" => "",
                "video_url" => $video_url,
                "virtual_tour" => "",
                "images" => $images,
                "property_map" => "1",
                "property_year" => "",
                "additional_features" => "",
                "confidential_info" => $this->buildConfidentialInfo($this->xmlUrl)
            ];

        } catch (Exception $e) {
            echo "❌ Error processing property: " . $e->getMessage() . "\n";
            return null;
        }
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
                "Owned By" => "JLL Portugal",
                "Contact Person" => "João Reis",
                "Phone" => "+351 917 045 905",
                "Email" => "joao.reis@jll.com"
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

    private function getXmlValue(SimpleXMLElement $xml, array $possibleFields): string {
        foreach ($possibleFields as $field) {
            if (isset($xml->$field)) {
                return trim((string)$xml->$field);
            }
            
            // Try with xpath for nested elements
            $nodes = $xml->xpath(".//$field");
            if (!empty($nodes)) {
                return trim((string)$nodes[0]);
            }
        }
        return '';
    }

    private function extractPrice(SimpleXMLElement $property): int {
        // Try different price fields based on actual XML structure
        $priceFields = ['Sale'];
        
        foreach ($priceFields as $field) {
            $priceValue = $this->getXmlValue($property, [$field]);
            
            if (!empty($priceValue)) {
                // Handle the Sale field which might be boolean (0/1) - skip if 0
                if ($field === 'Sale' && $priceValue === '0') {
                    continue;
                }
                
                // Remove currency symbols and formatting
                $cleanPrice = preg_replace('/[^\d.]/', '', $priceValue);
                
                if (is_numeric($cleanPrice) && (float)$cleanPrice > 0) {
                    return (int)$cleanPrice;
                }
            }
        }
        
        return 0;
    }

    private function extractSize(SimpleXMLElement $property): int {
        // Try different size fields based on actual XML structure
        $sizeFields = ['GrossArea'];
        
        foreach ($sizeFields as $field) {
            $sizeValue = $this->getXmlValue($property, [$field]);
            
            if (!empty($sizeValue)) {
                $cleanSize = preg_replace('/[^\d.]/', '', $sizeValue);
                
                if (is_numeric($cleanSize) && (float)$cleanSize > 0) {
                    return (int)$cleanSize;
                }
            }
        }
        
        return 0;
    }

    public function extractPropertyTypes($property): array {
        $typeFields = ['Propertytype', 'Type', 'PropertyType', 'Category'];
        $types = [];
        
        foreach ($typeFields as $field) {
            $typeValue = $this->getXmlValue($property, [$field]);
            
            if (!empty($typeValue)) {
                // Process individual words
                $words = preg_split('/\s+/', $typeValue);
                foreach ($words as $word) {
                    $word = trim($word, '.,!?;:-');
                    
                    if (!empty($word)) {
                        $allowedType = $this->helpers->allowedPropertyType($word);
                        
                        if ($allowedType && !in_array($allowedType, $types)) {
                            $types[] = $allowedType;
                        }
                    }
                }
            }
        }
        
        return array_unique($types);
    }

    private function extractPropertyStatus(SimpleXMLElement $property): array {
        $statusFields = ['Status', 'PropertyStatus', 'ListingStatus', 'Sale'];
        $statuses = [];
        
        foreach ($statusFields as $field) {
            $statusValue = $this->getXmlValue($property, [$field]);
            
            if (!empty($statusValue)) {
                // Handle boolean Sale field
                if ($field === 'Sale') {
                    if ($statusValue === '1' || strtolower($statusValue) === 'true') {
                        $statuses[] = 'For Sale';
                    }
                } else {
                    // Use helper method for consistent mapping
                    $allowedStatus = $this->helpers->allowedPropertyStatus($statusValue);
                    
                    if ($allowedStatus && !in_array($allowedStatus, $statuses)) {
                        $statuses[] = $statusValue;
                    }
                }
            }
        }
        
        return array_unique($statuses);
    }

    private function extractImages(SimpleXMLElement $property): array {
        $images = [];
        
        // Try different possible image structures
        $imageFields = ['ImageLink'];
        
        foreach ($imageFields as $field) {
            if (isset($property->$field)) {
                $imageContainer = $property->$field;
                
                // Handle different XML structures for images
                if (isset($imageContainer->image)) {
                    // <images><image>url</image></images>
                    foreach ($imageContainer->image as $img) {
                        $imageUrl = trim((string)$img);
                        if (!empty($imageUrl)) {
                            $images[] = $imageUrl;
                        }
                    }
                } elseif (isset($imageContainer->url)) {
                    // <images><url>url</url></images>
                    foreach ($imageContainer->url as $img) {
                        $imageUrl = trim((string)$img);
                        if (!empty($imageUrl)) {
                            $images[] = $imageUrl;
                        }
                    }
                } else {
                    // Direct URL in field
                    $imageUrl = trim((string)$imageContainer);
                    if (!empty($imageUrl)) {
                        $images[] = $imageUrl;
                    }
                }
                
                if (!empty($images)) {
                    break; // Found images, no need to check other fields
                }
            }
        }
        
        // Remove duplicates and limit to 10 images
        $images = array_unique($images);
        // Split any comma-separated URLs and remove parameters
        $images = array_filter(array_map('trim', explode(',', implode(',', $images))));
        $images = array_map(fn($url) => strtok($url, '?'), $images);
        
        return array_slice($images, 0, 10);
    }

    private function extractFeatures(SimpleXMLElement $property): array {
        $features = [];
        
        $featureFields = ['features', 'amenities', 'facilities', 'extras'];
        
        foreach ($featureFields as $field) {
            if (isset($property->$field)) {
                $featureContainer = $property->$field;
                
                if (isset($featureContainer->feature)) {
                    foreach ($featureContainer->feature as $feature) {
                        $featureText = trim((string)$feature);
                        if (!empty($featureText)) {
                            $features[] = $featureText;
                        }
                    }
                } elseif (isset($featureContainer->item)) {
                    foreach ($featureContainer->item as $feature) {
                        $featureText = trim((string)$feature);
                        if (!empty($featureText)) {
                            $features[] = $featureText;
                        }
                    }
                } else {
                    // Try to parse comma-separated features
                    $featureText = trim((string)$featureContainer);
                    if (!empty($featureText)) {
                        $parsedFeatures = array_map('trim', explode(',', $featureText));
                        $features = array_merge($features, $parsedFeatures);
                    }
                }
            }
        }
        
        return array_filter(array_unique($features));
    }
}

