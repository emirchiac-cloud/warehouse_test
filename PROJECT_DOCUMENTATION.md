# CENTRO ESCOLAR MÉXICO JUNIOR COLLEGE
## Information Technology Department — Semester 3
### Unified Inventory Management & Stock Control System

---

## 1. System Architecture Overview

```
+-------------------------------------------------------------+
|                UNIFIED ARCHITECTURE OVERVIEW                |
+-------------------------------------------------------------+
               |                               |
               v                               v
    +---------------------+         +---------------------+
    |   Back-Office Web   |         |   Warehouse Floor   |
    |   Portal (PHP 8 +   |         |   Mobile App        |
    |   Bootstrap 5)      |         |   (Native Android)  |
    +---------------------+         +---------------------+
               |                               |
               |                               | REST JSON
               | Session / Direct              | Retrofit 2 +
               | PDO Prepared Stmts            | Picasso
               v                               v
    +---------------------------------------------------------+
    |             Centralized PHP 8.x REST API Layer          |
    |            (/api/login.php, /api/products.php)          |
    +---------------------------------------------------------+
               |                               |
        (Media Uploads)                        | PDO Prepared Statements
               v                               v
    +---------------------+         +-------------------------+
    | Shared Media Folder |         |  MySQL 8 / MariaDB      |
    | (/uploads/products/)|         |  (inventory_system)     |
    +---------------------+         +-------------------------+
                                    | - users                 |
                                    | - categories            |
                                    | - products              |
                                    | - stock_logs (Audit)    |
                                    +-------------------------+
```

---

## 2. Relational Database Schema & ERD (3NF)

The database `inventory_system` is normalized into Third Normal Form (3NF) using the InnoDB storage engine with strict foreign key constraints:

```
+-------------------+        1:N        +-----------------------+
|    categories     |------------------>|       products        |
+-------------------+                   +-----------------------+
| PK id (INT)       |                   | PK id (INT)           |
|    name (VARCHAR) |                   | FK category_id (INT)  |
|    description    |                   |    sku (VARCHAR UQ)   |
+-------------------+                   |    name (VARCHAR)     |
                                        |    description (TEXT) |
+-------------------+                   |    unit_price (DEC)   |
|       users       |                   |    quantity (INT)     |
+-------------------+                   |    min_stock_threshold|
| PK id (INT)       |                   |    image_path (STR)   |
|    full_name (STR)|                   |    updated_at (TS)    |
|    email (VARCHAR)|                   +-----------------------+
|    password_hash  |                               |
|    role (ENUM)    |                               | 1:N (CASCADE)
|    created_at (TS)|                               v
+-------------------+                   +-----------------------+
          | 1:N                         |      stock_logs       |
          +---------------------------->+-----------------------+
                                        | PK id (INT)           |
                                        | FK product_id (INT)   |
                                        | FK user_id (INT)      |
                                        |    action_type (ENUM) |
                                        |    quantity_changed   |
                                        |    platform (ENUM)    |
                                        |    created_at (TS)    |
                                        +-----------------------+
```

### Table Definitions:
1. **`users`**: Manages authentication and role authorization (`admin`, `staff`). Passwords stored as BCrypt hashes generated via `password_hash()`.
2. **`categories`**: Taxonomy categories (e.g., Electronics, Office Supplies, Hardware). Restricted from deletion if products exist (`ON DELETE RESTRICT`).
3. **`products`**: Central catalog item records with stock count, unit price, safe minimum threshold, and relative image path.
4. **`stock_logs`**: Immutable audit trail of every stock modification. Logs action (`CREATED`, `STOCK_IN`, `STOCK_OUT`, `UPDATED`, `DELETED`), quantity delta, platform (`WEB` or `MOBILE`), user ID, and timestamp.

---

## 3. REST API Specification Table

| Endpoint | Method | Parameters / Body | Auth / Role | Success Response (200 / 201) | Error Response (400 / 401 / 404) |
|---|---|---|---|---|---|
| `/api/login.php` | `POST` | JSON: `{"email": "...", "password": "..."}` | Public | `{"status":"success", "id": 1, "full_name":"...", "role":"admin"}` | `{"status":"error", "message":"Invalid email or password"}` (401) |
| `/api/products.php` | `GET` | Query: `?category_id=X&search=keyword` | Public / Token | Array of Product JSON objects with `is_low_stock` boolean and `image_url` | `{"status":"error", "message":"..."}` (500) |
| `/api/products.php` | `POST` | `multipart/form-data`: `name`, `sku`, `category_id`, `unit_price`, `quantity`, `min_stock_threshold`, `image` file | Admin / Staff | `{"status":"success", "id":13, "message":"Product created"}` (201) | `{"status":"error", "message":"SKU already exists"}` (400) |
| `/api/products.php` | `PUT` / `POST (_method=PUT)` | Form / JSON: `id`, `stock_delta` (+1 / -1), `user_id`, `platform` | Admin / Staff | `{"status":"success", "quantity": 13, "is_low_stock": false}` (200) | `{"status":"error", "message":"Product not found"}` (404) |
| `/api/products.php` | `DELETE` | Query: `?id=X&user_id=Y&platform=MOBILE` | Admin | `{"status":"success", "id": X, "message":"Product deleted"}` (200) | `{"status":"error", "message":"Product ID is required"}` (400) |
| `/api/categories.php` | `GET` | None | Public | Array of Category JSON objects `[{"id":1, "name":"..."}]` | `{"status":"error", "message":"..."}` (500) |
| `/api/logs.php` | `GET` | Query: `?platform=MOBILE&limit=50` | Admin / Staff | Array of Stock Log objects with joined user and product names | `{"status":"error", "message":"..."}` (500) |

---

## 4. Setup & Deployment Guide

### A. Database Setup
1. Start MySQL / MariaDB via XAMPP Control Panel.
2. Open terminal / command prompt and import the database schema:
   ```bash
   mysql -u root < database/inventory_system.sql
   ```
3. Default user accounts:
   - **Administrator:** `admin@warehouse.com` / `admin123`
   - **Warehouse Staff:** `staff@warehouse.com` / `staff123`
   - **Floor Staff (Maria):** `maria@warehouse.com` / `staff123`

### B. Back-Office Web Portal Setup
1. Move or link `backend-web/` to your web server root (e.g., `C:\xampp\htdocs\warehouse\backend-web`).
2. Start Apache via XAMPP.
3. Open your browser and navigate to:
   ```
   http://localhost/warehouse/backend-web/
   ```
4. Log in with `admin@warehouse.com` and explore:
   - Real-time stock counts & KPI cards
   - Live search and category filters
   - Add/Edit/Delete product modals
   - **"Download Valuation & Low-Stock PDF"** button
   - **"Download Excel Report (.xlsx)"** button with live formulas
   - Stock Audit Logs view (filtering WEB vs. MOBILE events)

### C. Native Android Application Setup
1. Open this workspace in **Android Studio**.
2. Connect an Android device via USB (or start an Android Virtual Device / AVD emulator).
3. The app is pre-configured with Retrofit pointing to `http://10.0.2.2/warehouse/backend-web/` (default for Android Emulator).
   - If running on a **physical device**, tap **"Configure Server URL"** on the login screen and enter your PC's local WiFi IP (e.g., `http://192.168.1.100/warehouse/backend-web/`).
4. Log in with `staff@warehouse.com` / `staff123`.
5. Test the Five Core Workflows:
   - **Workflow 1 (Session Persistence):** Log in, close the app, re-open — auto-login brings you directly to the dashboard. Tap menu -> Logout to clear credentials.
   - **Workflow 2 (Product Grid & Picasso):** RecyclerView loads product photos asynchronously with placeholders and errors. Low-stock items are visually highlighted with a red badge and tinted background.
   - **Workflow 3 (Register New Inventory):** Tap the FAB button (+), snap a photo using the camera or gallery, pick category from the API spinner, fill details, and submit. The item appears instantly on the web portal.
   - **Workflow 4 (Detail, Quick Stock & Delete):** Tap any item to open details. Tap **+1** or **-1** to adjust stock in MySQL in real time. Tap **Delete Product** with confirmation AlertDialog.
   - **Workflow 5 (Hardware Alerts):** Tap **-1** until quantity drops below `min_stock_threshold`. The physical device will vibrate for **500ms** and post a system notification:
     - **Title:** `"Low Stock Warning"`
     - **Message:** `"[Product Name] is down to [X] units (Minimum: [Y]). Reorder required."`

---

## 5. APA 7th Edition References

- Google Developers. (2024). *Android Jetpack & Architecture Components documentation*. Google. https://developer.android.com/
- Oracle Corporation. (2024). *MySQL 8.0 Reference Manual*. Oracle. https://dev.mysql.com/doc/refman/8.0/en/
- Square Inc. (2024). *Retrofit: A type-safe HTTP client for Android and Java*. Square Open Source. https://square.github.io/retrofit/
- Square Inc. (2024). *Picasso: A powerful image downloading and caching library for Android*. Square Open Source. https://square.github.io/picasso/
- The PHP Group. (2024). *PHP: Hypertext Preprocessor documentation*. The PHP Group. https://www.php.net/docs.php
