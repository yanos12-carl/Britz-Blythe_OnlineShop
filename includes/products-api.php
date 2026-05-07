<?php
/**
 * ============================================================================
 * ADVANCED PRODUCT MANAGEMENT FUNCTIONS
 * Enhanced functions for the Add New Product feature
 * ============================================================================
 */

/**
 * Generate a unique SKU for products
 * Format: PROD-{YYYYMMDD}-{randomHex}
 */
function generate_sku(string $productName = ''): string
{
    $prefix = 'SKU';
    $timestamp = date('Ymd');
    $random = strtoupper(substr(md5(uniqid() . $productName), 0, 6));
    return "{$prefix}-{$timestamp}-{$random}";
}

/**
 * Create a new product with advanced options (variants, media, shipping)
 * Returns product ID on success, false on failure
 */
function create_product_advanced(array $data): int|false
{
    $db = get_db();
    if (!$db) return false;

    try {
        $db->beginTransaction();

        // Sanitize and prepare main product data
        $slug = $data['slug'] ?? slugify($data['name'] ?? 'product');
        $sku = $data['sku'] ?? generate_sku($data['name']);
        
        // Check for duplicate slug/SKU
        $stmt = $db->prepare('SELECT id FROM products WHERE slug = :slug OR sku = :sku LIMIT 1');
        $stmt->execute([':slug' => $slug, ':sku' => $sku]);
        if ($stmt->fetch()) {
            $db->rollBack();
            return false;
        }

        // Insert main product
        $stmt = $db->prepare('
            INSERT INTO products 
            (slug, name, category, price, discount_price, stock, image, excerpt, description, sku, weight, length, width, height, video_url, status, created_at, updated_at)
            VALUES 
            (:slug, :name, :category, :price, :discount, :stock, :image, :excerpt, :description, :sku, :weight, :length, :width, :height, :video_url, :status, NOW(), NOW())
        ');

        $stmt->execute([
            ':slug' => $slug,
            ':name' => sanitize($data['name'] ?? ''),
            ':category' => sanitize($data['category'] ?? 'uncategorized'),
            ':price' => (float)($data['price'] ?? 0),
            ':discount' => !empty($data['discount']) ? (float)$data['discount'] : null,
            ':stock' => (int)($data['stock'] ?? 0),
            ':image' => $data['image'] ?? 'assets/images/products/placeholder.svg',
            ':excerpt' => sanitize($data['excerpt'] ?? ''),
            ':description' => sanitize($data['description'] ?? ''),
            ':sku' => $sku,
            ':weight' => $data['weight'] ?? null,
            ':length' => $data['length'] ?? null,
            ':width' => $data['width'] ?? null,
            ':height' => $data['height'] ?? null,
            ':video_url' => $data['video_url'] ?? null,
            ':status' => $data['status'] ?? 'draft',
        ]);

        $productId = (int)$db->lastInsertId();

        // Add product media (images)
        if (!empty($data['media']) && is_array($data['media'])) {
            foreach ($data['media'] as $index => $media) {
                add_product_media(
                    $productId,
                    $media['url'] ?? '',
                    $media['type'] ?? 'image',
                    $media['alt'] ?? '',
                    $index
                );
            }
        }

        // Add product variants
        if (!empty($data['variants']) && is_array($data['variants'])) {
            foreach ($data['variants'] as $variant) {
                add_product_variant(
                    $productId,
                    $variant['name'] ?? '',
                    (float)($variant['price'] ?? 0),
                    (int)($variant['stock'] ?? 0),
                    $variant['sku'] ?? null,
                    $variant['attributes'] ?? []
                );
            }
        }

        // Add shipping options
        if (!empty($data['shipping_options']) && is_array($data['shipping_options'])) {
            foreach ($data['shipping_options'] as $option) {
                add_shipping_option(
                    $productId,
                    $option['type'] ?? 'standard',
                    $option['enabled'] ?? true,
                    $option['estimated_days'] ?? null
                );
            }
        }

        $db->commit();
        return $productId;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error creating product: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing product with advanced options (variants, media, shipping)
 * Returns true on success, false on failure
 */
function update_product_advanced(int $productId, array $data): bool
{
    $db = get_db();
    if (!$db) return false;

    try {
        $db->beginTransaction();

        // Sanitize and prepare main product data
        $slug = $data['slug'] ?? slugify($data['name'] ?? 'product');
        $sku = $data['sku'] ?? generate_sku($data['name']);

        // Ensure slug is unique, excluding the current product's slug
        $currentProduct = get_product_by_id($productId);
        if (!$currentProduct) {
            throw new Exception("Product with ID {$productId} not found for update.");
        }
        $slug = ensure_unique_slug($slug, $currentProduct['slug']);
        $oldImage = $currentProduct['image'];

        // Update main product
        $stmt = $db->prepare('
            UPDATE products 
            SET slug = :slug, name = :name, category = :category, price = :price, discount_price = :discount, 
                stock = :stock, image = :image, excerpt = :excerpt, description = :description, 
                sku = :sku, weight = :weight, length = :length, width = :width, height = :height, 
                video_url = :video_url, status = :status, updated_at = NOW()
            WHERE id = :id
        ');

        $stmt->execute([
            ':id' => $productId,
            ':slug' => $slug,
            ':name' => sanitize($data['name'] ?? ''),
            ':category' => sanitize($data['category'] ?? 'uncategorized'),
            ':price' => (float)($data['price'] ?? 0),
            ':discount' => !empty($data['discount']) ? (float)$data['discount'] : null,
            ':stock' => (int)($data['stock'] ?? 0),
            ':image' => $data['image'] ?? 'assets/images/products/placeholder.svg',
            ':excerpt' => sanitize($data['excerpt'] ?? ''),
            ':description' => sanitize($data['description'] ?? ''),
            ':sku' => $sku,
            ':weight' => $data['weight'] ?? null,
            ':length' => $data['length'] ?? null,
            ':width' => $data['width'] ?? null,
            ':height' => $data['height'] ?? null,
            ':video_url' => $data['video_url'] ?? null,
            ':status' => $data['status'] ?? 'draft',
        ]);

        // Delete existing related data and re-insert (simpler for update)
        // Media
        $stmt = $db->prepare('DELETE FROM product_media WHERE product_id = :id');
        $stmt->execute([':id' => $productId]);
        if (!empty($data['media']) && is_array($data['media'])) {
            foreach ($data['media'] as $index => $media) {
                add_product_media(
                    $productId,
                    $media['url'] ?? '',
                    $media['type'] ?? 'image',
                    $media['alt'] ?? '',
                    $index
                );
            }
        }

        // Variants
        // First delete variant attributes, then variants
        $stmt = $db->prepare('DELETE va FROM variant_attributes va JOIN product_variants pv ON va.variant_id = pv.id WHERE pv.product_id = :id');
        $stmt->execute([':id' => $productId]);
        $stmt = $db->prepare('DELETE FROM product_variants WHERE product_id = :id');
        $stmt->execute([':id' => $productId]);
        if (!empty($data['variants']) && is_array($data['variants'])) {
            foreach ($data['variants'] as $variant) {
                add_product_variant(
                    $productId,
                    $variant['name'] ?? '',
                    (float)($variant['price'] ?? 0),
                    (int)($variant['stock'] ?? 0),
                    $variant['sku'] ?? null,
                    $variant['attributes'] ?? []
                );
            }
        }

        // Shipping options
        $stmt = $db->prepare('DELETE FROM shipping_options WHERE product_id = :id');
        $stmt->execute([':id' => $productId]);
        if (!empty($data['shipping_options']) && is_array($data['shipping_options'])) {
            foreach ($data['shipping_options'] as $option) {
                add_shipping_option(
                    $productId,
                    $option['type'] ?? 'standard',
                    $option['enabled'] ?? true,
                    $option['estimated_days'] ?? null
                );
            }
        }

        $db->commit();

        if (!empty($oldImage) && $oldImage !== $data['image']) {
            delete_product_image_files($oldImage);
        }

        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error updating product: " . $e->getMessage());
        return false;
    }
}

/**
 * Get product by ID
 */
if (!function_exists('get_product_by_id')) {
function get_product_by_id(int $id): ?array
{
    $db = get_db();
    if (!$db) return null;
    $stmt = $db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}
}

/**
 * Add media (images/videos) to a product
 */
function add_product_media(int $productId, string $mediaUrl, string $mediaType = 'image', string $altText = '', int $sortOrder = 0): bool
{
    $db = get_db();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            INSERT INTO product_media (product_id, media_type, media_url, alt_text, sort_order, created_at)
            VALUES (:product_id, :media_type, :media_url, :alt_text, :sort_order, NOW())
        ');

        return $stmt->execute([
            ':product_id' => $productId,
            ':media_type' => $mediaType,
            ':media_url' => $mediaUrl,
            ':alt_text' => $altText,
            ':sort_order' => $sortOrder,
        ]);
    } catch (Exception $e) {
        error_log("Error adding product media: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all media for a product
 */
function get_product_media(int $productId): array
{
    $db = get_db();
    if (!$db) return [];

    try {
        $stmt = $db->prepare('
            SELECT * FROM product_media 
            WHERE product_id = :product_id 
            ORDER BY sort_order ASC
        ');
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching product media: " . $e->getMessage());
        return [];
    }
}

/**
 * Add a product variant (with SKU and pricing)
 */
function add_product_variant(int $productId, string $variantName, float $price, int $stock, ?string $sku = null, array $attributes = []): int|false
{
    $db = get_db();
    if (!$db) return false;

    try {
        $sku = $sku ?? generate_sku($variantName);
        
        $stmt = $db->prepare('
            INSERT INTO product_variants (product_id, variant_name, price, stock, sku)
            VALUES (:product_id, :variant_name, :price, :stock, :sku)
        ');

        $stmt->execute([
            ':product_id' => $productId,
            ':variant_name' => $variantName,
            ':price' => $price,
            ':stock' => $stock,
            ':sku' => $sku,
        ]);

        $variantId = (int)$db->lastInsertId();

        // Add variant attributes (e.g., Color: Red, Size: M)
        foreach ($attributes as $name => $value) {
            $attrStmt = $db->prepare('
                INSERT INTO variant_attributes (variant_id, attribute_name, attribute_value)
                VALUES (:variant_id, :attr_name, :attr_value)
            ');
            $attrStmt->execute([
                ':variant_id' => $variantId,
                ':attr_name' => $name,
                ':attr_value' => $value,
            ]);
        }

        return $variantId;
    } catch (Exception $e) {
        error_log("Error adding product variant: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all variants for a product
 */
function get_product_variants(int $productId): array
{
    $db = get_db();
    if (!$db) return [];

    try {
        $stmt = $db->prepare('
            SELECT pv.*, 
                   GROUP_CONCAT(CONCAT(va.attribute_name, ":", va.attribute_value) SEPARATOR "|") as attributes
            FROM product_variants pv
            LEFT JOIN variant_attributes va ON pv.id = va.variant_id
            WHERE pv.product_id = :product_id
            GROUP BY pv.id
            ORDER BY pv.id
        ');
        $stmt->execute([':product_id' => $productId]);
        $variants = $stmt->fetchAll();

        // Parse attributes back into arrays
        foreach ($variants as &$variant) {
            $variant['attributes_array'] = [];
            if (!empty($variant['attributes'])) {
                $pairs = explode('|', $variant['attributes']);
                foreach ($pairs as $pair) {
                    list($name, $value) = explode(':', $pair);
                    $variant['attributes_array'][$name] = $value;
                }
            }
        }

        return $variants;
    } catch (Exception $e) {
        error_log("Error fetching product variants: " . $e->getMessage());
        return [];
    }
}

/**
 * Add shipping option to a product
 */
function add_shipping_option(int $productId, string $shippingType, bool $isEnabled = true, ?int $estimatedDays = null): bool
{
    $db = get_db();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            INSERT INTO shipping_options (product_id, shipping_type, is_enabled, estimated_days, created_at)
            VALUES (:product_id, :shipping_type, :is_enabled, :estimated_days, NOW())
            ON DUPLICATE KEY UPDATE is_enabled = :is_enabled, estimated_days = :estimated_days
        ');

        return $stmt->execute([
            ':product_id' => $productId,
            ':shipping_type' => $shippingType,
            ':is_enabled' => (int)$isEnabled,
            ':estimated_days' => $estimatedDays,
        ]);
    } catch (Exception $e) {
        error_log("Error adding shipping option: " . $e->getMessage());
        return false;
    }
}

/**
 * Get shipping options for a product
 */
function get_shipping_options(int $productId): array
{
    $db = get_db();
    if (!$db) return [];

    try {
        $stmt = $db->prepare('
            SELECT * FROM shipping_options 
            WHERE product_id = :product_id AND is_enabled = 1
        ');
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching shipping options: " . $e->getMessage());
        return [];
    }
}

/**
 * Save product draft for auto-save functionality
 */
function save_product_draft(int $userId, array $draftData): bool
{
    $db = get_db();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            INSERT INTO product_drafts (user_id, draft_data, created_at, updated_at)
            VALUES (:user_id, :draft_data, NOW(), NOW())
            ON DUPLICATE KEY UPDATE draft_data = :draft_data, updated_at = NOW()
        ');

        return $stmt->execute([
            ':user_id' => $userId,
            ':draft_data' => json_encode($draftData),
        ]);
    } catch (Exception $e) {
        error_log("Error saving product draft: " . $e->getMessage());
        return false;
    }
}

/**
 * Get product draft for a user
 */
function get_product_draft(int $userId): ?array
{
    $db = get_db();
    if (!$db) return null;

    try {
        $stmt = $db->prepare('
            SELECT draft_data FROM product_drafts 
            WHERE user_id = :user_id 
            ORDER BY updated_at DESC 
            LIMIT 1
        ');
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        
        return $result ? json_decode($result['draft_data'], true) : null;
    } catch (Exception $e) {
        error_log("Error fetching product draft: " . $e->getMessage());
        return null;
    }
}

/**
 * Update product status (draft to published or vice versa)
 */
function update_product_status(int $productId, string $status): bool
{
    $db = get_db();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            UPDATE products 
            SET status = :status, updated_at = NOW() 
            WHERE id = :id
        ');
        return $stmt->execute([':id' => $productId, ':status' => $status]);
    } catch (Exception $e) {
        error_log("Error updating product status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get product with all related data (variants, media, shipping)
 */
function get_product_complete(int $productId): ?array
{
    $db = get_db();
    if (!$db) return null;

    try {
        // Get main product data
        $stmt = $db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            return null;
        }

        // Attach related data
        $product['media'] = get_product_media($productId);
        $product['variants'] = get_product_variants($productId);
        $product['shipping_options'] = get_shipping_options($productId);

        return $product;
    } catch (Exception $e) {
        error_log("Error fetching complete product: " . $e->getMessage());
        return null;
    }
}

/**
 * Delete product and all related data
 */
function delete_product_advanced(int $productId): bool
{
    $db = get_db();
    if (!$db) return false;

    try {
        $db->beginTransaction();

        // Delete media
        $stmt = $db->prepare('DELETE FROM product_media WHERE product_id = :id');
        $stmt->execute([':id' => $productId]);

        // Delete variant attributes
        $stmt = $db->prepare('
            DELETE va FROM variant_attributes va
            INNER JOIN product_variants pv ON va.variant_id = pv.id
            WHERE pv.product_id = :id
        ');
        $stmt->execute([':id' => $productId]);

        // Delete variants
        $stmt = $db->prepare('DELETE FROM product_variants WHERE product_id = :id');
        $stmt->execute([':id' => $productId]);

        // Delete shipping options
        $stmt = $db->prepare('DELETE FROM shipping_options WHERE product_id = :id');
        $stmt->execute([':id' => $productId]);

        // Delete main product
        $stmt = $db->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $productId]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error deleting product: " . $e->getMessage());
        return false;
    }
}

/**
 * Reorder product media
 */
function reorder_product_media(array $mediaOrder): bool
{
    $db = get_db();
    if (!$db) return false;

    try {
        $db->beginTransaction();
        foreach ($mediaOrder as $index => $mediaId) {
            $stmt = $db->prepare('UPDATE product_media SET sort_order = :order WHERE id = :id');
            $stmt->execute([':order' => $index, ':id' => $mediaId]);
        }
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error reordering media: " . $e->getMessage());
        return false;
    }
}

/**
 * Get category tree for nested category selection
 */
function get_categories_tree(): array
{
    $categories = get_categories();
    $tree = [];
    
    foreach ($categories as $cat) {
        $tree[] = [
            'id' => $cat['slug'],
            'name' => $cat['name'],
            'product_count' => $cat['product_count'],
        ];
    }
    
    return $tree;
}

/**
 * Extracts a grouped list of unique attribute options for a product
 * Used to build the variation selection buttons (e.g. Color: [Red, Blue])
 */
function get_product_attribute_groups(?array $variants): array
{
    if (empty($variants)) {
        return [];
    }

    $groups = [];
    foreach ($variants as $variant) {
        if (!empty($variant['attributes_array'])) {
            foreach ($variant['attributes_array'] as $name => $value) {
                if (!isset($groups[$name])) {
                    $groups[$name] = [];
                }
                if (!in_array($value, $groups[$name])) {
                    $groups[$name][] = $value;
                }
            }
        }
    }
    return $groups;
}
