document.addEventListener('DOMContentLoaded', function() {

    async function sharePhoto(photoId, recipientEmail) {
        try {
            const response = await fetch(`/photos/decrypt/${photoId}`);
            if (!response.ok) {
                throw new Error('Failed to fetch photo data');
            }

            const data = await response.json();
            const { encryptedSymmetricKey, encryptedIv } = data;

            const userEmailMeta = document.querySelector('meta[name="user-email"]');
            const userEmail = userEmailMeta.getAttribute('content');
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

            const encryptedSymmetricKeyArray = base64ToArrayBuffer(encryptedSymmetricKey);
            const encryptedIvArray = base64ToArrayBuffer(encryptedIv);

            const decryptedSymKey = await crypto.subtle.decrypt(
                { name: "RSA-OAEP" },
                privateKey,
                encryptedSymmetricKeyArray
            );

            const decryptedIv = await crypto.subtle.decrypt(
                { name: "RSA-OAEP" },
                privateKey,
                encryptedIvArray
            );

            const recipientPublicKeyResponse = await fetch(`/users/public-key?email=${encodeURIComponent(recipientEmail)}`);
            const recipientPublicKeyData = await recipientPublicKeyResponse.json();
            const recipientPublicKeyArrayBuffer = base64ToArrayBuffer(recipientPublicKeyData.public_key);

            const recipientPublicKey = await crypto.subtle.importKey(
                "spki",
                recipientPublicKeyArrayBuffer, {
                    name: "RSA-OAEP",
                    hash: "SHA-256"
                },
                true,
                ["encrypt"]
            );

            const newEncryptedKey = await crypto.subtle.encrypt(
                { name: "RSA-OAEP" },
                recipientPublicKey,
                decryptedSymKey
            );

            const newEncryptedIv = await crypto.subtle.encrypt(
                { name: "RSA-OAEP" },
                recipientPublicKey,
                decryptedIv
            );

            const newEncryptedKeyBase64 = arrayBufferToBase64(newEncryptedKey);
            const newEncryptedIvBase64 = arrayBufferToBase64(newEncryptedIv);

            await sendShareRequest(photoId, recipientEmail, newEncryptedKeyBase64, newEncryptedIvBase64);

        } catch (error) {
            console.error('Error sharing photo:', error);
            alert('Failed to share photo: ' + error.message);
        }
    }

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
        const binary = String.fromCharCode.apply(null, new Uint8Array(buffer));
        return btoa(binary);
    }

    async function sendShareRequest(photoId, recipientEmail, symmetricKey, symmetricIv) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const response = await fetch(`/photos/share/${photoId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                shareWith: recipientEmail,
                symmetric_key: symmetricKey,
                symmetric_iv: symmetricIv
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to share photo');
        }

        const responseData = await response.json();
        alert(responseData.message || 'Photo shared successfully');
    }

    function openShareModal(photoId) {
        document.getElementById('sharePhotoId').value = photoId;
        var sharePhotoModal = new bootstrap.Modal(document.getElementById('sharePhotoModal'));
        sharePhotoModal.show();
    }

    document.getElementById('sharePhotoButton').addEventListener('click', function() {
        const photoId = document.getElementById('sharePhotoId').value;
        const recipientEmail = document.getElementById('recipientEmailInput').value;
        sharePhoto(photoId, recipientEmail);
    });
});