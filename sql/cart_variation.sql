

-- Create index for faster lookups
CREATE INDEX idx_cart_variation ON cart(user_id, product_id, variation); 