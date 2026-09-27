# Geocoding Microservice

A minimalistic, standalone API microservice written in PHP and Symfony framework.

## Features

- Geocode - provide address and retrieve its representation in geographical coordinates
- Reverse geocode - provide coordinates in order to retrieve matching address
- Database cache - data is not loaded from external providers with every request you make, microservice uses bundled PostgreSQL database to store and retrieve addresses faster

## Tech Stack

**Backend:** Symfony 8.1 (PHP 8.4), PHPUnit

**Infrastructure:** Docker, PostgreSQL, Apache HTTP Server

## Supported Providers

- Nominatim - https://nominatim.org/

## Getting Started

The whole project runs in Docker. In order to host, first create an `.env` file in the project root, then run:

```bash
docker compose up --build -d
```

Services are then available at:

- API: http://localhost:80
- PostgreSQL: `localhost:5432`

## Example Usage

Both endpoints accept `GET` requests with query parameters and return JSON wrapped in a common envelope (`data`, `message`, `timestamp`).

### Geocoding

`GET /geocoding` - converts an address into coordinates.

| Parameter       | Required | Description                     |
|-----------------|----------|---------------------------------|
| `street`        | yes      | Street name with building number |
| `city`          | yes      | City name                       |
| `province`      | yes      | Province / state                |
| `countrySymbol` | yes      | ISO 3166-1 alpha-2 country code |
| `postalCode`    | no       | Postal code                     |

#### Request

```http
GET /geocoding?street=1234 Maple Avenue&city=Springfield&province=Illinois&countrySymbol=US&postalCode=62704
```

#### Response

```json
{
  "data": {
    "latitude": 39.7817213,
    "longitude": -89.6501481
  },
  "message": "Success",
  "timestamp": "2026-09-27 17:09:22"
}
```

### Reverse Geocoding

`GET /reverse-geocoding` - converts coordinates into an address.

| Parameter   | Required | Description                     |
|-------------|----------|---------------------------------|
| `latitude`  | yes      | Latitude, from `-90` to `90`    |
| `longitude` | yes      | Longitude, from `-180` to `180` |

#### Request

```http
GET /reverse-geocoding?latitude=45.7640&longitude=4.8357
```

#### Response

```json
{
  "data": {
    "street": "12 Rue des Lilas",
    "city": "Lyon",
    "province": "Auvergne-Rhône-Alpes",
    "countrySymbol": "FR",
    "postalCode": "69002"
  },
  "message": "Success",
  "timestamp": "2026-09-27 17:09:22"
}
```

### Errors

Missing or invalid parameters (e.g. `latitude=abc` or `latitude=100`) result in `422 Unprocessable Content`.



## Deployment

Docker images are built and pushed to GitHub Container Registry (`ghcr.io`) by GitHub Actions:

- Push to `main` → [Build And Deploy To Production](.github/workflows/deploy.yml)

Workflows can also be triggered manually via `workflow_dispatch`.

## Authors

- [@alex4270](https://github.com/AleX4270)

## License

See [LICENSE](LICENSE).
