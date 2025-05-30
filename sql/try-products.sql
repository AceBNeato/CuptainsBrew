ALTER TABLE users
ADD COLUMN reset_token VARCHAR(255) NULL,
ADD COLUMN reset_expires DATETIME NULL; 
//////////////////////////////////////////////////////////////
-- First ensure categories exist
INSERT IGNORE INTO categories (id, name) VALUES
(1, 'Coffee'),
(2, 'Non-Coffee'),
(3, 'Frappe'),
(4, 'Milktea');

-- Coffee items
INSERT INTO products (category_id, item_name, item_description, item_price, item_image, has_variation) VALUES
(1, 'AMERICANO', 'Classic espresso diluted with hot water', 59, 'americano.jpg', 1),
(1, 'CAFE LATTE', 'Espresso with steamed milk', 89, 'cafe_latte.jpg', 1),
(1, 'CARAMEL MACCHIATO', 'Espresso with caramel and steamed milk', 89, 'caramel_macchiato.jpg', 1),
(1, 'SALTED CARAMEL', 'Espresso with salted caramel and milk', 89, 'salted_caramel.jpg', 1),
(1, 'SPANISH LATTE', 'Espresso with condensed milk and regular milk', 89, 'spanish_latte.jpg', 1),
(1, 'MOCHA', 'Espresso with chocolate and steamed milk', 89, 'mocha.jpg', 1),
(1, 'DARK CHOCO', 'Espresso with dark chocolate and milk', 89, 'dark_choco.jpg', 1),
(1, 'HAZELNUT', 'Espresso with hazelnut flavor and milk', 89, 'hazelnut.jpg', 1),
(1, 'WHITE CHOCOLATE', 'Espresso with white chocolate and milk', 89, 'white_chocolate.jpg', 1),
(1, 'DIRTY MATCHA', 'Matcha with a shot of espresso', 89, 'dirty_matcha.jpg', 1),
(1, 'CAPPUCCINO', 'Espresso with equal parts steamed milk and foam', 89, 'cappuccino.jpg', 0);

-- Non-Coffee items
INSERT INTO products (category_id, item_name, item_description, item_price, item_image, has_variation) VALUES
(2, 'MILO DINO', 'Rich chocolate malt drink', 110, 'milo_dino.jpg', 0),
(2, 'ICED CHOCOLATE', 'Cold chocolate drink', 110, 'iced_chocolate.jpg', 0),
(2, 'MATCHA LATTE', 'Japanese green tea with milk', 125, 'matcha_latte.jpg', 0),
(2, 'CHOCO HAZELNUT', 'Chocolate with hazelnut flavor', 125, 'choco_hazelnut.jpg', 0),
(2, 'STRAWBERRY MILK', 'Fresh strawberry flavored milk', 125, 'strawberry_milk.jpg', 0),
(2, 'STRAWBERRY MATCHA', 'Matcha with strawberry flavor', 125, 'strawberry_matcha.jpg', 0),
(2, 'COOKIES AND CREAM', 'Milk with cookies and cream flavor', 125, 'cookies_cream.jpg', 0);

-- Frappe items
INSERT INTO products (category_id, item_name, item_description, item_price, item_image, has_variation) VALUES
(3, 'STRAWBERRY', 'Blended strawberry frappe', 150, 'strawberry_frappe.jpg', 0),
(3, 'MATCHA', 'Blended matcha green tea frappe', 150, 'matcha_frappe.jpg', 0),
(3, 'BISCOFF', 'Blended caramelized cookie frappe', 155, 'biscoff_frappe.jpg', 0),
(3, 'CHOCO KISSES', 'Blended chocolate frappe', 150, 'choco_kisses_frappe.jpg', 0),
(3, 'COOKIES AND CREAM', 'Blended cookies and cream frappe', 150, 'cookies_cream_frappe.jpg', 0),
(3, 'VANILLA', 'Blended vanilla frappe', 140, 'vanilla_frappe.jpg', 0),
(3, 'CHOCO HAZELNUT', 'Blended chocolate hazelnut frappe', 150, 'choco_hazelnut_frappe.jpg', 0),
(3, 'BLACK FOREST', 'Blended black forest cake flavor frappe', 150, 'black_forest_frappe.jpg', 0),
(3, 'CARAMEL MACCHIATO', 'Blended caramel macchiato frappe', 100, 'caramel_macchiato_frappe.jpg', 0),
(3, 'DARK CHOCO', 'Blended dark chocolate frappe', 100, 'dark_choco_frappe.jpg', 0),
(3, 'DULCE DE LECHE', 'Blended caramel frappe', 150, 'dulce_de_leche_frappe.jpg', 0),
(3, 'BLACK FOREST', 'Blended black forest cake flavor frappe', 150, 'black_forest_frappe2.jpg', 0);

-- Milktea items
INSERT INTO products (category_id, item_name, item_description, item_price, item_image, has_variation) VALUES
(4, 'OKINAWA', 'Brown sugar milk tea', 79, 'okinawa.jpg', 0),
(4, 'WINTERMELON', 'Wintermelon flavored milk tea', 79, 'wintermelon.jpg', 0),
(4, 'RED VELVET', 'Red velvet flavored milk tea', 89, 'red_velvet.jpg', 0),
(4, 'COOKIES AND CREAM', 'Cookies and cream flavored milk tea', 89, 'cookies_cream_milktea.jpg', 0),
(4, 'CHOCOLATE', 'Chocolate flavored milk tea', 89, 'chocolate_milktea.jpg', 0),
(4, 'MATCHA', 'Matcha green tea flavored milk tea', 99, 'matcha_milktea.jpg', 0);


-- Add variations for coffee items
INSERT INTO product_variations (product_id, variation_type, price) 
SELECT id, 'Hot', 89 FROM products WHERE category_id = 1 AND has_variation = 1;

INSERT INTO product_variations (product_id, variation_type, price) 
SELECT id, 'Iced', 100 FROM products WHERE category_id = 1 AND has_variation = 1;