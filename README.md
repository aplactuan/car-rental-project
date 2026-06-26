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
| **Business logic** | Availability conflict detection, multi-tenant ownership enforcement, billing lifecycle, trip report snapshots |
| **API design** | Versioned routes (`/api/v1`), nested resources, JSON:API-style error envelopes |
| **Auth** | Laravel Sanctum token-based authentication with login/logout lifecycle; user roles (`admin` / `user`) |
| **Testing** | 336+ Pest tests — happy paths, failure paths, authorization boundaries, conflict detection |
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
              │       │      └─── Driver──< Schedule (polymorphic)
              │       └──< TripReport
              │
              └── Bill ──< BillPayment
```

| Model | Responsibility |
|---|---|
| `User` | Authenticated API consumer; owns transactions; role: `admin` or `user` |
| `Customer` | Person or business renting a vehicle (personal / business type) |
| `Transaction` | Parent rental record linking a user to a customer |
| `Booking` | Car + driver assignment for a specific date range |
| `TripReport` | Driver-submitted trip log for a booking; stores immutable snapshots of related entities |
| `Car` | Rentable vehicle (make, model, year, plate, seats, doors) |
| `Driver` | Assignable driver with license details; linked to a `User` account on create |
| `Schedule` | Polymorphic availability block; used for overlap conflict detection |
| `Bill` | Invoice attached to a transaction (amount, status, issue/due/paid dates) |
| `BillPayment` | Installment payment recorded against a bill |

---

## API Reference

### Public

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/login` | Authenticate and receive a Sanctum token |
| `POST` | `/api/v1/users` | Register a new user account (requires `allow_user_registration` feature flag) |

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
| `POST` | `/api/v1/cars/import` | Queue a CSV import job for cars |
| `GET` | `/api/v1/cars/imports/{carImport}` | Check the status/result of a car import job |
| `GET` | `/api/v1/cars` | List cars (filterable by availability window) |
| `GET` | `/api/v1/cars/{car}` | View a single car |
| `PUT` | `/api/v1/cars/{car}` | Update a car |

#### Drivers

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/drivers` | Add a new driver |
| `POST` | `/api/v1/drivers/import` | Queue a CSV import job for drivers |
| `GET` | `/api/v1/drivers/imports/{driverImport}` | Check the status/result of a driver import job |
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
| `GET` | `/api/v1/customers/{customer}/bills` | List bills for a customer |

#### Billing

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/billing/summary` | Aggregated `totalPaid` / `totalUnpaid` totals (optional filters) |
| `GET` | `/api/v1/bills` | List all bills for the authenticated user |

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
| `GET` | `/api/v1/bookings` | List all bookings across the user's transactions (optional `status`: `completed`, `today`, `ongoing`, `incoming`) |
| `POST` | `/api/v1/transactions/{transaction}/book` | Add a booking to a transaction |
| `GET` | `/api/v1/transactions/{transaction}/bookings` | List transaction bookings (optional `status`, `period`, `car_id`, `driver_id`) |
| `GET` | `/api/v1/transactions/{transaction}/bookings/{booking}` | View a single booking |
| `PUT` | `/api/v1/transactions/{transaction}/bookings/{booking}` | Update a booking |
| `DELETE` | `/api/v1/transactions/{transaction}/bookings/{booking}` | Delete a booking |

#### Trip Reports

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports` | Create a trip report for a booking |
| `GET` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports` | List trip reports for a booking |
| `GET` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports/{tripReport}` | View a single trip report |
| `PUT` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports/{tripReport}` | Update a trip report |
| `DELETE` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports/{tripReport}` | Delete a trip report |

#### Bills

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/transactions/{transaction}/bill` | Create a bill for a transaction |
| `GET` | `/api/v1/transactions/{transaction}/bill` | View the bill |
| `GET` | `/api/v1/transactions/{transaction}/bill/invoice` | Printable invoice payload (bill + customer + bookings) |
| `PATCH` | `/api/v1/transactions/{transaction}/bill` | Update the bill |
| `DELETE` | `/api/v1/transactions/{transaction}/bill` | Delete the bill |

#### Bill Payments

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/transactions/{transaction}/bill/payments` | Record a payment (installment) against the bill |
| `GET` | `/api/v1/transactions/{transaction}/bill/payments` | List all payments for the bill |
| `DELETE` | `/api/v1/transactions/{transaction}/bill/payments/{payment}` | Delete a payment record |

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
- **Billing lifecycle** — a bill tracks amount, status, issued date, due date, and paid date. Status transitions are validated: `draft` → `issued` / `cancelled`; `issued` → `partially_paid` (via payment) / `cancelled`; `partially_paid` → `paid` (via payment) / `cancelled`. Illegal transitions are rejected.
- **Installment payments** — payments are recorded against a bill via `POST .../bill/payments` (`multipart/form-data`). Each payment requires `amount`, `method` (`bank_transfer`, `cash`, `gcash`), and `reference_number`. Optional fields: `notes`, `proof_image` (jpg/jpeg/png/webp, ≤ 10 MB). The bill status is recomputed after every payment or deletion: partial sum → `partially_paid`; full sum → `paid`. Deleting a payment reverses the status accordingly.
- **Bill numbering** — `invoiceNumber` is auto-assigned on create (`INV-YYMM#####`, incremented per month). `billNumber` uses a separate daily sequence (`INV-YYYYMMDD-####`).
- **Customer types** — supports both `personal` and `business` customer profiles.
- **User roles** — every user has a `role` of `admin` or `user`. Admin users can update any driver record and manage the driver-user link.
- **Driver-user link** — `POST /api/v1/drivers` auto-creates a linked user account (`email`, `password` required). Admins can reassign or unlink via `user_id` on update. A linked driver user can update their own record but cannot change `user_id`. CSV driver import does not create user accounts.
- **Trip reports** — drivers assigned to a booking (or admins) can submit trip logs with odometer, fuel, destinations, and collection details. Related entity data is snapshotted at creation time and preserved even if the booking, car, driver, customer, or transaction changes later. Deleting a booking cascades to its trip reports.

---

## Testing

```
Tests:    336+ passing
Runner:   Pest 3 (Feature + Unit)
Database: SQLite in-memory (isolated per test)
```

Coverage areas:

- Authentication and token lifecycle
- Per-user ownership and authorization boundaries
- Full CRUD for cars, drivers, customers, transactions, bookings, bills, and trip reports
- Availability overlap conflict detection
- Repository behavior and filter logic
- Booking list filters and edge cases
- Billing summary, bill listing, and installment payment lifecycle

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

### Postman collection

Import [`Car-Rental-API.postman_collection.json`](Car-Rental-API.postman_collection.json) into Postman to exercise every endpoint. Run **Login** first — the collection stores the Bearer token automatically and provides sample bodies for nested resources (cars, drivers, bookings, bills, trip reports, CSV imports).

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
curl "http://127.0.0.1:8000/api/v1/availability?type=car&start=2025-06-01&end=2025-06-05" \
  -H "Authorization: Bearer 1|abc123..."
```

**Step 3 — Create a transaction and add a booking**

```bash
# Create transaction
curl -X POST http://127.0.0.1:8000/api/v1/transactions \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{"customer_id":"6a9f858c-0f96-4af0-a7fa-5f2cfec5d899","name":"Corporate fleet deal"}'

# Book a car + driver
curl -X POST http://127.0.0.1:8000/api/v1/transactions/f4df123f-1294-43a1-b0b1-5ab6f27e3317/book \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{"car_id":"e7a08d85-1ebf-45ef-a17d-2a0cde35b495","driver_id":"91e49868-a2b2-420a-95bc-9f5e86a95d4d","start_date":"2025-06-01","end_date":"2025-06-05"}'
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
