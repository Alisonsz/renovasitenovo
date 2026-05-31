# Loja Laravel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrar a loja WooCommerce da Renova Laser para Laravel + Inertia/Vue, com catálogo, categorias, carrinho, checkout PagBank, dashboard administrativo, seeds dos produtos atuais e metadados Google Merchant.

**Architecture:** O Laravel será a fonte principal de catálogo, carrinho, pedidos e pagamentos. A loja pública continuará em Inertia/Vue, usando rotas SEO amigáveis como `/depilacao-feminina` e `/depilacao-masculina`; o admin ficará em `/admin`, protegido por autenticação Laravel. Pagamentos serão isolados em serviços de domínio para permitir iniciar com Checkout PagBank redirecionado e evoluir para API Order direta se necessário.

**Tech Stack:** Laravel 13, PHP 8.3, Inertia Laravel 3, Vue 3, Tailwind 4, MySQL/MariaDB no Laragon, PagBank Connect/API, Google Merchant feed XML.

---

## Current Discovery

### WordPress/WooCommerce Source

- WordPress local: `C:\Users\aliso\OneDrive\Documentos\Codex\renovasitevelho\renovalaserdepilacao.com.br`
- Database: `renova`
- Prefix: `wpk7_`
- Store engine: WooCommerce
- Active payment plugin: `pagbank-connect`
- Current products: `35` published simple products
- Current coupon count: `1`
- Product categories:
  - `depilacao-feminina`: 24 products
  - `depilacao-masculina`: 11 products
  - feminine children: `combos`, `pacotes`, `avulsas`
  - masculine children: `combos-depilacao-masculina`, `pacotes-depilacao-masculina`, `sessoes-avulsas`
- Google Merchant plugin: `google-listings-and-ads`
- Merchant settings found:
  - `gla_merchant_id`: `5636402645`
  - target country: `BR`
  - currency: `BRL`
  - shipping rate: flat, `0`
  - shipping time: `0`
  - global attribute rules:
    - `adult`: `no`
    - `ageGroup`: `adult`
    - `brand`: `Renova Laser Depilação`
    - `color`: `Padrão`
    - `condition`: `new`
    - `gender`: `female` only category `30`
    - `gender`: `male` only category `31`
    - `isBundle`: `yes` except category `34`
    - `size`: `Padrão`

### Payment Discovery

- PagBank global settings:
  - production mode: `is_sandbox = false`
  - title: `RenovaLaser Depilação Ltda`
  - soft descriptor: `RenovaLaserDepil`
- Active methods:
  - `rm-pagbank-pix`: enabled
  - `rm-pagbank-cc`: enabled
- Pix:
  - expiry: `1440` minutes
  - discount: `5%`
- Credit card:
  - fixed installments: `12`
  - minimum installment total: `78`
  - 3DS enabled

Official references used:

- PagBank API Order: https://developer.pagbank.com.br/docs/pedidos-e-pagamentos-order
- PagBank Checkout and Payment Link: https://developer.pagbank.com.br/docs/checkout

## Recommended Payment Decision

Start with **PagBank Checkout redirection** for the first production-ready version.

Why:

- Avoids storing or handling card data in the Laravel app.
- Reduces PCI and 3DS complexity.
- Preserves Pix and credit card availability.
- Lets us finish catalog, cart, orders, dashboard and product migration faster.
- The Laravel system still owns orders and receives webhook/status updates.

Keep an interface that allows replacing the redirected checkout with direct API Order later.

---

## Target File Structure

### Backend Domain

- Create: `app/Models/Product.php`
- Create: `app/Models/ProductCategory.php`
- Create: `app/Models/ProductImage.php`
- Create: `app/Models/Cart.php`
- Create: `app/Models/CartItem.php`
- Create: `app/Models/Order.php`
- Create: `app/Models/OrderItem.php`
- Create: `app/Models/Customer.php`
- Create: `app/Models/Coupon.php`
- Create: `app/Models/PaymentTransaction.php`
- Create: `app/Enums/OrderStatus.php`
- Create: `app/Enums/PaymentStatus.php`
- Create: `app/Enums/PaymentMethod.php`
- Create: `app/Services/Store/CartService.php`
- Create: `app/Services/Store/CheckoutService.php`
- Create: `app/Services/Store/ProductImportService.php`
- Create: `app/Services/Payments/PagBankClient.php`
- Create: `app/Services/Payments/PagBankCheckoutService.php`
- Create: `app/Services/Payments/PagBankWebhookVerifier.php`
- Create: `app/Services/Merchant/GoogleMerchantFeedService.php`

### Backend HTTP

- Create: `app/Http/Controllers/Store/CategoryController.php`
- Create: `app/Http/Controllers/Store/ProductController.php`
- Create: `app/Http/Controllers/Store/CartController.php`
- Create: `app/Http/Controllers/Store/CheckoutController.php`
- Create: `app/Http/Controllers/Store/PaymentReturnController.php`
- Create: `app/Http/Controllers/Webhooks/PagBankWebhookController.php`
- Create: `app/Http/Controllers/Merchant/GoogleMerchantFeedController.php`
- Create: `app/Http/Controllers/Admin/DashboardController.php`
- Create: `app/Http/Controllers/Admin/ProductController.php`
- Create: `app/Http/Controllers/Admin/ProductCategoryController.php`
- Create: `app/Http/Controllers/Admin/OrderController.php`
- Create: `app/Http/Controllers/Admin/CouponController.php`

### Requests

- Create: `app/Http/Requests/Store/AddCartItemRequest.php`
- Create: `app/Http/Requests/Store/UpdateCartItemRequest.php`
- Create: `app/Http/Requests/Store/CheckoutRequest.php`
- Create: `app/Http/Requests/Admin/StoreProductRequest.php`
- Create: `app/Http/Requests/Admin/UpdateProductRequest.php`
- Create: `app/Http/Requests/Admin/StoreCategoryRequest.php`
- Create: `app/Http/Requests/Admin/UpdateCategoryRequest.php`
- Create: `app/Http/Requests/Admin/StoreCouponRequest.php`
- Create: `app/Http/Requests/Admin/UpdateCouponRequest.php`

### Database

- Create: `database/migrations/2026_05_31_000001_create_product_categories_table.php`
- Create: `database/migrations/2026_05_31_000002_create_products_table.php`
- Create: `database/migrations/2026_05_31_000003_create_product_images_table.php`
- Create: `database/migrations/2026_05_31_000004_create_customers_table.php`
- Create: `database/migrations/2026_05_31_000005_create_carts_table.php`
- Create: `database/migrations/2026_05_31_000006_create_cart_items_table.php`
- Create: `database/migrations/2026_05_31_000007_create_coupons_table.php`
- Create: `database/migrations/2026_05_31_000008_create_orders_table.php`
- Create: `database/migrations/2026_05_31_000009_create_order_items_table.php`
- Create: `database/migrations/2026_05_31_000010_create_payment_transactions_table.php`
- Create: `database/seeders/StoreSeeder.php`
- Create: `database/seeders/ProductCategorySeeder.php`
- Create: `database/seeders/ProductSeeder.php`
- Create: `database/seeders/CouponSeeder.php`

### Console

- Create: `app/Console/Commands/ImportWooCommerceCatalog.php`
- Create: `app/Console/Commands/SyncProductImages.php`
- Create: `app/Console/Commands/CheckPagBankOrderStatus.php`

### Frontend Store

- Create: `resources/js/Pages/Store/Category.vue`
- Create: `resources/js/Pages/Store/Product.vue`
- Create: `resources/js/Pages/Store/Cart.vue`
- Create: `resources/js/Pages/Store/Checkout.vue`
- Create: `resources/js/Pages/Store/PaymentReturn.vue`
- Create: `resources/js/Components/Store/ProductCard.vue`
- Create: `resources/js/Components/Store/CategoryTabs.vue`
- Create: `resources/js/Components/Store/CartSummary.vue`
- Create: `resources/js/Components/Store/QuantityInput.vue`
- Create: `resources/js/Components/Store/Price.vue`
- Create: `resources/js/Components/Store/CheckoutForm.vue`

### Frontend Admin

- Create: `resources/js/Layouts/AdminLayout.vue`
- Create: `resources/js/Pages/Admin/Dashboard.vue`
- Create: `resources/js/Pages/Admin/Products/Index.vue`
- Create: `resources/js/Pages/Admin/Products/Form.vue`
- Create: `resources/js/Pages/Admin/Categories/Index.vue`
- Create: `resources/js/Pages/Admin/Categories/Form.vue`
- Create: `resources/js/Pages/Admin/Orders/Index.vue`
- Create: `resources/js/Pages/Admin/Orders/Show.vue`
- Create: `resources/js/Pages/Admin/Coupons/Index.vue`
- Create: `resources/js/Pages/Admin/Coupons/Form.vue`

### Routes and Config

- Modify: `routes/web.php`
- Create: `routes/admin.php`
- Create: `routes/webhooks.php`
- Modify: `bootstrap/app.php`
- Modify: `config/services.php`
- Modify: `.env.example`

### Tests

- Create: `tests/Feature/Store/CategoryPageTest.php`
- Create: `tests/Feature/Store/ProductPageTest.php`
- Create: `tests/Feature/Store/CartTest.php`
- Create: `tests/Feature/Store/CheckoutTest.php`
- Create: `tests/Feature/Payments/PagBankWebhookTest.php`
- Create: `tests/Feature/Merchant/GoogleMerchantFeedTest.php`
- Create: `tests/Feature/Admin/ProductCrudTest.php`
- Create: `tests/Feature/Admin/CategoryCrudTest.php`
- Create: `tests/Feature/Admin/OrderManagementTest.php`
- Create: `tests/Feature/Admin/CouponCrudTest.php`

---

## Data Model

### `product_categories`

Columns:

- `id`
- `parent_id` nullable self foreign key
- `wp_term_id` nullable unsigned bigint, unique
- `name`
- `slug`, unique
- `description` nullable text
- `google_gender` nullable string
- `merchant_visible` boolean default true
- `position` unsigned integer default 0
- timestamps

### `products`

Columns:

- `id`
- `wp_product_id` nullable unsigned bigint, unique
- `primary_category_id` foreign key
- `name`
- `slug`, unique
- `short_description` text nullable
- `description` long text nullable
- `sku` nullable string
- `regular_price_cents` unsigned integer
- `sale_price_cents` unsigned integer nullable
- `price_cents` unsigned integer
- `currency` string default `BRL`
- `stock_status` string default `instock`
- `is_active` boolean default true
- `is_custom_quote` boolean default false
- `merchant_visibility` string default `sync-and-show`
- `merchant_status` nullable string
- `merchant_google_id` nullable string
- `merchant_brand` nullable string
- `merchant_condition` nullable string
- `merchant_age_group` nullable string
- `merchant_gender` nullable string
- `merchant_color` nullable string
- `merchant_size` nullable string
- `merchant_is_bundle` boolean default false
- `metadata` json nullable
- timestamps

### `product_category_product`

Columns:

- `id`
- `product_id`
- `product_category_id`
- unique pair

### `product_images`

Columns:

- `id`
- `product_id`
- `wp_attachment_id` nullable unsigned bigint
- `url`
- `local_path` nullable string
- `alt` nullable string
- `position` unsigned integer default 0
- timestamps

### `customers`

Columns:

- `id`
- `user_id` nullable foreign key
- `name`
- `email`
- `phone`
- `document` nullable string
- timestamps

### `carts`

Columns:

- `id`
- `uuid`, unique
- `user_id` nullable foreign key
- `customer_id` nullable foreign key
- `coupon_id` nullable foreign key
- `expires_at` nullable timestamp
- timestamps

### `cart_items`

Columns:

- `id`
- `cart_id`
- `product_id`
- `quantity`
- `unit_price_cents`
- `total_cents`
- timestamps

### `coupons`

Columns:

- `id`
- `wp_coupon_id` nullable unsigned bigint, unique
- `code`, unique
- `type` string: `fixed_cart`, `percent`
- `amount_cents` nullable unsigned integer
- `percent` nullable decimal
- `starts_at` nullable timestamp
- `expires_at` nullable timestamp
- `usage_limit` nullable unsigned integer
- `used_count` unsigned integer default 0
- `is_active` boolean default true
- `metadata` json nullable
- timestamps

### `orders`

Columns:

- `id`
- `number`, unique
- `user_id` nullable foreign key
- `customer_id` foreign key
- `cart_id` nullable foreign key
- `coupon_id` nullable foreign key
- `status` string default `pending`
- `payment_status` string default `pending`
- `payment_method` nullable string
- `subtotal_cents`
- `discount_cents`
- `pix_discount_cents`
- `total_cents`
- `currency` string default `BRL`
- `pagbank_checkout_id` nullable string
- `pagbank_order_id` nullable string
- `pagbank_pay_url` nullable text
- `paid_at` nullable timestamp
- `cancelled_at` nullable timestamp
- `metadata` json nullable
- timestamps

### `order_items`

Columns:

- `id`
- `order_id`
- `product_id` nullable foreign key
- `product_name`
- `product_slug`
- `quantity`
- `unit_price_cents`
- `total_cents`
- `metadata` json nullable
- timestamps

### `payment_transactions`

Columns:

- `id`
- `order_id`
- `provider` string default `pagbank`
- `provider_transaction_id` nullable string
- `provider_checkout_id` nullable string
- `method` nullable string
- `status` string
- `amount_cents`
- `raw_payload` json nullable
- timestamps

---

## Route Map

### Public Store

- `GET /depilacao-feminina` -> `Store\CategoryController@show`
- `GET /depilacao-masculina` -> `Store\CategoryController@show`
- `GET /loja/categoria/{category:slug}` -> `Store\CategoryController@show`
- `GET /produto/{product:slug}` -> `Store\ProductController@show`
- `GET /carrinho` -> `Store\CartController@show`
- `POST /carrinho/items` -> `Store\CartController@store`
- `PATCH /carrinho/items/{cartItem}` -> `Store\CartController@update`
- `DELETE /carrinho/items/{cartItem}` -> `Store\CartController@destroy`
- `POST /carrinho/cupom` -> `Store\CartController@applyCoupon`
- `DELETE /carrinho/cupom` -> `Store\CartController@removeCoupon`
- `GET /checkout` -> `Store\CheckoutController@show`
- `POST /checkout` -> `Store\CheckoutController@store`
- `GET /pedido/{order:number}/retorno` -> `Store\PaymentReturnController@show`

### Webhooks

- `POST /webhooks/pagbank` -> `Webhooks\PagBankWebhookController@store`

### Merchant

- `GET /merchant/google.xml` -> `Merchant\GoogleMerchantFeedController@show`

### Admin

- `GET /admin` -> `Admin\DashboardController@index`
- `GET /admin/products` -> `Admin\ProductController@index`
- `GET /admin/products/create` -> `Admin\ProductController@create`
- `POST /admin/products` -> `Admin\ProductController@store`
- `GET /admin/products/{product}/edit` -> `Admin\ProductController@edit`
- `PATCH /admin/products/{product}` -> `Admin\ProductController@update`
- `DELETE /admin/products/{product}` -> `Admin\ProductController@destroy`
- `GET /admin/categories` -> `Admin\ProductCategoryController@index`
- `GET /admin/orders` -> `Admin\OrderController@index`
- `GET /admin/orders/{order}` -> `Admin\OrderController@show`
- `PATCH /admin/orders/{order}/status` -> `Admin\OrderController@updateStatus`
- `GET /admin/coupons` -> `Admin\CouponController@index`

---

## Task 1: Add Store Routes and Route Files

**Files:**

- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Create: `routes/admin.php`
- Create: `routes/webhooks.php`

- [ ] **Step 1: Create empty route files**

Create `routes/admin.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');
    });
```

Create `routes/webhooks.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')
    ->name('webhooks.')
    ->group(function () {
    });
```

- [ ] **Step 2: Register route files**

Modify `bootstrap/app.php` to include:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    then: function () {
        Route::middleware('web')->group(base_path('routes/admin.php'));
        Route::middleware('web')->group(base_path('routes/webhooks.php'));
    },
    health: '/up',
)
```

- [ ] **Step 3: Run route list**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan route:list
```

Expected: route list renders without errors.

---

## Task 2: Create Store Migrations

**Files:**

- Create migrations listed in the Database section.

- [ ] **Step 1: Create migration files**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_product_categories_table
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_products_table
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_product_images_table
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_customers_table
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_carts_table
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_cart_items_table
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_coupons_table
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_orders_table
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_order_items_table
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:migration create_payment_transactions_table
```

- [ ] **Step 2: Implement migration columns**

Use the Data Model section as the exact schema. Money values must be stored as integer cents, never decimal floats.

- [ ] **Step 3: Run migrations**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan migrate
```

Expected: migrations complete successfully.

---

## Task 3: Create Models and Relationships

**Files:**

- Create all models listed in Backend Domain.

- [ ] **Step 1: Create model files**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model Product
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model ProductCategory
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model ProductImage
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model Customer
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model Cart
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model CartItem
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model Coupon
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model Order
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model OrderItem
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan make:model PaymentTransaction
```

- [ ] **Step 2: Add model relationships**

Required relationships:

- `Product` belongs to `primaryCategory`, belongs to many `categories`, has many `images`.
- `ProductCategory` belongs to `parent`, has many `children`, belongs to many `products`.
- `Cart` has many `items`, belongs to `coupon`, belongs to `customer`.
- `CartItem` belongs to `cart`, belongs to `product`.
- `Order` has many `items`, belongs to `customer`, belongs to `coupon`, has many `paymentTransactions`.
- `OrderItem` belongs to `order`, belongs to `product`.
- `PaymentTransaction` belongs to `order`.

- [ ] **Step 3: Add casts**

Required casts:

- Money fields: `integer`
- Boolean fields: `boolean`
- JSON fields: `array`
- Dates: `datetime`

---

## Task 4: Import WooCommerce Catalog

**Files:**

- Create: `app/Console/Commands/ImportWooCommerceCatalog.php`
- Create: `app/Services/Store/ProductImportService.php`
- Create: `database/seeders/StoreSeeder.php`

- [ ] **Step 1: Add import command**

Command signature:

```php
protected $signature = 'store:import-woocommerce {--database=renova} {--prefix=wpk7_}';
```

Behavior:

- Connect to the local WordPress database.
- Import product categories from `terms`, `term_taxonomy`.
- Import products from `posts`, `postmeta`, `term_relationships`.
- Preserve `wp_product_id`.
- Preserve `wp_term_id`.
- Convert price strings to cents.
- Import Google Merchant postmeta.
- Import image URLs from `_thumbnail_id`.

- [ ] **Step 2: Add category mapping rules**

Rules:

- `depilacao-feminina` becomes top-level category route `/depilacao-feminina`.
- `depilacao-masculina` becomes top-level category route `/depilacao-masculina`.
- Preserve child slugs.
- Set `merchant_is_bundle = true` unless product has category `avulsas` or `sessoes-avulsas`.
- Set merchant gender from root category: feminine -> `female`, masculine -> `male`.

- [ ] **Step 3: Run import**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan store:import-woocommerce
```

Expected:

- `35` products imported.
- `8` product categories imported.
- Product images linked.
- Google Merchant fields stored.

---

## Task 5: Build Public Category Pages

**Files:**

- Create: `app/Http/Controllers/Store/CategoryController.php`
- Create: `resources/js/Pages/Store/Category.vue`
- Create: `resources/js/Components/Store/ProductCard.vue`
- Create: `resources/js/Components/Store/CategoryTabs.vue`
- Modify: `routes/web.php`
- Modify: `resources/js/data/site.js`

- [ ] **Step 1: Add routes**

Add:

```php
Route::get('/depilacao-feminina', [CategoryController::class, 'show'])
    ->defaults('slug', 'depilacao-feminina')
    ->name('store.feminine');

Route::get('/depilacao-masculina', [CategoryController::class, 'show'])
    ->defaults('slug', 'depilacao-masculina')
    ->name('store.masculine');

Route::get('/loja/categoria/{slug}', [CategoryController::class, 'show'])
    ->name('store.category');
```

- [ ] **Step 2: Implement controller**

Controller returns:

- current root category
- child categories
- products grouped or filtered by selected child category
- SEO metadata

- [ ] **Step 3: Build Vue page**

Page sections:

- Existing site header.
- Category hero styled close to current WooCommerce page.
- Category tabs: Combos, Pacotes, Sessões Avulsas.
- Product grid.
- Add-to-cart buttons.
- Existing footer.

- [ ] **Step 4: Test page**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan test --filter=CategoryPageTest
```

Expected: feminine and masculine pages return 200 and include products.

---

## Task 6: Build Product Detail Pages

**Files:**

- Create: `app/Http/Controllers/Store/ProductController.php`
- Create: `resources/js/Pages/Store/Product.vue`
- Create: `resources/js/Components/Store/Price.vue`

- [ ] **Step 1: Add route**

```php
Route::get('/produto/{product:slug}', [ProductController::class, 'show'])
    ->name('store.product');
```

- [ ] **Step 2: Implement product page data**

Return:

- product details
- images
- category breadcrumbs
- regular price
- sale/current price
- merchant metadata for structured data

- [ ] **Step 3: Add JSON-LD product data**

Expose JSON-LD in page head:

- name
- description
- image
- brand
- offers.price
- offers.priceCurrency
- offers.availability
- condition

---

## Task 7: Build Cart

**Files:**

- Create: `app/Services/Store/CartService.php`
- Create: `app/Http/Controllers/Store/CartController.php`
- Create: `app/Http/Requests/Store/AddCartItemRequest.php`
- Create: `app/Http/Requests/Store/UpdateCartItemRequest.php`
- Create: `resources/js/Pages/Store/Cart.vue`
- Create: `resources/js/Components/Store/CartSummary.vue`
- Create: `resources/js/Components/Store/QuantityInput.vue`

- [ ] **Step 1: Cart identity**

Use a signed cookie or session key containing `cart_uuid`.

- [ ] **Step 2: Add item behavior**

Rules:

- Product must be active.
- Quantity minimum is 1.
- Adding same product increments quantity.
- Store unit price snapshot at add time.

- [ ] **Step 3: Update/remove behavior**

Rules:

- Quantity 0 removes the item.
- Cart totals recalculate after every change.
- Coupon recalculates after every change.

- [ ] **Step 4: Cart UI**

Cart page includes:

- product image
- product name
- unit price
- quantity input
- line total
- coupon field
- subtotal/discount/total
- checkout button

---

## Task 8: Checkout and Orders

**Files:**

- Create: `app/Services/Store/CheckoutService.php`
- Create: `app/Http/Controllers/Store/CheckoutController.php`
- Create: `app/Http/Requests/Store/CheckoutRequest.php`
- Create: `resources/js/Pages/Store/Checkout.vue`
- Create: `resources/js/Components/Store/CheckoutForm.vue`

- [ ] **Step 1: Checkout request validation**

Required fields:

- name
- email
- phone
- document
- payment method: `pix`, `credit_card`, or `pagbank_checkout`

- [ ] **Step 2: Create customer**

Find or create customer by email/document.

- [ ] **Step 3: Create order**

Order number format:

```text
RL-YYYYMMDD-000001
```

Rules:

- Snapshot products into `order_items`.
- Clear cart only after PagBank checkout is created successfully.
- Initial order status: `pending`.
- Initial payment status: `pending`.

- [ ] **Step 4: Redirect to payment**

Use `PagBankCheckoutService` to create a hosted checkout and redirect to `pagbank_pay_url`.

---

## Task 9: PagBank Checkout Integration

**Files:**

- Create: `app/Services/Payments/PagBankClient.php`
- Create: `app/Services/Payments/PagBankCheckoutService.php`
- Modify: `config/services.php`
- Modify: `.env.example`

- [ ] **Step 1: Add env keys**

`.env.example`:

```dotenv
PAGBANK_ENV=production
PAGBANK_TOKEN=
PAGBANK_NOTIFICATION_URL="${APP_URL}/webhooks/pagbank"
PAGBANK_REDIRECT_BASE_URL="${APP_URL}"
PAGBANK_PIX_DISCOUNT_PERCENT=5
PAGBANK_MAX_INSTALLMENTS=12
PAGBANK_MIN_INSTALLMENT_CENTS=7800
PAGBANK_SOFT_DESCRIPTOR=RenovaLaserDepil
```

- [ ] **Step 2: Configure service**

`config/services.php`:

```php
'pagbank' => [
    'env' => env('PAGBANK_ENV', 'production'),
    'token' => env('PAGBANK_TOKEN'),
    'notification_url' => env('PAGBANK_NOTIFICATION_URL'),
    'redirect_base_url' => env('PAGBANK_REDIRECT_BASE_URL', env('APP_URL')),
    'pix_discount_percent' => (int) env('PAGBANK_PIX_DISCOUNT_PERCENT', 5),
    'max_installments' => (int) env('PAGBANK_MAX_INSTALLMENTS', 12),
    'min_installment_cents' => (int) env('PAGBANK_MIN_INSTALLMENT_CENTS', 7800),
    'soft_descriptor' => env('PAGBANK_SOFT_DESCRIPTOR', 'RenovaLaserDepil'),
],
```

- [ ] **Step 3: Implement client**

Client responsibilities:

- POST create checkout.
- GET checkout/order status.
- Throw typed exceptions for 4xx/5xx.
- Log request IDs without logging secrets.

- [ ] **Step 4: Implement checkout payload**

Payload includes:

- customer data
- item names, quantities and unit amounts
- accepted payment methods: Pix and credit card
- max installments 12
- redirect URL: `/pedido/{order:number}/retorno`
- notification URL: `/webhooks/pagbank`

---

## Task 10: PagBank Webhooks and Return Page

**Files:**

- Create: `app/Http/Controllers/Webhooks/PagBankWebhookController.php`
- Create: `app/Services/Payments/PagBankWebhookVerifier.php`
- Create: `app/Http/Controllers/Store/PaymentReturnController.php`
- Create: `resources/js/Pages/Store/PaymentReturn.vue`

- [ ] **Step 1: Webhook endpoint**

Behavior:

- Receive payload.
- Verify provider reference.
- Find order by PagBank checkout/order ID.
- Store raw payload in `payment_transactions`.
- Update order payment status.
- Set `paid_at` when approved/paid.

- [ ] **Step 2: Return page**

Return page states:

- pending
- paid
- cancelled
- failed

Include CTA to WhatsApp support and customer account.

---

## Task 11: Google Merchant Feed

**Files:**

- Create: `app/Services/Merchant/GoogleMerchantFeedService.php`
- Create: `app/Http/Controllers/Merchant/GoogleMerchantFeedController.php`
- Create: `tests/Feature/Merchant/GoogleMerchantFeedTest.php`

- [ ] **Step 1: Add feed route**

```php
Route::get('/merchant/google.xml', [GoogleMerchantFeedController::class, 'show'])
    ->name('merchant.google');
```

- [ ] **Step 2: Feed fields**

Each active merchant-visible product must include:

- `g:id`
- `g:title`
- `g:description`
- `g:link`
- `g:image_link`
- `g:availability`
- `g:price`
- `g:sale_price` when present
- `g:brand`
- `g:condition`
- `g:age_group`
- `g:gender`
- `g:color`
- `g:size`
- `g:is_bundle`
- `g:adult`

- [ ] **Step 3: Test XML**

Expected:

- HTTP 200.
- Content type XML.
- Contains 34 synced products if custom quote product remains `dont-sync-and-show`.

---

## Task 12: Admin Authentication

**Files:**

- Modify: existing auth setup or install Laravel Breeze-style equivalents manually.
- Create: `resources/js/Pages/Auth/Login.vue`
- Create: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

- [ ] **Step 1: Add login routes**

Routes:

- `GET /login`
- `POST /login`
- `POST /logout`

- [ ] **Step 2: Seed admin user**

Use `.env`:

```dotenv
ADMIN_EMAIL=admin@renovalaser.local
ADMIN_PASSWORD=change-me
```

- [ ] **Step 3: Protect admin routes**

All `/admin` routes must require `auth`.

---

## Task 13: Admin Product and Category CRUD

**Files:**

- Create admin controllers and Vue pages listed above.

- [ ] **Step 1: Product index**

Columns:

- image
- name
- categories
- price
- sale price
- status
- merchant visibility
- actions

- [ ] **Step 2: Product form**

Fields:

- name
- slug
- categories
- primary category
- short description
- description
- regular price
- sale price
- active
- stock status
- merchant visibility
- merchant brand
- merchant condition
- merchant gender
- merchant age group
- image URL/upload

- [ ] **Step 3: Category CRUD**

Fields:

- parent
- name
- slug
- description
- position
- merchant gender
- merchant visible

---

## Task 14: Admin Orders and Coupons

**Files:**

- Create admin order and coupon files listed above.

- [ ] **Step 1: Orders index**

Columns:

- number
- customer
- total
- order status
- payment status
- payment method
- created at

- [ ] **Step 2: Order detail**

Include:

- customer data
- items
- totals
- PagBank identifiers
- payment timeline
- raw transaction payload collapsed
- status update control

- [ ] **Step 3: Coupon CRUD**

Fields:

- code
- type
- amount/percent
- dates
- usage limit
- active

---

## Task 15: Visual QA Against WordPress

**Files:**

- No permanent files required.

- [ ] **Step 1: Capture reference pages**

Compare:

- WordPress `/depilacao-feminina/`
- Laravel `/depilacao-feminina`
- WordPress `/carrinho/`
- Laravel `/carrinho`
- WordPress checkout
- Laravel `/checkout`

- [ ] **Step 2: Verify responsive views**

Viewports:

- desktop: `1366x768`
- mobile: `390x844`

Check:

- header behavior
- category filters
- product card spacing
- price typography
- cart totals
- checkout form spacing
- footer consistency

---

## Task 16: End-to-End Test Checklist

- [ ] Import WooCommerce catalog.
- [ ] Confirm 35 products.
- [ ] Confirm 24 feminine products.
- [ ] Confirm 11 masculine products.
- [ ] Confirm Google Merchant metadata on imported products.
- [ ] Open `/depilacao-feminina`.
- [ ] Add a product to cart.
- [ ] Update quantity.
- [ ] Remove item.
- [ ] Apply valid coupon.
- [ ] Create checkout.
- [ ] Confirm order persisted before redirect.
- [ ] Confirm PagBank checkout URL generated.
- [ ] Simulate webhook paid event.
- [ ] Confirm order becomes paid.
- [ ] Confirm Google Merchant feed renders.
- [ ] Create product in admin.
- [ ] Edit product in admin.
- [ ] Disable product and confirm it disappears from public category.

---

## Implementation Order

1. Routes and database.
2. Models and import command.
3. Product/category seeds from WordPress.
4. Public category and product pages.
5. Cart.
6. Checkout order creation.
7. PagBank redirected checkout.
8. Webhook and return page.
9. Admin authentication.
10. Admin product/category CRUD.
11. Admin order/coupon management.
12. Google Merchant feed.
13. Full visual QA and payment QA.

---

## Risks and Decisions

### Payment PCI Risk

Do not build raw credit-card fields inside the Laravel checkout in the first release. Use PagBank hosted checkout. Direct card capture can be a later phase after confirming PCI and 3DS requirements.

### Service Products

These products are depilation services, not shippable physical items. Keep shipping amount at zero and avoid shipping forms unless PagBank Checkout requires customer address.

### Encoding

The WordPress DB output in terminal shows mojibake for Portuguese text. The importer must read with the correct connection charset and should normalize names like `Sessões`, `Buço`, `Glúteos`, `Região`.

### Product Images

First release can keep original image URLs. Production release should copy images to Laravel storage/public assets to remove dependency on WordPress.

### Google Merchant

Keep WooCommerce `gla_*` values during import. The new feed must preserve approved product IDs where practical, but Google may reprocess changed links after migration.

### Existing Site Pages

Do not break current home, `/quem-somos`, or `/nossa-tecnologia`. New store routes should compose with the existing `SiteLayout`.

---

## Self-Review

- Spec coverage: catalog, categories, products, cart, checkout, PagBank, dashboard, seeds/import, Google Merchant and QA are covered.
- Placeholder scan: no implementation step relies on an unnamed future decision. The only strategic choice is explicitly decided as PagBank hosted checkout for first release.
- Type consistency: money uses integer cents across cart, order, products and coupons; payment state uses enum/string consistently; product/category slugs drive routes.

