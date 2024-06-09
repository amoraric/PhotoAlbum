// resources/js/photoShare.js

// Utility functions for base64 encoding/decoding
function base64ToArrayBuffer(base64) {
    try {
        const binaryString = atob(base64);
        const len = binaryString.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes.buffer;
    } catch (error) {
        console.error('Error in base64ToArrayBuffer:', error);
        return null;
    }
}

function arrayBufferToBase64(buffer) {
    let binary = '';
    const bytes = new Uint8Array(buffer);
    const len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

// Function to get a user's public key from the server
async function getUserPublicKey(email) {
    const response = await fetch(`/users/public-key?email=${email}`);
    if (!response.ok) {
        throw new Error('Failed to fetch public key');
    }
    const data = await response.json();
    return data.publicKey;
}

// Function to encrypt the AES key and IV with the recipient's public key
async function encryptKeyAndIvWithPublicKey(publicKeyPem, aesKeyBuffer, aesIvBuffer) {
    const publicKey = await crypto.subtle.importKey(
        "spki",
        base64ToArrayBuffer(publicKeyPem), {
            name: "RSA-OAEP",
            hash: "SHA-256"
        },
        true,
        ["encrypt"]
    );

    const encryptedKey = await crypto.subtle.encrypt(
        { name: "RSA-OAEP" },
        publicKey,
        aesKeyBuffer
    );

    const encryptedIv = await crypto.subtle.encrypt(
        { name: "RSA-OAEP" },
        publicKey,
        aesIvBuffer
    );

    return {
        encryptedKey: arrayBufferToBase64(encryptedKey),
        encryptedIv: arrayBufferToBase64(encryptedIv)
    };
}

// Function to handle sharing the photo
async function sharePhoto(photoId, recipientEmail) {
    try {
        // Get the encrypted AES key and IV from the server
        const response = await fetch(`/photos/${photoId}/get-encrypted-keys`);
        const data = await response.json();
        const { encryptedKey, encryptedIv } = data;

        // Get the recipient's public key
        const recipientPublicKey = await getUserPublicKey(recipientEmail);

        // Decrypt the AES key and IV with the owner's private key
        const userEmail = document.querySelector('meta[name="user-email"]').getAttribute('content');
        const encPrivateKey = localStorage.getItem(`${userEmail}_encPrivateKey`);
        const encPrivateKeyBuffer = base64ToArrayBuffer(encPrivateKey);

        const privateKey = await crypto.subtle.importKey(
            "pkcs8",
            encPrivateKeyBuffer, {
                name: "RSA-OAEP",
                hash: "SHA-256"
            },
            true,
            ["decrypt"]
        );

        const aesKeyBuffer = await crypto.subtle.decrypt(
            { name: "RSA-OAEP" },
            privateKey,
            base64ToArrayBuffer(encryptedKey)
        );

        const aesIvBuffer = await crypto.subtle.decrypt(
            { name: "RSA-OAEP" },
            privateKey,
            base64ToArrayBuffer(encryptedIv)
        );

        // Encrypt the AES key and IV with the recipient's public key
        const encryptedData = await encryptKeyAndIvWithPublicKey(recipientPublicKey, aesKeyBuffer, aesIvBuffer);

        // Send the encrypted keys to the server to share the photo
        const shareResponse = await fetch(`/photos/${photoId}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                shareWith: recipientEmail,
                symmetric_key: encryptedData.encryptedKey,
                symmetric_iv: encryptedData.encryptedIv
            })
        });

        if (!shareResponse.ok) {
            throw new Error('Failed to share photo');
        }

        alert('Photo shared successfully!');
    } catch (error) {
        console.error('Error sharing photo:', error);
        alert('Failed to share photo: ' + error.message);
    }
}

// Ensure the DOM is loaded before adding event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Event listener for the share form submission
    document.querySelector('#shareForm').addEventListener('submit', async (event) => {
        event.preventDefault(); // Prevent the default form submission
        const photoId = document.querySelector('#photoId').value;
        const recipientEmail = document.querySelector('#shareWith').value;
        await sharePhoto(photoId, recipientEmail);
    });
});
