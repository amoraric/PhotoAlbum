// resources/js/photoShare.js

// Utility functions for base64 encoding/decoding
function base64ToArrayBuffer(base64) {
    const binaryString = atob(base64);
    const len = binaryString.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
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
    const response = await fetch(`/photo/${email}/getPublicUserKey`);
    if (!response.ok) {
        throw new Error('Failed to fetch public key');
    }
    const publicKey = await response.text();
    return publicKey;
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
export async function sharePhoto(photoId, recipientEmail) {
    try {
        const response = await fetch(`/photos/${photoId}/get-encrypted-keys`);
        if (!response.ok) {
            throw new Error('Failed to fetch photo');
        }

        const data = await response.json();
        const { encryptedKey, encryptedIv } = data;

        const ownerEmail = document.querySelector('meta[name="user-email"]').getAttribute('content');
        const encPrivateKey = localStorage.getItem(`${ownerEmail}_encPrivateKey`);
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

        const aesIvDecrypted = await crypto.subtle.decrypt(
            { name: "RSA-OAEP" },
            privateKey,
            base64ToArrayBuffer(encryptedIv)
        );

        const publicKeyPem = await getUserPublicKey(recipientEmail);
        const encryptedKeys = await encryptKeyAndIvWithPublicKey(publicKeyPem, aesKeyBuffer, aesIvDecrypted);

        const shareResponse = await fetch(`/photos/${photoId}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                shareWith: recipientEmail,
                symmetric_key: encryptedKeys.encryptedKey,
                symmetric_iv: encryptedKeys.encryptedIv
            })
        });

        if (!shareResponse.ok) {
            throw new Error('Failed to share photo');
        }

        alert('Photo shared successfully!');

    } catch (error) {
        console.error('Error decrypting image:', error);
        alert('Failed to decrypt image: ' + error.message);
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

function StringToArrayBuffer(pem) {
    const binaryDerString = atob(pem);
    const len = binaryDerString.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryDerString.charCodeAt(i);
    }
    return bytes.buffer;
}
