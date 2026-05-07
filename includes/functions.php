<?php

/**
 * ============================================================================
 * STRING & DATA UTILITIES
 * ============================================================================
 */

function format_price(float $amount): string
{
    return CURRENCY . number_format($amount, 2);
}

function sanitize(string $text): string
{
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\\pL0-9]+~u', '-', $text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

/**
 * Highlights occurrences of a search term within a string.
 * Sanitizes input first to prevent XSS.
 */
function highlight_term(string $text, string $term): string
{
    if (empty($term)) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    $safe_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $quoted_term = preg_quote(htmlspecialchars($term, ENT_QUOTES, 'UTF-8'), '/');
    return preg_replace('/(' . $quoted_term . ')/i', '<mark class="highlight">$1</mark>', $safe_text);
}

/**
 * Safely resolves an image/asset path to a full URL.
 * Prevents double-prefixing absolute URLs and handles null fallbacks.
 */
function resolve_asset_url(?string $path): string
{
    if (empty($path)) return SITE_URL . '/public/assets/images/products/placeholder.svg';
    if (strpos($path, 'http') === 0) return $path;

    $clean_path = str_replace('\\', '/', ltrim($path, '/'));
    
    // Ensure 'public/' prefix for local assets if missing (required for dev subfolder setups like XAMPP)
    if (strpos($clean_path, 'public/') !== 0) {
        if (strpos($clean_path, 'assets/') === 0) {
        $clean_path = 'public/' . $clean_path;
        } else {
            $clean_path = 'public/assets/images/products/' . $clean_path;
        }
    }

    // Use rtrim to prevent double slashes if SITE_URL was defined with a trailing slash
    return rtrim(SITE_URL, '/') . '/' . $clean_path;
}

/**
 * ============================================================================
 * DATABASE CORE
 * ============================================================================
 */

function get_db(): ?PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $pdo = require __DIR__ . '/../config/database.php';
        return $pdo;
    } catch (Throwable $exception) {
        return null;
    }
}

/**
 * ============================================================================
 * PRODUCT & CATEGORY REPOSITORY
 * ============================================================================
 */

function get_default_categories(): array
{
    return [
        ['slug' => 'living', 'name' => 'Living', 'product_count' => 0],
        ['slug' => 'office', 'name' => 'Office', 'product_count' => 0],
        ['slug' => 'travel', 'name' => 'Travel', 'product_count' => 0],
        ['slug' => 'gifts', 'name' => 'Gifts', 'product_count' => 0],
    ];
}

function get_default_products(): array
{
    return [
        [
            'id' => 0,
            'slug' => 'artisan-leather-tote',
            'name' => 'Artisan Leather Tote',
            'price' => 149.00,
            'category' => 'living',
            'stock' => 15,
            'image' => ASSET_PATH . '/images/products/placeholder.svg',
            'excerpt' => 'Handcrafted carry-all tote with premium vegetable-tanned leather.',
            'description' => 'A structured tote designed for everyday elegance, featuring soft leather, brass hardware, and a spacious interior.',
        ],
        [
            'id' => 0,
            'slug' => 'linen-bedroom-set',
            'name' => 'Linen Bedroom Set',
            'price' => 89.00,
            'category' => 'living',
            'stock' => 20,
            'image' => ASSET_PATH . '/images/products/placeholder.svg',
            'excerpt' => 'Breathable linen bedding for hotel-inspired comfort.',
            'description' => 'Soft, breathable linen sheets and pillowcases with a relaxed, lived-in finish for every season.',
        ],
        [
            'id' => 0,
            'slug' => 'desktop-organizer',
            'name' => 'Desktop Organizer',
            'price' => 29.00,
            'category' => 'office',
            'stock' => 50,
            'image' => ASSET_PATH . '/images/products/placeholder.svg',
            'excerpt' => 'Elegant organizer tray for cables, notebooks, and daily essentials.',
            'description' => 'Designed to keep your desk tidy with dedicated compartments for stationery and accessories.',
        ],
        [
            'id' => 0,
            'slug' => 'travel-essentials-kit',
            'name' => 'Travel Essentials Kit',
            'price' => 49.00,
            'category' => 'travel',
            'stock' => 12,
            'image' => ASSET_PATH . '/images/products/placeholder.svg',
            'excerpt' => 'Compact kit built for modern journeys and weekend escapes.',
            'description' => 'A stylish pouch filled with travel-ready essentials for the road, cabin, or train.',
        ],
    ];
}

function get_categories(): array
{
    $db = get_db();
    if ($db) {
$query = 'SELECT c.slug, c.name, COUNT(p.id) as product_count 
                  FROM categories c 
                  LEFT JOIN products p ON c.slug = p.category AND p.deleted_at IS NULL 
                  GROUP BY c.slug, c.name 
                  ORDER BY c.name';
        return $db->query($query)->fetchAll();
    }
    return get_default_categories();
}

function get_products(string $category_slug = '', string $search = '', string $sort = 'name-asc', int $limit = 0, int $offset = 0, string $status = 'published'): array
{
    $db = get_db();
    if ($db) {
$sql = 'SELECT p.*, c.name as category_name, COALESCE(AVG(r.rating), 0) as average_rating, COUNT(r.id) as review_count 
                FROM products p 
                LEFT JOIN categories c ON p.category = c.slug 
                LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = 1';
        $clauses = [];
        $params = [];

        if ($category_slug !== '') {
            $clauses[] = 'c.slug = :category_slug';
            $params[':category_slug'] = $category_slug;
        }
        if ($search !== '') {
            $clauses[] = '(p.name LIKE :search OR p.excerpt LIKE :search OR p.description LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($status === 'archived') {
            $clauses[] = 'p.deleted_at IS NOT NULL';
        } else {
            $clauses[] = 'p.deleted_at IS NULL';
            if ($status !== '' && $status !== 'all') {
                $clauses[] = 'p.status = :status';
                $params[':status'] = $status;
            }
        }

        if (!empty($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= ' GROUP BY p.id';

        // Sorting logic
        switch ($sort) {
            case 'price-asc': $sql .= ' ORDER BY p.price ASC'; break;
            case 'price-desc': $sql .= ' ORDER BY p.price DESC'; break;
            case 'newest': $sql .= ' ORDER BY p.id DESC'; break;
            case 'rating': $sql .= ' ORDER BY average_rating DESC'; break;
            case 'popular': $sql .= ' ORDER BY p.sold_count DESC'; break;
            default: $sql .= ' ORDER BY p.name ASC'; break;
        }

        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    // Fallback for non-database environment (assuming default products also include category_name)
    $products = get_default_products();
    $filtered = array_values(array_filter($products, function ($product) use ($category_slug, $search) {
        if ($category_slug !== '' && ($product['category_slug'] ?? $product['category']) !== $category_slug) { // Adjust for fallback structure
            return false; 
        }
        if ($search !== '') {
            $term = mb_strtolower($search);
            return strpos(mb_strtolower($product['name']), $term) !== false
                || strpos(mb_strtolower($product['excerpt']), $term) !== false
                || strpos(mb_strtolower($product['description']), $term) !== false;
        }
        return true;
    }));

    // Add default rating data for fallback
    foreach ($filtered as &$product) {
        $product['average_rating'] = 0;
        $product['category_name'] = 'Uncategorized'; // Default for fallback
        $product['review_count'] = 0;
        $product['sold_count'] = $product['sold_count'] ?? 0;
    }

    // Basic sorting for fallback
    if ($sort === 'price-asc') {
        usort($filtered, fn($a, $b) => $a['price'] <=> $b['price']);
    } elseif ($sort === 'price-desc') {
        usort($filtered, fn($a, $b) => $b['price'] <=> $a['price']);
    } elseif ($sort === 'rating') {
        usort($filtered, fn($a, $b) => $b['average_rating'] <=> $a['average_rating']);
    } elseif ($sort === 'popular') {
        usort($filtered, fn($a, $b) => $b['sold_count'] <=> $a['sold_count']);
    }

    // Basic pagination for fallback
    if ($limit > 0) {
        return array_slice($filtered, $offset, $limit);
    }

    return $filtered;
}

function get_total_products_count(string $category_slug = '', string $search = '', string $status = 'published'): int
{
    $db = get_db();
    if ($db) {
$sql = 'SELECT COUNT(p.id) FROM products p';
        $clauses = [];
        $params = [];

        if ($category_slug !== '') {
$sql .= ' LEFT JOIN categories c ON p.category = c.slug';
            $clauses[] = 'c.slug = :category_slug';
            $params[':category_slug'] = $category_slug;
        }
        if ($search !== '') {
            $clauses[] = '(p.name LIKE :search OR p.excerpt LIKE :search OR p.description LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($status === 'archived') {
            $clauses[] = 'p.deleted_at IS NOT NULL';
        } else {
            $clauses[] = 'p.deleted_at IS NULL';
            if ($status !== '' && $status !== 'all') {
                $clauses[] = 'p.status = :status';
                $params[':status'] = $status;
            }
        }
        if (!empty($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
    return count(get_default_products());
}

function get_product_by_slug(string $slug, bool $include_archived = false): ?array
{
    $db = get_db();
    if ($db) {
        // Join with categories to get category_name
$sql = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category = c.slug WHERE p.slug = :slug';
        if (!$include_archived) {
            $sql .= ' AND p.deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        $product = $stmt->fetch();
        return $product ? $product : null;
    }
    foreach (get_default_products() as $product) {
        if ($product['slug'] === $slug) {
            return $product;
        }
    }

    return null;
}

if (!function_exists('get_product_by_id')) {
function get_product_by_id(int $id, bool $include_archived = false): ?array
{
    $db = get_db();
    if ($db) {
        $sql = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category = c.slug WHERE p.id = :id';
        if (!$include_archived) {
            $sql .= ' AND p.deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
    return null; // No fallback for get_product_by_id if DB is not available
}
}
function search_products(string $term): array
{
    return get_products('', $term);
}

/**
 * Automatically converts a JPEG/PNG to WebP to optimize performance.
 */
function generate_webp(string $image_path): string
{
    // Check if GD extension is loaded to avoid fatal errors
    if (!extension_loaded('gd')) {
        return $image_path;
    }

    // If the path is already a full URL, we cannot process it locally
    if (strpos($image_path, 'http') === 0 || strpos($image_path, 'placeholder.svg') !== false) {
        return $image_path;
    }

    $root = dirname(__DIR__);
    
    // Normalize and handle potential absolute paths already stored in DB
    $normalized_input = str_replace('\\', '/', $image_path);
    if (strpos($normalized_input, str_replace('\\', '/', $root)) !== false) {
        $full_path = $normalized_input;
    } else {
        $full_path = str_replace('\\', '/', $root . '/public/' . ltrim($image_path, '/'));
    }

    if (!file_exists($full_path) || !is_file($full_path)) {
        return $image_path;
    }

    $info = getimagesize($full_path);
    if (!$info) return $image_path;

    // Define the new WebP path
    $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $full_path);
    
    // If WebP version already exists, return the path relative to public
    if (file_exists($webp_path)) {
        $normalized_webp = str_replace('\\', '/', $webp_path);
        return preg_replace('/^.*\/public\//', '', $normalized_webp);
    }

    $img = null;
    switch ($info[2]) {
        case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($full_path); break;
        case IMAGETYPE_PNG: 
            $img = imagecreatefrompng($full_path); 
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            break;
        default: return $image_path;
    }

    if ($img && imagewebp($img, $webp_path, 80)) {
        imagedestroy($img);
        $normalized_webp = str_replace('\\', '/', $webp_path);
        return preg_replace('/^.*\/public\//', '', $normalized_webp);
    }

    return $image_path;
}

/**
 * Resizes an image while maintaining aspect ratio to fit within standard dimensions.
 * Returns the relative path to the new resized image.
 */
function resize_image(string $image_path, int $max_width = 800, int $max_height = 800, string $suffix = ''): string
{
    // Check if GD extension is loaded to avoid fatal errors
    if (!extension_loaded('gd')) {
        return $image_path;
    }

    // If the path is already a full URL, we cannot process it locally
    if (strpos($image_path, 'http') === 0) {
        return $image_path;
    }

    $root = dirname(__DIR__);
    
    // Normalize and handle potential absolute paths already stored in DB
    $normalized_input = str_replace('\\', '/', $image_path);
    if (strpos($normalized_input, str_replace('\\', '/', $root)) !== false) {
        $full_path = $normalized_input;
    } else {
        $full_path = str_replace('\\', '/', $root . '/public/' . ltrim($image_path, '/'));
    }

    if (!file_exists($full_path) || !is_file($full_path)) {
        return $image_path;
    }

    $info = getimagesize($full_path);
    if (!$info) return $image_path;

    list($width, $height) = $info;
    $scale = min($max_width / $width, $max_height / $height);

    // If image is already smaller than the max dimensions, don't resize
    if ($scale >= 1) return $image_path;

    $new_width = (int)round($width * $scale);
    $new_height = (int)round($height * $scale);

    $path_info = pathinfo($full_path);
    $filename = $path_info['filename'] . ($suffix ? '-' . $suffix : '') . '.' . $path_info['extension'];
    $target_path = $path_info['dirname'] . '/' . $filename;

    $src = null;
    switch ($info[2]) {
        case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($full_path); break;
        case IMAGETYPE_PNG: 
            $src = imagecreatefrompng($full_path); 
            if ($src) imagepalettetotruecolor($src);
            break;
        default: return $image_path;
    }

    if (!$src) return $image_path;

    $dst = imagecreatetruecolor($new_width, $new_height);
    if ($info[2] === IMAGETYPE_PNG) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    switch ($info[2]) {
        case IMAGETYPE_JPEG: imagejpeg($dst, $target_path, 90); break;
        case IMAGETYPE_PNG: imagepng($dst, $target_path, 9); break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    $normalized_target = str_replace('\\', '/', $target_path);
    return preg_replace('/^.*\/public\//', '', $normalized_target);
}

/**
 * Generates a full suite of optimized WebP images for a product.
 * Returns the path to the 'large' version for database storage.
 */
function process_product_image_suite(string $source_path): string
{
    // Skip processing if it's already a placeholder or an already processed/external URL
    if (strpos($source_path, 'placeholder.svg') !== false || strpos($source_path, 'http') === 0 || strpos($source_path, '-large.webp') !== false) {
        return $source_path;
    }
    // Generate Thumb (150px), Medium (450px), and Large (900px)
    generate_webp(resize_image($source_path, 150, 150, 'thumb'));
    generate_webp(resize_image($source_path, 450, 450, 'medium'));
    $large = resize_image($source_path, 900, 900, 'large');
    
    return generate_webp($large);
}

/**
 * Cart Management (Shopify-style state handling)
 */
function add_to_cart(string $slug, int $quantity = 1): array
{
    $product = get_product_by_slug($slug);
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found.'];
    }
    if ($product['stock'] < $quantity) {
        return ['success' => false, 'message' => 'Not enough stock available for ' . htmlspecialchars($product['name']) . '. Only ' . $product['stock'] . ' item(s) left.'];
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $currentQty = $_SESSION['cart'][$slug] ?? 0;
    if (($currentQty + $quantity) > $product['stock']) {
        return ['success' => false, 'message' => 'You already have ' . $currentQty . ' of ' . htmlspecialchars($product['name']) . ' in your cart. Only ' . ($product['stock'] - $currentQty) . ' more can be added.'];
    }

    $_SESSION['cart'][$slug] = $currentQty + $quantity;
    return ['success' => true, 'message' => htmlspecialchars($product['name']) . ' added to cart.'];
}

function remove_from_cart(string $slug): void
{
    unset($_SESSION['cart'][$slug]);
}

function update_cart(string $slug, int $quantity): void
{
    if ($quantity <= 0) {
        remove_from_cart($slug);
        return;
    }
    $_SESSION['cart'][$slug] = $quantity;
}

function get_cart_count(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

/**
 * ============================================================================
 * CHECKOUT & ORDER SERVICES
 * ============================================================================
 */

function clear_cart(): void
{
    unset($_SESSION['cart']);
}

function update_cart_item_quantity(string $cartKey, int $change): array
{
    if (!isset($_SESSION['cart'][$cartKey])) {
        return ['success' => false, 'message' => 'Item not in cart.'];
    }

    // Parse slug and variation from key
    $slug = $cartKey;
    $variationItemId = null;
    if (strpos($cartKey, '_var') !== false) {
        list($slug, $varPart) = explode('_var', $cartKey, 2);
        $variationItemId = (int)$varPart;
    }

    $product = get_product_by_slug($slug);
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found.'];
    }

    $effectiveStock = $product['stock'];
    if ($variationItemId) {
        $db = get_db();
        $stmt = $db->prepare('SELECT stock FROM variation_items WHERE id = ?');
        $stmt->execute([$variationItemId]);
        $effectiveStock = $stmt->fetchColumn() ?: 0;
    }

    $newQty = $_SESSION['cart'][$cartKey] + $change;

    if ($newQty <= 0) {
        unset($_SESSION['cart'][$cartKey]);
        return ['success' => true, 'message' => 'Item removed.'];
    }

    if ($newQty > $effectiveStock) {
        return ['success' => false, 'message' => 'Maximum stock reached.'];
    }

    $_SESSION['cart'][$cartKey] = $newQty;
    return ['success' => true, 'message' => 'Quantity updated.'];
}

/**
 * Process Checkout (The core "Purchasing" function)
 * Wraps the entire order creation in a database transaction.
 */
function process_checkout(?int $userId = null, array $shippingData = [], array $billingData = []): array
{
    // Enforce login at the logic level
    if ($userId === null) {
        return ['success' => false, 'message' => 'Authentication required to complete checkout.'];
    }

    // Auto-fill from address book if no specific shipping data was provided
    if (empty($shippingData)) {
        $defaultAddress = get_default_user_address($userId);
        if ($defaultAddress) {
            $shippingData = $defaultAddress;
        }
    }

    // Security: Verify that if address IDs are provided, they actually belong to the user
    if (!empty($shippingData['id']) && !get_address_by_id((int)$shippingData['id'], (int)$userId)) {
        return ['success' => false, 'message' => 'Invalid shipping address selected.'];
    }

    if (!empty($billingData['id']) && !get_address_by_id((int)$billingData['id'], (int)$userId)) {
        return ['success' => false, 'message' => 'Invalid billing address selected.'];
    }

    // Default billing to shipping details if billingData is empty
    if (empty($billingData)) {
        $billingData = $shippingData;
    }

    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database connection failed.'];

    $items = get_cart_items_with_variations();
    if (empty($items)) return ['success' => false, 'message' => 'Cart is empty.'];

    $totals = calculate_cart_totals();

    try {
        $db->beginTransaction();

        // 1. Create Order
        $stmt = $db->prepare('INSERT INTO orders (user_id, recipient_name, address, city, state, zip_code, phone_number, 
                             billing_recipient_name, billing_address, billing_city, billing_state, billing_zip_code, billing_phone_number,
                             total, status) 
                             VALUES (:user_id, :recipient, :address, :city, :state, :zip, :phone, 
                             :b_recipient, :b_address, :b_city, :b_state, :b_zip, :b_phone,
                             :total, "pending")');
        $stmt->execute([
            ':user_id' => $userId,
            ':recipient' => sanitize($shippingData['recipient_name'] ?? ''),
            ':address' => sanitize($shippingData['address'] ?? ''),
            ':city' => sanitize($shippingData['city'] ?? ''),
            ':state' => sanitize($shippingData['state'] ?? ''),
            ':zip' => sanitize($shippingData['zip_code'] ?? ''),
            ':phone' => sanitize($shippingData['phone_number'] ?? ''),
            ':b_recipient' => sanitize($billingData['recipient_name'] ?? ''),
            ':b_address' => sanitize($billingData['address'] ?? ''),
            ':b_city' => sanitize($billingData['city'] ?? ''),
            ':b_state' => sanitize($billingData['state'] ?? ''),
            ':b_zip' => sanitize($billingData['zip_code'] ?? ''),
            ':b_phone' => sanitize($billingData['phone_number'] ?? ''),
            ':total' => $totals['total']
        ]);
        $orderId = $db->lastInsertId();

        // 2. Process Line Items & Update Inventory
        $itemStmt = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:oid, :pid, :qty, :price)');
        $productStockStmt = $db->prepare('UPDATE products SET stock = stock - :qty WHERE id = :pid AND stock >= :qty');
        $variationStockStmt = $db->prepare('UPDATE variation_items SET stock = stock - :qty WHERE id = :vid AND stock >= :qty');

        foreach ($items as $item) {
            $product = $item['product'];
            $variation = $item['variation_item'];
            $currentStock = $variation ? $variation['stock'] : $product['stock'];
            
            // Verify stock again inside transaction
            if ($currentStock < $item['quantity']) {
                throw new Exception("Product {$product['name']} is out of stock.");
            }

            // Add to order_items
            $itemStmt->execute([
                ':oid' => $orderId,
                ':pid' => $product['id'],
                ':qty' => $item['quantity'],
                ':price' => $item['price']
            ]);

            // Deduct inventory
            if ($variation) {
                $variationStockStmt->execute([
                    ':qty' => $item['quantity'],
                    ':vid' => $variation['id']
                ]);
                if ($variationStockStmt->rowCount() === 0) throw new Exception("Failed to update variant stock for {$product['name']}.");
            } else {
                $productStockStmt->execute([
                    ':qty' => $item['quantity'],
                    ':pid' => $product['id']
                ]);

                if ($productStockStmt->rowCount() === 0) {
                    throw new Exception("Failed to update stock for {$product['name']}.");
                }
            }
        }

        $db->commit();
        
        // Clear Shopify-style session cart
        $_SESSION['cart'] = [];
        
        return ['success' => true, 'order_id' => $orderId];

    } catch (Throwable $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function get_cart_items(): array
{
    $items = $_SESSION['cart'] ?? [];
    $cartItems = [];
    foreach ($items as $slug => $quantity) {
        $product = get_product_by_slug($slug);
        if ($product) {
            $cartItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $product['price'] * $quantity,
            ];
        }
    }
    return $cartItems;
}

function calculate_cart_totals(): array
{
    $items = get_cart_items_with_variations();
    $subtotal = array_reduce($items, fn($carry, $item) => $carry + $item['subtotal'], 0.0);
    $tax = $subtotal * 0.05; // Standardized tax
    $total = $subtotal + $tax;
    return ['subtotal' => $subtotal, 'tax' => $tax, 'total' => $total];
}

function get_orders(int $limit = 50): array
{
    $db = get_db();
    if (!$db) {
        return [];
    }
    $stmt = $db->prepare(
        'SELECT o.id, o.status, o.total, o.created_at, u.name AS customer_name, o.recipient_name, o.city, o.state
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         ORDER BY o.created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_order_by_id(int $id): ?array
{
    $db = get_db();
    if (!$db) {
        return null;
    }
    $stmt = $db->prepare(
        'SELECT o.id, o.status, o.total, o.created_at, u.name AS customer_name, u.email AS customer_email, 
                o.user_id, o.recipient_name, o.address, o.city, o.state, o.zip_code, o.phone_number,
                o.billing_recipient_name, o.billing_address, o.billing_city, o.billing_state, o.billing_zip_code, o.billing_phone_number
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         WHERE o.id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function get_order_items(int $orderId): array
{
    $db = get_db();
    if (!$db) {
        return [];
    }
    $stmt = $db->prepare(
        'SELECT oi.quantity, oi.price, p.name, p.slug, p.image
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = :order_id'
    );
    $stmt->execute([':order_id' => $orderId]);
    return $stmt->fetchAll();
}

function get_customers(): array
{
    $db = get_db();
    if (!$db) {
        return [];
    }
    $stmt = $db->query('SELECT id, name, email, role FROM users ORDER BY name');
    return $stmt->fetchAll();
}

function delete_user(int $id): bool
{
    $db = get_db();
    if (!$db) return false;

    // Prevent an admin from deleting their own account
    if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $id) {
        return false;
    }

    $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function get_user_by_email(string $email): ?array
{
    $db = get_db();
    if (!$db) {
        return null;
    }
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    return $stmt->fetch() ?: null;
}

function update_user_details(int $id, string $name, string $email, string $phone = ''): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || trim($name) === '') {
        return false;
    }

    $db = get_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
    $stmt->execute([':email' => $email, ':id' => $id]);
    if ($stmt->fetch()) {
        return false;
    }

    $stmt = $db->prepare('UPDATE users SET name = :name, email = :email, phone_number = :phone WHERE id = :id');
        $success = $stmt->execute([
        ':name' => sanitize($name),
        ':email' => $email,
        ':phone' => sanitize($phone),
        ':id' => $id,
    ]);

        // World-class UX: Synchronize the session so the profile page reflects changes immediately
        if ($success && isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $id) {
            $_SESSION['user']['name'] = sanitize($name);
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone_number'] = sanitize($phone);
        }

        return $success;
    }

/**
 * Generates a Google Maps link for a given address array.
 */
function get_address_location_link(?array $addr): string
{
    if (empty($addr['address'])) {
        return '';
    }

    $queryParts = array_filter([
        $addr['address'],
        $addr['city'] ?? '',
        $addr['state'] ?? '',
        $addr['zip_code'] ?? ''
    ]);

    return "https://www.google.com/maps/search/?api=1&query=" . urlencode(implode(' ', $queryParts));
}




function update_user_image(int $id, string $imagePath): bool
{
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('UPDATE users SET profile_image = :image WHERE id = :id');
    return $stmt->execute([':image' => $imagePath, ':id' => $id]);
}

function update_user_password(int $id, string $currentPassword, string $newPassword): array
{
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database error.'];

    $stmt = $db->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'New password must be at least 6 characters.'];
    }

    $stmt = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
    $success = $stmt->execute([':id' => $id, ':password' => password_hash($newPassword, PASSWORD_DEFAULT)]);

    return $success ? ['success' => true, 'message' => 'Password updated.'] : ['success' => false, 'message' => 'Update failed.'];
}

function get_user_orders(string $email): array
{
    $db = get_db();
    if (!$db) {
        return [];
    }
    $stmt = $db->prepare(
        'SELECT o.id, o.status, o.total, o.created_at, o.recipient_name, o.address, o.city, o.state, o.zip_code, o.phone_number
         FROM orders o
         JOIN users u ON o.user_id = u.id
         WHERE u.email = :email
         ORDER BY o.created_at DESC'
    );
    $stmt->execute([':email' => $email]);
    return $stmt->fetchAll();
}

function ensure_unique_slug(string $slug, ?string $excludeSlug = null): string
{
    $db = get_db();
    if (!$db) return $slug;

    $original = $slug;
    $count = 1;

    while (true) {
        $sql = 'SELECT COUNT(*) FROM products WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($excludeSlug !== null) {
            $sql .= ' AND slug != :exclude';
            $params[':exclude'] = $excludeSlug;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $original . '-' . $count++;
    }
}


function handle_product_images_upload(array $files, int $productId = 0): array {
    $uploadDir = dirname(__DIR__) . '/public/assets/images/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Normalize the PHP $_FILES array if multiple files were uploaded
    $normalizedFiles = [];
    if (is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            $normalizedFiles[] = [
                'name'     => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i]
            ];
        }
    } else {
        $normalizedFiles[] = $files;
    }

    $imagePaths = [];
    $errors = [];
    
    foreach ($normalizedFiles as $index => $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image $index upload error: " . $file['error'];
            continue;
        }
        
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp'])) {
            $errors[] = "Image $index: Invalid file type. Use JPG, PNG, WebP.";
            continue;
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            $errors[] = "Image $index: File too large (max 10MB).";
            continue;
        }
        
        $fileName = 'prod_' . uniqid() . '_' . time() . '.' . $fileExt;
        $destPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $processedPath = process_product_image_suite('assets/images/products/' . $fileName);
            $imagePaths[] = $processedPath;
            
            // Save to DB if product ID provided
            if ($productId > 0) {
                add_product_media($productId, $processedPath, 'image', '', $index);
            }
        } else {
            $errors[] = "Failed to save image $index.";
        }
    }
    
    return ['success' => empty($errors), 'paths' => $imagePaths, 'errors' => $errors];
}

function create_product(array $data): bool {
    $db = get_db();
    if (!$db) return false;

    $slug = ensure_unique_slug(slugify($data['slug'] ?? $data['name'] ?? ''));

    $stmt = $db->prepare('
        INSERT INTO products (name, slug, price, stock, status, description, excerpt, category, image)
        VALUES (:name, :slug, :price, :stock, :status, :description, :excerpt, :category, :image)
    ');

    return $stmt->execute([
        ':slug' => $slug,
        ':name' => sanitize($data['name']),
        ':price' => (float)($data['price'] ?? 0),
        ':stock' => (int)($data['stock'] ?? 0),
        ':status' => sanitize($data['status'] ?? 'draft'),
        ':description' => $data['description'],
        ':excerpt' => sanitize(substr(strip_tags($data['description']), 0, 150)),
        ':category' => $data['category'] ?? 'uncategorized',
        ':image' => $data['image']
    ]);
}

function update_product(int $id, array $data): bool
{
    $db = get_db();
    if (!$db) return false;

    $stmt = $db->prepare('SELECT slug, image FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $original = $stmt->fetch();
    if (!$original) return false;

    $originalSlug = $original['slug'];

    $slug = $data['slug'] === $originalSlug ? $originalSlug : ensure_unique_slug(slugify($data['slug']), $originalSlug);

    $stmt = $db->prepare('
        UPDATE products 
        SET name = :name, slug = :slug, price = :price, stock = :stock, status = :status, 
            description = :description, excerpt = :excerpt, category = :category, image = :image
        WHERE id = :id
    ');

    $success = $stmt->execute([
        ':id' => $id,
        ':name' => sanitize($data['name']),
        ':slug' => $slug,
        ':price' => (float)($data['price'] ?? 0),
        ':stock' => (int)($data['stock'] ?? 0),
        ':status' => sanitize($data['status'] ?? 'draft'),
        ':description' => $data['description'],
        ':excerpt' => sanitize(substr(strip_tags($data['description']), 0, 150)),
        ':category' => $data['category'] ?? 'uncategorized',
        ':image' => $data['image']
    ]);

    // If update succeeded and image changed, delete old files
    if ($success && !empty($original['image']) && $original['image'] !== $data['image']) {
        delete_product_image_files($original['image']);
    }

    return $success;
}

/**
 * Deletes product image files and their generated variants (thumb, medium, large, webp).
 */
function delete_product_image_files(?string $image_path): void
{
    if (empty($image_path) || strpos($image_path, 'placeholder.svg') !== false || strpos($image_path, 'http') === 0) {
        return;
    }

    $root = str_replace('\\', '/', dirname(__DIR__));
    $full_path = $root . '/' . ltrim($image_path, '/');
    $path_info = pathinfo($full_path);
    
    // Extract base name without size suffixes (e.g., -thumb, -medium, -large)
    $base_name = preg_replace('/-(thumb|medium|large)$/', '', $path_info['filename']);
    $pattern = $path_info['dirname'] . '/' . $base_name . '*';

    foreach (glob($pattern) as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

function validate_product_data(array $data): array {
    $errors = [];
    
    if (empty(trim($data['name'] ?? ''))) $errors['name'] = 'Product name is required.';
    if (empty(trim($data['category'] ?? ''))) $errors['category'] = 'Category is required.';
    if (!is_numeric($data['price'] ?? 0) || ($data['price'] ?? 0) <= 0) $errors['price'] = 'Valid price is required.';
    if (($data['stock'] ?? 0) < 0) $errors['stock'] = 'Stock cannot be negative.';
    if (empty($data['images'] ?? []) && empty($_FILES['images']['name'][0])) $errors['images'] = 'At least 1 image is required.';
    
    return $errors;
}


function update_order_status(int $id, string $status): bool
{
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('UPDATE orders SET status = :status WHERE id = :id');
    return $stmt->execute([':status' => $status, ':id' => $id]);
}

function delete_product(string $slug): bool
{
    $db = get_db();
    if (!$db) return false;
    
    $db->beginTransaction();
    
    try {
        // Archive: set deleted_at
        $stmt = $db->prepare('UPDATE products SET deleted_at = NOW() WHERE slug = :slug AND deleted_at IS NULL');
        $stmt->execute([':slug' => $slug]);

        // Check if a row was actually updated (i.e., product was not already archived)
        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            return false; // No product was archived or it was already archived
        }
        $db->commit();
        return true; // Product successfully archived
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function get_archived_products(): array
{
    $db = get_db();
    if (!$db) return [];
    
    $stmt = $db->prepare('SELECT * FROM products WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');
    try {
        $stmt->execute();
        $archived = $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
    return $archived;
}

function restore_product(string $slug): bool
{
    $db = get_db();
    if (!$db) return false;
    
    $stmt = $db->prepare('UPDATE products SET deleted_at = NULL WHERE slug = :slug AND deleted_at IS NOT NULL');
    return $stmt->execute([':slug' => $slug]);
}

function permanently_delete_product(string $slug): bool
{
    $db = get_db();
    if (!$db) return false;
    
    $db->beginTransaction();
    
    try {
        // Fetch image path before deleting the record
        $stmt = $db->prepare('SELECT image FROM products WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);
        $image = $stmt->fetchColumn();

        $stmt = $db->prepare('DELETE FROM products WHERE slug = :slug AND deleted_at IS NOT NULL');
        $stmt->execute([':slug' => $slug]);
        
        $db->commit();

        if ($image) delete_product_image_files($image);
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

function create_category(string $slug, string $name): bool
{
    $db = get_db();
    if (!$db) {
        return false;
    }
    $slug = slugify($slug);
    if ($slug === '' || trim($name) === '') {
        return false;
    }

    // Check if slug already exists to provide better feedback
    $check = $db->prepare('SELECT COUNT(*) FROM categories WHERE slug = ?');
    $check->execute([$slug]);
    if ($check->fetchColumn() > 0) return false;

    $stmt = $db->prepare('INSERT INTO categories (slug, name) VALUES (:slug, :name)');
    return $stmt->execute([':slug' => $slug, ':name' => sanitize($name)]);
}

function update_category(string $originalSlug, string $name, string $slug): bool
{
    $db = get_db();
    if (!$db) return false;

    $newSlug = slugify($slug ?: $name);
    if ($newSlug === '' || trim($name) === '') return false;

    if ($originalSlug === 'uncategorized' && $newSlug !== 'uncategorized') {
        return false;
    }

    try {
        $db->beginTransaction();
        $stmt = $db->prepare('UPDATE categories SET name = :name, slug = :new_slug WHERE slug = :old_slug');
        $stmt->execute([':name' => sanitize($name), ':new_slug' => $newSlug, ':old_slug' => $originalSlug]);
        
        return $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function delete_category(string $slug): bool
{
    $db = get_db();
    if (!$db) return false;

    // Safety check to prevent deleting the default category
    if ($slug === 'uncategorized') {
        return false;
    }

    try {
        $db->beginTransaction();

        // World-class safety: Reassign products to 'uncategorized' before deleting
        $stmt = $db->prepare('UPDATE products SET category = "uncategorized" WHERE category = :slug');
        $stmt->execute([':slug' => $slug]);

        // Delete the category record
        $stmt = $db->prepare('DELETE FROM categories WHERE slug = :slug');
        $executed = $stmt->execute([':slug' => $slug]);

        $db->commit();
        return $executed;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function get_reviews(bool $onlyApproved = false): array
{
    $db = get_db();
    if ($db) {
        try {
            $sql = 'SELECT r.*, p.name as product_name, p.slug as product_slug 
                    FROM reviews r 
                    LEFT JOIN products p ON r.product_id = p.id';
            if ($onlyApproved) $sql .= ' WHERE r.is_approved = 1';
            $sql .= ' ORDER BY r.created_at DESC';
            return $db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            // Fall back to default data if the table does not exist
        }
    }
    return [
        ['id' => 1, 'user_id' => 1, 'user_name' => 'Julianne Smith', 'rating' => 5, 'comment' => 'The quality of the linen set exceeded my expectations. Fast shipping too!', 'image' => null, 'is_approved' => 1, 'created_at' => '2023-11-15 10:00:00', 'product_name' => 'Linen Bedroom Set', 'product_slug' => 'linen-bedroom-set'],
        ['id' => 2, 'user_id' => 2, 'user_name' => 'Marcus V.', 'rating' => 4, 'comment' => 'Great design on the leather tote. A bit smaller than I thought, but beautiful.', 'image' => null, 'is_approved' => 1, 'created_at' => '2023-11-10 14:30:00', 'product_name' => 'Artisan Leather Tote', 'product_slug' => 'artisan-leather-tote']
    ];
}

function submit_review(string $name, int $rating, string $comment, ?string $image = null): bool
{
    $db = get_db();
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare('INSERT INTO reviews (user_name, rating, comment, image, is_approved) VALUES (:name, :rating, :comment, :image, 0)');
    return $stmt->execute([
        ':name' => sanitize($name),
        ':rating' => (int)$rating,
        ':comment' => sanitize($comment),
        ':image' => $image
    ]);
}

function render_stars(float $rating): string
{
    $fullStars = floor($rating);
    $emptyStars = 5 - ceil($rating);
    $stars = str_repeat('★', $fullStars);
    $empty = str_repeat('☆', $emptyStars);
    return '<span class="stars">' . $stars . $empty . '</span>';
}

function delete_review(int $id): bool
{
    $db = get_db();
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare('DELETE FROM reviews WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function update_order_shipping_address(int $orderId, int $userId, array $data): bool
{
    $db = get_db();
    if (!$db) return false;

    $stmt = $db->prepare('UPDATE orders SET recipient_name = :recipient, address = :address, city = :city, state = :state, zip_code = :zip, phone_number = :phone WHERE id = :id AND user_id = :uid AND (status = "placed" OR status = "pending" OR status = "processing")');
    
    return $stmt->execute([
        ':recipient' => sanitize($data['recipient_name']),
        ':address' => sanitize($data['address']),
        ':city' => sanitize($data['city']),
        ':state' => sanitize($data['state']),
        ':zip' => sanitize($data['zip_code']),
        ':phone' => sanitize($data['phone_number'] ?? ''),
        ':id' => $orderId,
        ':uid' => $userId
    ]);
}

/**
 * ============================================================================
 * VARIATIONS & ADVANCED PRODUCT FEATURES
 * ============================================================================
 */

function publish_product(int $productId): bool {
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('UPDATE products SET status = "published" WHERE id = :id AND status = "draft"');
    return $stmt->execute([':id' => $productId]);
}

function save_product_variations(int $productId, array $variationData): bool {
    $db = get_db();
    if (!$db) return false;
    
    $db->beginTransaction();
    try {
        // Clear existing variation combos for this product
        $stmt = $db->prepare('DELETE FROM variation_combos WHERE product_id = :pid');
        $stmt->execute([':pid' => $productId]);
        
        // Save new combos
        foreach ($variationData as $combo) {
            save_variation_combo($productId, $combo['options'], $combo['price'], $combo['stock'], $combo['image'] ?? null);
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function get_product_images(int $productId): array
{
    $db = get_db();
    if (!$db) return [];
    $stmt = $db->prepare('SELECT * FROM product_images WHERE product_id = :pid ORDER BY sort_order ASC, id ASC');
    $stmt->execute([':pid' => $productId]);
    return $stmt->fetchAll();
}


function get_product_variations(int $productId): array
{
    $db = get_db();
    if (!$db) return [];
    $stmt = $db->prepare('SELECT * FROM variations WHERE product_id = :pid ORDER BY sort_order ASC, id ASC');
    $stmt->execute([':pid' => $productId]);
    $variations = $stmt->fetchAll();

    foreach ($variations as &$variation) {
        $variation['options'] = get_variation_options($variation['id']);
    }

    return $variations;
}

function get_variation_options(int $variationId): array
{
    $db = get_db();
    if (!$db) return [];
    $stmt = $db->prepare('SELECT * FROM variation_options WHERE variation_id = :vid ORDER BY sort_order ASC, id ASC');
    $stmt->execute([':vid' => $variationId]);
    return $stmt->fetchAll();
}

function get_variation_items(int $productId): array
{
    $db = get_db();
    if (!$db) return [];
    $stmt = $db->prepare('SELECT * FROM variation_items WHERE product_id = :pid AND is_active = 1');
    $stmt->execute([':pid' => $productId]);
    return $stmt->fetchAll();
}

function get_variation_item_by_combination(int $productId, array $combination): ?array
{
    $db = get_db();
    if (!$db) return null;
    
    // Sort the combination to match database storage
    ksort($combination);
    $jsonCombination = json_encode($combination);
    
    $stmt = $db->prepare('SELECT * FROM variation_items WHERE product_id = :pid AND option_combination = :combo AND is_active = 1 LIMIT 1');
    $stmt->execute([':pid' => $productId, ':combo' => $jsonCombination]);
    return $stmt->fetch() ?: null;
}

function get_product_reviews(int $productId, bool $onlyApproved = true): array
{
    $db = get_db();
    if (!$db) return [];
    
    $sql = 'SELECT r.*, u.name as user_name FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = :pid';
    if ($onlyApproved) {
        $sql .= ' AND r.is_approved = 1';
    }
    $sql .= ' ORDER BY r.created_at DESC';
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':pid' => $productId]);
    return $stmt->fetchAll();
}

function get_product_rating_summary(int $productId): array
{
    $reviews = get_product_reviews($productId, true);
    
    if (empty($reviews)) {
        return [
            'average' => 0,
            'total' => 0,
            'breakdown' => [5 => ['count' => 0, 'percentage' => 0], 4 => ['count' => 0, 'percentage' => 0], 3 => ['count' => 0, 'percentage' => 0], 2 => ['count' => 0, 'percentage' => 0], 1 => ['count' => 0, 'percentage' => 0]]
        ];
    }
    
    $total = count($reviews);
    $sum = array_sum(array_column($reviews, 'rating'));
    $average = round($sum / $total, 1);
    
    $breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($reviews as $review) {
        $breakdown[$review['rating']]++;
    }
    
    // Convert to array with percentage
    $breakdownWithPercent = [];
    foreach ($breakdown as $rating => $count) {
        $breakdownWithPercent[$rating] = [
            'count' => $count,
            'percentage' => $total > 0 ? round(($count / $total) * 100) : 0
        ];
    }
    
    return [
        'average' => $average,
        'total' => $total,
        'breakdown' => $breakdownWithPercent
    ];
}

function get_recommended_products(int $productId, int $limit = 8): array
{
    $db = get_db();
    if (!$db) return [];
    
    // Get current product category
$stmt = $db->prepare('SELECT category FROM products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) return [];
    
    // Get products from same category, excluding current product
$stmt = $db->prepare('SELECT * FROM products WHERE category = :category AND id != :id AND deleted_at IS NULL ORDER BY sold_count DESC, id DESC LIMIT :limit');
$stmt->bindValue(':category', $product['category']);
    $stmt->bindValue(':id', $productId);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // If not enough products in same category, fill with random products
    if (count($products) < $limit) {
        $remaining = $limit - count($products);
        $excludeIds = array_column($products, 'id');
        $excludeIds[] = $productId;
        
        $placeholders = str_repeat('?,', count($excludeIds) - 1) . '?';
        $sql = "SELECT * FROM products WHERE id NOT IN ($placeholders) AND deleted_at IS NULL ORDER BY RAND() LIMIT ?";
        $stmt = $db->prepare($sql);

        $paramIndex = 1;
        foreach ($excludeIds as $id) {
            $stmt->bindValue($paramIndex++, $id, PDO::PARAM_INT);
        }
        // Explicitly bind the LIMIT parameter as an integer to prevent SQL syntax errors
        $stmt->bindValue($paramIndex, (int)$remaining, PDO::PARAM_INT);
        $stmt->execute();

        $randomProducts = $stmt->fetchAll();
        $products = array_merge($products, $randomProducts);
    }
    
    return array_slice($products, 0, $limit);
}

function submit_product_review(int $productId, int $userId, int $rating, string $comment, ?string $image = null): bool
{
    $db = get_db();
    if (!$db) return false;
    
    // Check if user has purchased this product
    $stmt = $db->prepare('
        SELECT COUNT(*) FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = :uid AND oi.product_id = :pid AND o.status IN ("completed", "shipped")
    ');
    $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    $hasPurchased = $stmt->fetchColumn() > 0;
    
    if (!$hasPurchased) {
        return false; // User hasn't purchased this product
    }
    
    $stmt = $db->prepare('INSERT INTO reviews (product_id, user_id, rating, comment, image, is_approved) VALUES (:pid, :uid, :rating, :comment, :image, 0)');
    return $stmt->execute([
        ':pid' => $productId,
        ':uid' => $userId,
        ':rating' => $rating,
        ':comment' => sanitize($comment),
        ':image' => $image
    ]);
}

function add_to_cart_with_variation(string $slug, int $quantity = 1, ?int $variationItemId = null): array
{
    $product = get_product_by_slug($slug);
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found.'];
    }
    
    // Check if product has variations and variation is required
    $variations = get_product_variations($product['id']);
    if (!empty($variations) && $variationItemId === null) {
        return ['success' => false, 'message' => 'Please select product options.'];
    }
    
    // If variation selected, get variation details
    $variationItem = null;
    $effectivePrice = $product['price'];
    $effectiveStock = $product['stock'];
    
    if ($variationItemId !== null) {
        $db = get_db();
        if (!$db) return ['success' => false, 'message' => 'Database connection failed.'];
        
        $stmt = $db->prepare('SELECT * FROM variation_items WHERE id = :id AND product_id = :pid AND is_active = 1 LIMIT 1');
        $stmt->execute([':id' => $variationItemId, ':pid' => $product['id']]);
        $variationItem = $stmt->fetch();
        
        if (!$variationItem) {
            return ['success' => false, 'message' => 'Invalid product variation.'];
        }
        
        $effectivePrice = $variationItem['price'] ?? $product['price'];
        $effectiveStock = $variationItem['stock'] ?? $product['stock'];
    }
    
    if ($effectiveStock < $quantity) {
        return ['success' => false, 'message' => 'Not enough stock available.'];
    }
    
    // Use session-based cart with variation support
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $cartKey = $slug . ($variationItemId ? "_var{$variationItemId}" : '');
    $currentQty = $_SESSION['cart'][$cartKey] ?? 0;
    
    if (($currentQty + $quantity) > $effectiveStock) {
        return ['success' => false, 'message' => 'Maximum stock reached.'];
    }
    
    $_SESSION['cart'][$cartKey] = $currentQty + $quantity;
    return ['success' => true, 'message' => htmlspecialchars($product['name']) . ' added to cart.'];
}

function get_cart_items_with_variations(): array
{
    $items = $_SESSION['cart'] ?? [];
    $cartItems = [];
    
    $db = get_db();
    if (!$db) return [];
    
    foreach ($items as $cartKey => $quantity) {
        // Parse cart key: slug or slug_var{id}
        if (strpos($cartKey, '_var') !== false) {
            list($slug, $varPart) = explode('_var', $cartKey, 2);
            $variationItemId = (int)$varPart;
        } else {
            $slug = $cartKey;
            $variationItemId = null;
        }
        
        $product = get_product_by_slug($slug);
        if (!$product) continue;
        
        $variationItem = null;
        $effectivePrice = $product['price'];
        $variationDetails = '';
        
        if ($variationItemId) {
            $stmt = $db->prepare('SELECT * FROM variation_items WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $variationItemId]);
            $variationItem = $stmt->fetch();
            
            if ($variationItem) {
                $combination = json_decode($variationItem['option_combination'], true);
                $variationDetails = implode(', ', $combination);
                $effectivePrice = $variationItem['price'] ?? $product['price'];
            }
        }
        
        $cartItems[] = [
            'cart_key' => $cartKey,
            'product' => $product,
            'variation_item' => $variationItem,
            'variation_details' => $variationDetails,
            'quantity' => $quantity,
            'price' => $effectivePrice,
            'subtotal' => $effectivePrice * $quantity,
        ];
    }
    
    return $cartItems;
}

function approve_review(int $id): bool
{
    $db = get_db();
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare('UPDATE reviews SET is_approved = 1 WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function get_total_revenue(): float
{
    $db = get_db();
    if ($db) {
        return (float)$db->query('SELECT SUM(total) FROM orders WHERE status != "cancelled"')->fetchColumn();
    }
    return 456.00; // Fallback demo revenue
}

function add_product_image(int $productId, string $path, bool $isPrimary = false, int $sortOrder = 0): bool {
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)');
    return $stmt->execute([$productId, $path, $isPrimary, $sortOrder]);
}

function get_product_variation_types(int $productId): array {
    $db = get_db();
    if (!$db) return [];
    $stmt = $db->prepare('SELECT * FROM variation_types WHERE product_id = ? ORDER BY id');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function add_product_variation_type(int $productId, string $name): bool {
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('INSERT INTO variation_types (product_id, name) VALUES (?, ?)');
    return $stmt->execute([$productId, $name]);
}

function add_variation_option(int $typeId, string $value): bool {
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('INSERT INTO variation_options (variation_id, value) VALUES (?, ?)');
    return $stmt->execute([$typeId, $value]);
}

function get_product_variation_combos(int $productId): array {
    $db = get_db();
    if (!$db) return [];
    $stmt = $db->prepare('SELECT * FROM variation_combos WHERE product_id = ?');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function save_variation_combo(int $productId, array $options, float $price, int $stock, ?string $image = null): bool {
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('INSERT INTO variation_combos (product_id, options, price, stock, image_path) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price), stock = VALUES(stock), image_path = VALUES(image_path)');
    return $stmt->execute([$productId, json_encode($options), $price, $stock, $image]);
}


function update_product_stock(int $id, int $stock): bool
{
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('UPDATE products SET stock = :stock WHERE id = :id');
    return $stmt->execute([':stock' => $stock, ':id' => $id]);
}

/**
 * Address Book Management Functions
 */

function get_user_addresses(int $userId): array
{
    $db = get_db();
    if (!$db) return [];
    try {
        $stmt = $db->prepare('SELECT * FROM user_addresses WHERE user_id = :uid ORDER BY is_default DESC, created_at DESC');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // If table doesn't exist yet, return empty array instead of crashing
        error_log("Address Book Error: " . $e->getMessage());
        return [];
    }
}

function get_address_by_id(int $addressId, int $userId): ?array
{
    $db = get_db();
    if (!$db) return null;
    $stmt = $db->prepare('SELECT * FROM user_addresses WHERE id = :id AND user_id = :uid LIMIT 1');
    $stmt->execute([':id' => $addressId, ':uid' => $userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Retrieves the user's marked primary address.
 */
function get_default_user_address(int $userId): ?array
{
    $db = get_db();
    if (!$db) return null;
    try {
        $stmt = $db->prepare('SELECT * FROM user_addresses WHERE user_id = :uid AND is_default = 1 LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        // Table might not exist or connection lost
        error_log("Default Address Lookup Error: " . $e->getMessage());
        return null;
    }
}

/**
 * ============================================================================
 * ADDRESS BOOK REPOSITORY
 * ============================================================================
 */

function save_user_address(int $userId, array $data, ?int $addressId = null): array
{
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database connection failed.'];

    // Basic validation
    if (empty(trim($data['label'] ?? ''))) {
        return ['success' => false, 'message' => 'Address label is required.'];
    }
    if (empty(trim($data['recipient_name'] ?? ''))) {
        return ['success' => false, 'message' => 'Recipient name is required.'];
    }
    if (empty(trim($data['address'] ?? ''))) {
        return ['success' => false, 'message' => 'Street address is required.'];
    }
    if (empty(trim($data['city'] ?? ''))) {
        return ['success' => false, 'message' => 'City is required.'];
    }
    if (empty(trim($data['state'] ?? ''))) {
        return ['success' => false, 'message' => 'State/Province is required.'];
    }
    if (empty(trim($data['zip_code'] ?? ''))) {
        return ['success' => false, 'message' => 'Zip Code is required.'];
    }

    $is_default = !empty($data['is_default']) ? 1 : 0;

    try {
        // Professional touch: If this is the user's first address, force it to be default
        $stmt = $db->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $addressCount = (int)$stmt->fetchColumn();
        
        if ($addressCount === 0) {
            $is_default = 1;
        }

        $db->beginTransaction();

        // If this is set as default, remove default status from all other user addresses
        if ($is_default) {
            $stmt = $db->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :uid');
            $stmt->execute([':uid' => $userId]);
        }

        if ($addressId) {
            // Update existing
            $stmt = $db->prepare('UPDATE user_addresses SET label = :label, recipient_name = :recipient, phone_number = :phone, address = :address, city = :city, state = :state, zip_code = :zip, is_default = :is_default WHERE id = :id AND user_id = :uid');
            $params = [':id' => $addressId, ':uid' => $userId];
        } else {
            // Create new
            $stmt = $db->prepare('INSERT INTO user_addresses (user_id, label, recipient_name, phone_number, address, city, state, zip_code, is_default) VALUES (:uid, :label, :recipient, :phone, :address, :city, :state, :zip, :is_default)');
            $params = [':uid' => $userId];
        }

        $params = array_merge($params, [
            ':label' => sanitize(trim($data['label'] ?? '') ?: 'Home'),
            ':recipient' => sanitize($data['recipient_name']),
            ':phone' => sanitize($data['phone_number'] ?? ''),
            ':address' => sanitize($data['address']),
            ':city' => sanitize($data['city']),
            ':state' => sanitize($data['state']),
            ':zip' => sanitize($data['zip_code']),
            ':is_default' => $is_default
        ]);

        $success = $stmt->execute($params);
        $db->commit();
        return ['success' => $success, 'message' => $success ? 'Address saved successfully.' : 'Failed to save address.'];
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error saving user address: " . $e->getMessage()); // Log the error for debugging
        return ['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()];
    }
}

function delete_user_address(int $addressId, int $userId): bool
{
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('DELETE FROM user_addresses WHERE id = :id AND user_id = :uid');
    return $stmt->execute([':id' => $addressId, ':uid' => $userId]);
}

function set_default_user_address(int $addressId, int $userId): bool
{
    $db = get_db();
    if (!$db) return false;
    try {
        $db->beginTransaction();
        $db->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :uid')->execute([':uid' => $userId]);
        $db->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = :id AND user_id = :uid')->execute([':id' => $addressId, ':uid' => $userId]);
        return $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function delete_all_user_addresses(int $userId): bool
{
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare('DELETE FROM user_addresses WHERE user_id = :uid');
    return $stmt->execute([':uid' => $userId]);
}

/**
 * Validates a Philippine phone number format.
 * Matches: +639123456789, 09123456789, or 9123456789
 */
function is_valid_ph_phone(string $phone): bool
{
    // Remove all non-digit characters except the leading plus
    $clean = preg_replace('/(?<!^)\+|[^\d+]/', '', $phone);
    return (bool)preg_match('/^(\+63|0)?9\d{9}$/', $clean);
}

/**
 * Returns the current stage index (0-3) for order tracking.
 */
function get_order_status_stage(string $status): int
{
    $map = [
        'placed' => 0,
        'pending' => 0,
        'processing' => 1,
        'shipped' => 2,
        'delivered' => 3,
        'completed' => 3,
    ];
    return $map[strtolower($status)] ?? 0;
}
