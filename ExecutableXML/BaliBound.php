<?php
require_once __DIR__ . '/../Api/ApiSender.php';
require_once __DIR__ . '/../Helpers/ScraperHelpers.php';
require_once __DIR__ . '/../Helpers/XMLHelpers.php';

class BaliBound {
    private string $foldername;
    private string $filename;
    private array $scrapedData = [];
    private ApiSender $apiSender;
    private ScraperHelpers $helpers;
    private XMLHelpers $xmlHelpers;
    private int $successCreated;
    private int $successUpdated;
    private bool $enableUpload = false;
    private bool $testingMode = false;
    private string $xmlSource = '';
    private array $config = [];
    private array $confidentialInfo = [];
    
    public function __construct(string $foldername = 'BaliBound', string $filename = 'properties.json') {
        $this->foldername = $foldername;
        // $this->filename = $filename;
        // Add timestamp to filename
        $timestamp = date('Y-m-d_H-i-s');
        $filenameParts = pathinfo($filename);
        $this->filename = $filenameParts['filename'] . '_' . $timestamp . '.' . $filenameParts['extension'];
        
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
        // Disable output buffering to ensure messages appear in correct order
        ob_implicit_flush(true);

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

        // Apply limit if specified
        if ($limit > 0) {
            $properties = array_slice($properties, 0, $limit);
        }

        echo "📊 Total properties to process: " . count($properties) . "\n\n";

        // Start a fresh JSON array
        file_put_contents($outputFile, "[");

        $propertyCounter = 0;
        foreach ($properties as $index => $property) {
            $startUpload = 0;
            echo "🏠 Processing property " . ($index + 1) . "/" . count($properties) . "\n";
            flush(); // Ensure this message is displayed immediately
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
            'property',      // <properties><property> - BaliBound structure
            'Row',           // <Report><Row> - Generic structure
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
                'description' => ['description', 'Description', 'desc'],
                'listing_id' => ['id', 'ref'],
                'price' => ['price', 'Price'],
                'currency' => ['currency', 'Currency'],
                'bedrooms' => ['bedrooms', 'Bedrooms', 'beds'],
                'bathrooms' => ['bathrooms', 'Bathrooms', 'baths', 'Baths'],
                'land_size' => ['land_size'],
                'building_size' => ['building_size'],
                'size' => ['size', 'Size', 'Area', 'area'],
                'property_type' => ['type', 'Type'],
                'property_status' => ['price_freq'],
                'address' => ['address', 'Address', 'FullAddress', 'street', 'Street'],
                'area' => ['area', 'area_name'],
                'region' => ['region', 'Region'],
                'city' => ['city', 'City', 'Town', 'town'],
                'state' => ['state', 'State', 'Region', 'Province', 'province'],
                'country' => ['country', 'Country', 'nation'],
                'zipcode' => ['zipcode', 'Zipcode', 'PostalCode', 'zip', 'postal_code'],
                'latitude' => ['latitude', 'Latitude', 'lat', 'Lat'],
                'longitude' => ['longitude', 'Longitude', 'lng', 'Lng', 'long'],
                'images' => ['images', 'Images', 'photos', 'Photos', 'ImageLink', 'image'],
                'video_url' => ['video', 'video_url', 'VideoUrl', 'Video', 'VirtualTour'],
                'year_built' => ['year_built', 'YearBuilt', 'ConstructionYear', 'BuildYear'],
                'features' => ['features', 'Features', 'amenities', 'Amenities', 'facilities'],
                'website' => ['url', 'link'],
            ],
            'property_types' => ['Villa', 'Condo', 'Apartment', 'House', 'Penthouse', 'Casa', 'Studio', 'Home', 'Hotel'],
            'property_statuses' => ['For Sale', 'For Rent', 'Sold', 'Rented'],
            'default_currency' => 'USD',
            'size_prefix' => 'sqm',
            'listing_id_prefix' => 'BR-'
        ];
    }

    private function processProperty(SimpleXMLElement $property): ?array {
        try {
            $url = $this->getNestedUrl($property, 'en');
            $confidentialInfo = $this->buildConfidentialInfo($url);
            // Extract basic property information using field mappings
            $listing_id = $this->getXmlValue($property, $this->config['field_mappings']['listing_id']);

            $title = $this->getXmlValue($property, $this->config['field_mappings']['title']);
            // Check if listing_id already contains the prefix to avoid duplication
            $prefix = $this->config['listing_id_prefix'];
            if (strpos($listing_id, $prefix) === 0) {
                $fullListingId = $listing_id; // Already has prefix
            } else {
                $fullListingId = $prefix . $listing_id; // Add prefix
            }

            // Extract description
            $description = $this->extractDescription($property, 'en');

            // Decode HTML entities first
            $plainText = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plainText = strip_tags($plainText);
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $plainText = trim($plainText);
            $excerpt = substr($plainText, 0, 300);

            // Price extraction
            $price = $this->extractPrice($property);
            $currency = $this->extractCurrency($property);

            if (empty($price) || !is_numeric($price) || (int)$price <= 0) {
                echo "❌ Skipping property with invalid price. Extracted value: '$price'\n";
                return null;
            }

            // Property type and status
            $property_type = $this->extractPropertyTypes($property);
            if (empty($property_type)) {
                echo "❌ Skipping property with invalid type\n";
                return null;
            }

            //status
            $property_status = $this->extractPropertyStatus($property);
            if (empty($property_status)) {
                echo "❌ Skipping property with invalid status\n";
                return null;
            }

            // Images
            $images = $this->extractImages($property);
            // Check if we found any images
            if (empty($images)) {
                echo "❌ Skipping property with no images \n";
                $this->helpers->updatePostToDraft($url);
                return null;
            }

            // Extract bedrooms and bathrooms from details element or field mappings
            $bedrooms = 0;
            $bathrooms = 0;

            if (isset($property->details->bedrooms)) {
                $bedrooms = (int)trim((string)$property->details->bedrooms);
            } else {
                $bedrooms = (int)$this->getXmlValue($property, $this->config['field_mappings']['bedrooms']);
            }

            if (isset($property->details->bathrooms)) {
                $bathrooms = (int)trim((string)$property->details->bathrooms);
            } else {
                $bathrooms = (int)$this->getXmlValue($property, $this->config['field_mappings']['bathrooms']);
            }

            $size = $this->extractSize($property);

            $features = $this->extractFeatures($property);

            // Extract location data - try nested location element first (BaliBound structure)
            $city = '';
            $state = '';
            $country = 'Indonesia'; // Default for Bali properties

            if (isset($property->location)) {
                // BaliBound uses area (Bukit Peninsula) and region (Bali)
                $area = trim((string)$property->location->area);
                $region = trim((string)$property->location->region);

                // Map region to state and area to city
                if (!empty($region)) {
                    $state = $region; // "Bali"
                }
                if (!empty($area)) {
                    $city = $area; // "Bukit Peninsula", etc.
                }
            }

            // Fallback to standard field mappings if location element not found
            if (empty($city)) {
                $city = $this->getXmlValue($property, $this->config['field_mappings']['city']);
            }
            if (empty($state)) {
                $state = $this->getXmlValue($property, $this->config['field_mappings']['state']);
            }

            // Extract country and convert if it's a code
            $countryCode = $this->getXmlValue($property, $this->config['field_mappings']['country']);
            if (!empty($countryCode)) {
                $country = $this->getCountryName($countryCode);
            }

            $latitude = '';
            $longitude = '';
            $location = '';
            $address = '';

            $addressParts = array_filter([$city, $state, $country]);
            $address = implode(', ', $addressParts);

            // Check if location element exists with nested lat/long
            if (isset($property->location)) {
                $locationElement = $property->location;
                
                // Extract latitude
                if (isset($locationElement->latitude)) {
                    $lat = trim((string)$locationElement->latitude);
                    if (!empty($lat) && $lat !== '0' && is_numeric($lat)) {
                        $latitude = $lat;
                    }
                }
                
                // Extract longitude
                if (isset($locationElement->longitude)) {
                    $long = trim((string)$locationElement->longitude);
                    if (!empty($long) && $long !== '0' && is_numeric($long)) {
                        $longitude = $long;
                    }
                }
            }

            

            // Build location string only if both coordinates are valid
            if (!empty($latitude) && !empty($longitude)) {
                $location = $latitude . ', ' . $longitude;
            } else if(!empty($country) && !empty($city)){
                
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
            }

            // Extract video URL before building title (to avoid variable name conflict)
            $videoUrl = $this->getXmlValue($property, $this->config['field_mappings']['video_url']);

            // $title = '';
            // if($bedrooms > 0) {
            //     $propertyTypeName = $property_type[0];
            //     $title = "{$bedrooms} Bedroom" . ($bedrooms > 1 ? 's' : '') . " {$propertyTypeName} in " . ($city ?: $state);
            // } else {
            //     $propertyTypeName = $property_type[0];
            //     $title = "{$propertyTypeName} in " . ($city ?: $state);
            // }

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
                "size_prefix" => $this->config['size_prefix'],
                "property_type" => $property_type,
                "property_status" => $property_status,
                "property_address" => $address,
                "property_area" => "",
                "city" => $city,
                "state" => $state,
                "country" => $country,
                "zip_code" => "",
                "latitude" => $latitude,
                "longitude" => $longitude,
                "listing_id" => $fullListingId,
                "agent_id" => "150",
                "agent_display_option" => "agent_info",
                "video_url" => $videoUrl,
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
    private function getCountryName(string $countryCode): string {
        // Convert to uppercase for consistency
        $countryCode = strtoupper(trim($countryCode));
        
        // If it's already a full country name (more than 3 characters), return it properly capitalized
        if (strlen($countryCode) > 3) {
            return ucfirst(strtolower($countryCode));
        }
        
        // Country code mapping (ISO 3166-1 alpha-3 and alpha-2)
        $countryCodes = [
            'BGR' => 'Bulgaria',
            'BG' => 'Bulgaria',
            'ESP' => 'Spain',
            'ES' => 'Spain',
            'PRT' => 'Portugal',
            'PT' => 'Portugal',
            'ITA' => 'Italy',
            'IT' => 'Italy',
            'FRA' => 'France',
            'FR' => 'France',
            'GRC' => 'Greece',
            'GR' => 'Greece',
            'TUR' => 'Turkey',
            'TR' => 'Turkey',
            'CYP' => 'Cyprus',
            'CY' => 'Cyprus',
            'HRV' => 'Croatia',
            'HR' => 'Croatia',
            'DEU' => 'Germany',
            'DE' => 'Germany',
            'GBR' => 'United Kingdom',
            'GB' => 'United Kingdom',
            'USA' => 'United States',
            'US' => 'United States',
            'ARE' => 'United Arab Emirates',
            'AE' => 'United Arab Emirates',
            'THA' => 'Thailand',
            'TH' => 'Thailand',
            'MEX' => 'Mexico',
            'MX' => 'Mexico',
            // Add more country codes as needed
        ];
        
        // Return the full country name if found, otherwise return the original code
        return $countryCodes[$countryCode] ?? $countryCode;
    }

    private function getXmlAttributeFromElement($element, $attributeName): string{
        if (isset($element[$attributeName])) {
            return (string) $element[$attributeName];
        }
        return '';
    }

    private function extractDescription(SimpleXMLElement $property, string $lang = 'en'): string {
        // First try direct description field (BaliBound structure uses this with CDATA)
        if (isset($property->description)) {
            $description = trim((string)$property->description);
            if (!empty($description)) {
                return $description;
            }
        }

        // Check if desc element exists (for nested language structure)
        if (isset($property->desc)) {
            // Check if the specific language element exists
            if (isset($property->desc->$lang)) {
                return trim((string)$property->desc->$lang);
            }

            // Fallback: try to get any available description if requested language not found
            $availableLanguages = ['en', 'de', 'it', 'ru'];
            foreach ($availableLanguages as $fallbackLang) {
                if (isset($property->desc->$fallbackLang)) {
                    return trim((string)$property->desc->$fallbackLang);
                }
            }
        }

        // If nested desc not found, try the standard field mappings
        $descFields = $this->config['field_mappings']['description'];
        return $this->getXmlValue($property, $descFields);
    }

    private function getNestedUrl(SimpleXMLElement $property, string $lang = 'en'): string {
        // Check if url element exists
        if (isset($property->url)) {
            // First try as direct URL value (BaliBound structure)
            $urlValue = trim((string)$property->url);
            if (!empty($urlValue) && filter_var($urlValue, FILTER_VALIDATE_URL)) {
                return $urlValue;
            }

            // Check if the specific language element exists (nested structure)
            if (isset($property->url->$lang)) {
                return trim((string)$property->url->$lang);
            }

            // Fallback: try to get any available URL if requested language not found
            $availableLanguages = ['en', 'de', 'it', 'ru'];
            foreach ($availableLanguages as $fallbackLang) {
                if (isset($property->url->$fallbackLang)) {
                    return trim((string)$property->url->$fallbackLang);
                }
            }
        }

        return '';
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

    private function extractCurrency(SimpleXMLElement $property): string {
        // First try to get currency from price element attribute
        if (isset($property->price['currency'])) {
            $currency = trim((string)$property->price['currency']);
            if (!empty($currency)) {
                return strtoupper($currency);
            }
        }

        // Fallback to field mappings
        $currency = $this->getXmlValue($property, $this->config['field_mappings']['currency']);
        if (!empty($currency)) {
            return strtoupper($currency);
        }

        // Return default currency
        return $this->config['default_currency'];
    }

    private function extractSize(SimpleXMLElement $property): int {
        // Try to get building_size from details element (BaliBound structure)
        if (isset($property->details->building_size)) {
            $buildingSize = trim((string)$property->details->building_size);
            if (is_numeric($buildingSize) && (float)$buildingSize > 0) {
                return (int)$buildingSize;
            }
        }

        // Try to get land_size from details element as fallback
        if (isset($property->details->land_size)) {
            $landSize = trim((string)$property->details->land_size);
            if (is_numeric($landSize) && (float)$landSize > 0) {
                return (int)$landSize;
            }
        }

        // Try to get built size from surface_area element (generic structure)
        if (isset($property->surface_area->built)) {
            $builtSize = trim((string)$property->surface_area->built);
            if (is_numeric($builtSize) && (float)$builtSize > 0) {
                return (int)$builtSize;
            }
        }

        // If not found, try the standard field mappings
        $sizeFields = $this->config['field_mappings']['size'];

        foreach ($sizeFields as $field) {
            $sizeValue = $this->getXmlValue($property, [$field]);

            if (!empty($sizeValue)) {
                // Handle common range patterns
                if (preg_match('/(\d+(?:\.\d+)?)\s*[-–—]\s*(\d+(?:\.\d+)?)/', $sizeValue, $matches)) {
                    // For ranges like "307 - 742", take the first number
                    $firstNumber = (float)$matches[1];

                    if ($firstNumber > 0) {
                        return (int)$firstNumber;
                    }
                }

                // If no range pattern, just get the first number
                if (preg_match('/(\d+(?:\.\d+)?)/', $sizeValue, $matches)) {
                    $firstNumber = (float)$matches[1];

                    if ($firstNumber > 0) {
                        return (int)$firstNumber;
                    }
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
                // Handle "villa-sale", "apartment-rent" format from BaliBound
                if (strpos($typeValue, '-') !== false) {
                    $parts = explode('-', $typeValue);
                    $typeValue = $parts[0]; // Take the first part (villa, apartment, etc.)
                }

                // Process individual words
                $words = preg_split('/\s+/', $typeValue);
                foreach ($words as $word) {
                    $word = trim($word, '.,!?;:-');

                    if (!empty($word)) {
                        $allowedType = $this->helpers->allowedPropertyType($word);

                        if ($allowedType && !in_array($allowedType, $types)) {
                            $types[] = $allowedType;
                        }

                        if($typeValue == 'Appartment') {
                            $types[] = "Apartment";
                        }
                    }
                }
            }
        }

        return array_unique($types);
    }

    private function extractPropertyStatus(SimpleXMLElement $property): array {
        $statusFields = $this->config['field_mappings']['property_status'];
        $statuses = [];

        // First try to extract from type field (BaliBound uses "villa-sale", "apartment-rent" format)
        $typeValue = $this->getXmlValue($property, $this->config['field_mappings']['property_type']);
        if (!empty($typeValue) && strpos($typeValue, '-') !== false) {
            $parts = explode('-', $typeValue);
            $statusPart = $parts[1]; // sale, rent, etc.

            if ($statusPart == 'sale') {
                $statuses[] = "For Sale";
            } elseif ($statusPart == 'rent') {
                $statuses[] = "For Rent";
            }
        }

        // Fallback to standard status field mappings
        foreach ($statusFields as $field) {
            $statusValue = $this->getXmlValue($property, [$field]);
            if (!empty($statusValue)) {
                // Use helper method for consistent mapping
                $allowedStatus = $this->helpers->allowedPropertyStatus($statusValue);
                if($statusValue == 'sale') {
                    $statuses[] = "For Sale";
                }
                if ($allowedStatus && !in_array($allowedStatus, $statuses)) {
                    $statuses[] = $statusValue;
                }
            }
        }

        return array_unique($statuses);
    }

    private function extractImages(SimpleXMLElement $property): array {
        $images = [];
        $imageFields = $this->config['field_mappings']['images'];

        foreach ($imageFields as $field) {
            if (isset($property->$field)) {
                $imageContainer = $property->$field;

                // Handle different XML structures for images
                // BaliBound structure: <images><image>URL</image></images>
                if (isset($imageContainer->image)) {
                    foreach ($imageContainer->image as $img) {
                        $imageUrl = '';

                        // Check if image has url child element
                        if (isset($img->url)) {
                            $imageUrl = trim((string)$img->url);
                        } else {
                            $imageUrl = trim((string)$img);
                        }

                        // Clean up the URL (remove extra whitespace from CDATA)
                        $imageUrl = trim($imageUrl);

                        if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                            $images[] = $imageUrl;
                        }
                    }
                } elseif (isset($imageContainer->url)) {
                    foreach ($imageContainer->url as $img) {
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