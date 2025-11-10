# ThaiProperty1 XML Scraper

## Overview
Clean XML scraper for ThaiProperty1 real estate listings from the Nestopa feed API.

## XML Feed
- **URL**: `https://thaiproperty1.com/api/lA1bwo/feeds/nestopa-feed`
- **Structure**: `<listings><listing>...</listing></listings>`
- **Total Properties**: 771 listings

## Features Extracted

### Basic Information
- Multilingual titles (English, Thai, Norwegian)
- Multilingual descriptions with CDATA support
- Property reference and ID
- Property type (Villa/House, Condo)

### Pricing
- Sale price (`priceSale`)
- Rental price (`priceRent`)
- Currency: THB (Thai Baht)

### Property Details
- Bedrooms
- Bathrooms
- Interior size (sqm)
- Land size (sqm)
- Floor level
- Garage spaces
- Ownership type (FREEHOLD/LEASEHOLD)

### Location
- GPS coordinates (latitude/longitude)
- Country: Thailand

### Media
- Property images (up to 10 images)
- Video URLs (if available)

### Additional Features
- Property amenities and features
- Ownership details added to features
- Land size info (when different from interior size)
- Floor and garage information

## Usage

### Basic Usage
```php
require_once 'ExecutableXML/ThaiProperty1.php';

$scraper = new ThaiProperty1();
$scraper->run('https://thaiproperty1.com/api/lA1bwo/feeds/nestopa-feed');
```

### With Options
```php
$scraper = new ThaiProperty1();

// Enable testing mode (saves debug files)
$scraper->setTestingMode(true);

// Enable API upload
$scraper->enableUpload(true);

// Custom configuration
$scraper->setConfig([
    'listing_id_prefix' => 'CUSTOM-',
    'website_url' => 'https://custom-website.com'
]);

// Pass confidential info dynamically
$confidentialInfo = [
    'Owned by' => 'Thai Property 1',
    'Contact Person' => 'John Doe',
    'Phone' => '+66 (0) 38 412 122',
    'Email' => 'info@thaiproperty1.com',
    'listing_id_prefix' => 'CUSTOM-' // Can also set prefix here
];

// Process with limit and confidential info
$scraper->run($xmlUrl, 10, $confidentialInfo);
```

## Configuration Options

```php
'default_currency' => 'THB'
'size_prefix' => 'sqm'
'listing_id_prefix' => 'TP1-'
'default_country' => 'Thailand'
'website_url' => 'https://thaiproperty1.com/api/lA1bwo/feeds/nestopa-feed'
```

## Confidential Info (Dynamic)

The scraper supports dynamic confidential information that can be passed via the `run()` method:

```php
$confidentialInfo = [
    'Owned by' => 'Company Name',
    'Contact Person' => 'Person Name',
    'Phone' => 'Phone Number',
    'Email' => 'Email Address',
    // Add any custom fields here
];
```

The `Website` field is automatically added from the config `website_url`.

## Output

Properties are saved to: `ScrapeFile/ThaiProperty1/properties.json`

### Debug Files (Testing Mode)
- `xml_structure_debug.txt` - First property structure
- `available_fields.txt` - List of available XML fields
- `xml_sample.xml` - Sample XML property

## Property Type Mapping

- `Villa/House` → `['Villa', 'House']`
- `Villa` → `['Villa']`
- `House` → `['House']`
- `Condo` → `['Condo']`
- `Apartment` → `['Apartment']`
- `Penthouse` → `['Penthouse']`

## Property Status

Automatically determined by price type:
- If `priceSale > 0` → `For Sale`
- If `priceRent > 0` → `For Rent`

## Example Output

```json
{
    "property_title": "Single-Storey Pool Villa in Central Pattaya",
    "property_description": "...",
    "price": 12900000,
    "currency": "THB",
    "bedrooms": 3,
    "bathrooms": 4,
    "size": 150,
    "property_type": ["Villa", "House"],
    "property_status": ["For Sale"],
    "location": "12.9312625, 100.8919531",
    "listing_id": "TP1-14351",
    "additional_features": [
        "Ownership: FREEHOLD",
        "Land Size: 216 sqm",
        "Western Kitchen",
        "Private swimming pool"
    ],
    "confidential_info": [
        {
            "fave_additional_feature_title": "Website",
            "fave_additional_feature_value": "https://thaiproperty1.com/api/lA1bwo/feeds/nestopa-feed"
        },
        {
            "fave_additional_feature_title": "Owned by",
            "fave_additional_feature_value": "Thai Property 1"
        },
        {
            "fave_additional_feature_title": "Phone",
            "fave_additional_feature_value": "+66 (0) 38 412 122"
        }
    ]
}
```

## Clean Code Features

- No unnecessary code or commented sections
- Clear method names and documentation
- Proper error handling
- Validation for required fields
- Structured extraction methods
- Configurable settings
- Testing mode support

## Key Improvements

1. **Removed** unnecessary code from other scrapers
2. **Specific** to ThaiProperty1 XML structure
3. **Clean** extraction methods for multilingual content
4. **Proper** handling of CDATA sections
5. **Smart** property type and status detection
6. **Validation** for images, prices, and IDs
