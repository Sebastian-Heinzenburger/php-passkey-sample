# WebAuthn PHP Demo

This project is a demonstration of using the WebAuthn PHP library for user registration and authentication via WebAuthn. It provides a simple web application that allows users to register and log in using their WebAuthn-compatible devices.

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd webauthn-php
   ```

2. Install dependencies using Composer:
   ```
   composer install
   ```

3. Configure your web server to serve the `src` directory as the document root.
   ```
   php -S localhost:8000 -t src
   ```

## Usage

1. Open your web browser and navigate to the application URL (e.g., `http://localhost`).
2. Use the registration form to create a new user account with WebAuthn.
3. After registration, use the login form to authenticate with your registered WebAuthn credentials.

## Requirements

- PHP 7.2 or higher
- Composer
- A WebAuthn-compatible device (e.g., security key, biometric device)