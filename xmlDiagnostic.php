<?php
// Working authentication script for EgoRealEstate

class EgoAuthenticator {
    private string $loginUrl = "https://media.egorealestate.com/XML/1320/Properties/login.aspx";
    private string $xmlUrl = "https://media.egorealestate.com/XML/1320/Properties/Properties_XML_1320.xml";
    private string $username = "cobeegoproperties";
    private string $password = "5Cx!propertiestaB!04";
    
    public function getXmlData(): ?string {
        echo "Starting authentication process...\n";
        
        // Step 1: Get login form with hidden fields
        $formData = $this->getLoginFormData();
        if (!$formData) {
            echo "Failed to get login form data\n";
            return null;
        }
        
        // Step 2: Submit login
        $cookieJar = tempnam(sys_get_temp_dir(), 'ego_cookies');
        $loginSuccess = $this->submitLogin($formData, $cookieJar);
        
        if (!$loginSuccess) {
            echo "Login failed\n";
            unlink($cookieJar);
            return null;
        }
        
        // Step 3: Get XML data with session cookies
        $xmlData = $this->fetchXmlData($cookieJar);
        unlink($cookieJar);
        
        return $xmlData;
    }
    
    private function getLoginFormData(): ?array {
        echo "Getting login form...\n";
        
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
        
        // Extract hidden form fields
        $formData = [];
        
        // Extract __VIEWSTATE
        if (preg_match('/<input[^>]*name="__VIEWSTATE"[^>]*value="([^"]*)"[^>]*>/i', $html, $matches)) {
            $formData['__VIEWSTATE'] = $matches[1];
        }
        
        // Extract __VIEWSTATEGENERATOR
        if (preg_match('/<input[^>]*name="__VIEWSTATEGENERATOR"[^>]*value="([^"]*)"[^>]*>/i', $html, $matches)) {
            $formData['__VIEWSTATEGENERATOR'] = $matches[1];
        }
        
        // Extract __EVENTVALIDATION
        if (preg_match('/<input[^>]*name="__EVENTVALIDATION"[^>]*value="([^"]*)"[^>]*>/i', $html, $matches)) {
            $formData['__EVENTVALIDATION'] = $matches[1];
        }
        
        echo "Extracted " . count($formData) . " hidden form fields\n";
        return $formData;
    }
    
    private function submitLogin(array $formData, string $cookieJar): bool {
        echo "Submitting login...\n";
        
        // Add credentials to form data
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
        
        echo "Login HTTP Code: $httpCode\n";
        echo "Final URL after login: $finalUrl\n";
        
        // Check if login was successful (should redirect away from login page)
        $loginSuccessful = ($httpCode === 200 && strpos($finalUrl, 'login.aspx') === false);
        
        if ($loginSuccessful) {
            echo "Login appears successful - redirected away from login page\n";
        } else {
            echo "Login may have failed - still on login page\n";
            // Save response for debugging
            if ($result) {
                file_put_contents('login_debug.html', $result);
                echo "Login response saved to login_debug.html\n";
            }
        }
        
        return $loginSuccessful;
    }
    
    private function fetchXmlData(string $cookieJar): ?string {
        echo "Fetching XML data with authenticated session...\n";
        
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
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        
        echo "XML fetch HTTP Code: $httpCode\n";
        echo "Final URL: $finalUrl\n";
        
        if ($httpCode === 200 && $result) {
            // Check if it's actually XML
            if (strpos(trim($result), '<?xml') === 0 || strpos($result, '<Report>') !== false) {
                echo "SUCCESS: Got XML data!\n";
                echo "XML length: " . strlen($result) . " bytes\n";
                
                // Save the XML
                file_put_contents('ego_properties.xml', $result);
                echo "XML saved to: ego_properties.xml\n";
                
                return $result;
            } else {
                echo "Got response but not XML. First 200 chars:\n";
                echo substr($result, 0, 200) . "\n";
                file_put_contents('xml_fetch_debug.txt', $result);
                echo "Full response saved to xml_fetch_debug.txt\n";
            }
        }
        
        return null;
    }
}

// Test the authentication
echo "Testing EgoRealEstate XML Authentication\n";
echo "=====================================\n\n";

$auth = new EgoAuthenticator();
$xmlData = $auth->getXmlData();

if ($xmlData) {
    // Parse and show structure
    $xml = simplexml_load_string($xmlData);
    if ($xml) {
        echo "\nXML Structure Analysis:\n";
        echo "Root element: " . $xml->getName() . "\n";
        
        if (isset($xml->Row)) {
            echo "Found " . count($xml->Row) . " property records\n";
            
            if (count($xml->Row) > 0) {
                echo "\nFirst property fields:\n";
                $count = 0;
                foreach ($xml->Row[0]->children() as $name => $value) {
                    $val = trim((string)$value);
                    if (strlen($val) > 50) $val = substr($val, 0, 50) . "...";
                    echo "- $name: $val\n";
                    if (++$count >= 10) {
                        echo "... and " . (count($xml->Row[0]->children()) - 10) . " more fields\n";
                        break;
                    }
                }
            }
        }
    }
} else {
    echo "\nFailed to get XML data. Check the debug files for more information.\n";
}