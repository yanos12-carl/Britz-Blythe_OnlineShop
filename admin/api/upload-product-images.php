<?php
/**
 * Image Upload API Endpoint
 * Handles multiple image uploads with validation and optimization
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/products-api.php';

// Only allow authenticated admins
require_admin();

// Set response header
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate file uploads
    if (empty($_FILES['images'])) {
        throw new Exception('No files uploaded');
    }

    $uploadDir = __DIR__ . '/../public/assets/images/products/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    $uploadedFiles = [];
    $errors = [];

    $files = $_FILES['images'];
    
    // Handle both single and multiple file uploads
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;
    
    for ($i = 0; $i < $fileCount; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
        $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];

        // Skip if no file
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        // Validate upload
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading $name: Upload error code $error";
            continue;
        }

        if ($size > $maxFileSize) {
            $errors[] = "$name exceeds maximum file size of 10MB";
            continue;
        }

        if (!in_array($type, $allowedTypes)) {
            $errors[] = "$name has unsupported file type";
            continue;
        }

        // Generate unique filename
        $fileExt = pathinfo($name, PATHINFO_EXTENSION);
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $safeFileName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $baseName);
        $uniqueFileName = $safeFileName . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $fileExt;
        $filePath = $uploadDir . $uniqueFileName;

        // Move uploaded file
        if (!move_uploaded_file($tmpName, $filePath)) {
            $errors[] = "Failed to save $name";
            continue;
        }

        // Optimize image if it's an image
        $mediaType = strpos($type, 'image') !== false ? 'image' : 'video';
        $relativePath = 'assets/images/products/' . $uniqueFileName;

        if ($mediaType === 'image') {
            // Resize and optimize image
            $processedPath = process_product_image_suite($relativePath);
        } else {
            $processedPath = $relativePath;
        }

        $uploadedFiles[] = [
            'url' => $processedPath,
            'type' => $mediaType,
            'name' => $name,
            'size' => $size,
        ];
    }

    // Return response
    if (empty($uploadedFiles) && !empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors,
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'files' => $uploadedFiles,
            'errors' => $errors,
            'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
?>
