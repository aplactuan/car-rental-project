# Car Rental API

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![Sanctum](https://img.shields.io/badge/Sanctum-API_Auth-orange?style=flat-square)
![Pest](https://img.shields.io/badge/Tested_with-Pest-green?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)

A production-grade REST API for managing car rental operations — bookings, availability, billing, and customer management — built with Laravel 12, strict architectural layering, and a comprehensive automated test suite.

> Built as a portfolio project to demonstrate real-world backend engineering: clean API design, business rule enforcement, layered architecture, and strong test coverage.

---

## Highlights

| Area | What's demonstrated |
|---|---|
| **Architecture** | Single-action controllers, Repository pattern with interface contracts, Form Request validation |
| **Business logic** | Availability conflict detection, multi-tenant ownership enforcement, billing lifecycle |
| **API design** | Versioned routes (`/api/v1`), nested resources, JSON:API-style error envelopes |
| **Auth** | Laravel Sanctum token-based authentication with login/logout lifecycle |
| **Testing** | 80+ Pest tests — happy paths, failure paths, authorization boundaries, conflict detection |
| **Code quality** | Laravel Pint formatting, PHPDoc typing, no raw `DB::` queries |

---

## Architecture

```
HTTP Request
     │
     ▼
 Controller          ← Single-action, thin — delegates immediately
     │
     ▼
 Form Request        ← Input validation and authorization rules
     │
     ▼
 Repository          ← Interface contract + Eloquent implementation
     │
     ▼
 Eloquent Model      ← Relationships, casts, scopes
     │
     ▼
 API Resource        ← Structured JSON response shaping
```

**Key decisions:**

- **Single-action controllers** — one class per endpoint, easy to locate and test in isolation
- **Repository pattern with contracts** — `RepositoryInterface` → `EloquentRepository`; swap implementations without touching business logic
- **Form Request classes** — validation and authorization co-located, controllers stay clean
- **Polymorphic `Schedule` model** — cars and drivers share one availability table; overlap queries are enforced at the database level

---

## Domain Model

```
User ──< Transaction >── Customer
              │
              ├──< Booking >── Car ──< Schedule (polymorphic)
              │          └─── Driver──< Schedule (polymorphic)
              │
              └── Bill
```

| Model | Responsibility |
|---|---|
| `User` | Authenticated API consumer; owns transactions |
| `Customer` | Person or business renting a vehicle (personal / business type) |
| `Transaction` | Parent rental record linking a user to a customer |
| `Booking` | Car + driver assignment for a specific date range |
| `Car` | Rentable vehicle (make, model, year, plate, seats, mileage) |
| `Driver` | Assignable driver with license details |
| `Schedule` | Polymorphic availability block; used for overlap conflict detection |
| `Bill` | Invoice attached to a transaction (amount, status, issue/due/paid dates) |

---

## API Reference

### Public

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/login` | Authenticate and receive a Sanctum token |

### Authenticated

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/logout` | Revoke current token |
| `GET` | `/api/user` | Return the authenticated user |

### V1 — All require `Authorization: Bearer {token}`

#### Cars

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/cars` | Add a new car |
| `GET` | `/api/v1/cars` | List cars (filterable by availability window) |
| `GET` | `/api/v1/cars/{car}` | View a single car |
| `PUT` | `/api/v1/cars/{car}` | Update a car |

#### Drivers

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/drivers` | Add a new driver |
| `GET` | `/api/v1/drivers` | List drivers |
| `GET` | `/api/v1/drivers/{driver}` | View a single driver |
| `PUT` | `/api/v1/drivers/{driver}` | Update a driver |

#### Customers

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/customers` | Add a new customer |
| `GET` | `/api/v1/customers` | List customers |
| `GET` | `/api/v1/customers/{customer}` | View a single customer |
| `PUT` | `/api/v1/customers/{customer}` | Update a customer |
| `DELETE` | `/api/v1/customers/{customer}` | Delete a customer |

#### Transactions

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/transactions` | Create a transaction |
| `GET` | `/api/v1/transactions` | List user-owned transactions |
| `GET` | `/api/v1/transactions/{transaction}` | View a single transaction |
| `POST` | `/api/v1/customers/{customer}/transactions` | Create a transaction under a customer |
| `GET` | `/api/v1/customers/{customer}/transactions` | List a customer's transactions |
| `GET` | `/api/v1/customers/{customer}/transactions/{transaction}` | View a customer's transaction |
| `PUT` | `/api/v1/customers/{customer}/transactions/{transaction}` | Update a customer's transaction |
| `DELETE` | `/api/v1/customers/{customer}/transactions/{transaction}` | Delete a customer's transaction |

#### Bookings

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/transactions/{transaction}/book` | Add a booking to a transaction |
| `GET` | `/api/v1/transactions/{transaction}/bookings` | List transaction bookings |
| `GET` | `/api/v1/transactions/{transaction}/bookings/{booking}` | View a single booking |
| `PUT` | `/api/v1/transactions/{transaction}/bookings/{booking}` | Update a booking |
| `DELETE` | `/api/v1/transactions/{transaction}/bookings/{booking}` | Delete a booking |

#### Bills

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/transactions/{transaction}/bill` | Create a bill for a transaction |
| `GET` | `/api/v1/transactions/{transaction}/bill` | View the bill |
| `PATCH` | `/api/v1/transactions/{transaction}/bill` | Update the bill |
| `DELETE` | `/api/v1/transactions/{transaction}/bill` | Delete the bill |

#### Availability

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/availability` | Query available cars or drivers for a date range |

---

## Request / Response

### Success envelope

```json
{
  "data": {
    "id": 1,
    "car": { "make": "Toyota", "model": "Camry", "year": 2022 },
    "driver": { "name": "Juan Dela Cruz" },
    "start_date": "2025-06-01",
    "end_date": "2025-06-05"
  },
  "meta": {
    "message": "Booking created successfully."
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

---

## Key Business Rules

- **Availability conflict detection** — before any booking is confirmed, the API checks for date overlaps in the `schedules` table for both the requested car and driver. Double-booking is rejected with a descriptive error.
- **Ownership enforcement** — users can only read and mutate their own transactions. Attempts to access another user's resources return `403 Forbidden`.
- **Billing lifecycle** — a bill tracks amount, status, issued date, due date, and paid date. Status transitions are validated to prevent illegal state changes.
- **Customer types** — supports both `personal` and `business` customer profiles.

---

## Testing

```
Tests:    80+ passing
Runner:   Pest 3 (Feature + Unit)
Database: SQLite in-memory (isolated per test)
```

Coverage areas:

- Authentication and token lifecycle
- Per-user ownership and authorization boundaries
- Full CRUD for cars, drivers, customers, transactions, bookings, and bills
- Availability overlap conflict detection
- Repository behavior and filter logic
- Booking list filters and edge cases

```bash
# Run the full suite
php artisan test --compact

# Run a specific file
php artisan test --compact tests/Feature/AddBookingTest.php

# Filter by test name
php artisan test --compact --filter=prevents_double_booking
```

---

## Local Setup

**Requirements:** PHP 8.2+, Composer, MySQL

```bash
# 1. Install dependencies
composer install

# 2. Environment setup
cp .env.example .env
php artisan key:generate

# 3. Configure your database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=car_rental_project
DB_USERNAME=root
DB_PASSWORD=

# 4. Run migrations and seeders
php artisan migrate --seed

# 5. Start the development server
php artisan serve
```

API base URL: `http://127.0.0.1:8000/api`

---

## Example Flow

**Step 1 — Authenticate**

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

```json
{
  "data": { "token": "1|abc123..." },
  "meta": { "message": "Login successful." }
}
```

**Step 2 — Check availability**

```bash
curl "http://127.0.0.1:8000/api/v1/availability?type=car&start_date=2025-06-01&end_date=2025-06-05" \
  -H "Authorization: Bearer 1|abc123..."
```

**Step 3 — Create a transaction and add a booking**

```bash
# Create transaction
curl -X POST http://127.0.0.1:8000/api/v1/transactions \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{"customer_id": 1}'

# Book a car + driver
curl -X POST http://127.0.0.1:8000/api/v1/transactions/1/book \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{"car_id":1,"driver_id":1,"start_date":"2025-06-01","end_date":"2025-06-05"}'
```

---

## Tech Stack

| | |
|---|---|
| **Runtime** | PHP 8.2 |
| **Framework** | Laravel 12 |
| **Authentication** | Laravel Sanctum |
| **Testing** | Pest 3 + PHPUnit 11 |
| **Code style** | Laravel Pint |
| **Database** | MySQL (production) · SQLite (tests) |

---

## License

This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
