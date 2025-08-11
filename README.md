# Ongkir API

A Laravel-based Indonesian shipping cost API that provides access to province, city, district, and subdistrict data with fallback support between local database and external API services.

## Features

- **Dual Data Source**: Automatically checks local database first, falls back to external API if needed
- **Request Logging**: All API calls are logged with performance metrics and source tracking
- **Consistent Response Format**: Unified JSON response structure across all endpoints
- **Rate Limiting**: Built-in protection against API abuse
- **Error Handling**: Comprehensive error handling with detailed logging

## API Versions

### V1 API (Legacy)
Traditional RajaOngkir-compatible responses using local database only.

### V2 API (Recommended)
Modern API with enhanced features:
- Database-first approach with API fallback
- Consistent response format with `meta` and `data` structure
- Performance optimized with local data caching
- Comprehensive request logging

## V2 API Endpoints

### Base URL
```
https://ongkir.velocitydeveloper.id/api/v2
```

### Authentication
Include your API key in the request headers:
```
Authorization: Bearer YOUR_API_KEY
```

### Response Format
All V2 endpoints return responses in the following format:
```json
{
  "meta": {
    "message": "Success Get [Resource]",
    "code": 200,
    "status": "success"
  },
  "data": [...]
}
```

### Endpoints

#### 1. Provinces

**Get All Provinces**
```
GET /v2/province
```

**Get Province by ID**
```
GET /v2/province?id={province_id}
```

**Example Response:**
```json
{
  "meta": {
    "message": "Success Get Province",
    "code": 200,
    "status": "success"
  },
  "data": [
    {
      "province_id": "1",
      "province": "Bali"
    }
  ]
}
```

#### 2. Cities

**Get All Cities**
```
GET /v2/city
```

**Get Cities by Province**
```
GET /v2/city/{province_id}
GET /v2/city?province={province_id}
```

**Get City by ID**
```
GET /v2/city?id={city_id}
```

**Example Response:**
```json
{
  "meta": {
    "message": "Success Get City",
    "code": 200,
    "status": "success"
  },
  "data": [
    {
      "city_id": "17",
      "type": "Kabupaten",
      "city_name": "Badung",
      "postal_code": "80351",
      "province_id": "1",
      "province": "Bali"
    }
  ]
}
```

#### 3. Districts

**Get All Districts**
```
GET /v2/district
```

**Get Districts by City**
```
GET /v2/district/{city_id}
GET /v2/district?city={city_id}
```

**Get District by ID**
```
GET /v2/district?id={district_id}
```

**Example Response:**
```json
{
  "meta": {
    "message": "Success Get District",
    "code": 200,
    "status": "success"
  },
  "data": [
    {
      "district_id": "1",
      "district_name": "Abiansemal",
      "city_id": "17",
      "city": "Badung",
      "type": "Kabupaten",
      "province_id": "1",
      "province": "Bali"
    }
  ]
}
```

#### 4. Subdistricts

**Get All Subdistricts**
```
GET /v2/subdistrict
```

**Get Subdistricts by City**
```
GET /v2/subdistrict?city={city_id}
```

**Get Subdistrict by District ID**
```
GET /v2/subdistrict/{district_id}
```

**Get Subdistrict by ID**
```
GET /v2/subdistrict?id={subdistrict_id}
```

**Example Response:**
```json
{
  "meta": {
    "message": "Success Get Subdistrict",
    "code": 200,
    "status": "success"
  },
  "data": [
    {
      "subdistrict_id": "1",
      "subdistrict_name": "Abiansemal",
      "city_id": "17",
      "city": "Badung",
      "type": "Kabupaten",
      "province_id": "1",
      "province": "Bali"
    }
  ]
}
```

## Data Sources

The API intelligently manages data from two sources:

1. **Local Database** (Primary): Fast response times, no external dependencies
2. **External API** (Fallback): Ensures data completeness when local data is unavailable

### Request Logging

All requests are logged with the following information:
- Request method and endpoint
- Data source used (`db` or `api`)
- Response time in milliseconds
- Success/failure status
- Client IP address and User-Agent
- Request payload

## Error Handling

### Error Response Format
```json
{
  "meta": {
    "message": "Error description",
    "code": 500,
    "status": "error"
  },
  "data": []
}
```

### Common Error Codes
- `200`: Success
- `400`: Bad Request (Invalid parameters)
- `404`: Not Found (Resource not found)
- `500`: Internal Server Error

## Performance

- **Database queries**: Sub-millisecond response times for local data
- **API fallback**: Automatic fallback when local data is unavailable
- **Caching**: Local database acts as a cache for frequently accessed data
- **Logging**: Minimal performance impact with asynchronous logging

## Installation

1. Clone the repository
2. Install dependencies: `composer install`
3. Configure environment variables in `.env`
4. Run migrations: `php artisan migrate`
5. Seed the database (optional): `php artisan db:seed`
6. Start the server: `php artisan serve`

## Environment Variables

```env
RAJAONGKIR_API_KEY=your_rajaongkir_api_key
RAJAONGKIR_API_URL=https://api.rajaongkir.com/starter
API_KEY=your_api_key_for_authentication
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions, please contact the development team or create an issue in the repository.