<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebAuthn Demo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        button { padding: 10px 20px; margin: 10px 0; cursor: pointer; }
        .section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>WebAuthn Demo</h1>

    <div class="section">
        <h2>Register</h2>
        <input type="text" id="username" placeholder="Enter username" />
        <button onclick="register()">Register with WebAuthn</button>
        <div id="register-result"></div>
    </div>

    <div class="section">
        <h2>Login</h2>
        <button onclick="login()">Login with WebAuthn</button>
        <div id="login-result"></div>
    </div>

    <script src="/public/js/webauthn.js"></script>
</body>
</html>
