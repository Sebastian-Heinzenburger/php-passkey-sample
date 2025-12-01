<?php
// Add this at the very top, before any other output
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 0);

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/database.php";

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;

session_start();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data["username"] ?? "";
    $db = Database::getInstance()->getConnection();

    if ($data["step"] === "options") {
        // Check if user already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(["error" => "Username already registered"]);
            exit();
        }

        $host = explode(":", $_SERVER["HTTP_HOST"])[0];
        $rpEntity = new PublicKeyCredentialRpEntity("WebAuthn Demo", $host);

        $userId = random_bytes(16);
        $userEntity = new PublicKeyCredentialUserEntity(
            $username,
            $userId,
            $username,
        );

        $challenge = random_bytes(32);
        $_SESSION["challenge"] = base64_encode($challenge);
        $_SESSION["username"] = $username;
        $_SESSION["userId"] = base64_encode($userId);

        $publicKeyCredentialCreationOptions = new PublicKeyCredentialCreationOptions(
            $rpEntity,
            $userEntity,
            $challenge,
            [
                new PublicKeyCredentialParameters("public-key", -7),
                new PublicKeyCredentialParameters("public-key", -257),
            ],
        );

        $options = json_decode(
            json_encode($publicKeyCredentialCreationOptions),
            true,
        );
        $options["challenge"] = base64_encode($challenge);
        $options["user"]["id"] = base64_encode($userId);

        // Enable discoverable credentials for usernameless authentication
        $options["authenticatorSelection"] = [
            "authenticatorAttachment" => "platform",
            "requireResidentKey" => true,
            "residentKey" => "required",
            "userVerification" => "required",
        ];

        echo json_encode($options);
    } elseif ($data["step"] === "verify") {
        if (!isset($_SESSION["username"]) || !isset($_SESSION["userId"])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid session"]);
            exit();
        }

        // Insert user
        $stmt = $db->prepare(
            "INSERT INTO users (username, user_id) VALUES (?, ?)",
        );
        $stmt->execute([$_SESSION["username"], $_SESSION["userId"]]);
        $userId = $db->lastInsertId();

        // Insert credential
        $stmt = $db->prepare(
            "INSERT INTO credentials (user_id, credential_id, credential_raw_id, public_key) VALUES (?, ?, ?, ?)",
        );
        $stmt->execute([
            $userId,
            $data["credential"]["id"],
            $data["credential"]["rawId"],
            $data["credential"]["response"]["attestationObject"],
        ]);

        echo json_encode(["success" => true]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
exit();
