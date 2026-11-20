# Vending Machine System — PHP 8 (Vanilla MVC)

A robust vending‑machine application built in **plain PHP 8** (no framework), covering
product management, inventory tracking, purchase transactions, authentication and a
JWT‑secured REST API.

Author: **Si Thu Aung** · Submission for the Senior Backend Developer test.

---

## 1. Tech stack
- **PHP 8.1+**, **PDO** (MySQL in production; SQLite for tests)
- Custom lightweight **MVC + Router** (manual + PHP 8 **attribute** routing)
- Session auth with `password_hash`, **role‑based access control** (Admin / User)
- **Dependency‑free JWT (HS256)** for the REST API
- **PHPUnit** tests with **dependency injection + mocking**
- No runtime dependencies — runs without `composer install`

## 2. How each task is addressed
| # | Task | Where |
|---|------|-------|
| 1 | DB design (products, users, transactions) | `database/schema.sql`, `database/migrate.php` |
| 2 | PDO connection class + CRUD | `src/Core/Database.php`, `src/Models/*` |
| 3 | Auth (sessions + password hashing, roles) | `src/Core/Auth.php`, `src/Models/User.php` |
| 4 | Role‑based access control | `src/Middleware/AdminMiddleware.php` |
| 5 | `ProductsController` (CRUD) | `src/Controllers/ProductsController.php` |
| 6 | Purchase function (qty update + log) | `ProductsController::purchase`, `src/Services/PurchaseService.php` |
| 7 | Views for CRUD (admin‑only management) | `views/products/*`, `AdminMiddleware` |
| 8 | Product list with pagination + sorting | `views/products/index.php`, `Product::paginate` |
| 9 | Purchase view | `views/products/purchase.php` |
| 10 | Routing for ProductsController (CRUD + purchase) | `routes.php` |
| 11 | Attribute routing for purchase | `#[Route]` in `ProductsController` |
| 12 | Product input validation (required, price>0, qty>=0) | `src/Core/Validator.php` |
| 13 | Server‑side **and** client‑side validation | `Validator.php` + `public/assets/js/validate.js` |
| 14 | PHPUnit tests for ProductsController | `tests/ProductsControllerTest.php` |
| 15 | DI + mocking (test controller without DB) | mocked `Product` / `PurchaseService` |
| 16 | RESTful API | `src/Controllers/Api/*`, `routes.php` |
| 17 | Token‑based (JWT) auth for the API | `src/Services/JwtService.php`, `src/Middleware/JwtMiddleware.php` |
| + | Purchase history (admin: all, user: own) | `TransactionsController`, `views/transactions/index.php`, `GET /api/transactions` |

## 3. Project structure
```
public/            Front controller (index.php) + assets   ← web root
src/Core/          Router, Database, Request, Response, Auth, Validator, View
src/Models/        Product, User, Transaction (PDO, injectable)
src/Controllers/   Web + Api controllers
src/Middleware/    Auth, Admin, JWT guards
src/Services/      PurchaseService (atomic purchase), JwtService
views/             PHP templates
database/          schema.sql + migrate.php (schema + seed)
tests/             PHPUnit tests
routes.php         Route definitions
bootstrap.php      Autoloader + config + helpers
```

## 4. Local setup
```bash
# 1. Configure environment
cp .env.example .env            # then edit DB_* and JWT_SECRET

# 2. Create schema + seed demo data
php database/migrate.php

# 3. Run
php -S 127.0.0.1:8000 -t public
# open http://127.0.0.1:8000
```
> To try it instantly without MySQL, set `DB_CONNECTION=sqlite` and
> `DB_DATABASE=./database/app.sqlite` in `.env`, then run the two commands above.

## 5. Deployment (Railway + MySQL)
The repo ships a `Dockerfile` (PHP 8.3 + `pdo_mysql`). On start it runs
`php database/migrate.php` (creates tables + seeds demo data, waits for MySQL to be ready),
then serves `public/` with PHP's built-in server on `$PORT`.

1. Railway → **New Project → Deploy from GitHub repo** → pick this repo.
2. In the project: **+ New → Database → MySQL**.
3. App service → **Variables**:
   ```
   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   JWT_SECRET=<long random string>
   APP_ENV=production
   APP_DEBUG=false
   ```
4. App service → **Settings → Networking → Generate Domain**.

Any Docker host works the same way; for Apache/cPanel point the document root at `public/`
(`public/.htaccess` handles pretty URLs).

## 6. Demo credentials (after migrate)
| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@vending.test` | `admin123` |
| User | `user@vending.test` | `user123` |

## 7. REST API
All `/api/products*` and `/api/transactions` routes require `Authorization: Bearer <token>`
(paste the raw token: no `<` `>` around it).
Write operations (create/update/delete) require an **admin** token.

```bash
# Login → get a JWT
curl -X POST http://localhost:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@vending.test","password":"admin123"}'

TOKEN=...   # access_token from the response

# List products
curl http://localhost:8000/api/products -H "Authorization: Bearer $TOKEN"

# Create (admin)
curl -X POST http://localhost:8000/api/products -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' -d '{"name":"Juice","price":2.5,"quantity_available":5}'

# Purchase
curl -X POST http://localhost:8000/api/products/1/purchase -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' -d '{"quantity":2}'
```

| Method | Endpoint | Access |
|--------|----------|--------|
| POST | `/api/login` | public |
| GET | `/api/products` | any authed |
| GET | `/api/products/{id}` | any authed |
| POST | `/api/products` | admin |
| PUT | `/api/products/{id}` | admin |
| DELETE | `/api/products/{id}` | admin |
| POST | `/api/products/{id}/purchase` | any authed |
| GET | `/api/transactions` | admin: all · user: own |

## 8. Running tests
```bash
composer install            # installs PHPUnit (dev)
./vendor/bin/phpunit        # runs the suite
```
Tests use an **in‑memory SQLite** database and **mocked** collaborators, so no real
database is needed.

## 9. Design & security notes
- Purchases run inside a **DB transaction** with row locking (`FOR UPDATE` on MySQL)
  and a guarded decrement, preventing overselling / double‑decrement under concurrency.
- All queries use **prepared statements** (SQL‑injection safe).
- **CSRF tokens** on every state‑changing web form; output is **HTML‑escaped** (XSS).
- A product that has purchase history cannot be deleted (keeps transaction records intact);
  set its quantity to 0 instead. Web shows a message, API returns `409`.
- Passwords hashed with `password_hash` (bcrypt); sessions regenerated on login.
- Prices stored as `DECIMAL` and computed as fixed‑precision strings (no float money).
