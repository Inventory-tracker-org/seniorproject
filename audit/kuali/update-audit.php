<?php

declare(strict_types=1);

include_once('../../config.php');

header('Content-Type: application/json');

try {
    if (
        !isset($_SESSION['data']) ||
        !is_array($_SESSION['data'])
    ) {
        throw new Exception(
            'No audit data was found in the session.'
        );
    }

    if (
        !isset($_SESSION['info'][5]) ||
        !isset($_SESSION['info'][4])
    ) {
        throw new Exception(
            'Audit information is missing from the session.'
        );
    }

    $audit_id =
        (int)$_SESSION['info'][5];

    $dept_id =
        (string)$_SESSION['info'][4];

    foreach (
        $_SESSION['data']
        as $index => $asset
    ) {
        $status =
            (string)(
                $asset['Tag Status'] ??
                ''
            );

        if (
            in_array(
                $status,
                [
                    'Found',
                    'Extra'
                ],
                true
            )
        ) {
            continue;
        }

        $tag = trim(
            (string)(
                $asset['Tag Number'] ??
                ''
            )
        );

        if ($tag === '') {
            continue;
        }

        //Check-outs are GLOBAL.

        $checkoutSql = "
            SELECT
                asset_notes
            FROM asset_info
            WHERE asset_tag = :tag
              AND asset_notes ILIKE '%CHCKD%'
            LIMIT 1
        ";

        $stmt =
            $dbh->prepare(
                $checkoutSql
            );

        $stmt->execute([
            ':tag' => $tag
        ]);

        $notes =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (
            $notes &&
            !empty($notes['asset_notes'])
        ) {
            $_SESSION['data'][$index]['Found Note'] =
                'Check-Out {' .
                $notes['asset_notes'] .
                '}';

            $_SESSION['data'][$index]['Tag Status'] =
                'Found';

            $_SESSION['data'][$index]['Found Room Number'] =
                'CHCKD';
        }

        $checkoutAuditSql = "
            SELECT
                note,
                dept_id,
                audit_id,
                room_tag
            FROM audited_asset
            WHERE asset_tag = :tag
              AND note ILIKE '%CHCKD%'
            ORDER BY audit_id DESC
            LIMIT 1
        ";

        $stmt =
            $dbh->prepare(
                $checkoutAuditSql
            );

        $stmt->execute([
            ':tag' => $tag
        ]);

        $checkoutAudit =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if ($checkoutAudit) {
            $_SESSION['data'][$index]['Found Note'] =
                'Check-Out {' .
                (
                    $checkoutAudit['note'] ??
                    ''
                ) .
                '}';

            $_SESSION['data'][$index]['Tag Status'] =
                'Found';

            $_SESSION['data'][$index]['Found Room Number'] =
                'CHCKD';
        }

        $foundSql = "
            SELECT
                asset_tag,
                note,
                dept_id,
                audit_id,
                room_tag
            FROM audited_asset
            WHERE asset_tag = :tag
              AND audit_id = :audit_id
              AND dept_id != :dept_id
            LIMIT 1
        ";

        $stmt =
            $dbh->prepare(
                $foundSql
            );

        $stmt->execute([
            ':tag' =>
            $tag,

            ':audit_id' =>
            $audit_id,

            ':dept_id' =>
            $dept_id
        ]);

        $result =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if ($result) {
            $foundNote =
                'Found at ' .
                $result['dept_id'];

            if (
                !empty($result['note'])
            ) {
                $foundNote .=
                    ' {' .
                    $result['note'] .
                    '}';
            }

            $_SESSION['data'][$index]['Found Note'] =
                $foundNote;

            $_SESSION['data'][$index]['Tag Status'] =
                'Found';

            $_SESSION['data'][$index]['Found Room Tag'] =
                $result['room_tag'] ?? '';
        }
    }

    echo json_encode(['status' => 'Ok']);
} catch (PDOException $e) {
    error_log(
        'Audit update database error: ' .
            $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'status' => 'Error',
        'message' =>
        'Database error'
    ]);
} catch (Exception $e) {
    error_log(
        'Audit update error: ' .
            $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'status' => 'Error',
        'message' =>
        $e->getMessage()
    ]);
}

exit;
