document.addEventListener('DOMContentLoaded', function() {

    async function decryptAndShowImage(photoId) {
        try {
            const response = await fetch(`/photos/decrypt/${photoId}`);
            if (!response.ok) {
                throw new Error('Failed to fetch photo');
            }

            const data = await response.json();
            const { encryptedContent, encryptedSymmetricKey, encryptedIv, signature } = data;
            console.log('Data received from server:', data);

            if (!encryptedContent || !encryptedSymmetricKey || !encryptedIv || !signature) {
                throw new Error('Missing encrypted data components');
            }

            const userEmail = document.querySelector('meta[name="user-email"]').getAttribute('content');
            if (!userEmail) {
                throw new Error('User email not found');
            }

            const encPrivateKey = localStorage.getItem(`${userEmail}_encPrivateKey`);
            if (!encPrivateKey) {
                throw new Error('No private key found in local storage');
            }

            // Convert the private key from base64 string to ArrayBuffer
            const encPrivateKeyBuffer = Uint8Array.from(atob(encPrivateKey), c => c.charCodeAt(0));
            if (!encPrivateKeyBuffer) {
                throw new Error('Failed to convert private key to ArrayBuffer');
            }

            const privateKey = await crypto.subtle.importKey(
                "pkcs8",
                encPrivateKeyBuffer.buffer, {
                    name: "RSA-OAEP",
                    hash: "SHA-256"
                },
                true,
                ["decrypt"]
            );

            console.log('Private key properties:', {
                type: privateKey.type,
                extractable: privateKey.extractable,
                algorithm: privateKey.algorithm,
                usages: privateKey.usages
            });
            console.log('Private key imported successfully');

            // Convert encrypted symmetric key from base64 to ArrayBuffer



            const encryptedSymmetricKeyArray = Uint8Array.from(atob(encryptedSymmetricKey), c => c.charCodeAt(0));
            // Decrypt the symmetric key using the private key
            const decryptedSymKey = await crypto.subtle.decrypt(
                { name: "AES-CBC" }, privateKey, encryptedSymmetricKeyArray.buffer);
            console.log('Decrypted symmetric key:', new Uint8Array(decryptedSymKey));

            // Decode base64-encoded encrypted data

            const encryptedIvBuffer = Uint8Array.from(atob(encryptedIv), c => c.charCodeAt(0));
            const encryptedContentBuffer = Uint8Array.from(atob(encryptedContent), c => c.charCodeAt(0));
            const signatureBuffer = Uint8Array.from(atob(signature), c => c.charCodeAt(0));

            console.log('Encrypted AES key (Uint8Array):', encryptedKeyBuffer);

            if (encryptedKeyBuffer.length === 0) {
                throw new Error('Encrypted AES key is empty');
            }

            // Perform RSA decryption of the AES key
            let aesKeyBuffer;
            try {
                aesKeyBuffer = await crypto.subtle.decrypt(
                    { name: "RSA-OAEP" },
                    privateKey,
                    encryptedKeyBuffer
                );
            } catch (e) {
                console.error('Error during AES key decryption:', e);
                throw new Error('Failed to decrypt AES key');
            }

            if (!aesKeyBuffer) {
                throw new Error('Failed to decrypt AES key');
            }

            console.log('AES key decrypted successfully');

            // Decrypt the content using the AES key
            let decryptedContent;
            try {
                decryptedContent = await crypto.subtle.decrypt({
                    name: "AES-CBC",
                    iv: encryptedIvBuffer
                }, aesKeyBuffer, encryptedContentBuffer);
            } catch (e) {
                console.error('Error during content decryption:', e);
                throw new Error('Failed to decrypt content');
            }

            if (!decryptedContent) {
                throw new Error('Failed to decrypt content');
            }

            console.log('Content decrypted successfully');

            // Verify the signature
            const publicKeyPem = document.querySelector('meta[name="public-sign-key"]').getAttribute('content');
            const publicKey = await crypto.subtle.importKey(
                "spki",
                pemToArrayBuffer(publicKeyPem), {
                    name: "ECDSA",
                    namedCurve: "P-256"
                },
                true,
                ["verify"]
            );

            const isSignatureValid = await crypto.subtle.verify(
                {
                    name: "ECDSA",
                    hash: { name: "SHA-256" }
                },
                publicKey,
                signatureBuffer,
                decryptedContent
            );

            if (!isSignatureValid) {
                throw new Error('Signature verification failed');
            }

            console.log('Signature verified successfully');

            const blob = new Blob([decryptedContent], { type: 'image/jpeg' });
            const url = URL.createObjectURL(blob);

            document.getElementById(`photo-${photoId}`).src = url;
            document.getElementById('modalImage').src = url;

            console.log('Image decrypted and displayed successfully');

        } catch (error) {
            console.error('Error decrypting image:', error);
            alert('Failed to decrypt image: ' + error.message);
        }
    }

    function pemToArrayBuffer(pem) {
        const pemHeader = "-----BEGIN PUBLIC KEY-----";
        const pemFooter = "-----END PUBLIC KEY-----";
        const pemContents = pem.substring(pemHeader.length, pem.length - pemFooter.length);
        const binaryDerString = atob(pemContents);
        const len = binaryDerString.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binaryDerString.charCodeAt(i);
        }
        return bytes.buffer;
    }

    // Decrypt images on page load
    document.querySelectorAll('img[data-photo-id]').forEach(img => {
        const photoId = img.getAttribute('data-photo-id');
        decryptAndShowImage(photoId);
    });
});
