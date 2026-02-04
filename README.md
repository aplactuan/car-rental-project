# Car Rental API

A RESTful API for managing car rentals, built with Laravel 12. The system handles cars, drivers, and rental transactions (bookings) with availability checking and JSON:API-style responses.

## Features

- **Authentication** — Login/logout via Laravel Sanctum (token-based)
- **Car Management** — Add, list, view, and update cars with availability by date range
- **Driver Management** — Add, list, view, and update drivers with availability by date range
- **Transaction Management** — Create and list rental transactions with multiple bookings
- **Availability Validation** — Ensures cars and drivers are available for the requested rental period
- **JSON:API** — JSON:API-style error responses and resources

## Tech Stack

- **PHP** 8.2+
- **Laravel** 12
- **Laravel Sanctum** — API authentication
- **MySQL** (default) / SQLite (testing)
- **Pest** — Testing framework

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL 5.7+ / MariaDB or SQLite
- Node.js & npm (for frontend assets, optional)

## Installation

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd car-rental-project
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Configure environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database** — Edit `.env` with your database credentials:

   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=car_rental_project
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

5. **Run migrations**

   ```bash
   php artisan migrate
   ```

6. **Seed the database** (optional — creates a test user)

   ```bash
   php artisan db:seed
   ```

   Default test user:
   - Email: `test@example.com`
   - Password: `password`

## Running the Application

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`.

## API Documentation

### Base URL

```
http://localhost:8000/api
```

### Authentication

All `/api/v1/*` endpoints require authentication. Use the Bearer token from the login response.

#### Login

```http
POST /api/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "message": "Authenticated",
  "data": {
    "token": "1|..."
  }
}
```

#### Logout

```http
POST /api/logout
Authorization: Bearer {token}
```

#### Get Current User

```http
GET /api/user
Authorization: Bearer {token}
```

---

### Cars

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/cars` | Add a new car |
| GET | `/api/v1/cars` | List available cars (supports date filters) |
| GET | `/api/v1/cars/{id}` | View a single car |
| PUT | `/api/v1/cars/{id}` | Update a car |

#### Add Car

```http
POST /api/v1/cars
Authorization: Bearer {token}
Content-Type: application/json

{
  "make": "Toyota",
  "model": "Camry",
  "year": 2023,
  "plate_number": "ABC-1234",
  "mileage": 15000,
  "type": "sedan",
  "number_of_seats": 5
}
```

---

### Drivers

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/drivers` | Add a new driver |
| GET | `/api/v1/drivers` | List drivers (paginated, supports `per_page`) |
| GET | `/api/v1/drivers/{id}` | View a single driver |
| PUT | `/api/v1/drivers/{id}` | Update a driver |

#### Add Driver

```http
POST /api/v1/drivers
Authorization: Bearer {token}
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "license_number": "DL-12345",
  "license_expiry_date": "2028-12-31",
  "address": "123 Main St",
  "phone_number": "+1234567890"
}
```

---

### Transactions

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/transactions` | Create a transaction (rental) with bookings |
| GET | `/api/v1/transactions` | List transactions (supports `per_page`) |
| GET | `/api/v1/transactions/{id}` | View a single transaction |

#### Create Transaction

Each transaction can include multiple bookings. Cars and drivers must be available for the requested dates.

```http
POST /api/v1/transactions
Authorization: Bearer {token}
Content-Type: application/json

{
  "bookings": [
    {
      "car_id": "uuid",
      "driver_id": "uuid",
      "start_date": "2026-02-10",
      "end_date": "2026-02-15",
      "note": "Optional note"
    }
  ]
}
```

**Validation:**
- `end_date` must be on or after `start_date`
- Car must be available for the date range
- Driver must be available for the date range

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── LoginController.php
│   │   ├── LogoutController.php
│   │   └── V1/
│   │       ├── Cars/          # AddCar, ListAvailableCars, SingleCar, UpdateCar
│   │       ├── Drivers/       # AddDriver, ListDrivers, SingleDriver, UpdateDriver
│   │       └── Transactions/  # AddTransaction, ListTransactions, SingleTransaction
│   ├── Middleware/
│   │   └── AuthenticateApi.php
│   ├── Requests/              # Form validation
│   └── Resources/V1/          # API resources (JSON:API style)
├── Models/
│   ├── Booking.php
│   ├── Car.php
│   ├── Driver.php
│   ├── Transaction.php
│   └── User.php
└── Repositories/              # Data access layer
    ├── Contracts/
    └── Eloquent/
```

## Data Models

- **User** — Authenticated users
- **Car** — Vehicles (make, model, year, type, seats, mileage, plate number)
- **Driver** — Drivers (name, license, expiry, address, phone)
- **Transaction** — Rental transactions (linked to user)
- **Booking** — Individual rentals within a transaction (car, driver, dates, note)

All entities use UUIDs as primary keys (via `HasUuid` trait).

## Testing

Tests use Pest and SQLite in-memory database.

```bash
composer test
# or
./vendor/bin/pest
```

### Test Coverage

- **Feature:** Add/update car, driver, transaction; list drivers, transactions; view car, driver, transaction; login/logout; availability checks
- **Unit:** Car model, BookingRepository, TransactionRepository

## Docker

A Dockerfile is provided for containerized development:

```bash
docker build -t car-rental .
```

PHP 8.2-FPM with extensions: pdo, pdo_mysql, pgsql, redis, xdebug, gd, bcmath, zip.

## Error Responses

API errors follow a JSON:API-inspired structure:

```json
{
  "errors": [
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "The selected car is not available for the given dates.",
      "pointer": "/data/attributes/bookings/0/car_id"
    }
  ]
}
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
