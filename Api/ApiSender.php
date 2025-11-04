<?php
// File: ApiSender.php

class ApiSender {
    private string $apiUrl;
    private string $linksApiUrl;
    private string $draftApiUrl;
    private string $token;
    private int $maxRetries;
    private int $timeout;
    private int $connectTimeout;
    private bool $debug;
    private array $config;

    public function __construct(bool $debug = false, ?string $customDomain = null) {
        // Load configuration
        $this->loadConfig($customDomain);

        // Build API URLs
        $this->apiUrl = $this->config['base_domain'] . $this->config['endpoints']['properties'];
        $this->linksApiUrl = $this->config['base_domain'] . $this->config['endpoints']['links'];
        $this->draftApiUrl = $this->config['base_domain'] . $this->config['endpoints']['properties'];

        // Set other properties from config
        $this->token = $this->config['token'];
        $this->maxRetries = $this->config['max_retries'];
        $this->timeout = $this->config['timeout'];
        $this->connectTimeout = $this->config['connect_timeout'];
        $this->debug = $debug !== false ? $debug : $this->config['debug'];
    }

    /**
     * Load configuration from database (priority), config file (fallback), or use custom domain
     * @param string|null $customDomain Custom domain to override config
     */
    private function loadConfig(?string $customDomain = null): void {
        // Default configuration
        $this->config = [
            'base_domain' => 'https://internationalpropertyalerts.com',
            'endpoints' => [
                'properties' => '/wp-json/houzez/v1/properties',
                'links' => '/wp-json/houzez/v1/links-by-owner',
            ],
            'token' => 'eyJpYXQiOjE3NTk4NDI5OTYsImV4cCI6MTc2MDAxNTc5Nn0=',
            'max_retries' => 3,
            'timeout' => 600,
            'connect_timeout' => 60,
            'debug' => false,
        ];

        // Try to load from database first
        try {
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance();

            $dbSettings = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE category = 'api'");

            if (!empty($dbSettings)) {
                foreach ($dbSettings as $setting) {
                    $key = $setting['setting_key'];
                    $value = $setting['setting_value'];

                    // Map database keys to config structure
                    switch ($key) {
                        case 'api_base_domain':
                            $this->config['base_domain'] = $value;
                            break;
                        case 'api_token':
                            $this->config['token'] = $value;
                            break;
                        case 'api_max_retries':
                            $this->config['max_retries'] = (int)$value;
                            break;
                        case 'api_timeout':
                            $this->config['timeout'] = (int)$value;
                            break;
                        case 'api_connect_timeout':
                            $this->config['connect_timeout'] = (int)$value;
                            break;
                        case 'api_debug':
                            $this->config['debug'] = (bool)$value;
                            break;
                        case 'api_properties_endpoint':
                            $this->config['endpoints']['properties'] = $value;
                            break;
                        case 'api_links_endpoint':
                            $this->config['endpoints']['links'] = $value;
                            break;
                    }
                }
            }
        } catch (Exception $e) {
            // Database not available or table doesn't exist, try config file
        }

        // Fallback to config file if database didn't have settings
        $configFile = __DIR__ . '/../config/config.php';
        if (file_exists($configFile) && empty($dbSettings)) {
            $appConfig = require $configFile;
            if (isset($appConfig['api'])) {
                $this->config = array_merge($this->config, $appConfig['api']);
            }
        }

        // Override base domain if custom domain is provided
        if ($customDomain !== null) {
            $this->config['base_domain'] = rtrim($customDomain, '/');
        }
    }

    public function sendProperty(array $propertyData): array {
        $postData = [
            'properties' => [$propertyData]
        ];

        $attempt = 0;
        $lastError = null;
        $lastResponse = null;
        
        while ($attempt < $this->maxRetries) {
            $attempt++;
            $this->log("Attempt $attempt of {$this->maxRetries}");

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Expect:' // Fixes 100-continue server issues
                ],
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FAILONERROR => false, // We'll handle errors manually
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TCP_KEEPALIVE => true,
                CURLOPT_TCP_KEEPIDLE => 120,
                CURLOPT_TCP_KEEPINTVL => 60
            ]);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $duration = round(microtime(true) - $startTime, 2);
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $lastResponse = $response;
            curl_close($ch);

            if ($error) {
                $lastError = "CURL Error: $error";
                $this->log("⚠️ Request failed: $lastError (Duration: {$duration}s)");
            } elseif ($httpCode >= 200 && $httpCode < 300) {
                $decodedResponse = json_decode($response, true) ?? $response;
                $this->log("✅ Success (HTTP $httpCode) in {$duration}s");
                return [
                    'success' => true,
                    'response' => $decodedResponse,
                    'attempts' => $attempt,
                    'duration' => $duration
                ];
            } else {
                $lastError = "HTTP $httpCode";
                $this->log("⚠️ Server responded with HTTP $httpCode (Duration: {$duration}s)");
                if ($this->debug && $response) {
                    $this->log("Response: " . substr($response, 0, 1000));
                }
            }

            if ($attempt < $this->maxRetries) {
                $sleepTime = min(10, pow(2, $attempt)); // Cap at 10 seconds max
                $this->log("⏳ Retrying in $sleepTime seconds...");
                sleep($sleepTime);
            }
        }

        $this->log("❌ All attempts failed. Last error: $lastError");
        return [
            'success' => false,
            'error' => $lastError,
            'attempts' => $attempt,
            'last_response' => $lastResponse,
            'http_code' => $httpCode ?? null
        ];
    }

    /**
     * Get property details by property ID
     * @param string $propertyId The property ID to retrieve
     * @return array Array containing success status and property data
     */
    public function getPropertyById(string $propertyId): array {
        try {
            $this->log("Fetching property details for ID: $propertyId");
            
            $url = $this->apiUrl . '/' . urlencode($propertyId);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FAILONERROR => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $duration = round(microtime(true) - $startTime, 2);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->log("CURL Error: $error");
                return [
                    'success' => false,
                    'error' => "CURL Error: $error",
                    'property_id' => $propertyId
                ];
            }

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if ($data) {
                    $this->log("✅ Successfully retrieved property details in {$duration}s");
                    return [
                        'success' => true,
                        'property' => $data,
                        'property_id' => $propertyId,
                        'duration' => $duration
                    ];
                } else {
                    $this->log("Failed to decode JSON response\n");
                    return [
                        'success' => false,
                        'error' => 'Invalid JSON response',
                        'property_id' => $propertyId,
                        'raw_response' => $response
                    ];
                }
            } elseif ($httpCode === 404) {
                $this->log("Property not found (HTTP 404)");
                return [
                    'success' => false,
                    'error' => 'Property not found',
                    'property_id' => $propertyId,
                    'http_code' => $httpCode
                ];
            } elseif ($httpCode === 401) {
                $this->log("Unauthorized access (HTTP 401) - Check token \n");
                return [
                    'success' => false,
                    'error' => 'Unauthorized access - Invalid or expired token',
                    'property_id' => $propertyId,
                    'http_code' => $httpCode
                ];
            } else {
                $this->log("API request failed with HTTP code: $httpCode \n");
                if ($this->debug) {
                    $this->log("Response: " . substr($response, 0, 500));
                }
                return [
                    'success' => false,
                    'error' => "HTTP $httpCode",
                    'property_id' => $propertyId,
                    'http_code' => $httpCode,
                    'raw_response' => $response
                ];
            }

        } catch (Exception $e) {
            $this->log("Exception while fetching property: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'property_id' => $propertyId
            ];
        }
    }

    public function getPropertyLinks(string $owner, ?int $start = null, ?int $end = null): array {
        try {
            $logMessage = "Fetching property links for owner: $owner";
            if ($start !== null && $end !== null) {
                $logMessage .= " (range: $start to $end)";
            }
            $this->log($logMessage);
            
            // Build URL with parameters
            $url = $this->linksApiUrl . '?owner=' . urlencode($owner);
            if ($start !== null) {
                $url .= '&start=' . $start;
            }
            if ($end !== null) {
                $url .= '&end=' . $end;
            }
            
            // Make API request to get property links (no token needed)
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $duration = round(microtime(true) - $startTime, 2);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->log("CURL Error: $error");
                return [
                    'success' => false,
                    'error' => "CURL Error: $error",
                    'links' => [],
                    'count' => 0
                ];
            }

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if ($data && isset($data['links']) && is_array($data['links'])) {
                    $totalCount = $data['count'] ?? count($data['links']);
                    $returnedCount = count($data['links']);
                    
                    $logMessage = "Retrieved $returnedCount";
                    if ($start !== null && $end !== null) {
                        $logMessage .= " of " . ($data['pagination']['total_count'] ?? 'unknown') . " total";
                    }
                    $logMessage .= " property links in {$duration}s";
                    $this->log($logMessage);
                    
                    return [
                        'success' => true,
                        'links' => $data['links'],
                        'count' => $returnedCount,
                        'total_count' => $data['pagination']['total_count'] ?? $totalCount,
                        'pagination' => $data['pagination'] ?? null,
                        'start' => $start,
                        'end' => $end,
                        'duration' => $duration
                    ];
                } else {
                    $this->log("API response format unexpected");
                    if ($this->debug) {
                        $this->log("Response: " . substr($response, 0, 200));
                    }
                    return [
                        'success' => false,
                        'error' => 'Invalid API response format',
                        'links' => [],
                        'count' => 0,
                        'raw_response' => $response
                    ];
                }
            } else {
                $this->log("API request failed with HTTP code: $httpCode");
                if ($this->debug) {
                    $this->log("Response: " . substr($response, 0, 200));
                }
                return [
                    'success' => false,
                    'error' => "HTTP $httpCode",
                    'links' => [],
                    'count' => 0,
                    'http_code' => $httpCode,
                    'raw_response' => $response
                ];
            }

        } catch (Exception $e) {
            $this->log("Exception while fetching links from API: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'links' => [],
                'count' => 0
            ];
        }
    }

     /**
     * Update a single property to draft status by listing ID
     * @param string $listingId The listing ID of the property to update
     * @return array Array containing success status and response data
     */
    public function updatePropertyToDraft(string $listingId): array {
        try {
            $this->log("Updating property to draft status. Listing ID: $listingId");
            
            $url = $this->draftApiUrl . '/' . urlencode($listingId) . '/draft';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FAILONERROR => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $duration = round(microtime(true) - $startTime, 2);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->log("CURL Error: $error");
                return [
                    'success' => false,
                    'error' => "CURL Error: $error",
                    'listing_id' => $listingId
                ];
            }

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if ($data && isset($data['success']) && $data['success']) {
                    $this->log("Successfully updated property to draft in {$duration}s");
                    return [
                        'success' => true,
                        'data' => $data,
                        'listing_id' => $listingId,
                        'duration' => $duration
                    ];
                } else {
                    $this->log("API returned success=false or unexpected format");
                    return [
                        'success' => false,
                        'error' => $data['message'] ?? 'Unknown API error',
                        'listing_id' => $listingId,
                        'raw_response' => $response
                    ];
                }
            } elseif ($httpCode === 404) {
                $this->log("Property not found (HTTP 404)");
                return [
                    'success' => false,
                    'error' => 'Property not found',
                    'listing_id' => $listingId,
                    'http_code' => $httpCode
                ];
            } else {
                $this->log("API request failed with HTTP code: $httpCode");
                if ($this->debug) {
                    $this->log("Response: " . substr($response, 0, 500));
                }
                return [
                    'success' => false,
                    'error' => "HTTP $httpCode",
                    'listing_id' => $listingId,
                    'http_code' => $httpCode,
                    'raw_response' => $response
                ];
            }

        } catch (Exception $e) {
            $this->log("Exception while updating property to draft: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'listing_id' => $listingId
            ];
        }
    }


    /**
     * Get the current base domain
     * @return string The current base domain
     */
    public function getBaseDomain(): string {
        return $this->config['base_domain'];
    }

    /**
     * Set a custom base domain
     * @param string $domain The new base domain
     */
    public function setBaseDomain(string $domain): void {
        $this->config['base_domain'] = rtrim($domain, '/');

        // Rebuild API URLs
        $this->apiUrl = $this->config['base_domain'] . $this->config['endpoints']['properties'];
        $this->linksApiUrl = $this->config['base_domain'] . $this->config['endpoints']['links'];
        $this->draftApiUrl = $this->config['base_domain'] . $this->config['endpoints']['properties'];
    }

    /**
     * Get the current API token
     * @return string The current API token
     */
    public function getToken(): string {
        return $this->token;
    }

    /**
     * Set a custom API token
     * @param string $token The new API token
     */
    public function setToken(string $token): void {
        $this->token = $token;
        $this->config['token'] = $token;
    }

    private function log(string $message): void {
        echo "[" . date('Y-m-d H:i:s') . "] $message\n";
        // Consider adding file logging here for production
    }
}