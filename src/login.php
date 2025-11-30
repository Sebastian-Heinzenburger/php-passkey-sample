<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/database.php';

use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;

session_start();

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $db = Database::getInstance()->getConnection();

    if ($data['step'] === 'options') {
        // Get user and credentials from database
        $stmt = $db->prepare('
            SELECT u.id, u.user_id, c.credential_id, c.credential_raw_id, c.public_key
            FROM users u
            JOIN credentials c ON u.id = c.user_id
            WHERE u.username = ?
        ');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $challenge = random_bytes(32);
        $_SESSION['challenge'] = base64_encode($challenge);
        $_SESSION['username'] = $username;

        $allowCredentials = [
            new PublicKeyCredentialDescriptor('public-key', base64_decode($user['credential_raw_id']))
        ];

        $host = explode(':', $_SERVER['HTTP_HOST'])[0];

        $options = new PublicKeyCredentialRequestOptions(
            $challenge,
            $host,
            $allowCredentials,
            'preferred',
            60000
        );

        $optionsArray = json_decode(json_encode($options), true);
        $optionsArray['challenge'] = base64_encode($challenge);
        $optionsArray['allowCredentials'][0]['id'] = $user['credential_raw_id'];

        echo json_encode($optionsArray);

    } elseif ($data['step'] === 'verify') {
        if (!isset($_SESSION['challenge']) || !isset($_SESSION['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid session']);
            exit;
        }

        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;