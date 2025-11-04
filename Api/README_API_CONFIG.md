# API Configuration Guide

## Overview
The `ApiSender` class now supports configurable API domains and settings, making it easy to switch between different environments or API endpoints.

## Configuration Location
The API settings are stored in: `config/config.php`

## Configuration Options

```php
'api' => [
    'base_domain' => 'https://internationalpropertyalerts.com',  // EDITABLE: Change this to your API domain
    'endpoints' => [
        'properties' => '/wp-json/houzez/v1/properties',
        'links' => '/wp-json/houzez/v1/links-by-owner',
    ],
    'token' => 'your-api-token-here',                            // EDITABLE: Your API authentication token
    'max_retries' => 3,                                          // Number of retry attempts
    'timeout' => 600,                                            // Request timeout in seconds
    'connect_timeout' => 60,                                     // Connection timeout in seconds
    'debug' => false,                                            // Enable/disable debug mode
],
```

## Usage Examples

### 1. Using Default Configuration
```php
require_once 'Api/ApiSender.php';

// Uses configuration from config/config.php
$apiSender = new ApiSender();

// Send a property
$result = $apiSender->sendProperty($propertyData);
```

### 2. Using Custom Domain (Constructor)
```php
require_once 'Api/ApiSender.php';

// Override domain at instantiation
$apiSender = new ApiSender(false, 'https://custom-domain.com');

$result = $apiSender->sendProperty($propertyData);
```

### 3. Using Custom Domain (Setter Method)
```php
require_once 'Api/ApiSender.php';

$apiSender = new ApiSender();

// Change domain after instantiation
$apiSender->setBaseDomain('https://staging-domain.com');

$result = $apiSender->sendProperty($propertyData);
```

### 4. Enable Debug Mode
```php
require_once 'Api/ApiSender.php';

// Enable debug mode
$apiSender = new ApiSender(true);

$result = $apiSender->sendProperty($propertyData);
```

### 5. Update API Token
```php
require_once 'Api/ApiSender.php';

$apiSender = new ApiSender();

// Update token dynamically
$apiSender->setToken('new-token-here');

$result = $apiSender->sendProperty($propertyData);
```

## Available Methods

### Configuration Methods
- `getBaseDomain()` - Get the current base domain
- `setBaseDomain(string $domain)` - Set a new base domain
- `getToken()` - Get the current API token
- `setToken(string $token)` - Set a new API token

### API Methods
- `sendProperty(array $propertyData)` - Send property data to the API
- `getPropertyById(string $propertyId)` - Retrieve a property by ID
- `getPropertyLinks(string $owner, ?int $start, ?int $end)` - Get property links by owner
- `updatePropertyToDraft(string $listingId)` - Update a property to draft status

## Editing the Configuration

### Step 1: Open the config file
Navigate to: `config/config.php`

### Step 2: Locate the API section
Find the `'api'` array in the configuration file.

### Step 3: Update the base_domain
Change the `'base_domain'` value to your desired API domain:

```php
'base_domain' => 'https://your-custom-domain.com',
```

### Step 4: Update the token (if needed)
Change the `'token'` value to your API authentication token:

```php
'token' => 'your-new-api-token',
```

### Step 5: Save the file
Save the `config/config.php` file and your changes will take effect immediately.

## Environment-Specific Configuration

For different environments (development, staging, production), you can:

1. Create environment-specific config files:
   - `config/config.dev.php`
   - `config/config.staging.php`
   - `config/config.production.php`

2. Load the appropriate config based on environment variable

## Security Notes

- **Never commit sensitive tokens** to version control
- Consider using environment variables for sensitive data
- Use `.gitignore` to exclude `config/config.php` if it contains sensitive information
- Rotate API tokens regularly

## Backward Compatibility

The class maintains backward compatibility. If the config file is missing, it falls back to default values.
