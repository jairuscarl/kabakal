CREATE DATABASE IF NOT EXISTS kabakal_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE kabakal_db;

CREATE TABLE category (
  category_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  description   TEXT NULL,
  PRIMARY KEY (category_id),
  UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB;

CREATE TABLE supplier (
  supplier_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(150) NOT NULL,
  contact_person  VARCHAR(100) NULL,
  email           VARCHAR(150) NULL,
  phone           VARCHAR(30)  NULL,
  address         VARCHAR(255) NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (supplier_id),
  UNIQUE KEY uq_supplier_email (email)
) ENGINE=InnoDB;

CREATE TABLE admin (
  admin_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username    VARCHAR(50)  NOT NULL,
  password    VARCHAR(255) NOT NULL,  -- store a hash (e.g. bcrypt), never plain text
  full_name   VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  role        VARCHAR(30)  NOT NULL DEFAULT 'staff',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id),
  UNIQUE KEY uq_admin_username (username),
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB;

CREATE TABLE customer (
  customer_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name   VARCHAR(50)  NOT NULL,
  last_name    VARCHAR(50)  NOT NULL,
  full_name    VARCHAR(100) NOT NULL,  -- convenience copy "first last", always kept in sync by the app
  email        VARCHAR(150) NOT NULL,
  phone        VARCHAR(30)  NULL,
  password     VARCHAR(255) NOT NULL,  -- store a hash
  address      VARCHAR(255) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id),
  UNIQUE KEY uq_customer_email (email)
) ENGINE=InnoDB;

CREATE TABLE equipment (
  equipment_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id     INT UNSIGNED NOT NULL,
  supplier_id     INT UNSIGNED NULL,
  name            VARCHAR(150) NOT NULL,
  description     TEXT NULL,
  price           DECIMAL(10,2) NOT NULL CHECK (price >= 0),
  stock_quantity  INT UNSIGNED NOT NULL DEFAULT 0,
  image_url       VARCHAR(255) NULL,
  is_featured     TINYINT(1) NOT NULL DEFAULT 0,   -- ADDED: show on Home page
  status          ENUM('active','inactive','discontinued') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_restocked_at DATETIME NULL,               -- ADDED: date stock was last added (restock)
  PRIMARY KEY (equipment_id),
  KEY idx_equipment_category (category_id),
  KEY idx_equipment_supplier (supplier_id),
  CONSTRAINT fk_equipment_category FOREIGN KEY (category_id)
    REFERENCES category (category_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_equipment_supplier FOREIGN KEY (supplier_id)
    REFERENCES supplier (supplier_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE inventory (
  inventory_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  equipment_id  INT UNSIGNED NOT NULL,
  admin_id      INT UNSIGNED NULL,
  quantity      INT UNSIGNED NOT NULL DEFAULT 0,
  status        ENUM('available','reserved','sold','damaged','out_of_stock')
                NOT NULL DEFAULT 'available',
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (inventory_id),
  UNIQUE KEY uq_inventory_equipment_status (equipment_id, status),  -- ADDED: one row per status
  KEY idx_inventory_admin (admin_id),
  CONSTRAINT fk_inventory_equipment FOREIGN KEY (equipment_id)
    REFERENCES equipment (equipment_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_inventory_admin FOREIGN KEY (admin_id)
    REFERENCES admin (admin_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE cart (
  cart_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id  INT UNSIGNED NOT NULL,
  status       ENUM('active','checked_out','abandoned') NOT NULL DEFAULT 'active',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_id),
  KEY idx_cart_customer (customer_id),
  CONSTRAINT fk_cart_customer FOREIGN KEY (customer_id)
    REFERENCES customer (customer_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_item (
  cart_item_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id       INT UNSIGNED NOT NULL,
  equipment_id  INT UNSIGNED NOT NULL,
  quantity      INT UNSIGNED NOT NULL DEFAULT 1 CHECK (quantity > 0),
  added_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_item_id),
  UNIQUE KEY uq_cart_equipment (cart_id, equipment_id),
  KEY idx_cart_item_equipment (equipment_id),
  CONSTRAINT fk_cart_item_cart FOREIGN KEY (cart_id)
    REFERENCES cart (cart_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cart_item_equipment FOREIGN KEY (equipment_id)
    REFERENCES equipment (equipment_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
  order_id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id       INT UNSIGNED NOT NULL,
  order_date        DATE NOT NULL DEFAULT (CURRENT_DATE),
  total_amount      DECIMAL(12,2) NOT NULL DEFAULT 0.00 CHECK (total_amount >= 0),
  status            ENUM('pending','paid','shipped','delivered','cancelled')
                    NOT NULL DEFAULT 'pending',
  shipping_date     DATE NULL,
  shipping_address  VARCHAR(255) NOT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (order_id),
  KEY idx_orders_customer (customer_id),
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id)
    REFERENCES customer (customer_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_item (
  order_item_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id       INT UNSIGNED NOT NULL,
  equipment_id   INT UNSIGNED NOT NULL,
  quantity       INT UNSIGNED NOT NULL CHECK (quantity > 0),
  unit_price     DECIMAL(10,2) NOT NULL CHECK (unit_price >= 0),
  subtotal       DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  PRIMARY KEY (order_item_id),
  KEY idx_order_item_order (order_id),
  KEY idx_order_item_equipment (equipment_id),
  CONSTRAINT fk_order_item_order FOREIGN KEY (order_id)
    REFERENCES orders (order_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_order_item_equipment FOREIGN KEY (equipment_id)
    REFERENCES equipment (equipment_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE payment (
  payment_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id          INT UNSIGNED NOT NULL,
  verified_by       INT UNSIGNED NULL,               -- ADDED: admin who verified/rejected
  payment_method    VARCHAR(50) NOT NULL DEFAULT 'gcash',  -- e.g. gcash, cash
  reference_number  VARCHAR(50) NULL,                -- ADDED: GCash reference no.
  proof_image       VARCHAR(255) NULL,               -- ADDED: receipt screenshot path
  amount            DECIMAL(12,2) NOT NULL CHECK (amount >= 0),
  -- pending = waiting for admin, paid = verified, failed = rejected
  payment_status    ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  remarks           VARCHAR(255) NULL,               -- ADDED: admin note (e.g. reject reason)
  payment_date      DATE NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (payment_id),
  KEY idx_payment_order (order_id),                  -- CHANGED: was UNIQUE, now many per order
  KEY idx_payment_admin (verified_by),
  CONSTRAINT fk_payment_order FOREIGN KEY (order_id)
    REFERENCES orders (order_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_payment_admin FOREIGN KEY (verified_by)
    REFERENCES admin (admin_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Categories (the 3 shown to customers)
INSERT INTO category (name) VALUES ('Boxing Equipment'), ('Gym Equipment'), ('Gym Machines');

-- Products imported from the stock control sheet (inventory-sept-15-2026.pdf)
-- stock_quantity = 'AVAILABLE STOCK' column. Products with a placeholder price (100) are 'inactive' (hidden) until priced.
INSERT INTO equipment (category_id, name, description, price, stock_quantity, status) VALUES
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Smith Machine Red', NULL, 30000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Monster Smith Machine w/ Pec Deck (New)', NULL, 60000.00, 9, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Monster Smith Machine w/ Leg Press & Row', NULL, 50000.00, 5, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Power Cage With Pulley', NULL, 16500.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), '7-in-1 Bench (Fabricated)', NULL, 8000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), '3-in-1 Bench (Fabricated)', NULL, 6500.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Plate Tree (Standard)', NULL, 2799.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Plate Tree (Olympic)', NULL, 2499.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), '3-Layer Dumbbell Rack', NULL, 5000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Pyramid Rack', NULL, 2500.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), '7-in-1 Bench with 80 lbs Weight', NULL, 8500.00, 9, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), '6-in-1 Bench with 80 lbs Weight', NULL, 9000.00, 2, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), '5-in-1 Bench', NULL, 5500.00, 1, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Cable Crossover Machine 150lbs/150lbs', NULL, 25000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Spin Bike White', NULL, 9000.00, 13, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Spin Bike Yellow/Black', NULL, 13000.00, 15, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Elliptical Bike w/ Twister', NULL, 10000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Premium Elliptical Bike', NULL, 7300.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Boxing Equipment'), 'Heavy Duty Punching Bag Set', NULL, 5500.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Boxing Equipment'), 'Premium Punching Bag Set', NULL, 3000.00, 20, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Resistance Band', NULL, 700.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Push Up Board', NULL, 500.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Ab Wheel', NULL, 300.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Boxing Equipment'), 'Boxing Gloves', NULL, 750.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hand Gloves', NULL, 100.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Rubber Belt', NULL, 200.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Dumbbell/Barbell Set 40 kg', NULL, 2000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Dumbbell/Barbell Set 30 kg', NULL, 2500.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Dumbbell/Barbell Set 20 kg', NULL, 3000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), '3 Station Home Gym', NULL, 33000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), '4 Station Home Gym', NULL, 34000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), '1 Station Home Gym (Live Up)', NULL, 12500.00, 5, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), '1 Station Home Gym w/ Leg Press', NULL, 21000.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Bar 7ft', NULL, 1500.00, 9, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Bar 6ft', NULL, 1200.00, 16, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Bar 5ft', NULL, 950.00, 17, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Curl Bar (Standard)', NULL, 950.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Plate 2.5 lbs', NULL, 112.50, 22, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Plate 5 lbs', NULL, 225.00, 64, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Plate 10 lbs', NULL, 450.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Plate 15 lbs', NULL, 675.00, 13, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Plate 20 lbs', NULL, 900.00, 16, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Plate 30 lbs', NULL, 1350.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Plate 40 lbs', NULL, 1800.00, 2, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Standard Plate 50 lbs', NULL, 2250.00, 8, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Olympic Bar 7ft', NULL, 3500.00, 7, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Olympic Curl Bar', NULL, 2000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Olympic Plate 2.5 kg', NULL, 247.50, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Olympic Plate 5 kg', NULL, 495.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Olympic Plate 10 kg', NULL, 990.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Olympic Plate 15 kg', NULL, 1485.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Olympic Plate 20 kg', NULL, 1980.00, 12, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Olympic Plate 25 kg', NULL, 2475.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 5 lbs', NULL, 250.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 10 lbs', NULL, 500.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 15 lbs', NULL, 750.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 20 lbs', NULL, 1000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 25 lbs', NULL, 1250.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 30 lbs', NULL, 1500.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 35 lbs', NULL, 1750.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 40 lbs', NULL, 2000.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 45 lbs', NULL, 2250.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 50 lbs', NULL, 2500.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Mat 4ft x 8ft (1 inch)', NULL, 2000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Mat 4ft x 8ft (1/2 inch)', NULL, 1200.00, 14, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Short Bar (Standard)', NULL, 400.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Treadmill KML', NULL, 12000.00, 1, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 5 lbs', NULL, 300.00, 2, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 10 lbs', NULL, 600.00, 7, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 15 lbs', NULL, 900.00, 2, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 20 lbs', NULL, 1200.00, 5, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 25 lbs', NULL, 1500.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 30 lbs', NULL, 1800.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 35 lbs', NULL, 2100.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 40 lbs', NULL, 2400.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 1 lbs', NULL, 60.00, 2, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 2 lbs', NULL, 120.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 3 lbs', NULL, 180.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 4 lbs', NULL, 240.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 5 lbs', NULL, 300.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 6 lbs', NULL, 360.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 7 lbs', NULL, 420.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 8 lbs', NULL, 480.00, 6, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 9 lbs', NULL, 540.00, 8, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Vinyl Dumbbell 10 lbs', NULL, 600.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Boxing Equipment'), 'Speed Ball', NULL, 420.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Multi Ab Tower Bench with Lat', NULL, 6000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 60 lbs', NULL, 6000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 70 lbs', NULL, 6000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 80 lbs', NULL, 6000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 90 lbs', NULL, 6000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Hex Dumbbell 100 lbs', NULL, 6000.00, 0, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'High Density Mat', NULL, 1250.00, 29, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), '3-in-1 Bench (Live Up)', NULL, 3000.00, 4, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Manual Treadmill Commercial', NULL, 55000.00, 5, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Transformer Bike', NULL, 15000.00, 8, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 3 lbs', NULL, 180.00, 3, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Kettlebell 7 lbs', NULL, 420.00, 1, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Tricep Bar', NULL, 1500.00, 5, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Stair Machine', NULL, 70000.00, 2, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Hip Thrust Machine', NULL, 21000.00, 5, 'active'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Lat Pull Down Machine', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Incline/Flat Bench with Rack', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Decline Bench With Rack', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Flat Bench With Rack', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Dip / Leg Raise', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Pec Deck Machine', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Leg Press Hack Squat', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Leg Press Machine', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Bicep Preacher Curl with Rack', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Utility Stool', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Utility Bench Adjustable', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Multi Bench 7-in-1 with Bicep Preacher', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Leg Extension & Hamstring Curl', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Shoulder Press Machine', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Calf Raise', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Rowing Machine', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Machines'), 'Roman Chair', NULL, 100.00, 0, 'inactive'),
  ((SELECT category_id FROM category WHERE name = 'Gym Equipment'), 'Roller', NULL, 100.00, 0, 'inactive');

-- CHANGED: 'available' row per product. Out of Stock is shown whenever available = 0.
INSERT INTO inventory (equipment_id, quantity, status)
SELECT equipment_id, stock_quantity, 'available' FROM equipment;

-- Date each starter product's stock was added (the app keeps this updated on every restock).
UPDATE equipment SET last_restocked_at = created_at WHERE stock_quantity > 0;

-- ===================== ADDED TABLES =====================

-- Shop settings (GCash details, shop info) editable by admin
CREATE TABLE settings (
  setting_key    VARCHAR(50) NOT NULL,
  setting_value  TEXT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB;

-- Promo videos: uploaded MP4 file OR a Facebook video link
CREATE TABLE promo_video (
  video_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title        VARCHAR(150) NOT NULL,
  source_type  ENUM('file','facebook') NOT NULL DEFAULT 'file',
  file_path    VARCHAR(255) NULL,   -- e.g. assets/videos/abc.mp4
  embed_url    VARCHAR(500) NULL,   -- Facebook video link
  poster       VARCHAR(255) NULL,   -- optional poster image
  is_visible   TINYINT(1) NOT NULL DEFAULT 1,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (video_id)
) ENGINE=InnoDB;

-- ===================== ADDED SEED DATA =====================

-- Default admin account.  Username: admin   Password: password   (CHANGE IT after first login!)
INSERT INTO admin (username, password, full_name, email, role) VALUES
  ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Shop Admin', 'admin@kabakal.local', 'admin');

INSERT INTO settings (setting_key, setting_value) VALUES
  ('shop_name',    'Kabakal Fitness Equipment'),
  ('shop_phone',   '(02) 7882-2187'),
  ('shop_mobile',  '+63 930 018 5018'),
  ('shop_email',   'kabakalequipmenthub@gmail.com'),
  ('shop_facebook','https://www.facebook.com/rogandomarkhel'),
  ('shop_instagram','https://www.instagram.com/rogandomarkhel'),
  ('shop_map',     'https://www.google.com/maps/search/?api=1&query=Kabakal%20Fitness%20Equipment%2C%20258%20Tandang%20Sora%20Avenue%20Extension%20Caloocan%20Metro%20Manila'),
  ('shop_address', 'Enter shop address in Admin > Settings'),
  ('shop_about',   'Kabakal Fitness Equipment sells gym and boxing equipment for gym owners, fitness enthusiasts and anyone building a home gym.'),
  ('gcash_name',   'KABAKAL FITNESS'),
  ('gcash_number', '09XX XXX XXXX'),
  ('gcash_qr',     '');

-- Featured products on the Home page (admin can change in Products)
UPDATE equipment SET is_featured = 1
WHERE name IN ('Monster Smith Machine w/ Pec Deck (New)', 'Spin Bike Yellow/Black', '1 Station Home Gym w/ Leg Press', 'Premium Punching Bag Set', 'Olympic Bar 7ft', 'Hex Dumbbell 50 lbs');

-- ===================== ADDED: CUSTOMER REVIEWS / FEEDBACK =====================

-- Public Reviews page: logged-in customers rate 1-5 stars, write text, attach a photo.
CREATE TABLE review (
  review_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id INT UNSIGNED NOT NULL,
  rating      TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment     TEXT NOT NULL,
  image_url   VARCHAR(255) NULL,   -- e.g. uploads/reviews/ab12....jpg
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  KEY idx_review_customer (customer_id),
  CONSTRAINT fk_review_customer FOREIGN KEY (customer_id)
    REFERENCES customer (customer_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Heart button on each review: one like per customer per review, counted for display.
CREATE TABLE review_like (
  review_id   INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id, customer_id),
  KEY idx_review_like_customer (customer_id),
  CONSTRAINT fk_review_like_review FOREIGN KEY (review_id)
    REFERENCES review (review_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_review_like_customer FOREIGN KEY (customer_id)
    REFERENCES customer (customer_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Replies to a review. ONLY admin/staff accounts may post these (enforced in reviews.php).
CREATE TABLE review_reply (
  reply_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  review_id  INT UNSIGNED NOT NULL,
  admin_id   INT UNSIGNED NOT NULL,
  comment    TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (reply_id),
  KEY idx_review_reply_review (review_id),
  CONSTRAINT fk_review_reply_review FOREIGN KEY (review_id)
    REFERENCES review (review_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_review_reply_admin FOREIGN KEY (admin_id)
    REFERENCES admin (admin_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
