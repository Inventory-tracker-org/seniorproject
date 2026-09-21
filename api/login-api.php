<?php

declare(strict_types=1);

include_once __DIR__ . "/../config.php";

header("Content-Type: application/json; charset=utf-8");

function sendJson(int $statusCode, array $response): never
{
    http_response_code($statusCode);
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");

    sendJson(405, [
        "success" => false,
        "error" => "Method Not Allowed"
    ]);
}

try {
    /*
    $email = trim($_POST["email"] ?? "");
    $pw = $_POST["pw"] ?? "";
*/
    $input = json_decode(file_get_contents("php://input"), true);

    $email = trim($input["email"] ?? "");
    $pw = $input["pw"] ?? "";

    if ($email === "" || $pw === "") {
        sendJson(400, [
            "success" => false,
            "error" => "Email and password are required"
        ]);
    }

    $stmt = $dbh->prepare(
        "SELECT
            id,
            email,
            pw,
            username,
            f_name,
            l_name,
            u_role,
            dept_id,
            is_active
         FROM user_table
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($pw, $user["pw"])) {
        sendJson(401, [
            "success" => false,
            "error" => "Invalid email or password"
        ]);
    }

    $isActive =
        $user["is_active"] === true ||
        $user["is_active"] === 1 ||
        $user["is_active"] === "1" ||
        $user["is_active"] === "t";

    if (!$isActive) {
        sendJson(403, [
            "success" => false,
            "error" => "This account is inactive"
        ]);
    }

    //64-bit token generation

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $rawToken);

    $expiresAt = new DateTimeImmutable("+30 days");

    $update = $dbh->prepare(
        "UPDATE user_table
         SET
            last_login = CURRENT_TIMESTAMP,
            token_expires = ?,
            api_token = ?
         WHERE id = ?"
    );

    $update->execute([
        $expiresAt->format("Y-m-d H:i:s"),
        $tokenHash,
        $user["id"]
    ]);

    sendJson(200, [
        "success" => true,
        "message" => "Login successful",
        "data" => [
            "token" => $rawToken,
            "token_type" => "Bearer",
            "expires_at" => $expiresAt->format(DATE_ATOM),
            "user" => [
                "id" => $user["id"],
                "email" => $user["email"],
                "username" => $user["username"],
                "first_name" => $user["f_name"],
                "last_name" => $user["l_name"],
                "role" => $user["u_role"],
                "dept_id" => $user["dept_id"]
            ]
        ]
    ]);
} catch (PDOException $e) {
    error_log($e->__toString());

    sendJson(500, [
        "success" => false,
        "error" => "Database error"
    ]);
} catch (Throwable $e) {
    error_log($e->__toString());

    sendJson(500, [
        "success" => false,
        "error" => "Server error"
    ]);
}
