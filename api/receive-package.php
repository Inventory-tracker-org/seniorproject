<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Use the same authentication and minimum permission as create-package.php.
check_api_auth($dbh, 'low');

if (
    !isset($_POST['barcode']) ||
    !is_string($_POST['barcode']) ||
    (isset($_POST['description']) && !is_string($_POST['description']))
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid receiving fields.']);
    exit;
}

$barcode = strtoupper(trim($_POST['barcode']));
$description = trim($_POST['description'] ?? '');

if (preg_match('/\AFP[0-9]{5}\z/', $barcode) !== 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Enter a freight barcode in the format FP12345.'
    ]);
    exit;
}

$descriptionLength = preg_match_all('/./us', $description);

if ($descriptionLength === false || $descriptionLength > 250) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'The description must be valid text of 250 characters or fewer.'
    ]);
    exit;
}

// Description is nullable in the database/API, but required by the app.
$description = $description === '' ? null : $description;

try {
    // Insert only these five columns. Other columns retain database defaults.
    // A primary-key conflict never updates any existing package or freight.
    $insertStmt = $dbh->prepare(
        "INSERT INTO packages (
             barcode,
             delivered_status,
             received_date,
             package_type,
             description
         ) VALUES (
             :barcode,
             'received',
             NOW(),
             'freight',
             :description
         )
         ON CONFLICT (barcode) DO NOTHING
         RETURNING barcode, delivered_status, received_date, package_type, description"
    );

    $insertStmt->bindValue(':barcode', $barcode, PDO::PARAM_STR);
    $insertStmt->bindValue(
        ':description',
        $description,
        $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR
    );
    $insertStmt->execute();
    $freight = $insertStmt->fetch(PDO::FETCH_ASSOC);
    $alreadyReceived = false;

    if (!$freight) {
        $checkStmt = $dbh->prepare(
            'SELECT barcode, delivered_status, received_date, package_type, description
             FROM packages
             WHERE barcode = :barcode'
        );
        $checkStmt->execute(['barcode' => $barcode]);
        $freight = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // A retry after a lost response succeeds without changing the row.
        if (
            !$freight ||
            $freight['package_type'] !== 'freight' ||
            $freight['delivered_status'] !== 'received' ||
            $freight['description'] !== $description
        ) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => $barcode . ' already exists. Its existing information was not changed.'
            ]);
            exit;
        }

        $alreadyReceived = true;
    }

    http_response_code($alreadyReceived ? 200 : 201);
    echo json_encode([
        'success' => true,
        'message' => $alreadyReceived
            ? 'Freight was already received. Its original receipt was preserved.'
            : 'Freight received successfully.',
        'already_received' => $alreadyReceived,
        'freight' => $freight
    ]);
} catch (PDOException $e) {
    error_log('Freight receiving database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to save the freight receipt.']);
} catch (Throwable $e) {
    error_log('Freight receiving server error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to receive the freight.']);
}
