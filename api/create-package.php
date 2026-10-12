<?php

declare(strict_types=1);
include("../config.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$currentUser = null;
$isDevelopmentMode = false;

if (!$isDevelopmentMode) {
    $currentUser = check_api_auth($dbh, 'low');
}

function uploadToSupabase(
    array $file,
    string $bucket,
    string $objectPath,
    array $allowedTypes,
    int $maxBytes,
    string $fileLabel
): string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception($fileLabel . ' upload was not successful.');
    }

    $size = isset($file['size']) ? (int) $file['size'] : 0;

    if ($size <= 0) {
        throw new Exception($fileLabel . ' file is empty.');
    }

    if ($size > $maxBytes) {
        throw new Exception($fileLabel . ' size exceeds the 5 MB limit.');
    }

    $tmpFile = (string) ($file['tmp_name'] ?? '');

    if ($tmpFile === '' || !is_uploaded_file($tmpFile)) {
        throw new Exception('Invalid ' . strtolower($fileLabel) . ' upload.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpFile);

    if ($mimeType === false || !isset($allowedTypes[$mimeType])) {
        throw new Exception('Invalid ' . strtolower($fileLabel) . ' type.');
    }

    $fileContents = file_get_contents($tmpFile);

    if ($fileContents === false) {
        throw new Exception('Unable to read the uploaded ' . strtolower($fileLabel) . '.');
    }

    $encodedObjectPath = implode(
        '/',
        array_map('rawurlencode', explode('/', $objectPath))
    );

    $supabaseUrl = rtrim((string) getenv('SB_URL'), '/');
    $secretKey = (string) getenv('SB_SECRET_KEY');

    if ($supabaseUrl === '' || $secretKey === '') {
        throw new Exception('Supabase storage configuration is missing.');
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL =>
        $supabaseUrl .
            '/storage/v1/object/' .
            rawurlencode($bucket) .
            '/' .
            $encodedObjectPath,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'apikey: ' . $secretKey,
            'Content-Type: ' . $mimeType
        ],
        CURLOPT_POSTFIELDS => $fileContents,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        throw new Exception($fileLabel . ' cURL error: ' . $curlError);
    }

    if ($status < 200 || $status >= 300) {
        throw new Exception(
            $fileLabel . ' upload failed. HTTP ' . $status . ': ' . $response
        );
    }

    return $objectPath;
}

function deleteSupabaseObject(string $bucket, ?string $objectPath): void
{
    if ($objectPath === null || $objectPath === '') {
        return;
    }

    $supabaseUrl = rtrim((string) getenv('SB_URL'), '/');
    $secretKey = (string) getenv('SB_SECRET_KEY');

    if ($supabaseUrl === '' || $secretKey === '') {
        return;
    }

    $encodedObjectPath = implode(
        '/',
        array_map('rawurlencode', explode('/', $objectPath))
    );

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL =>
        $supabaseUrl .
            '/storage/v1/object/' .
            rawurlencode($bucket) .
            '/' .
            $encodedObjectPath,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'apikey: ' . $secretKey
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        error_log(
            'Supabase cleanup failed for ' .
                $bucket .
                '/' .
                $objectPath .
                '. HTTP ' .
                $status .
                '. cURL: ' .
                $curlError .
                '. Response: ' .
                (string) $response
        );
    }
}

$photoURL = null;
$sigURL = null;

try {
    if (
        !$isDevelopmentMode &&
        (
            !is_array($currentUser) ||
            empty($currentUser['f_name']) ||
            empty($currentUser['l_name'])
        )
    ) {
        throw new Exception('Unable to determine the authenticated user.');
    }

    $deliveredBy = $isDevelopmentMode
        ? 'Development User'
        : trim(
            (string) $currentUser['f_name'] .
                ' ' .
                (string) $currentUser['l_name']
        );

    $date = trim((string) ($_POST['date'] ?? ''));
    $time = trim((string) ($_POST['time'] ?? ''));

    $comments = isset($_POST['comment'])
        ? strip_tags(trim((string) $_POST['comment']))
        : '';

    $deliveredTo = isset($_POST['lastName'])
        ? strip_tags(trim((string) $_POST['lastName']))
        : '';

    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== ''
        ? (string) $_POST['latitude']
        : null;

    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== ''
        ? (string) $_POST['longitude']
        : null;

    /*
     * New batch format:
     * packages = JSON array of barcode/carrier objects.
     *
     * The single-package fallback keeps this endpoint
     * compatible while the app is being updated.
     */
    $packages = [];

    if (isset($_POST['packages']) && trim((string) $_POST['packages']) !== '') {
        $decodedPackages = json_decode((string) $_POST['packages'], true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedPackages)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid packages JSON.'
            ]);
            exit;
        }

        $packages = $decodedPackages;
    } else {
        $singleBarcode = trim((string) ($_POST['barcode'] ?? ''));

        if ($singleBarcode !== '') {
            $packages[] = [
                'barcode' => $singleBarcode,
                'carrier' => strtolower(
                    trim((string) ($_POST['carrier'] ?? 'unknown'))
                )
            ];
        }
    }

    if (count($packages) === 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No packages were provided.'
        ]);
        exit;
    }

    if ($deliveredTo === '' || $deliveredBy === '' || $date === '' || $time === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required delivery fields.'
        ]);
        exit;
    }

    $allowedCarriers = [
        'usps',
        'fedex',
        'ups',
        'amazon',
        'gofo',
        'ontrac',
        'gls',
        'custom',
        'unknown'
    ];

    $normalizedPackages = [];
    $seenBarcodes = [];

    foreach ($packages as $index => $package) {
        if (!is_array($package)) {
            throw new Exception('Package entry ' . ($index + 1) . ' is invalid.');
        }

        $barcode = trim((string) ($package['barcode'] ?? ''));
        $carrier = strtolower(
            trim((string) ($package['carrier'] ?? 'unknown'))
        );

        if ($barcode === '') {
            throw new Exception(
                'Package entry ' . ($index + 1) . ' is missing its barcode.'
            );
        }

        if (!in_array($carrier, $allowedCarriers, true)) {
            $carrier = 'unknown';
        }

        if (isset($seenBarcodes[$barcode])) {
            continue;
        }

        $seenBarcodes[$barcode] = true;

        $normalizedPackages[] = [
            'barcode' => $barcode,
            'carrier' => $carrier
        ];
    }

    if (count($normalizedPackages) === 0) {
        throw new Exception('No valid packages remained after validation.');
    }

    /*
     * The first barcode names the one shared photo and
     * signature uploaded for the complete delivery.
     */
    $firstBarcode = $normalizedPackages[0]['barcode'];

    $safeFirstBarcode = preg_replace(
        '/[^A-Za-z0-9_-]/',
        '_',
        $firstBarcode
    );

    if ($safeFirstBarcode === null || $safeFirstBarcode === '') {
        throw new Exception('Unable to create a safe delivery filename.');
    }

    $deliveryIdentifier = time() . '_' . bin2hex(random_bytes(4));

    if (
        isset($_FILES['photo']) &&
        isset($_FILES['photo']['error']) &&
        $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $photo = $_FILES['photo'];

        $photoMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $tmpFile = (string) ($photo['tmp_name'] ?? '');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $tmpFile !== '' ? $finfo->file($tmpFile) : false;

        if ($mimeType === false || !isset($photoMimeTypes[$mimeType])) {
            throw new Exception(
                'Invalid photo type. Only JPG, PNG, and WebP are allowed.'
            );
        }

        $photoPath =
            'delivery-photos/' .
            $safeFirstBarcode .
            '_' .
            $deliveryIdentifier .
            '.' .
            $photoMimeTypes[$mimeType];

        $photoURL = uploadToSupabase(
            $photo,
            'photos-api',
            $photoPath,
            $photoMimeTypes,
            5 * 1024 * 1024,
            'Photo'
        );
    }

    if (
        isset($_FILES['signature']) &&
        isset($_FILES['signature']['error']) &&
        $_FILES['signature']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $signature = $_FILES['signature'];

        $signatureMimeTypes = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg'
        ];

        $tmpFile = (string) ($signature['tmp_name'] ?? '');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $signatureMimeType = $tmpFile !== '' ? $finfo->file($tmpFile) : false;

        if (
            $signatureMimeType === false ||
            !isset($signatureMimeTypes[$signatureMimeType])
        ) {
            throw new Exception(
                'Invalid signature type. Only PNG and JPG are allowed.'
            );
        }

        $signaturePath =
            'delivery-signature/' .
            $safeFirstBarcode .
            '_' .
            $deliveryIdentifier .
            '.' .
            $signatureMimeTypes[$signatureMimeType];

        $sigURL = uploadToSupabase(
            $signature,
            'signatures-api',
            $signaturePath,
            $signatureMimeTypes,
            5 * 1024 * 1024,
            'Signature'
        );
    }

    $checkStmt = $dbh->prepare(
        'SELECT barcode
         FROM packages
         WHERE barcode = :barcode
         LIMIT 1'
    );

    $updateStmt = $dbh->prepare(
        'UPDATE packages
         SET
             delivered_date = :delivered_date,
             delivered_time = :delivered_time,
             delivered_by = :delivered_by,
             delivered_to = :delivered_to,
             comments = :comments,
             delivered_status = :delivered_status,
             signature_path = :signature_path,
             photo_path = :photo_path,
             latitude = :latitude,
             longitude = :longitude,
             carrier = :carrier
         WHERE barcode = :barcode'
    );

    $insertStmt = $dbh->prepare(
        'INSERT INTO packages (
             barcode,
             delivered_date,
             delivered_time,
             delivered_by,
             delivered_to,
             comments,
             delivered_status,
             signature_path,
             photo_path,
             latitude,
             longitude,
             carrier
         )
         VALUES (
             :barcode,
             :delivered_date,
             :delivered_time,
             :delivered_by,
             :delivered_to,
             :comments,
             :delivered_status,
             :signature_path,
             :photo_path,
             :latitude,
             :longitude,
             :carrier
         )'
    );

    $dbh->beginTransaction();

    $insertedCount = 0;
    $updatedCount = 0;
    $processedBarcodes = [];

    foreach ($normalizedPackages as $package) {
        $barcode = $package['barcode'];
        $carrier = $package['carrier'];
        $status = $package['delivered_status'];

        $checkStmt->execute(['barcode' => $barcode]);
        $existingRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

        $commonValues = [
            'barcode' => $barcode,
            'delivered_date' => $date,
            'delivered_time' => $time,
            'delivered_by' => $deliveredBy,
            'delivered_to' => $deliveredTo,
            'comments' => $comments,
            'delivered_status' => $status ?? '',
            'signature_path' => $sigURL,
            'photo_path' => $photoURL,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'carrier' => $carrier
        ];

        if ($existingRow) {
            $updateStmt->execute($commonValues);
            $updatedCount++;
        } else {
            $insertStmt->execute($commonValues);
            $insertedCount++;
        }

        $processedBarcodes[] = $barcode;
    }

    $dbh->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Delivery uploaded successfully.',
        'package_count' => count($processedBarcodes),
        'inserted_count' => $insertedCount,
        'updated_count' => $updatedCount,
        'barcodes' => $processedBarcodes,
        'photo_path' => $photoURL,
        'signature_path' => $sigURL
    ]);
} catch (PDOException $e) {
    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }

    deleteSupabaseObject('photos-api', $photoURL);
    deleteSupabaseObject('signatures-api', $sigURL);

    error_log('Batch package database error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }

    deleteSupabaseObject('photos-api', $photoURL);
    deleteSupabaseObject('signatures-api', $sigURL);

    error_log('Batch package server error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
