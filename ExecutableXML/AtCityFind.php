<?php
require_once __DIR__ . '/../Api/ApiSender.php';
require_once __DIR__ . '/../Helpers/ScraperHelpers.php';
require_once __DIR__ . '/../Helpers/XMLHelpers.php';

class AtCityFind {
    private string $foldername;
    private string $filename;
    private array $scrapedData = [];
    private ApiSender $apiSender;
    private ScraperHelpers $helpers;
    private XMLHelpers $xmlHelpers;
    private int $successCreated;
    private int $successUpdated;
    private bool $enableUpload = true;
    private bool $testingMode = false;
    private string $xmlSource = '';
    private array $config = [];
    private array $confidentialInfo = [];
    
    public function __construct(string $foldername = 'AtCityFind', string $filename = 'properties.json') {
        $this->foldername = $foldername;
        $this->filename = $filename;
        // Add timestamp to filename
        // $timestamp = date('Y-m-d_H-i-s');
        // $filenameParts = pathinfo($filename);
        // $this->filename = $filenameParts['filename'] . '_' . $timestamp . '.' . $filenameParts['extension'];
        
        $this->apiSender = new ApiSender(true);
        $this->helpers = new ScraperHelpers();
        $this->xmlHelpers = new XMLHelpers();
        $this->successCreated = 0;
        $this->successUpdated = 0;
        $this->config = $this->getDefaultConfig();
    }

    public function setConfidentialInfo(array $confidentialInfo): void {
        $this->confidentialInfo = $confidentialInfo;
    }

    public function setTestingMode(bool $mode): void {
        $this->testingMode = $mode;
    }

    public function enableUpload(bool $enable = true): void {
        $this->enableUpload = $enable;
    }

    public function setConfig(array $config): void {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Main method - automatically detects input type and processes accordingly
     * 
     * @param string $xmlInput - Can be URL, file path, or XML string content
     * @param int $limit - Limit number of properties to process (0 = no limit)
     * @return bool - Success status
     */
    public function run(string $xmlInput, int $limit = 0, array $confidentialInfo = []): bool {
         if (!empty($confidentialInfo)) {
            // Extract listing_id_prefix if provided
            if (isset($confidentialInfo['listing_id_prefix'])) {
                $this->config['listing_id_prefix'] = $confidentialInfo['listing_id_prefix'];
                unset($confidentialInfo['listing_id_prefix']);
            }
            
            $this->setConfidentialInfo($confidentialInfo);
        }

        return $this->xmlHelpers->runXML($xmlInput, $limit, $this, 'processXmlContent');
    }

    public function processXmlContent(string $xmlContent, int $limit): bool {
        $folder = __DIR__ . '/../ScrapeFile/' . $this->foldername;
        $outputFile = $folder . '/' . $this->filename;
        
        // Create the folder if it doesn't exist
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        // Validate and parse XML
        if (!$this->xmlHelpers->validateXml($xmlContent)) {
            return false;
        }

        $properties = $this->parseXmlProperties($xmlContent);
        if (empty($properties)) {
            echo "❌ No properties found in XML\n";
            return false;
        }

        $startFrom = 0; 
        $properties = array_slice($properties, $startFrom);
        
        echo "📊 Starting from property " . ($startFrom + 1) . "\n";
        echo "📊 Total properties to process: " . count($properties) . "\n\n";

        // Apply limit if specified (after skipping)
        if ($limit > 0) {
            $properties = array_slice($properties, 0, $limit);
        }

        // Start a fresh JSON array
        file_put_contents($outputFile, "[");

        $propertyCounter = 0;
        foreach ($properties as $index => $property) {
            $actualIndex = $startFrom + $index + 1; // Actual property number
            echo "🏠 Processing property " . $actualIndex . "\n";
            $propertyData = $this->processProperty($property);
            
            if (!empty($propertyData)) {
                $jsonEntry = json_encode($propertyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                file_put_contents($outputFile, ($propertyCounter > 0 ? "," : "") . "\n" . $jsonEntry, FILE_APPEND);
                $propertyCounter++;

                // Send the property data via the ApiSender
                if ($this->enableUpload) {
                    $result = $this->apiSender->sendProperty($propertyData);
                    if ($result['success']) {
                        echo "✅ Success after {$result['attempts']} attempt(s)\n";
                        if (count($result['response']['updated_properties']) > 0) {
                            echo "✅ Updated # " . ++$this->successUpdated . "\n";
                        } else {
                            echo "✅ Created # " . ++$this->successCreated . "\n";
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
        
        return true;
    }
    private function parseXmlProperties(string $xmlContent): array {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            return [];
        }

        $properties = [];
        
        // Try different common XML structures for properties
        $possibleStructures = [
            'Row',           // <Report><Row> - Your current structure
            'property',      // <properties><property>
            'Property',      // <Properties><Property>
            'listing',       // <listings><listing>
            'Listing',       // <Listings><Listing>
            'item',          // <items><item>
            'Item',          // <Items><Item>
            'record',        // <records><record>
            'Record'         // <Records><Record>
        ];
        
        foreach ($possibleStructures as $structure) {
            if (isset($xml->$structure)) {
                foreach ($xml->$structure as $row) {
                    $properties[] = $row;
                }
                echo "📋 Found properties using structure: <$structure>\n";
                break;
            }
        }
        
        // If no structured elements found, try direct children
        if (empty($properties)) {
            echo "📋 Trying direct XML children as properties\n";
            foreach ($xml->children() as $child) {
                $properties[] = $child;
            }
        }

        echo "📋 Total properties found: " . count($properties) . "\n";
        
        if ($this->testingMode && count($properties) > 0) {
            $this->saveDebugInfo($properties[0]);
        }

        return $properties;
    }

    private function saveDebugInfo(SimpleXMLElement $firstProperty): void {
        $debugFolder = __DIR__ . '/../ScrapeFile/' . $this->foldername;
        
        // Save first property structure
        $structureFile = $debugFolder . '/xml_structure_debug.txt';
        file_put_contents($structureFile, print_r($firstProperty, true));
        
        // Save available fields
        $fieldsFile = $debugFolder . '/available_fields.txt';
        $fields = [];
        foreach ($firstProperty as $key => $value) {
            $fields[] = $key . ' = ' . (string)$value;
        }
        file_put_contents($fieldsFile, implode("\n", $fields));
        
        // Save XML sample
        $sampleFile = $debugFolder . '/xml_sample.xml';
        file_put_contents($sampleFile, $firstProperty->asXML());
        
        echo "🔍 Debug files saved: xml_structure_debug.txt, available_fields.txt, xml_sample.xml\n";
    }

    private function getDefaultConfig(): array {
        return [
            'field_mappings' => [
                'title' => ['title'],
                'description' => ['description'],
                'listing_id' => ['id','reference'],
                'price' => ['priceSale'],
                'currency' => ['currency', 'Currency'],
                'bedrooms' => ['bedrooms', 'Bedrooms'],
                'bathrooms' => ['bathrooms', 'Bathrooms'],
                'size' => ['interiorSize'],
                'property_type' => ['type', 'Type'],
                'property_status' => ['status'],
                'address' => ['address', 'Address', 'FullAddress', 'street', 'Street'],
                'area' => ['location'],
                'city' => ['district'],
                'state' => ['location'],
                'country' => ['country'],
                'zipcode' => ['zipcode', 'Zipcode', 'PostalCode', 'zip', 'postal_code'],
                'latitude' => ['gpsLat'],
                'longitude' => ['gpsLon'],
                'images' => ['images', 'Images', 'url', 'image'],
                'video_url' => ['video_url', 'VideoUrl', 'Video', 'VirtualTour'],
                'year_built' => ['build_date'],
                'features' => ['features', 'Features', 'amenities', 'Amenities', 'facilities'],
                'website' => ['url'],
            ],
            'property_types' => ['Villa', 'Condo', 'Apartment', 'House', 'Penthouse', 'Casa', 'Studio', 'Home', 'Hotel'],
            'property_statuses' => ['For Sale', 'Sold', 'Rented'],
            'default_currency' => 'EUR',
            'size_prefix' => 'sqm',
            'listing_id_prefix' => 'THE-'
        ];
    }

    private function processProperty(SimpleXMLElement $property): ?array {

        try {
            $confidentialInfo = $this->buildConfidentialInfo('https://atcityfind.com/api/gwNVwd/feeds/nestopa-feed');

            $title = $this->extractTitle($property, 'en');
            $description = $this->extractDescription($property, 'en');
            // Decode HTML entities first
            $plainText = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plainText = strip_tags($plainText);
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $plainText = trim($plainText);
            $excerpt = substr($plainText, 0, 300);

            $listing_id = $this->getXmlValue($property, $this->config['field_mappings']['listing_id']);
            $fullListingId = $this->config['listing_id_prefix'] . $listing_id;
            $price = (int)$this->getXmlValue($property, $this->config['field_mappings']['price']);
            if (empty($price) || !is_numeric($price) || (int)$price <= 0) {
                echo "❌ Skipping property with invalid price. Extracted value: '$price'\n";
                return null;
            }
            $bedrooms = (int)$this->getXmlValue($property, $this->config['field_mappings']['bedrooms']);
            $bathrooms = (int)$this->getXmlValue($property, $this->config['field_mappings']['bathrooms']);
            $size = (int)$this->getXmlValue($property, $this->config['field_mappings']['size']);
            $size_prefix = '';
            if ($size === 0) {
                $size = '';
            } else {
                $size_prefix = $this->config['size_prefix'];
            }

            // Property type and status
            $property_type = $this->extractPropertyTypes($property);
            if (empty($property_type)) {
                echo "❌ Skipping property with invalid type\n";
                return null;
            }

            $property_status[] = "For Sale";
            $features = $this->extractFeatures($property);

            // Images
            $images = $this->extractImages($property);
            // Check if we found any images
            if (empty($images) || count($images) === 1) {
                echo "❌ Skipping property with no images or contain 1 image only \n";
                // $this->helpers->updatePostToDraft($url);
                return null;
            }

            $latitude = '';
            $longitude = '';
            $location = '';
            $address = '';
            $address_data = [];
            $lat = $this->getXmlValue($property, $this->config['field_mappings']['latitude']);
            $long = $this->getXmlValue($property, $this->config['field_mappings']['longitude']);

            if (!empty($lat) && $lat !== '0' && is_numeric($lat)) {
                $latitude = $lat;
            }

            if (!empty($long) && $long !== '0' && is_numeric($long)) {
                $longitude = $long;
            }
             // Build location string only if both coordinates are valid
            if (!empty($latitude) && !empty($longitude)) {
                $location = $latitude . ', ' . $longitude;

                $address_data = $this->helpers->getLocationDataByCoords($latitude, $longitude) ?? [];
                $country = "Thailand";
                if($country !== $address_data['country'] && !empty($address_data)) {
                    echo "❌ Skipping property with invalid Coordinates, FROM SITE: {$country}, FROM API: {$address_data['country']} \n Trying another way.....";

                    $addressParts2 = array_filter([$address_data['city'], $country]);
                    $address2 = implode(', ', $addressParts2);
                    $address_data = $this->helpers->getCoordinatesFromQuery($address2);

                    if ($address_data) {
                        if($country !== $address_data['country']) { 
                            echo "❌ Skipping again property with invalid Coordinates, FROM SITE: {$country}, FROM API: {$address_data['country']} \n";
                            // $this->helpers->updatePostToDraft($url);
                            return null;
                        }
                    }
                
                }
            }

            if($address_data['country'] == null) {
                echo "❌ Skipping property with invalid Coordinates and null Country \n";
                return null;
            }
            return [
                "property_title" => $title,
                "property_description" => $this->helpers->translateHtmlPreservingTags($description),
                "property_excerpt" => $excerpt,
                "price" => $price,
                "currency" => 'THB',
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
                "zip_code" => "",
                "latitude" => $latitude,
                "longitude" => $longitude,
                "listing_id" => $fullListingId,
                "agent_id" => "150",
                "agent_display_option" => "agent_info",
                "video_url" => "",
                "images" => $images,    
                "property_map" => "1",
                "property_year" => "",
                "additional_features" => $features,
                "confidential_info" => $confidentialInfo
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
        
        // Add dynamic confidential information
        foreach ($this->confidentialInfo as $title => $value) {
            if (!empty($value)) {
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

    private function extractDescription(SimpleXMLElement $property, string $lang = 'en'): string {
        // Check if desc element exists
        if (isset($property->descriptions->description)) {
            // Check if the specific language element exists
            if (isset($property->descriptions->descriptions->description->$lang)) {
                return trim((string)$property->descriptions->description->$lang);
            }
            
            // Fallback: try to get any available description if requested language not found
            $availableLanguages = ['en', 'de', 'it', 'ru', 'th'];
            foreach ($availableLanguages as $fallbackLang) {
                if (isset($property->descriptions->descriptions->description->$fallbackLang)) {
                    return trim((string)$property->descriptions->description->$fallbackLang);
                }
            }
        }
        
        // If nested desc not found, try the standard field mappings
        $descFields = $this->config['field_mappings']['description'];
        return $this->getXmlValue($property, $descFields);
    }

    private function extractTitle(SimpleXMLElement $property, string $lang = 'en'): string {
        // Check if desc element exists
        if (isset($property->titles->title)) {
            // Check if the specific language element exists
            if (isset($property->titles->title->$lang)) {
                return trim((string)$property->titles->title->$lang);
            }
            
            // Fallback: try to get any available description if requested language not found
            $availableLanguages = ['en', 'de', 'it', 'ru','th'];
            foreach ($availableLanguages as $fallbackLang) {
                if (isset($property->titles->title->$fallbackLang)) {
                    return trim((string)$property->titles->title->$fallbackLang);
                }
            }
        }
        
        // If nested desc not found, try the standard field mappings
        $titleField = $this->config['field_mappings']['title'];
        return $this->getXmlValue($property, $titleField);
    }

    


    private function extractPrice(SimpleXMLElement $property): int {
        $priceFields = $this->config['field_mappings']['price'];
        
        foreach ($priceFields as $field) {
            $priceValue = $this->getXmlValue($property, [$field]);
            
            if (!empty($priceValue)) {
                // Handle boolean Sale field - skip if 0
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

    public function extractPropertyTypes($property): array {
        $typeFields = $this->config['field_mappings']['property_type'];
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


    private function extractImages(SimpleXMLElement $property): array {
        $images = [];
        $imageFields = $this->config['field_mappings']['images'];
        
        foreach ($imageFields as $field) {
            if (isset($property->$field)) {
                $imageContainer = $property->$field;
                
                // Handle different XML structures for images
                if (isset($imageContainer->url)) {
                    foreach ($imageContainer->url as $img) {
                        $imageUrl = '';
                        
                        // Check if image has url child element
                        if (isset($img)) {
                            $imageUrl = trim((string)$img);
                        } else {
                            $imageUrl = trim((string)$img);
                        }
                        
                        // Clean up the URL (remove extra whitespace from CDATA)
                        $imageUrl = trim($imageUrl);
                        
                        if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                            $images[] = $imageUrl;
                        }
                    }
                } elseif (isset($imageContainer)) {
                    foreach ($imageContainer as $img) {
                        $imageUrl = trim((string)$img);
                        if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                            $images[] = $imageUrl;
                        }
                    }
                } else {
                    // Direct URL in field
                    $imageUrl = trim((string)$imageContainer);
                    if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $images[] = $imageUrl;
                    }
                }
                
                if (!empty($images)) {
                    break;
                }
            }
        }
        
        // Remove duplicates and limit to 10 images
        $images = array_unique($images);
        return array_slice($images, 0, 10);
    }

   

    private function extractFeatures(SimpleXMLElement $property): array {
        $features = [];
        $featureFields = $this->config['field_mappings']['features'];
        
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
                    $featureText = trim((string)$featureContainer);
                    if (!empty($featureText)) {
                        // Remove serialized data (everything after semicolon)
                        $featureText = explode(';', $featureText)[0];
                        
                        // Split by pipe and add to features
                        $parsedFeatures = array_filter(array_map('trim', explode('|', $featureText)));
                        $features = array_merge($features, $parsedFeatures);
                    }
                }
            }
        }
        
        return array_filter(array_unique($features));
    }
   
}
?>