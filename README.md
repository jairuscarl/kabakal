# Kabakal Fitness Equipment – Shop Management & Online Shopping System

PHP (PDO) + MySQL + HTML/CSS/JS. No frameworks.

## A. Run it locally (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\kabakal`  (the folder name must match `BASE_URL` in `config.php`).
2. Open XAMPP Control Panel and start **Apache** and **MySQL**.
3. Open http://localhost/phpmyadmin, click **Import**, choose `database.sql`, click **Go**.
   (It creates the `kabakal_fitness` database, all tables and starter data.)
4. Open http://localhost/kabakal  (shop)  and  http://localhost/kabakal/admin/login.php  (admin).
5. Default admin login: **admin / password**  ->  change it right away in Admin > My Password.
6. All products start with **0 stock** (as in your SQL file). Set stock in Admin > Products (edit) or Admin > Inventory > Restock, otherwise customers cannot order.
7. Admin > Settings: enter your real GCash name, number and QR code image.

### Allow bigger video uploads (XAMPP default is only 2 MB)
Open `C:\xampp\php\php.ini`, set:
```
upload_max_filesize = 64M
post_max_size = 64M
```
then restart Apache. (Videos are limited to 50 MB in `config.php`.)

## B. Deploy to web hosting

1. **Database:** in your hosting panel (cPanel > MySQL Databases) create a database + user and give the user all privileges.
2. **Import:** in phpMyAdmin on the host, select that database and import `database.sql`.
   Before importing, delete the first 4 lines of the file (`CREATE DATABASE ...` and `USE kabakal_fitness;`) because shared hosts do not let you create or pick the database name yourself.
3. **Edit `config.php`** (the only file you change):
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` = the values from your hosting panel
   - `BASE_URL` = `''` if the site is at the domain root (e.g. https://yourshop.com), or `'/foldername'` if it is in a sub-folder
   - `DEBUG` = `false`
4. **Upload** all files (File Manager or FTP) into `public_html` (or the sub-folder).
5. **Permissions:** make sure `uploads/` (and its sub-folders) and `assets/videos/` are writable (usually 755; use 775 if uploads fail).
6. **Check PHP:** PHP 8.0+ with the `pdo_mysql` and `fileinfo` extensions (on by default on most hosts). Raise `upload_max_filesize` / `post_max_size` in the hosting panel's PHP settings if you upload videos.
7. **Turn on HTTPS** (free SSL in most panels).
8. Log in to `/admin/login.php`, **change the default password**, then set GCash details and add products, photos and videos.

## Folder guide
```
config.php            all settings (DB, BASE_URL)
database.sql          tables + starter data
includes/             init.php (helpers), header/footer
assets/css, js        style.css, main.js;  assets/videos = promo MP4 files
uploads/              product photos, GCash proofs, posters, QR code
admin/                admin pages
index.php, products.php, product.php, reviews.php, cart.php, checkout.php,
order_confirm.php, my_account.php, login.php, register.php, logout.php
```

## How stock and payments work
- Admin > Inventory shows the **Date stock added** (last restock date) for every product; the date is stamped whenever you restock or raise a product's stock.
- The Admin Dashboard groups items as **High / Moderate / Low Performing** based on units sold (cancelled orders are not counted; products with no sales are Low Performing). Each group lists all of its items inside a fixed-height scroll box.
- Order placed: stock goes **Available -> Reserved**.
- Order becomes Paid / Shipped / Delivered: **Reserved -> Sold**.
- Order cancelled: items return to **Available**.
- Customer uploads GCash proof -> payment is "Pending Verification" -> admin **Verifies** or **Rejects**.
- When verified payments add up to the order total, the order becomes **Paid** automatically.
  Partial payments keep the order "Partially Paid" and show the remaining balance.
