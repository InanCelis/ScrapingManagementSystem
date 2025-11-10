<?php
require_once __DIR__ . '/../Api/ApiSender.php';
require_once __DIR__ . '/../Helpers/ScraperHelpers.php';
require_once __DIR__ . '/../Helpers/XMLHelpers.php';

class ThaiProperty1 {
    private string $foldername;
    private string $filename;
    private ApiSender $apiSender;
    private ScraperHelpers $helpers;
    private XMLHelpers $xmlHelpers;
    private int $successCreated = 0;
    private int $successUpdated = 0;
    private bool $enableUpload = false;
    private bool $testingMode = false;
    private array $config = [];
    private array $confidentialInfo = [];

    public function __construct(string $foldername = 'ThaiProperty1', string $filename = 'properties.json') {
        $this->foldername = $foldername;
        $this->filename = $filename;
        $this->apiSender = new ApiSender(true);
        $this->helpers = new ScraperHelpers();
        $this->xmlHelpers = new XMLHelpers();
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

    public function setConfidentialInfo(array $confidentialInfo): void {
        $this->confidentialInfo = $confidentialInfo;
    }

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

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        if (!$this->xmlHelpers->validateXml($xmlContent)) {
            return false;
        }

        $properties = $this->parseXmlProperties($xmlContent);
        if (empty($properties)) {
            echo "❌ No properties found in XML\n";
            return false;
        }

        if ($limit > 0) {
            $properties = array_slice($properties, 0, $limit);
        }

        echo "📊 Total properties to process: " . count($properties) . "\n\n";

        file_put_contents($outputFile, "[");

        $propertyCounter = 0;
        foreach ($properties as $index => $property) {
            echo "🏠 Processing property " . ($index + 1) . "/" . count($properties) . "\n";
            $propertyData = $this->processProperty($property);

            if (!empty($propertyData)) {
                $jsonEntry = json_encode($propertyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                file_put_contents($outputFile, ($propertyCounter > 0 ? "," : "") . "\n" . $jsonEntry, FILE_APPEND);
                $propertyCounter++;

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
                    }
                    sleep(1);
                }
            }
        }

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

        // ThaiProperty1 uses <listings><listing> structure
        if (isset($xml->listing)) {
            foreach ($xml->listing as $listing) {
                $properties[] = $listing;
            }
            echo "📋 Found properties using structure: <listing>\n";
        }

        echo "📋 Total properties found: " . count($properties) . "\n";

        if ($this->testingMode && count($properties) > 0) {
            $this->saveDebugInfo($properties[0]);
        }

        return $properties;
    }

    private function saveDebugInfo(SimpleXMLElement $firstProperty): void {
        $debugFolder = __DIR__ . '/../ScrapeFile/' . $this->foldername;

        $structureFile = $debugFolder . '/xml_structure_debug.txt';
        file_put_contents($structureFile, print_r($firstProperty, true));

        $fieldsFile = $debugFolder . '/available_fields.txt';
        $fields = [];
        foreach ($firstProperty as $key => $value) {
            $fields[] = $key . ' = ' . (string)$value;
        }
        file_put_contents($fieldsFile, implode("\n", $fields));

        $sampleFile = $debugFolder . '/xml_sample.xml';
        file_put_contents($sampleFile, $firstProperty->asXML());

        echo "🔍 Debug files saved: xml_structure_debug.txt, available_fields.txt, xml_sample.xml\n";
    }

    private function getDefaultConfig(): array {
        return [
            'default_currency' => 'THB',
            'size_prefix' => 'sqm',
            'listing_id_prefix' => 'TP1-',
            'default_country' => 'Thailand',
            'website_url' => 'https://thaiproperty1.com/api/lA1bwo/feeds/nestopa-feed'
        ];
    }

    private function processProperty(SimpleXMLElement $property): ?array {
        try {
            // Build confidential info
            $confidentialInfo = $this->buildConfidentialInfo();

            // Extract listing ID and reference
            $listing_id = trim((string)$property->id);
            $reference = trim((string)$property->reference);

            if (empty($listing_id)) {
                echo "❌ Skipping property with missing listing ID\n";
                return null;
            }

            // Extract multilingual title (prefer English)
            $title = $this->extractTitle($property);

            // Extract multilingual description (prefer English)
            $description = $this->extractDescription($property);

            // Create excerpt
            $plainText = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plainText = strip_tags($plainText);
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $plainText = trim($plainText);
            $excerpt = substr($plainText, 0, 300);

            // Price extraction - check both sale and rent
            $priceSale = (int)trim((string)$property->priceSale);
            $priceRent = (int)trim((string)$property->priceRent);

            $price = $priceSale > 0 ? $priceSale : $priceRent;

            if ($price <= 0) {
                echo "❌ Skipping property with invalid price\n";
                return null;
            }

            // Property details
            $bedrooms = (int)trim((string)$property->bedrooms);
            $bathrooms = (int)trim((string)$property->bathrooms);
            $interiorSize = (int)trim((string)$property->interiorSize);
            $landSize = (int)trim((string)$property->landSize);
            $floor = trim((string)$property->floor);
            $garages = (int)trim((string)$property->garages);

            // Use interior size, fallback to land size
            $size = $interiorSize > 0 ? $interiorSize : $landSize;

            // Property type
            $typeValue = trim((string)$property->type);
            $property_type = $this->mapPropertyType($typeValue);

            if (empty($property_type)) {
                echo "❌ Skipping property with invalid type: $typeValue\n";
                return null;
            }

            // Property status based on which price is set
            $property_status = $priceSale > 0 ? ['For Sale'] : ['For Rent'];

            // Location - extract coordinates
            $latitude = trim((string)$property->gpsLat);
            $longitude = trim((string)$property->gpsLon);
            $location = '';
            $city = '';
            $state = '';
            $area = '';
            $country = $this->config['default_country'];
            $address = '';

            if (!empty($latitude) && !empty($longitude) && is_numeric($latitude) && is_numeric($longitude)) {
                $location = $latitude . ', ' . $longitude;

                // Get location details from coordinates
                $locationData = $this->helpers->getLocationDataByCoords($latitude, $longitude);

                if ($locationData) {
                    $city = $locationData['city'] ?? '';
                    $state = $locationData['state'] ?? '';
                    $country = $locationData['country'] ?? $this->config['default_country'];
                    $address = $locationData['address'] ?? $country;

                    // Use formatted_address as area if available
                    $area = $locationData['formatted_address'] ?? '';
                } else {
                    // Fallback to default country if no location data
                    $address = $country;
                }
            }

            // Images
            $images = $this->extractImages($property);

            if (empty($images)) {
                echo "❌ Skipping property with no images\n";
                return null;
            }

            $images = [];
            // Features - get only from XML
            $features = $this->extractFeatures($property);

            // Video URL
            $videoUrl = trim((string)$property->videos);

            return [
                "property_title" => $title,
                "property_description" => $description,
                "property_excerpt" => $excerpt,
                "price" => $price,
                "currency" => $this->config['default_currency'],
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
                "property_area" => $area,
                "city" => $city,
                "state" => $state,
                "country" => $country,
                "zip_code" => "",
                "latitude" => $latitude,
                "longitude" => $longitude,
                "listing_id" => $this->config['listing_id_prefix'] . $listing_id,
                "agent_id" => "150",
                "agent_display_option" => "agent_info",
                "video_url" => $videoUrl,
                "images" => $images,
                "property_map" => "1",
                "property_year" => "",
                "additional_features" => $features,
                "confidential_info" => $confidentialInfo,
                "post_author" => "163",
                "draft" => true,
            ];

        } catch (Exception $e) {
            echo "❌ Error processing property: " . $e->getMessage() . "\n";
            return null;
        }
    }

    private function extractTitle(SimpleXMLElement $property): string {
        // ThaiProperty1 uses <titles><title lang="en">...</title></titles>
        if (isset($property->titles)) {
            foreach ($property->titles->title as $title) {
                $lang = (string)$title['lang'];
                if ($lang === 'en') {
                    return trim((string)$title);
                }
            }

            // Fallback to first available title
            if (isset($property->titles->title[0])) {
                return trim((string)$property->titles->title[0]);
            }
        }

        return 'Property in Thailand';
    }

    private function extractDescription(SimpleXMLElement $property): string {
        // ThaiProperty1 uses <descriptions><description lang="en"><![CDATA[...]]></description></descriptions>
        if (isset($property->descriptions)) {
            foreach ($property->descriptions->description as $description) {
                $lang = (string)$description['lang'];
                if ($lang === 'en') {
                    return trim((string)$description);
                }
            }

            // Fallback to first available description
            if (isset($property->descriptions->description[0])) {
                return trim((string)$property->descriptions->description[0]);
            }
        }

        return '';
    }

    private function mapPropertyType(string $typeValue): array {
        $typeMapping = [
            'Villa/House' => ['Villa', 'House'],
            'Villa' => ['Villa'],
            'House' => ['House'],
            'Condo' => ['Condo'],
            'Condominium' => ['Condo'],
            'Apartment' => ['Apartment'], 
            'Penthouse' => ['Penthouse'],
            'Townhouse' => ['Townhouse'],
            // 'Land' => ['Land']
        ];

        if (isset($typeMapping[$typeValue])) {
            return $typeMapping[$typeValue];
        }

        // Try to match with helper
        $allowedType = $this->helpers->allowedPropertyType($typeValue);
        if ($allowedType) {
            return [$allowedType];
        }

        return [];
    }

    private function extractImages(SimpleXMLElement $property): array {
        $images = [];

        if (isset($property->images)) {
            foreach ($property->images->url as $url) {
                $imageUrl = trim((string)$url);
                if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $images[] = $imageUrl;
                }
            }
        }

        // Limit to 10 images
        return array_slice(array_unique($images), 0, 10);
    }

    private function extractFeatures(SimpleXMLElement $property): array {
        $features = [];

        if (isset($property->features)) {
            foreach ($property->features->feature as $feature) {
                $featureText = trim((string)$feature);
                if (!empty($featureText)) {
                    $features[] = $featureText;
                }
            }
        }

        return array_unique($features);
    }

    private function buildConfidentialInfo(): array {
        $confidentialInfo = [];

        // Add Website first
        if (!empty($this->config['website_url'])) {
            $confidentialInfo[] = [
                "fave_additional_feature_title" => "Website",
                "fave_additional_feature_value" => $this->config['website_url']
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
}

?>
