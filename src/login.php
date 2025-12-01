<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 0);

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/database.php";

use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;

session_start();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $db = Database::getInstance()->getConnection();

    if ($data["step"] === "options") {
        $challenge = random_bytes(32);
        $_SESSION["challenge"] = base64_encode($challenge);

        $host = explode(":", $_SERVER["HTTP_HOST"])[0];

        // Empty allowCredentials array for usernameless authentication
        $allowCredentials = [];

        $options = new PublicKeyCredentialRequestOptions(
            $challenge,
            $host,
            $allowCredentials,
            "preferred",
            60000,
        );

        $optionsArray = json_decode(json_encode($options), true);
        $optionsArray["challenge"] = base64_encode($challenge);
        $optionsArray["allowCredentials"] = [];

        echo json_encode($optionsArray);
    } elseif ($data["step"] === "verify") {
        if (!isset($_SESSION["challenge"])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid session"]);
            exit();
        }

        // The credential ID from the assertion will identify the user
        $credentialId = $data["assertion"]["id"] ?? "";

        if (!$credentialId) {
            http_response_code(400);
            echo json_encode(["error" => "Missing credential ID"]);
            exit();
        }

        // Look up the user by credential ID
        $stmt = $db->prepare('
            SELECT u.username
            FROM users u
            JOIN credentials c ON u.id = c.user_id
            WHERE c.credential_id = ?
        ');
        $stmt->execute([$credentialId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(["error" => "Credential not found"]);
            exit();
        }

        $_SESSION["username"] = $user["username"];
        echo json_encode(["success" => true, "username" => $user["username"]]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
exit();
