async function register() {
    const username = document.getElementById('username').value;
    const resultDiv = document.getElementById('register-result');

    if (!username) {
        resultDiv.innerHTML = '<p class="error">Please enter a username</p>';
        return;
    }

    try {
        const optionsResponse = await fetch('register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, step: 'options' })
        });

        const options = await optionsResponse.json();

        if (!optionsResponse.ok) {
            resultDiv.innerHTML = '<p class="error">' + (options.error || 'Registration failed') + '</p>';
            return;
        }

        options.challenge = base64ToArrayBuffer(options.challenge);
        options.user.id = base64ToArrayBuffer(options.user.id);

        const credential = await navigator.credentials.create({ publicKey: options });

        const verifyResponse = await fetch('register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username,
                step: 'verify',
                credential: {
                    id: credential.id,
                    rawId: arrayBufferToBase64(credential.rawId),
                    response: {
                        clientDataJSON: arrayBufferToBase64(credential.response.clientDataJSON),
                        attestationObject: arrayBufferToBase64(credential.response.attestationObject)
                    },
                    type: credential.type
                }
            })
        });

        const result = await verifyResponse.json();
        
        if (!verifyResponse.ok) {
            resultDiv.innerHTML = '<p class="error">' + (result.error || 'Registration failed') + '</p>';
            return;
        }
        
        resultDiv.innerHTML = result.success 
            ? '<p class="success">Registration successful!</p>' 
            : '<p class="error">Registration failed: ' + (result.error || 'Unknown error') + '</p>';

    } catch (error) {
        resultDiv.innerHTML = '<p class="error">Error: ' + error.message + '</p>';
    }
}

async function login() {
    const username = document.getElementById('login-username').value;
    const resultDiv = document.getElementById('login-result');
    
    if (!username) {
        resultDiv.innerHTML = '<p class="error">Please enter a username</p>';
        return;
    }

    try {
        const optionsResponse = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, step: 'options' })
        });
        
        const options = await optionsResponse.json();
        
        if (!optionsResponse.ok) {
            resultDiv.innerHTML = '<p class="error">' + (options.error || 'Login failed') + '</p>';
            return;
        }

        options.challenge = base64ToArrayBuffer(options.challenge);
        options.allowCredentials = options.allowCredentials.map(cred => ({
            ...cred,
            id: base64ToArrayBuffer(cred.id)
        }));

        const assertion = await navigator.credentials.get({ publicKey: options });

        const verifyResponse = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username,
                step: 'verify',
                assertion: {
                    id: assertion.id,
                    rawId: arrayBufferToBase64(assertion.rawId),
                    response: {
                        clientDataJSON: arrayBufferToBase64(assertion.response.clientDataJSON),
                        authenticatorData: arrayBufferToBase64(assertion.response.authenticatorData),
                        signature: arrayBufferToBase64(assertion.response.signature),
                        userHandle: assertion.response.userHandle ? arrayBufferToBase64(assertion.response.userHandle) : null
                    },
                    type: assertion.type
                }
            })
        });

        const result = await verifyResponse.json();
        
        if (!verifyResponse.ok) {
            resultDiv.innerHTML = '<p class="error">' + (result.error || 'Login failed') + '</p>';
            return;
        }
        
        resultDiv.innerHTML = result.success 
            ? '<p class="success">Login successful!</p>' 
            : '<p class="error">Login failed: ' + (result.error || 'Unknown error') + '</p>';

    } catch (error) {
        resultDiv.innerHTML = '<p class="error">Error: ' + error.message + '</p>';
    }
}

function base64ToArrayBuffer(base64) {
    const binary = atob(base64.replace(/-/g, '+').replace(/_/g, '/'));
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}

function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.length; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}