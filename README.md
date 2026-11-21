# Folio Books Admin

A Laravel 13 bookstore administration panel for organizing a book catalogue, managing inventory, and maintaining categories, subcategories, and publishers.

## Requirements

- PHP 8.3+ with the PDO MySQL extension enabled
- Composer
- Node.js and npm
- MySQL or MariaDB (the default local configuration is for XAMPP)

## Local setup

1. Install PHP and frontend dependencies:

   ```sh
   composer install
   npm install
   ```

2. On a fresh checkout only, create `.env` from the example if it does not already exist, then configure the app key:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   Keep an existing `.env` intact. The example configuration uses MySQL on `127.0.0.1:3306`, with database `book_store` and XAMPP's default `root` account without a password. Create the database first if it does not exist, or update the `DB_*` values for your setup. Set `ADMIN_NAME`, `ADMIN_EMAIL`, and a strong `ADMIN_PASSWORD` in `.env` before creating the first administrator. No default administrator password is provided.

3. Create the database tables, seed the configured administrator, and expose uploaded images:

   ```sh
   php artisan migrate
   php artisan db:seed
   php artisan storage:link
   ```

4. Build frontend assets and start the application:

   ```sh
   npm run build
   php artisan serve
   ```

   For frontend development, use `npm run dev` in a separate terminal.

5. Open `http://127.0.0.1:8000` and sign in with the administrator credentials configured in `.env`.

## Features

- Admin-only session authentication with throttled login, profile photo, and password settings
- Dashboard metrics, inventory alerts, category distribution, and recent activity
- Menu Master with parent/child links, URL validation, display order, tab behavior, and active status for future storefront integration
- Contact Us Page Master for single-record page copy, contact details, banner image, social links, and SEO settings
- Searchable, filterable, paginated CRUD for categories, subcategories, publishers, and books
- Book ISBN/SKU validation, calculated discount, stock status, cover and gallery image uploads
- Protected relationship-aware deletion and activity logging
- Media library, responsive navigation, confirmation dialogs, and toast notifications

## Tests

Run the feature and unit suites with:

```sh
php artisan test
```

The feature tests use an in-memory SQLite database and do not alter the configured local database.
