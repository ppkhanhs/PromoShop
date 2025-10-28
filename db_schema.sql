CREATE KEYSPACE IF NOT EXISTS promo_shop
  WITH replication = {
    'class': 'SimpleStrategy',
    'replication_factor': 1
  };

USE promo_shop;

-- User-Defined Type cho các sản phẩm trong đơn hàng
CREATE TYPE IF NOT EXISTS order_item (
  product_id text,
  name text,
  price decimal,
  quantity int
);

-- ===============================
--  Bảng người dùng
-- ===============================
CREATE TABLE IF NOT EXISTS users (
  user_id text PRIMARY KEY,
  name text,
  email text,
  password text,
  role text,
  created_at timestamp
);

CREATE TABLE IF NOT EXISTS users_by_email (
  email text PRIMARY KEY,
  user_id text,
  name text,
  password text,
  role text,
  created_at timestamp
);

-- ===============================
--  Bảng sản phẩm
-- ===============================
CREATE TABLE IF NOT EXISTS products (
  category text,
  product_id text,
  name text,
  price decimal,
  stock int,
  image_url text,
  status text,
  updated_at timestamp,
  PRIMARY KEY (category, product_id)
) WITH CLUSTERING ORDER BY (product_id ASC);

CREATE TABLE IF NOT EXISTS products_by_id (
  product_id text PRIMARY KEY,
  category text,
  name text,
  price decimal,
  stock int,
  image_url text,
  status text,
  updated_at timestamp
);

-- ===============================
--  Bảng khuyến mãi
-- ===============================
CREATE TABLE IF NOT EXISTS promotions (
  promo_id text PRIMARY KEY,
  title text,
  type text,
  min_order decimal,
  discount_percent int,
  reward_type text,
  max_discount_amount decimal,
  start_date date,
  end_date date,
  status text,
  auto_apply boolean,
  stackable boolean,
  created_at timestamp,
  updated_at timestamp
);

CREATE TABLE IF NOT EXISTS promotions_by_status (
  status text,
  start_date date,
  promo_id text,
  title text,
  type text,
  reward_type text,
  PRIMARY KEY ((status), start_date, promo_id)
) WITH CLUSTERING ORDER BY (start_date DESC, promo_id ASC);

-- >> BẢNG MỚI <<
-- Dùng để tra cứu ngược: tìm khuyến mãi từ sản phẩm
CREATE TABLE IF NOT EXISTS promotions_by_product (
  product_id text,
  promo_id text,
  title text,
  end_date date,
  PRIMARY KEY (product_id, promo_id)
);


-- ===============================
--  Bảng tầng khuyến mãi
-- ===============================
CREATE TABLE IF NOT EXISTS promotion_tiers (
  promo_id text,
  tier_level int,
  label text,
  min_value decimal,
  discount_percent int,
  discount_amount decimal,
  freeship boolean,
  gift_product_id text,
  gift_quantity int,
  combo_description text,
  metadata text,
  PRIMARY KEY ((promo_id), tier_level)
) WITH CLUSTERING ORDER BY (tier_level ASC);

-- ===============================
--  Bảng đơn hàng
-- ===============================
CREATE TABLE IF NOT EXISTS orders (
  user_id text,
  created_at timestamp,
  order_id text,
  items list<frozen<order_item>>, -- Sử dụng UDT
  total decimal,
  discount decimal,
  final_amount decimal,
  shipping_fee decimal,
  status text,
  promo_id text,
  applied_tier int,
  note text,
  customer_name text,
  customer_phone text,
  shipping_address text,
  PRIMARY KEY ((user_id), created_at, order_id)
) WITH CLUSTERING ORDER BY (created_at DESC, order_id ASC);

CREATE TABLE IF NOT EXISTS orders_by_id (
  order_id text PRIMARY KEY,
  user_id text,
  created_at timestamp,
  items list<frozen<order_item>>, -- Sử dụng UDT
  total decimal,
  discount decimal,
  final_amount decimal,
  shipping_fee decimal,
  status text,
  promo_id text,
  applied_tier int,
  note text,
  customer_name text,
  customer_phone text,
  shipping_address text
);

-- ===============================
--  Bảng nhật ký áp dụng khuyến mãi
-- ===============================
CREATE TABLE IF NOT EXISTS promotion_logs (
  promo_id text,
  applied_at timestamp,
  log_id uuid,
  order_id text,
  user_id text,
  tier_level int,
  discount_amount decimal,
  freeship boolean,
  reward text,
  PRIMARY KEY ((promo_id), applied_at, log_id)
) WITH CLUSTERING ORDER BY (applied_at DESC, log_id ASC);

CREATE TABLE IF NOT EXISTS promotion_logs_by_order (
  order_id text,
  applied_at timestamp,
  log_id uuid,
  promo_id text,
  tier_level int,
  discount_amount decimal,
  PRIMARY KEY ((order_id), applied_at, log_id)
) WITH CLUSTERING ORDER BY (applied_at DESC, log_id ASC);

-- ===============================
--  Bảng giỏ hàng người dùng 
-- ===============================
CREATE TABLE IF NOT EXISTS user_carts (
  user_id text PRIMARY KEY,
  items map<text, int>, -- Key: product_id, Value: quantity
  last_updated timestamp
);
-- ===============================
--  Dữ liệu mẫu
-- ===============================

-- Người dùng
INSERT INTO users (user_id, name, email, password, role, created_at) VALUES ('USR-ALICE', 'Nguyễn Minh Ánh', 'alice@example.com', 'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f', 'customer', toTimestamp(now()));
INSERT INTO users (user_id, name, email, password, role, created_at) VALUES ('USR-BOB', 'Trần Quốc Bảo', 'bao@example.com', 'fc8d5c17ee6bd893ac3d47583df509da68ada40070b9c9e1890cae52bc62de28', 'customer', toTimestamp(now()));
INSERT INTO users (user_id, name, email, password, role, created_at) VALUES ('USR-ADMIN', 'Quản trị viên', 'admin@promoshop.vn', '7676aaafb027c825bd9abab78b234070e702752f625b752e55e55b48e607e358', 'admin', toTimestamp(now()));
INSERT INTO users_by_email (email, user_id, name, password, role, created_at) VALUES ('alice@example.com', 'USR-ALICE', 'Nguyễn Minh Ánh', 'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f', 'customer', toTimestamp(now()));
INSERT INTO users_by_email (email, user_id, name, password, role, created_at) VALUES ('bao@example.com', 'USR-BOB', 'Trần Quốc Bảo', 'fc8d5c17ee6bd893ac3d47583df509da68ada40070b9c9e1890cae52bc62de28', 'customer', toTimestamp(now()));
INSERT INTO users_by_email (email, user_id, name, password, role, created_at) VALUES ('admin@promoshop.vn', 'USR-ADMIN', 'Quản trị viên', '7676aaafb027c825bd9abab78b234070e702752f625b752e55e55b48e607e358', 'admin', toTimestamp(now()));

-- Sản phẩm
INSERT INTO products (category, product_id, name, price, stock, image_url, status, updated_at) VALUES ('Milk Tea', 'MT001', 'Trà sữa trân châu đường đen', 45000, 120, 'https://th.bing.com/th/id/OIP.qbDZXn8la8wCHMRvEpGw8QHaET?w=235&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', 'active', toTimestamp(now()));
INSERT INTO products (category, product_id, name, price, stock, image_url, status, updated_at) VALUES ('Milk Tea', 'MT002', 'Trà sữa matcha kem cheese', 52000, 85, 'https://th.bing.com/th/id/OIP.SPG75TMbuz8SF5R5riTWowHaFP?w=272&h=193&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', 'active', toTimestamp(now()));
INSERT INTO products (category, product_id, name, price, stock, image_url, status, updated_at) VALUES ('Coffee', 'CF001', 'Cà phê sữa đá Sài Gòn', 39000, 150, 'https://th.bing.com/th/id/OIP.-kwobbU7-_CkAir5ejO4vgHaFy?w=218&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', 'active', toTimestamp(now()));
INSERT INTO products (category, product_id, name, price, stock, image_url, status, updated_at) VALUES ('Coffee', 'CF002', 'Latte caramel nóng', 59000, 60, 'https://parisdeli.vn/wp-content/uploads/2024/03/19-scaled.jpg', 'active', toTimestamp(now()));
INSERT INTO products (category, product_id, name, price, stock, image_url, status, updated_at) VALUES ('Bakery', 'BK001', 'Bánh croissant bơ Pháp', 32000, 45, 'https://tse4.mm.bing.net/th/id/OIP.0WYZmJ06e8mTF7jbVj27RgHaEK?rs=1&pid=ImgDetMain&o=7&rm=3', 'active', toTimestamp(now()));
INSERT INTO products_by_id (product_id, category, name, price, stock, image_url, status, updated_at) VALUES ('MT001', 'Milk Tea', 'Trà sữa trân châu đường đen', 45000, 120, 'https://th.bing.com/th/id/OIP.qbDZXn8la8wCHMRvEpGw8QHaET?w=235&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', 'active', toTimestamp(now()));
INSERT INTO products_by_id (product_id, category, name, price, stock, image_url, status, updated_at) VALUES ('MT002', 'Milk Tea', 'Trà sữa matcha kem cheese', 52000, 85, 'https://th.bing.com/th/id/OIP.SPG75TMbuz8SF5R5riTWowHaFP?w=272&h=193&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', 'active', toTimestamp(now()));
INSERT INTO products_by_id (product_id, category, name, price, stock, image_url, status, updated_at) VALUES ('CF001', 'Coffee', 'Cà phê sữa đá Sài Gòn', 39000, 150, 'https://th.bing.com/th/id/OIP.-kwobbU7-_CkAir5ejO4vgHaFy?w=218&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', 'active', toTimestamp(now()));
INSERT INTO products_by_id (product_id, category, name, price, stock, image_url, status, updated_at) VALUES ('CF002', 'Coffee', 'Latte caramel nóng', 59000, 60, 'https://parisdeli.vn/wp-content/uploads/2024/03/19-scaled.jpg', 'active', toTimestamp(now()));
INSERT INTO products_by_id (product_id, category, name, price, stock, image_url, status, updated_at) VALUES ('BK001', 'Bakery', 'Bánh croissant bơ Pháp', 32000, 45, 'https://tse4.mm.bing.net/th/id/OIP.0WYZmJ06e8mTF7jbVj27RgHaEK?rs=1&pid=ImgDetMain&o=7&rm=3', 'active', toTimestamp(now()));

-- Khuyến mãi
INSERT INTO promotions (promo_id, title, type, min_order, discount_percent, reward_type, max_discount_amount, start_date, end_date, status, auto_apply, stackable, created_at, updated_at) VALUES ('SPRING2025', 'Spring Treats - Giảm giá thức uống', 'tiered', 0, null, 'discount', 70000, '2025-03-01', '2025-06-30', 'active', true, false, toTimestamp(now()), toTimestamp(now()));
INSERT INTO promotions (promo_id, title, type, min_order, discount_percent, reward_type, max_discount_amount, start_date, end_date, status, auto_apply, stackable, created_at, updated_at) VALUES ('FREESHIP200', 'Freeship đơn từ 200K', 'tiered', 0, null, 'shipping', null, '2025-01-01', '2025-12-31', 'active', true, true, toTimestamp(now()), toTimestamp(now()));
INSERT INTO promotions (promo_id, title, type, min_order, discount_percent, reward_type, max_discount_amount, start_date, end_date, status, auto_apply, stackable, created_at, updated_at) VALUES ('COMBOCAFE', 'Combo cà phê + bánh croissant', 'combo', 0, null, 'combo', null, '2025-04-15', '2025-09-30', 'active', false, true, toTimestamp(now()), toTimestamp(now()));
INSERT INTO promotions_by_status (status, start_date, promo_id, title, type, reward_type) VALUES ('active', '2025-03-01', 'SPRING2025', 'Spring Treats - Giảm giá thức uống', 'tiered', 'discount');
INSERT INTO promotions_by_status (status, start_date, promo_id, title, type, reward_type) VALUES ('active', '2025-01-01', 'FREESHIP200', 'Freeship đơn từ 200K', 'tiered', 'shipping');
INSERT INTO promotions_by_status (status, start_date, promo_id, title, type, reward_type) VALUES ('active', '2025-04-15', 'COMBOCAFE', 'Combo cà phê + bánh croissant', 'combo', 'combo');

-- Tầng khuyến mãi
INSERT INTO promotion_tiers (promo_id, tier_level, label, min_value, discount_percent, discount_amount, freeship, gift_product_id, gift_quantity, combo_description, metadata) VALUES ('SPRING2025', 1, 'Giảm 10% đơn từ 150K', 150000, 10, 0, false, null, null, null, null);
INSERT INTO promotion_tiers (promo_id, tier_level, label, min_value, discount_percent, discount_amount, freeship, gift_product_id, gift_quantity, combo_description, metadata) VALUES ('SPRING2025', 2, 'Giảm 20% đơn từ 250K', 250000, 20, 0, true, null, null, null, null);
INSERT INTO promotion_tiers (promo_id, tier_level, label, min_value, discount_percent, discount_amount, freeship, gift_product_id, gift_quantity, combo_description, metadata) VALUES ('SPRING2025', 3, 'Giảm 25% + tặng bánh', 350000, 25, 0, true, 'BK001', 1, 'Tặng 1 croissant bơ', null);
INSERT INTO promotion_tiers (promo_id, tier_level, label, min_value, discount_percent, discount_amount, freeship, gift_product_id, gift_quantity, combo_description, metadata) VALUES ('FREESHIP200', 1, 'Freeship đơn 200K', 200000, 0, 0, true, null, null, null, null);
INSERT INTO promotion_tiers (promo_id, tier_level, label, min_value, discount_percent, discount_amount, freeship, gift_product_id, gift_quantity, combo_description, metadata) VALUES ('FREESHIP200', 2, 'Freeship + giảm 30K', 350000, 0, 30000, true, null, null, null, null);
INSERT INTO promotion_tiers (promo_id, tier_level, label, min_value, discount_percent, discount_amount, freeship, gift_product_id, gift_quantity, combo_description, metadata) VALUES ('COMBOCAFE', 1, 'Combo cà phê + bánh', 90000, 0, 15000, false, 'BK001', 1, 'Giảm 15K khi mua CF002 kèm BK001', null);


INSERT INTO promotions_by_product (product_id, promo_id, title, end_date) VALUES ('CF002', 'COMBOCAFE', 'Combo cà phê + bánh croissant', '2025-09-30');
INSERT INTO promotions_by_product (product_id, promo_id, title, end_date) VALUES ('BK001', 'COMBOCAFE', 'Combo cà phê + bánh croissant', '2025-09-30');


INSERT INTO orders (user_id, created_at, order_id, items, total, discount, final_amount, shipping_fee, status, promo_id, applied_tier, note, customer_name, customer_phone, shipping_address, promotion_snapshot, gifts)
VALUES ('USR-ALICE', toTimestamp(now()), 'ORD-0001', [
  {product_id: 'MT001', name: 'Trà sữa trân châu', price: 45000, quantity: 2},
  {product_id: 'CF001', name: 'Cà phê sữa đá', price: 39000, quantity: 1}
], 129000, 12900, 116100, 15000, 'completed', 'SPRING2025', 1, 'Nhận hàng tại quầy', 'Alice Nguyen', '0901 234 567', '123 Lê Lợi, Quận 1, TP.HCM', '[{"promo_id":"SPRING2025","title":"Spring Treats","tier_level":1,"tier_label":"Giảm 10% từ 2 món","discount_amount":12900,"shipping_discount":0}]', '[]');

INSERT INTO orders_by_id (order_id, user_id, created_at, items, total, discount, final_amount, shipping_fee, status, promo_id, applied_tier, note, customer_name, customer_phone, shipping_address, promotion_snapshot, gifts)
VALUES ('ORD-0001', 'USR-ALICE', toTimestamp(now()), [
  {product_id: 'MT001', name: 'Trà sữa trân châu', price: 45000, quantity: 2},
  {product_id: 'CF001', name: 'Cà phê sữa đá', price: 39000, quantity: 1}
], 129000, 12900, 116100, 15000, 'completed', 'SPRING2025', 1, 'Nhận hàng tại quầy', 'Alice Nguyen', '0901 234 567', '123 Lê Lợi, Quận 1, TP.HCM', '[{"promo_id":"SPRING2025","title":"Spring Treats","tier_level":1,"tier_label":"Giảm 10% từ 2 món","discount_amount":12900,"shipping_discount":0}]', '[]');


INSERT INTO orders (user_id, created_at, order_id, items, total, discount, final_amount, shipping_fee, status, promo_id, applied_tier, note, customer_name, customer_phone, shipping_address, promotion_snapshot, gifts)
VALUES ('USR-BOB', toTimestamp(now()), 'ORD-0002', [
  {product_id: 'MT002', name: 'Trà sữa matcha', price: 52000, quantity: 3},
  {product_id: 'BK001', name: 'Croissant bơ', price: 32000, quantity: 2}
], 220000, 44000, 176000, 0, 'shipping', 'SPRING2025', 2, 'Giao buổi sáng', 'Bob Tran', '0909 888 777', '45 Nguyễn Huệ, Quận 1, TP.HCM', '[{"promo_id":"SPRING2025","title":"Spring Treats","tier_level":2,"tier_label":"Giảm 20% đơn từ 3 món","discount_amount":44000,"shipping_discount":0}]', '[]');

INSERT INTO orders_by_id (order_id, user_id, created_at, items, total, discount, final_amount, shipping_fee, status, promo_id, applied_tier, note, customer_name, customer_phone, shipping_address, promotion_snapshot, gifts)
VALUES ('ORD-0002', 'USR-BOB', toTimestamp(now()), [
  {product_id: 'MT002', name: 'Trà sữa matcha', price: 52000, quantity: 3},
  {product_id: 'BK001', name: 'Croissant bơ', price: 32000, quantity: 2}
], 220000, 44000, 176000, 0, 'shipping', 'SPRING2025', 2, 'Giao buổi sáng', 'Bob Tran', '0909 888 777', '45 Nguyễn Huệ, Quận 1, TP.HCM', '[{"promo_id":"SPRING2025","title":"Spring Treats","tier_level":2,"tier_label":"Giảm 20% đơn từ 3 món","discount_amount":44000,"shipping_discount":0}]', '[]');


INSERT INTO orders (user_id, created_at, order_id, items, total, discount, final_amount, shipping_fee, status, promo_id, applied_tier, note, customer_name, customer_phone, shipping_address, promotion_snapshot, gifts)
VALUES ('USR-BOB', toTimestamp(now()), 'ORD-0003', [
  {product_id: 'CF002', name: 'Latte caramel', price: 59000, quantity: 2},
  {product_id: 'BK001', name: 'Croissant bơ', price: 32000, quantity: 1}
], 150000, 30000, 120000, 0, 'completed', 'COMBOCAFE', 1, 'Tặng quà sinh nhật', 'Bob Tran', '0909 888 777', '45 Nguyễn Huệ, Quận 1, TP.HCM', '[{"promo_id":"COMBOCAFE","title":"Combo cà phê + bánh croissant","tier_level":1,"tier_label":"Combo cà phê kèm bánh","discount_amount":30000,"shipping_discount":0}]', '[{"description":"Tặng croissant","quantity":1}]');

INSERT INTO orders_by_id (order_id, user_id, created_at, items, total, discount, final_amount, shipping_fee, status, promo_id, applied_tier, note, customer_name, customer_phone, shipping_address, promotion_snapshot, gifts)
VALUES ('ORD-0003', 'USR-BOB', toTimestamp(now()), [
  {product_id: 'CF002', name: 'Latte caramel', price: 59000, quantity: 2},
  {product_id: 'BK001', name: 'Croissant bơ', price: 32000, quantity: 1}
], 150000, 30000, 120000, 0, 'completed', 'COMBOCAFE', 1, 'Tặng quà sinh nhật', 'Bob Tran', '0909 888 777', '45 Nguyễn Huệ, Quận 1, TP.HCM', '[{"promo_id":"COMBOCAFE","title":"Combo cà phê + bánh croissant","tier_level":1,"tier_label":"Combo cà phê kèm bánh","discount_amount":30000,"shipping_discount":0}]', '[{"description":"Tặng croissant","quantity":1}]');


-- Nhật ký khuyến mãi
INSERT INTO promotion_logs (promo_id, applied_at, log_id, order_id, user_id, tier_level, discount_amount, freeship, reward) VALUES ('SPRING2025', toTimestamp(now()), uuid(), 'ORD-0001', 'USR-ALICE', 1, 12900, false, null);
INSERT INTO promotion_logs (promo_id, applied_at, log_id, order_id, user_id, tier_level, discount_amount, freeship, reward) VALUES ('SPRING2025', toTimestamp(now()), uuid(), 'ORD-0002', 'USR-BOB', 2, 44000, true, null);
INSERT INTO promotion_logs (promo_id, applied_at, log_id, order_id, user_id, tier_level, discount_amount, freeship, reward) VALUES ('COMBOCAFE', toTimestamp(now()), uuid(), 'ORD-0003', 'USR-BOB', 1, 30000, false, '{"gift_product_id":"BK001","quantity":1}');
INSERT INTO promotion_logs_by_order (order_id, applied_at, log_id, promo_id, tier_level, discount_amount) VALUES ('ORD-0001', toTimestamp(now()), uuid(), 'SPRING2025', 1, 12900);
INSERT INTO promotion_logs_by_order (order_id, applied_at, log_id, promo_id, tier_level, discount_amount) VALUES ('ORD-0002', toTimestamp(now()), uuid(), 'SPRING2025', 2, 44000);
INSERT INTO promotion_logs_by_order (order_id, applied_at, log_id, promo_id, tier_level, discount_amount) VALUES ('ORD-0003', toTimestamp(now()), uuid(), 'COMBOCAFE', 1, 30000);
