<?php

class XMLHelpers {
    private $processor;
    private $processorMethod;
    
    public function runXML(string $xmlInput, int $limit = 0, $processor = null, string $method = 'processXmlContent'): bool {
        $this->processor = $processor;
        $this->processorMethod = $method;
        
        $inputType = $this->detectInputType($xmlInput);
        
        echo "🔍 Input detected as: $inputType\n";
        
        switch ($inputType) {
            case 'url':
                return $this->processFromUrl($xmlInput, $limit);
                
            case 'file':
                return $this->processFromFile($xmlInput, $limit);
                
            case 'xml_string':
                return $this->processFromString($xmlInput, $limit);
                
            default:
                echo "❌ Could not determine input type for: $xmlInput\n";
                return false;
        }
    }

    /**
     * Detect whether input is URL, file path, or XML string
     */
    public function detectInputType(string $input): string {
        // Check if it's a URL
        if (filter_var($input, FILTER_VALIDATE_URL) !== false) {
            return 'url';
        }
        
        // Check if it's a file path
        if (file_exists($input)) {
            return 'file';
        }
        
        // Check if it's an XML string (starts with <?xml or contains XML tags)
        $trimmedInput = trim($input);
        if (strpos($trimmedInput, '<?xml') === 0 || 
            (strpos($trimmedInput, '<') !== false && strpos($trimmedInput, '>') !== false)) {
            return 'xml_string';
        }
        
        // If none of the above, it might be a file path that doesn't exist yet
        // or a URL without proper protocol
        if (strpos($input, '.xml') !== false || strpos($input, '/') !== false) {
            // Try to treat as file first, then URL
            if (strpos($input, 'http') === false && strpos($input, '://') === false) {
                return 'file'; // Likely a file path
            } else {
                return 'url'; // Likely a URL
            }
        }
        
        return 'unknown';
    }

    public function processFromUrl(string $xmlUrl, int $limit): bool {
        echo "🌐 Fetching XML from URL: {$xmlUrl}\n";

        $xmlContent = $this->fetchXmlFromUrl($xmlUrl);
        if (!$xmlContent) {
            echo "❌ Failed to fetch XML content from URL\n";
            return false;
        }

        return $this->callProcessor($xmlContent, $limit);
    }

    public function processFromFile(string $filePath, int $limit): bool {
        echo "📁 Reading XML from local file: {$filePath}\n";

        if (!file_exists($filePath)) {
            echo "❌ File not found: {$filePath}\n";
            return false;
        }

        $xmlContent = file_get_contents($filePath);
        if (!$xmlContent) {
            echo "❌ Failed to read XML content from file\n";
            return false;
        }

        return $this->callProcessor($xmlContent, $limit);
    }

    public function processFromString(string $xmlContent, int $limit): bool {
        echo "📄 Processing XML from string content\n";
        return $this->callProcessor($xmlContent, $limit);
    }

    private function callProcessor(string $xmlContent, int $limit): bool {
        if (!$this->processor) {
            echo "❌ No processor provided\n";
            return false;
        }

        $method = $this->processorMethod;
        
        if (method_exists($this->processor, $method)) {
            return $this->processor->$method($xmlContent, $limit);
        } else {
            echo "❌ Method {$method} not found in processor class\n";
            return false;
        }
    }

    private function fetchXmlFromUrl(string $xmlUrl): ?string {
        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $xmlUrl)) {
            $xmlUrl = 'https://' . $xmlUrl;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $xmlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 600, // Increased timeout to 2 minutes
            CURLOPT_CONNECTTIMEOUT => 150, // Connection timeout
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; DynamicXMLScraper/1.0)',
            CURLOPT_HTTPHEADER => [
                'Accept: application/xml, text/xml, */*',
                'Cache-Control: no-cache'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '', // Handle compression
            CURLOPT_BUFFERSIZE => 128000, // Larger buffer for big files
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            echo "❌ cURL Error: $error\n";
            return null;
        }

        if ($httpCode !== 200) {
            echo "❌ HTTP Error: $httpCode\n";
            if ($contentType) {
                echo "⚠️ Content-Type: $contentType\n";
            }
            return null;
        }

        return $result;
    }



    public function validateXml(string $xmlContent): bool {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            echo "❌ XML validation errors:\n";
            foreach ($errors as $error) {
                echo "  - Line {$error->line}: " . trim($error->message) . "\n";
            }
            libxml_clear_errors();
            return false;
        }
        
        echo "✅ XML validation successful\n";
        return true;
    }
}

?>