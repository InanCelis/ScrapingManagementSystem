# WordPress API Update - Post Author & Draft Support

## Changes Required

### 1. Update `create_property_post` method

Add support for `post_author` and `draft` parameters:

```php
private function create_property_post($property_data) {
    // Determine post status
    $post_status = 'publish'; // Default
    if (isset($property_data['draft']) && $property_data['draft'] === true) {
        $post_status = 'draft';
    }

    // Determine post author
    $post_author = get_current_user_id(); // Default
    if (isset($property_data['post_author']) && !empty($property_data['post_author'])) {
        $author_id = intval($property_data['post_author']);
        // Verify the user exists
        if (get_user_by('id', $author_id)) {
            $post_author = $author_id;
        }
    }

    $post_data = [
        'post_title'   => sanitize_text_field($property_data['property_title']),
        'post_content' => wp_kses_post($property_data['property_description']),
        'post_excerpt' => sanitize_textarea_field($property_data['property_excerpt']),
        'post_status'  => $post_status,  // Use determined status
        'post_type'    => 'property',
        'post_author'  => $post_author,  // Use determined author
        'post_date'    => current_time('mysql'),
        'post_date_gmt' => current_time('mysql', 1),
        'post_modified' => current_time('mysql'),
        'post_modified_gmt' => current_time('mysql', 1),
        'post_name' => sanitize_title_with_dashes($property_data['property_title']),
    ];

    return wp_insert_post($post_data);
}
```

### 2. Update `update_property_post` method

Add support for updating draft status and author:

```php
private function update_property_post($post_id, $property_data) {
    $post_data = [
        'ID'           => $post_id,
        'post_title'   => sanitize_text_field($property_data['property_title']),
        'post_content' => wp_kses_post($property_data['property_description']),
        'post_excerpt' => sanitize_textarea_field($property_data['property_excerpt']),
        'post_modified' => current_time('mysql'),
        'post_modified_gmt' => current_time('mysql', 1),
        'post_name' => sanitize_title_with_dashes($property_data['property_title']),
    ];

    // Update post status if draft parameter is provided
    if (isset($property_data['draft'])) {
        $post_data['post_status'] = $property_data['draft'] === true ? 'draft' : 'publish';
    }

    // Update post author if provided
    if (isset($property_data['post_author']) && !empty($property_data['post_author'])) {
        $author_id = intval($property_data['post_author']);
        // Verify the user exists
        if (get_user_by('id', $author_id)) {
            $post_data['post_author'] = $author_id;
        }
    }

    return wp_update_post($post_data);
}
```

## Usage Example

### Create Property with Custom Author and Draft Status

```json
{
  "properties": [
    {
      "property_title": "Beautiful Villa in Thailand",
      "property_description": "...",
      "price": 12900000,
      "currency": "THB",
      "post_author": "163",
      "draft": true,
      ...
    }
  ]
}
```

### Parameters:

- **`post_author`** (string|int): WordPress user ID to assign as the property author
  - If not provided or invalid, uses the current authenticated user
  - The system verifies the user exists before assigning

- **`draft`** (boolean): Whether to save the property as draft
  - `true` = Save as draft
  - `false` or omitted = Save as published

## Benefits

1. **Flexible Author Assignment**: Assign properties to specific users programmatically
2. **Draft Control**: Import properties as drafts for review before publishing
3. **Backward Compatible**: Existing API calls work without changes
4. **Validation**: Ensures user IDs are valid before assignment

## Testing

```bash
# Test creating property as draft with custom author
curl -X POST https://your-site.com/wp-json/houzez/v1/properties \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "properties": [{
      "property_title": "Test Property",
      "property_description": "Test Description",
      "price": 100000,
      "post_author": "163",
      "draft": true,
      "listing_id": "TEST-001"
    }]
  }'
```
