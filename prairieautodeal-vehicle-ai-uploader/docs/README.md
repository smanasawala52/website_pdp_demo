## PrairieAutoDeal Vehicle AI Uploader

WordPress plugin to upload vehicle images, extract vehicle data via AI (Make, Model, Year, Body Type, Color, Mileage), and manage listings for admin/dealers.

### Requirements
- WordPress 6.x+
- PHP 8.0+

### Installation
1. Zip the `prairieautodeal-vehicle-ai-uploader/` folder and upload via WordPress → Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. Go to Settings → PrairieAutoDeal Vehicle AI to configure:
   - API URL (e.g., `https://your-ai.example.com/`)
   - API Key
   - Limits and confidence threshold
   - Enable/disable bulk upload

### Usage
- Create a new `Vehicle Listing` and give it a Title.
- Use the `Vehicle Images` metabox to add multiple images. Drag to reorder.
- Click `Run AI Extraction` to populate fields. Low-confidence fields are highlighted.
- Edit any field and save the post.

### Bulk Upload
Tools → Vehicle Bulk Upload (AI)
- Select many images.
- Choose images-per-listing.
- Optionally run AI automatically.

### REST API
- Endpoint (admin or users with capability): `/wp-json/pad/v1/vehicle-listings?ai_status=Completed&dealer_id=123`
- Returns array of listings including image URLs and metadata.

### AI Microservice Contract
POST `{API_URL}/extract`
Body (JSON):
```json
{
  "image_urls": ["https://example.com/wp-content/uploads/2025/10/car1.jpg"]
}
```
Response (JSON):
```json
{
  "make": "Toyota",
  "model": "Corolla",
  "year": 2018,
  "body_type": "Sedan",
  "color": "White",
  "mileage": 45000,
  "confidence": {"make": 0.95, "model": 0.92}
}
```

### Security
- Nonces for AJAX
- Capability checks for all actions
- MIME/size validation for images
- API key stored in options; sent via Authorization header

### Meta Fields
- `_make`, `_model`, `_year`, `_body_type`, `_color`, `_mileage`
- `_fuel_type`, `_transmission`, `_vin`, `_price`
- `_images` (array of attachment IDs)
- `_dealer_id` (int)
- `_ai_status` (Pending/Completed/Error)
- `_ai_confidence` (map)
- `_ai_extracted_at` (timestamp)
- `_source` (string)

### Logging
- Toggle logging in Settings. Logs go to `WP_DEBUG_LOG`.

### Future Extensions
- Damage/feature detection, price suggestion
- VIN enrichment via SGI
- Mobile uploader & dealer dashboards
