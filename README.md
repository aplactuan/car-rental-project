# Car Rental API

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![Sanctum](https://img.shields.io/badge/Sanctum-API_Auth-orange?style=flat-square)
![Pest](https://img.shields.io/badge/Tested_with-Pest-green?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)

A REST API for managing car rental operations: cars, drivers, customers, transactions, bookings, billing, availability, and trip reports.

This project is built with Laravel 12 and follows a layered backend structure with single-action controllers, Form Requests, repositories, API resources, and Pest tests.

---

## Highlights

| Area | What is included |
| --- | --- |
| Architecture | Single-action controllers, repository contracts, Form Request validation, API resources |
| Auth | Laravel Sanctum login/logout flow with `admin` and `user` roles |
| Booking workflow | Transactions, nested bookings, availability checks, global booking listing |
| Billing | Bills, invoice numbers, bill payments, billing summary |
| Customer management | Personal/business customers, parent-child customer accounts, contact fields |
| Driver operations | Driver CRUD, linked user accounts, CSV imports |
| Reporting | Booking trip reports with immutable snapshots |
| Testing | Feature and unit coverage with Pest |

---

## Architecture

```text
HTTP Request
  -> Controller
  -> Form Request
  -> Repository
  -> Eloquent Model
  -> API Resource
  -> JSON Response
```

Key patterns used in the app:

- Single-action controllers keep each endpoint isolated and easy to test.
- Form Requests own validation and authorization rules.
- Repository interfaces separate controllers from persistence details.
- API resources shape responses consistently.
- Schedules are shared across cars and drivers for overlap detection.

---

## Domain Model

```text
User --< Transaction >-- Customer
          |
          +--< Booking >-- Car --< Schedule
          |      |
          |      +------ Driver --< Schedule
          |      |
          |      +--< TripReport
          |
          +-- Bill --< BillPayment
```

| Model | Responsibility |
| --- | --- |
| `User` | Authenticated API consumer with role-based access |
| `Customer` | Personal or business renter, optionally linked to a parent customer |
| `Transaction` | Rental record owned by a user and linked to a customer |
| `Booking` | Car and driver assignment for a time range with a price |
| `TripReport` | Booking trip log with immutable snapshots |
| `Car` | Rentable vehicle |
| `Driver` | Assignable driver, optionally linked to a user account |
| `Schedule` | Availability block used for conflict detection |
| `Bill` | Invoice for a transaction |
| `BillPayment` | Recorded installment/payment against a bill |

---

## Authentication

### Public endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/login` | Authenticate and receive a Sanctum token |
| `POST` | `/api/v1/users` | Register a user when `allow_user_registration` is enabled |

### Authenticated endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/user` | Get the authenticated user |
| `PUT` | `/api/password` | Change the authenticated user's password |
| `POST` | `/api/logout` | Revoke the current token |

All `/api/v1/*` routes require `Authorization: Bearer {token}` unless otherwise noted above.

---

## API Reference

### Cars

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/v1/cars` | Add a car |
| `POST` | `/api/v1/cars/import` | Queue a CSV import for cars |
| `GET` | `/api/v1/cars/imports/{carImport}` | Get car import status/details |
| `GET` | `/api/v1/cars` | List cars with optional filters |
| `GET` | `/api/v1/cars/{car}` | Get a single car |
| `PUT` | `/api/v1/cars/{car}` | Update a car |

Supported list filters on `/api/v1/cars`: `per_page`, `type`, `door`, `seats`, `year`, `color`, `make`, `model`, `plate_number`.

### Drivers

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/v1/drivers` | Add a driver and create a linked user account |
| `POST` | `/api/v1/drivers/import` | Queue a CSV import for drivers |
| `GET` | `/api/v1/drivers/imports/{driverImport}` | Get driver import status/details |
| `GET` | `/api/v1/drivers` | List drivers |
| `GET` | `/api/v1/drivers/{driver}` | Get a single driver |
| `PUT` | `/api/v1/drivers/{driver}` | Update a driver |

### Customers

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/v1/customers` | Add a customer |
| `GET` | `/api/v1/customers` | List customers |
| `GET` | `/api/v1/customers/{customer}` | Get a single customer |
| `GET` | `/api/v1/customers/{customer}/parent` | Get the immediate parent customer |
| `PUT` | `/api/v1/customers/{customer}` | Update a customer |
| `DELETE` | `/api/v1/customers/{customer}` | Delete a customer |
| `GET` | `/api/v1/customers/{customer}/bills` | List bills for a customer |
| `POST` | `/api/v1/customers/{customer}/transactions` | Create a transaction for a customer |
| `GET` | `/api/v1/customers/{customer}/transactions` | List a customer's transactions |
| `GET` | `/api/v1/customers/{customer}/transactions/{transaction}` | Get a customer's transaction |
| `PUT` | `/api/v1/customers/{customer}/transactions/{transaction}` | Update a customer's transaction |
| `DELETE` | `/api/v1/customers/{customer}/transactions/{transaction}` | Delete a customer's transaction |

Customer payloads support:

- `name` required
- `type` required: `personal` or `business`
- `parent_id` optional, nullable, must reference an existing customer
- `contact_person` optional
- `contact_mobile_number` optional
- `contact_email` optional and must be a valid email

Single-customer responses include the parent relationship with the parent name when loaded. List responses include parent linkage without parent attributes.

### Transactions

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/v1/transactions` | Create a transaction |
| `GET` | `/api/v1/transactions` | List transactions owned by the authenticated user |
| `GET` | `/api/v1/transactions/{transaction}` | Get a single transaction |

Transaction payloads require:

- `customer_id`
- `name`

List endpoints support `has_bill` filtering.

### Bookings

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/v1/bookings` | List bookings in the authenticated scope |
| `POST` | `/api/v1/transactions/{transaction}/book` | Create a booking |
| `GET` | `/api/v1/transactions/{transaction}/bookings` | List bookings for a transaction |
| `GET` | `/api/v1/transactions/{transaction}/bookings/{booking}` | Get a single booking |
| `PUT` | `/api/v1/transactions/{transaction}/bookings/{booking}` | Update a booking |
| `DELETE` | `/api/v1/transactions/{transaction}/bookings/{booking}` | Delete a booking |

Booking payloads:

- `car_id` required on create
- `driver_id` required on create
- `price` required on create, integer, minimum `0`
- `start_date` required on create
- `end_date` required on create and must be on/after `start_date`
- `note` optional

Global booking list behavior:

- For regular users, `/api/v1/bookings` is scoped to their transactions.
- For linked driver users, `/api/v1/bookings` is scoped to bookings assigned to their driver profile.
- Supported global status filters: `completed`, `today`, `ongoing`, `incoming`.

Per-transaction booking list filters:

- `per_page`
- `status`: `upcoming` or `previous`
- `period`: `week` or `month`
- `car_id`
- `driver_id`

### Trip Reports

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports` | Create a trip report |
| `GET` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports` | List trip reports |
| `GET` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports/{tripReport}` | Get a single trip report |
| `PUT` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports/{tripReport}` | Update a trip report |
| `DELETE` | `/api/v1/transactions/{transaction}/bookings/{booking}/trip-reports/{tripReport}` | Delete a trip report |

Trip reports are allowed only for:

- admin users
- the driver linked to the booking

Trip report fields:

- `report_date` required on create
- `po_number`, `time_in`, `time_out`, `rate`
- `odometer_in`, `odometer_out`
- `fuel_liters`, `fuel_amount`
- `invoice_or_or_number`
- `collection_amount`, `percentage`
- `destinations` array of up to 6 `{from, to}` objects

Snapshots such as driver, car, customer, and transaction details are stored at creation time and do not change later even if the related records are updated.

### Bills

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/v1/bills` | List bills |
| `GET` | `/api/v1/billing/summary` | Get billing totals |
| `POST` | `/api/v1/transactions/{transaction}/bill` | Create a bill |
| `GET` | `/api/v1/transactions/{transaction}/bill` | Get a bill |
| `GET` | `/api/v1/transactions/{transaction}/bill/invoice` | Get invoice payload |
| `PATCH` | `/api/v1/transactions/{transaction}/bill` | Update a bill |
| `DELETE` | `/api/v1/transactions/{transaction}/bill` | Delete a bill |

Bill fields:

- `amount` required on create, integer, minimum `1`
- `notes` optional
- `due_at` optional

Bill numbers are generated automatically:

- `billNumber`: `INV-YYYYMMDD-####`
- `invoiceNumber`: `INV-YYMM#####`

Supported bill statuses in the app:

- `draft`
- `issued`
- `partially_paid`
- `paid`
- `cancelled`

`PATCH /api/v1/transactions/{transaction}/bill` accepts `status`, `amount`, `notes`, and `due_at`.

### Bill Payments

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/v1/transactions/{transaction}/bill/payments` | Record a bill payment |
| `GET` | `/api/v1/transactions/{transaction}/bill/payments` | List bill payments |
| `DELETE` | `/api/v1/transactions/{transaction}/bill/payments/{payment}` | Delete a payment |

Bill payment fields:

- `amount` required, integer, minimum `1`, cannot exceed remaining bill balance
- `method` required: `bank_transfer`, `cash`, `gcash`
- `reference_number` required
- `notes` optional
- `proof_image` optional image: `jpg`, `jpeg`, `png`, `webp`, max 10 MB

### Availability

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/v1/availability` | List available cars or drivers in a period |

Required query parameters:

- `type`: `car` or `driver`
- `start`
- `end` and it must be after `start`

---

## Response Shape

### Example success response

```json
{
  "data": {
    "id": "booking-uuid",
    "type": "booking",
    "attributes": {
      "price": 500000,
      "startDate": "2026-03-10T00:00:00+00:00",
      "endDate": "2026-03-15T00:00:00+00:00",
      "note": "Customer requested specific car"
    }
  }
}
```

### Example error response

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

- Cars and drivers cannot be double-booked across overlapping schedules.
- Users can access only resources within their allowed scope.
- Driver-linked users have booking/trip-report access through their linked driver profile, not through a special `driver` user role.
- Adding a driver creates a linked user account automatically.
- Admins can relink or unlink a driver from a user by updating `user_id`.
- Customer accounts can be arranged into parent-child hierarchies.
- A customer cannot be its own parent or use one of its descendants as its parent.
- Deleting a parent customer clears `parent_id` on its children.
- Trip report snapshots remain immutable after creation.
- Deleting a booking cascades to related trip reports.
- Bill payment totals update bill balances and statuses automatically.

---

## Local Setup

Requirements:

- PHP 8.2+
- Composer
- MySQL

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Configure your database in `.env` before running migrations.

Seeded non-production users:

- `test@example.com` / `password`
- `admin@example.com` / `password`

Default API base URL:

```text
http://127.0.0.1:8000/api
```

---

## Postman Collection

Import [Car-Rental-API.postman_collection.json](Car-Rental-API.postman_collection.json) into Postman.

Recommended flow:

1. Run `Login` to store the Bearer token.
2. Create or inspect cars, drivers, and customers.
3. Create a transaction.
4. Add bookings, bills, payments, and trip reports.

The collection includes examples for:

- auth and user registration
- CSV imports
- customer parent accounts and contact fields
- bookings and availability
- billing and bill payments
- trip reports

---

## Testing

Run the full suite:

```bash
php artisan test --compact
```

Run a single file:

```bash
php artisan test --compact tests/Feature/TripReportTest.php
```

Filter by test name:

```bash
php artisan test --compact --filter=can_create_a_trip_report
```

---

## License

This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
