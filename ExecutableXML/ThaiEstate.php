<?php
require_once __DIR__ . '/../Api/ApiSender.php';
require_once __DIR__ . '/../Helpers/ScraperHelpers.php';
require_once __DIR__ . '/../Helpers/XMLHelpers.php';

class ThaiEstate {
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
    
    public function __construct(string $foldername = 'ThaiEstate', string $filename = 'properties2.json') {
        $this->foldername = $foldername;
        $this->filename = $filename;
        $this->apiSender = new ApiSender(true);
        $this->helpers = new ScraperHelpers();
        $this->xmlHelpers = new XMLHelpers();
        $this->successCreated = 0;
        $this->successUpdated = 0;
        $this->config = $this->getDefaultConfig();
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
    public function run(string $xmlInput, int $limit = 0): bool {
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

        // Apply limit if specified
        if ($limit > 0) {
            $properties = array_slice($properties, 0, $limit);
        }

        echo "📊 Total properties to process: " . count($properties) . "\n\n";

        // Start a fresh JSON array
        file_put_contents($outputFile, "[");

        $propertyCounter = 0;
        foreach ($properties as $index => $property) {
            $startUpload = 385;
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
                'title' => ['title', 'Title', 'PropertyTitle', 'name', 'Name', 'Propertytype'],
                'description' => ['description', 'Description', 'PropertyDescription', 'Description_en-gb', 'desc', 'details'],
                'listing_id' => ['id', 'ID', 'listing_id', 'Reference', 'reference', 'property_id'],
                'price' => ['price', 'Price', 'SalePrice', 'sale_price', 'ListPrice', 'Amount', 'Sale'],
                'currency' => ['currency', 'Currency', 'PriceCurrency'],
                'bedrooms' => ['bedrooms', 'Bedrooms', 'BedroomCount', 'rooms', 'Rooms'],
                'bathrooms' => ['bathrooms', 'Bathrooms', 'BathroomCount', 'baths', 'Baths'],
                'size' => ['size', 'Size', 'Area', 'area'],
                'property_type' => ['type', 'Type', 'PropertyType', 'Propertytype', 'Category'],
                'property_status' => ['status', 'Status', 'PropertyStatus', 'ListingStatus', 'Sale', 'price_freq'],
                'address' => ['address', 'Address', 'FullAddress', 'street', 'Street'],
                'area' => ['area_name'],
                'city' => ['city', 'City', 'Town', 'locality'],
                'state' => ['state', 'State', 'Region', 'Province', 'area'],
                'country' => ['country', 'Country', 'nation'],
                'zipcode' => ['zipcode', 'Zipcode', 'PostalCode', 'zip', 'postal_code'],
                'latitude' => ['latitude', 'Latitude', 'lat', 'Lat'],
                'longitude' => ['longitude', 'Longitude', 'lng', 'Lng', 'long'],
                'images' => ['images', 'Images', 'photos', 'Photos', 'ImageLink', 'image'],
                'video_url' => ['video_url', 'VideoUrl', 'Video', 'VirtualTour'],
                'year_built' => ['year_built', 'YearBuilt', 'ConstructionYear', 'BuildYear'],
                'features' => ['features', 'Features', 'amenities', 'Amenities', 'facilities'],
                'website' => ['link'],
            ],
            'property_types' => ['Villa', 'Condo', 'Apartment', 'House', 'Penthouse', 'Casa', 'Studio', 'Home', 'Hotel'],
            'property_statuses' => ['For Sale', 'For Rent', 'Sold', 'Rented'],
            'default_currency' => 'EUR',
            'size_prefix' => 'sqm',
            'listing_id_prefix' => 'THE-'
        ];
    }

    private function processProperty(SimpleXMLElement $property): ?array {

        $ownedBy = "Thai Estate by Andaman Infinity Co., Ltd.";
        $contactPerson = "Jay Birnbaum";
        $phone = "+66 82 273 7880";
        $email = "thaiestate.asia@gmail.com";

        try {
            // Extract basic property information using field mappings
            $title = $this->getXmlValue($property, $this->config['field_mappings']['title']);
            $description = $this->getXmlValue($property, $this->config['field_mappings']['description']);
            $listing_id = $this->getXmlValue($property, $this->config['field_mappings']['listing_id']);
            
            // // Price extraction
            $price = $this->extractPrice($property);

            $currency = '';
            // Check if price element has currency attribute
            foreach ($this->config['field_mappings']['price'] as $priceField) {
                if (isset($property->$priceField)) { 
                    $priceElement = $property->$priceField;
                    $currency = $this->getXmlAttributeFromElement($priceElement, 'currency');
                    if ($currency) {
                        break;
                    }
                }
            }

            if (empty($price) || !is_numeric($price) || (int)$price <= 0) {
                echo "❌ Skipping property with invalid price. Extracted value: '$price'\n";
                return null;
            }

            // // Property details
            $bedrooms = (int)$this->getXmlValue($property, $this->config['field_mappings']['bedrooms']);
            $bathrooms = (int)$this->getXmlValue($property, $this->config['field_mappings']['bathrooms']);
            $size = $this->extractSize($property);

            // // Property type and status
            $property_type = $this->extractPropertyTypes($property);
            if (empty($property_type)) {
                echo "❌ Skipping property with invalid type\n";
                return null;
            }
            $property_status = $this->extractPropertyStatus($property);
            if (empty($property_status)) {
                echo "❌ Skipping property with invalid status\n";
                return null;
            }

            // // Location information
            $address = $this->getXmlValue($property, $this->config['field_mappings']['address']);
            $area = $this->getXmlValue($property, $this->config['field_mappings']['area']);
            $area = str_replace('>', ', ', $area);
            $city = $this->getXmlValue($property, $this->config['field_mappings']['city']);
            $state = $this->getXmlValue($property, $this->config['field_mappings']['state']);
            $country = $this->getXmlValue($property, $this->config['field_mappings']['country']) ?: 'Thailand';
            $zipcode = $this->getXmlValue($property, $this->config['field_mappings']['zipcode']);

            // // Build full address
            $addressParts = array_filter([$address, $area, $city, $state, $zipcode, $country]);
            $fullAddress = implode(', ', $addressParts);

            // // Coordinates
            $latitude = (float)$this->getXmlValue($property, $this->config['field_mappings']['latitude']);
            $longitude = (float)$this->getXmlValue($property, $this->config['field_mappings']['longitude']);
            $location = !empty($latitude) && !empty($longitude) ? "$latitude, $longitude" : '';

            // Images
            $images = $this->extractImages($property);

            // // Features
            $features = $this->extractFeatures($property);

            // // Validation checks
            if (empty($listing_id)) {
                echo "❌ Skipping property with missing listing ID\n";
                return null;
            }

            // // Create excerpt from description
            $plainText = strip_tags($description);
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $plainText = trim($plainText);
            $excerpt = substr($plainText, 0, 300);


            $website_url = $this->getXmlValue($property, $this->config['field_mappings']['website']);
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
                "property_address" => $fullAddress,
                "property_area" => $area,
                "city" => $city,
                "state" => $state,
                "country" => $country,
                "zip_code" => $zipcode,
                "latitude" => $latitude,
                "longitude" => $longitude,
                "listing_id" => $this->config['listing_id_prefix'] . $listing_id,
                "agent_id" => "150",
                "agent_display_option" => "agent_info",
                "video_url" => "",
                "images" => $images,
                "property_map" => "1",
                "property_year" => "",
                "additional_features" => $features,
                "confidential_info" => [
                    [
                        "fave_additional_feature_title" => "Owned by",
                        "fave_additional_feature_value" => $ownedBy
                    ],
                    [
                        "fave_additional_feature_title" => "Website",
                        "fave_additional_feature_value" => $website_url,
                    ],
                    [
                        "fave_additional_feature_title" => "Contact Person",
                        "fave_additional_feature_value" => $contactPerson
                    ],
                    [
                        "fave_additional_feature_title" => "Phone",
                        "fave_additional_feature_value" => $phone
                    ],
                    [
                        "fave_additional_feature_title" => "Email",
                        "fave_additional_feature_value" => $email
                    ]
                ]
            ];

        } catch (Exception $e) {
            echo "❌ Error processing property: " . $e->getMessage() . "\n";
            return null;
        }
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

    private function getXmlAttributeFromElement($element, $attributeName): string{
        if (isset($element[$attributeName])) {
            return (string) $element[$attributeName];
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

    private function extractSize(SimpleXMLElement $property): int {
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
        $statusFields = $this->config['field_mappings']['property_status'];
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
        $imageFields = $this->config['field_mappings']['images'];
        
        foreach ($imageFields as $field) {
            if (isset($property->$field)) {
                $imageContainer = $property->$field;
                
                // Handle different XML structures for images
                if (isset($imageContainer->image)) {
                    foreach ($imageContainer->image as $img) {
                        $imageUrl = trim((string)$img);
                        if (!empty($imageUrl)) {
                            $images[] = $imageUrl;
                        }
                    }
                } elseif (isset($imageContainer->url)) {
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

// Usage Examples
/*

// Example 1: Automatic detection - URL
$scraper = new DynamicXmlScraper('AutoScraper', 'auto_properties.json');
$scraper->setTestingMode(true);
$scraper->run('https://example.com/properties.xml', 50);

// Example 2: Automatic detection - Local file
$scraper = new DynamicXmlScraper('LocalScraper', 'local_properties.json');
$scraper->run('/path/to/properties.xml');

// Example 3: Automatic detection - XML string
$xmlString = '<?xml version="1.0"?><properties><property><id>123</id><title>Test</title></property></properties>';
$scraper = new DynamicXmlScraper('StringScraper', 'string_properties.json');
$scraper->run($xmlString);

// Example 4: With custom configuration
$scraper = new DynamicXmlScraper('CustomScraper', 'custom_properties.json');
$scraper->setConfig([
    'listing_id_prefix' => 'CUSTOM-',
    'default_currency' => 'USD',
    'size_prefix' => 'sqft'
]);
$scraper->enableUpload(); // Enable API upload
$scraper->run('properties.xml');

// Example 5: Batch processing with error handling
$sources = [
    'https://example.com/feed1.xml',
    '/local/path/feed2.xml',
    'backup-properties.xml'
];

foreach ($sources as $index => $source) {
    $scraper = new DynamicXmlScraper("Batch$index", "batch_$index.json");
    $success = $scraper->run($source, 100); // Limit to 100 per source
    
    if ($success) {
        echo "✅ Successfully processed source: $source\n";
    } else {
        echo "❌ Failed to process source: $source\n";
    }
    
    sleep(2); // Delay between sources
}

*/

// Advanced Usage Class with Helper Methods
// class DynamicXmlScraperAdvanced extends ThaiEstate {
    
//     /**
//      * Run scraper with fallback sources
//      * Tries primary source, falls back to secondary if fails
//      */
//     public function runWithFallback(array $sources, int $limit = 0): bool {
//         foreach ($sources as $source) {
//             echo "🔄 Attempting source: $source\n";
//             if ($this->run($source, $limit)) {
//                 echo "✅ Successfully processed: $source\n";
//                 return true;
//             }
//             echo "❌ Failed, trying next source...\n";
//         }
        
//         echo "❌ All sources failed\n";
//         return false;
//     }
    
//     /**
//      * Run with automatic retry on failure
//      */
//     public function runWithRetry(string $xmlInput, int $limit = 0, int $maxRetries = 3): bool {
//         $attempt = 1;
        
//         while ($attempt <= $maxRetries) {
//             echo "🔄 Attempt $attempt of $maxRetries\n";
            
//             if ($this->run($xmlInput, $limit)) {
//                 return true;
//             }
            
//             if ($attempt < $maxRetries) {
//                 $delay = $attempt * 2; // Exponential backoff
//                 echo "⏳ Waiting {$delay} seconds before retry...\n";
//                 sleep($delay);
//             }
            
//             $attempt++;
//         }
        
//         echo "❌ All retry attempts failed\n";
//         return false;
//     }
    
//     /**
//      * Validate XML structure before processing
//      */
//     public function validateXmlStructure(string $xmlInput): array {
//         $inputType = $this->detectInputType($xmlInput);
//         $xmlContent = '';
        
//         switch ($inputType) {
//             case 'url':
//                 $xmlContent = $this->fetchXmlFromUrl($xmlInput);
//                 break;
//             case 'file':
//                 $xmlContent = file_get_contents($xmlInput);
//                 break;
//             case 'xml_string':
//                 $xmlContent = $xmlInput;
//                 break;
//             default:
//                 return ['valid' => false, 'error' => 'Invalid input type'];
//         }
        
//         if (!$xmlContent) {
//             return ['valid' => false, 'error' => 'Could not fetch XML content'];
//         }
        
//         libxml_use_internal_errors(true);
//         $xml = simplexml_load_string($xmlContent);
        
//         if ($xml === false) {
//             $errors = libxml_get_errors();
//             return [
//                 'valid' => false, 
//                 'errors' => array_map(function($error) {
//                     return "Line {$error->line}: " . trim($error->message);
//                 }, $errors)
//             ];
//         }
        
//         // Analyze structure
//         $structure = [];
//         $sampleProperties = 0;
        
//         foreach (['Row', 'property', 'Property', 'listing', 'Listing', 'item', 'Item'] as $element) {
//             if (isset($xml->$element)) {
//                 $sampleProperties = count($xml->$element);
//                 $structure['element_type'] = $element;
//                 $structure['property_count'] = $sampleProperties;
                
//                 // Get sample fields from first property
//                 if ($sampleProperties > 0) {
//                     $firstProperty = $xml->$element[0];
//                     $fields = [];
//                     foreach ($firstProperty as $key => $value) {
//                         $fields[$key] = (string)$value;
//                     }
//                     $structure['sample_fields'] = $fields;
//                 }
//                 break;
//             }
//         }
        
//         return [
//             'valid' => true,
//             'input_type' => $inputType,
//             'structure' => $structure
//         ];
//     }
    
//     /**
//      * Get statistics about processed data
//      */
//     public function getProcessingStats(): array {
//         return [
//             'created' => $this->successCreated,
//             'updated' => $this->successUpdated,
//             'total_processed' => $this->successCreated + $this->successUpdated,
//             'source' => $this->xmlSource
//         ];
//     }
// }

// Quick Usage Functions
// function quickScrape($xmlSource, $limit = 0) {
//     $scraper = new DynamicXmlScraper('QuickScrape', 'quick_scrape.json');
//     $scraper->setTestingMode(true);
//     return $scraper->run($xmlSource, $limit);
// }

// function validateXmlSource($xmlSource) {
//     $scraper = new DynamicXmlScraperAdvanced('Validator', 'temp.json');
//     return $scraper->validateXmlStructure($xmlSource);
// }

// function scrapeWithBackup($primarySource, $backupSource, $limit = 0) {
//     $scraper = new DynamicXmlScraperAdvanced('BackupScraper', 'backup_scrape.json');
//     return $scraper->runWithFallback([$primarySource, $backupSource], $limit);
// }

// // CLI Integration Example
// if (php_sapi_name() === 'cli') {
//     function runCliScraper($argv) {
//         if (count($argv) < 2) {
//             echo "Usage: php scraper.php <xml_source> [limit] [--upload] [--test]\n";
//             echo "  xml_source: URL, file path, or XML string\n";
//             echo "  limit: Number of properties to process (optional)\n";
//             echo "  --upload: Enable API upload\n";
//             echo "  --test: Enable testing mode\n";
//             return;
//         }
        
//         $xmlSource = $argv[1];
//         $limit = isset($argv[2]) && is_numeric($argv[2]) ? (int)$argv[2] : 0;
//         $enableUpload = in_array('--upload', $argv);
//         $testMode = in_array('--test', $argv);
        
//         echo "🚀 Starting Dynamic XML Scraper\n";
//         echo "📊 Source: $xmlSource\n";
//         echo "📊 Limit: " . ($limit > 0 ? $limit : 'No limit') . "\n";
//         echo "📊 Upload: " . ($enableUpload ? 'Enabled' : 'Disabled') . "\n";
//         echo "📊 Test Mode: " . ($testMode ? 'Enabled' : 'Disabled') . "\n\n";
        
//         $scraper = new DynamicXmlScraperAdvanced('CLIScraper', 'cli_scrape.json');
        
//         if ($testMode) {
//             $scraper->setTestingMode(true);
//         }
        
//         if ($enableUpload) {
//             $scraper->enableUpload();
//         }
        
//         $success = $scraper->runWithRetry($xmlSource, $limit, 3);
        
//         if ($success) {
//             $stats = $scraper->getProcessingStats();
//             echo "\n📈 Final Statistics:\n";
//             echo "✅ Created: {$stats['created']}\n";
//             echo "✅ Updated: {$stats['updated']}\n";
//             echo "✅ Total: {$stats['total_processed']}\n";
//         }
        
//         return $success;
//     }
    
//     // Uncomment the following line to enable CLI usage:
//     // runCliScraper($argv);
// }

?>