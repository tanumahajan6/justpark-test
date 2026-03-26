# JustPark — Parking Availability System

A full-stack parking availability and booking system built with **Laravel 11** (backend API) and **React + Vite** (frontend SPA).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.2+ |
| Database | SQLite (local) |
| Frontend | React 18, Vite |
| HTTP Client | Axios |
| Date Handling | Day.js |
| Testing | PHPUnit (Laravel Feature Tests) |

---

## Project Structure

```
justpark-test/
├── backend/                  # Laravel API
│   ├── app/
│   │   ├── DTOs/             # Data Transfer Objects
│   │   ├── Exceptions/       # Custom exceptions
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/      # API controllers
│   │   │   ├── Requests/     # Form request validation
│   │   │   └── Resources/    # API response shaping
│   │   ├── Models/           # Eloquent models
│   │   └── Services/         # Business logic
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php
│   └── tests/
│       └── Feature/
└── frontend/                 # React SPA
    └── src/
        ├── api/              # Axios instance + API calls
        ├── components/       # UI components + CSS
        └── hooks/            # Custom React hooks
```

---

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm

---

## Backend Setup

```bash
cd backend

# Install dependencies
composer install

Configure `.env` for SQLite:

```env
DB_CONNECTION=sqlite
```

Remove all other `DB_` lines from `.env`, then:

```bash
# Create the SQLite database file
touch database/database.sqlite

# Run migrations and seed with fixture data
php artisan migrate --seed

# Start the development server
php artisan serve
```

Backend runs at `http://localhost:8000`.

---

## Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start the development server
npm run dev
```

Frontend runs at `http://localhost:5173`.

---

## Seed Data

The seeder loads the following fixture data:

**Parking Spaces**

| ID | Location | Price/hr |
|---|---|---|
| 1 | City Center Garage A | £5.00 |
| 2 | City Center Garage A | £5.00 |
| 3 | Suburban Lot B | £3.50 |
| 4 | Airport Parking C | £7.50 |

**Initial Bookings**

| ID | Space | User | From | To |
|---|---|---|---|---|
| 101 | 1 | 1 | 2026-03-01 09:00 | 2026-03-01 12:00 |
| 102 | 3 | 2 | 2026-03-02 14:30 | 2026-03-02 18:00 |

To reset the database back to this fixture state at any time:

```bash
php artisan migrate:fresh --seed
```

---

## API Reference

### GET `/api/v1/parking-spaces`

Returns parking spaces that are **not booked** within the requested time window.

**Query Parameters**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `start_time` | datetime | Yes | `YYYY-MM-DD HH:MM:SS` |
| `end_time` | datetime | Yes | `YYYY-MM-DD HH:MM:SS` — must be after `start_time` |
| `location` | string | No | Partial match filter |
| `max_price` | numeric | No | Maximum hourly rate |

**Example Request**

```bash
curl "http://localhost:8000/api/v1/parking-spaces?start_time=2026-03-01+09:00:00&end_time=2026-03-01+12:00:00"
```

**Example Response**

```json
{
  "data": [
    { "id": 2, "location": "City Center Garage A", "hourly_price": 5.00 },
    { "id": 3, "location": "Suburban Lot B", "hourly_price": 3.50 },
    { "id": 4, "location": "Airport Parking C", "hourly_price": 7.50 }
  ]
}
```

---

### POST `/api/v1/bookings`

Creates a booking for a parking space. Returns `409 Conflict` if the space is already booked within the requested window.

**Request Body**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `parking_space_id` | integer | Yes | Must exist in the database |
| `user_id` | integer | Yes | Placeholder — no auth required |
| `start_time` | datetime | Yes | `YYYY-MM-DD HH:MM:SS` |
| `end_time` | datetime | Yes | `YYYY-MM-DD HH:MM:SS` — must be after `start_time` |

**Example Request**

```bash
curl -X POST http://localhost:8000/api/v1/bookings \
  -H "Content-Type: application/json" \
  -d '{"parking_space_id": 2, "user_id": 1, "start_time": "2026-03-01 09:00:00", "end_time": "2026-03-01 12:00:00"}'
```

**Success Response — 201**

```json
{
  "data": {
    "id": 1,
    "parking_space_id": 2,
    "user_id": 1,
    "start_time": "2026-03-01 09:00:00",
    "end_time": "2026-03-01 12:00:00"
  }
}
```

**Conflict Response — 409**

```json
{
  "message": "This parking space is already booked for the requested time range.",
  "error": "conflict"
}
```

---

## Running Tests

```bash
cd backend
php artisan test
```

**Test coverage — 27 tests across two areas:**

*Search / Availability (15 tests)*
- Returns all spaces for an unbooked window
- Excludes spaces with exact, partial (left/right), wrapped, and inner overlaps
- Correctly allows adjacent bookings (boundary conditions)
- Filters by partial location name and max price
- Validates required fields and date ordering
- Asserts correct response structure

*Conflict Detection (12 tests)*
- Creates booking successfully when no conflict
- Returns 409 for exact, partial, wrapped, and inner overlapping bookings
- Allows adjacent bookings that touch but don't overlap
- Confirms different spaces never conflict with each other
- Validates required fields, non-existent space IDs, and date ordering

---

## Architecture Decisions

### Laravel — Layered architecture

```
Request → FormRequest (validate) → Controller → DTO → Service → Resource → Response
```

- **Form Requests** — validate and authorise all incoming input before it reaches the controller
- **DTOs** — immutable typed objects that carry data from the HTTP layer into services; services have no knowledge of `Request` objects
- **Services** — contain all business logic; can be called from controllers, Artisan commands, or queue jobs without modification
- **API Resources** — control exactly what fields are returned to the client, decoupling the database schema from the API contract

### Conflict detection

The overlap condition used throughout is:

```
existing.start_time < requested.end_time
AND existing.end_time > requested.start_time
```

This single condition correctly handles all overlap cases — exact match, partial from either direction, outer containment, and inner containment — while strict `<` and `>` operators correctly allow adjacent (back-to-back) bookings.

### Race condition prevention

The `POST /api/v1/bookings` endpoint wraps the conflict check and insert in a database transaction with `lockForUpdate()`, preventing double-bookings under concurrent requests.

### Why SQLite for local development

Zero setup friction for reviewers. The overlap queries, locking, and all application logic work identically on MySQL or PostgreSQL in production.

### Why service layer over repository pattern

For a Laravel project of this scope where Eloquent is the permanent data layer, the service layer provides sufficient separation of concerns without the additional abstraction overhead of a repository interface. The services are independently testable and the data layer can be swapped at the service level if ever needed.

---

## Stretch Goals Implemented

| Goal | Status |
|---|---|
| Pessimistic locking / race condition prevention | ✅ `lockForUpdate()` inside `DB::transaction` |
| Error handling and validation UX | ✅ Client-side date validation, field-level errors, 409 conflict message |
| Testing | ✅ 27 feature tests covering all overlap cases and edge conditions |
