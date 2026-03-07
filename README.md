# Car Rental API

A production-style REST API for managing car rental operations, built with Laravel 12.

This project demonstrates practical backend engineering patterns: token auth, clean validation, service boundaries, availability conflict rules, JSON:API-style error handling, and a strong automated test suite.

## Why this project stands out

- Business-focused API design for real rental workflows
- Explicit ownership checks to protect tenant/user data
- Booking conflict detection for both cars and drivers
- Consistent API responses with JSON:API-style errors
- Layered architecture (Controllers -> Requests -> Repositories -> Models)
- Pest test suite covering happy paths and failure paths

## Tech stack

- PHP 8.2
- Laravel 12
- Laravel Sanctum (token-based API auth)
- Pest + PHPUnit
- Laravel Pint
- MySQL (default), SQLite (tests)

## Core domain

- `User`: authenticated API consumer
- `Car`: rentable vehicle
- `Driver`: assignable driver
- `Transaction`: parent rental record owned by a user
- `Booking`: car + driver assignment with date range
- `Schedule`: polymorphic schedule entries used for availability checks

## Key capabilities

### Authentication

- Login (`/api/login`) to get a Sanctum token
- Logout (`/api/logout`) to revoke current token
- All `/api/v1/*` routes require `auth:sanctum`

### Cars

- Create, list by availability window, view single, update

### Drivers

- Create, list, view single, update

### Transactions

- Create transaction
- List user-owned transactions
- View a single user-owned transaction

### Bookings (within transactions)

- Add booking to a transaction
- List transaction bookings
- View single booking
- Update booking
- Delete booking

### Availability

- Query available cars or drivers for a date range
- Enforces overlap checks against existing schedules/bookings

## API endpoint map

### Public

- `POST /api/login`

### Authenticated

- `POST /api/logout`
- `GET /api/user`

### V1 authenticated (`/api/v1`)

- `POST /cars`
- `GET /cars`
- `GET /cars/{car}`
- `PUT /cars/{car}`

- `POST /drivers`
- `GET /drivers`
- `GET /drivers/{driver}`
- `PUT /drivers/{driver}`

- `POST /transactions`
- `GET /transactions`
- `GET /transactions/{transaction}`

- `POST /transactions/{transaction}/book`
- `GET /transactions/{transaction}/bookings`
- `GET /transactions/{transaction}/bookings/{booking}`
- `PUT /transactions/{transaction}/bookings/{booking}`
- `DELETE /transactions/{transaction}/bookings/{booking}`

- `GET /availability`

## Request/response behavior

### Success envelope

Most custom controller responses follow:

```json
{
  "data": {},
  "meta": {
    "message": "..."
  }
}
```

### Error envelope (JSON:API style)

```json
{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "The selected car is not available for the given dates."
    }
  ]
}
```

## Local setup

1. Install dependencies

```bash
composer install
```

2. Create environment file and app key

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure database in `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=car_rental_project
DB_USERNAME=root
DB_PASSWORD=
```

4. Run migrations and (optional) seeders

```bash
php artisan migrate
php artisan db:seed
```

5. Run the API

```bash
php artisan serve
```

API base URL: `http://127.0.0.1:8000/api`

## Development commands

```bash
# Run tests
php artisan test --compact

# Run formatter
php ./vendor/bin/pint --format agent

# Run app + queue + vite concurrently
composer run dev
```

## Testing

- Framework: Pest (Feature + Unit)
- Current suite contains 80+ tests (82 at the time this README was updated)
- Focus areas include:
  - authentication and token lifecycle
  - ownership/authorization boundaries
  - CRUD flows for cars, drivers, transactions, bookings
  - availability and overlap conflict handling
  - repository behavior

## Example flow (quick smoke test)

1. Login and get token:

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

2. Use token on a protected endpoint:

```bash
curl http://127.0.0.1:8000/api/v1/transactions \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Architecture notes

- Validation uses dedicated Form Request classes
- Data access is abstracted via repository contracts
- Business constraints (availability, ownership) are enforced at endpoint level
- API resources are used for structured JSON output

## License

This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
