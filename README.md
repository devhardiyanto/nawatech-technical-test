# Nawatech Technical Test - Order Management API

Laravel implementation for Nawatech PHP Developer Coding Test.

## Tech Stack

- PHP 8.2+
- Laravel 12
- PostgreSQL (primary target)
- Redis (queue + cache)
- Pest (testing)
- k6 (performance test)

## Architecture

Layered flow:

- Controller -> Service -> Model
- Validation via FormRequest
- API response envelope via `App\Support\ApiResponse`

## Implemented API Endpoints

- `GET /api/orders`
  - List orders with nested `user`, `items`, and `product`.
  - Supports filters: `status`, `payment_status`, `user_id`, `from_date`, `to_date`.
  - Supports pagination mode:
    - cursor pagination (default)
    - offset pagination (`pagination_mode=offset`)
  - `per_page` is clamped to max `100`.

- `POST /api/orders`
  - Create order with stock validation and DB transaction.
  - Uses row lock (`lockForUpdate`) to reduce overselling risk.

- `POST /api/orders/{order}/pay`
  - Queue dummy async payment process (`redis` queue target).

- `GET /api/reports/orders-summary`
  - Cached report with metrics:
    - `total_revenue` (paid orders only)
    - `total_orders`
    - `average_order_value`
    - `top_3_selling_products`
  - Cache TTL: `120` seconds.

## Business Rules Applied

- Allowed `payment_status`:
  - `pending`
  - `paid`
  - `failed`
  - `refunded`
- Flat Envelope API contract:
  - success: `code`, `message`, `data`, `meta`
  - error: `code`, `message`, `details`
- Pagination:
  - default: `20`
  - max: `100`

## Local Setup

1. Install dependencies:

```bash
composer install
```

2. Copy env and configure DB/Redis:

```bash
cp .env.example .env
php artisan key:generate
```

3. Run migrations:

```bash
php artisan migrate
```

4. Run app:

```bash
php artisan serve
```

## Queue Worker (for payment job)

```bash
php artisan queue:work --queue=payments
```

## Run Tests

```bash
php artisan test
```

Note: if your local PHP does not have `pdo_sqlite` enabled, use PostgreSQL for test DB by setting `DB_CONNECTION=pgsql` in `.env.testing`.

## Performance Check (k6)

File: `tests/performance/orders-list.k6.js`

Example run:

```bash
k6 run tests/performance/orders-list.k6.js -e BASE_URL=http://127.0.0.1:8000 -e VUS=100 -e DURATION=30s
```

Default threshold in script:

- `p(95)` of `GET /api/orders` duration < `300ms`
- failure rate < `1%`

## Branching Workflow

- `chore/day-1` -> implement, commit locally, merge to `main` locally.
- Create `chore/day-2` from updated `main`.
- Repeat until `day-3`.
- No remote push required during development phase.
