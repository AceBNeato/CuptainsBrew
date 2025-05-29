

-- Add indexes for better performance
CREATE INDEX idx_product_variations ON product_variations(product_id, variation_type); 