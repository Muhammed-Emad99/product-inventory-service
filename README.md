# Product Inventory Microservice

A production-ready Product Inventory microservice built with **Laravel 11**, **PostgreSQL**, and **Redis**, fully containerized via **Docker**.

## Core Features
* **RESTful CRUD**: Add, edit, list, show, and soft-delete products.
* **Concurrency-Safe Stock Adjustment**: Prevent race conditions using database locks.
* **O(1) Redis Caching**: Cached product listing using cache-versioning for instant invalidation.
* **Low-Stock Alerting**: Triggers a `LowStockAlert` event to alert logs whenever quantities fall below the threshold.
* **PostgreSQL Optimization**: Partial indexes to accelerate low-stock scanning.
* **Rate Limiting**: Built-in API throttle middleware restricting requests to 60 per minute per IP.
* **Comprehensive Testing**: Full feature test suite using SQLite in-memory databases.

---

## Getting Started

### Prerequisites
* [Docker Desktop](https://www.docker.com/products/docker-desktop/)
* [Docker Compose](https://docs.docker.com/compose/)

### Installation & Run

1. **Clone & Enter Directory**
   Ensure the files reside in the project directory.

2. **Run Containers**
   Build and launch the PostgreSQL, Redis, PHP-FPM, and Nginx stack:
   ```bash
   docker-compose up -d --build
   ```

3. **Install Dependencies**
   Run Composer install inside the PHP container:
   ```bash
   docker-compose exec app composer install
   ```

4. **Generate App Key**
   Create the application key (if not already set in `.env`):
   ```bash
   docker-compose exec app php artisan key:generate
   ```

5. **Run Database Migrations**
   Create tables and PostgreSQL partial indexes:
   ```bash
   docker-compose exec app php artisan migrate
   ```

6. **Access Application**
   The API will be available at [http://localhost:8000](http://localhost:8000).

---

## Running Automated Tests

Run feature tests utilizing the in-memory SQLite configuration:
```bash
docker-compose exec app php artisan test
```

---

## API Endpoints List

All API endpoints are prefixed with `/api` and expect/respond with `Accept: application/json`.

| Method | Endpoint | Description | Rate Limited |
| :--- | :--- | :--- | :--- |
| **GET** | `/api/products` | Paginated listing of products. (Redis cached) | Yes (60/min) |
| **GET** | `/api/products/low-stock` | Retrieve products below their threshold limit. | Yes (60/min) |
| **GET** | `/api/products/{id}` | Details of a single product. (Redis cached) | Yes (60/min) |
| **POST** | `/api/products` | Create a new product. (Clears listing cache) | Yes (60/min) |
| **PUT** | `/api/products/{id}` | Edit properties of a product. (Clears cache) | Yes (60/min) |
| **DELETE** | `/api/products/{id}` | Soft-delete a product. (Clears cache) | Yes (60/min) |
| **POST** | `/api/products/{id}/stock` | Adjust stock quantity (+/- amount). (Clears cache) | Yes (60/min) |

---

## Postman Collection
An importable Postman collection is included in the root folder:
* File: [Product_Inventory_API.postman_collection.json](file:///C:/Users/alreada/.gemini/antigravity-ide/scratch/product-inventory-service/Product_Inventory_API.postman_collection.json)

To use it:
1. Open Postman, click **Import**, and select the JSON file.
2. The collection defines a `base_url` variable defaulted to `http://localhost:8000`.
3. Create a product using the `Create Product` request. Copy its `id` from the response.
4. Set the `product_id` variable in your Postman collection variables to easily test the `Get Single Product`, `Update Product`, `Adjust Stock`, and `Delete Product` routes.

---

## Development Notes
For detailed reasoning about DB queries, pessimistic locks, O(1) Redis versioning, and architectural patterns, refer to [architectural_decisions.md](file:///C:/Users/alreada/.gemini/antigravity-ide/scratch/product-inventory-service/architectural_decisions.md).
