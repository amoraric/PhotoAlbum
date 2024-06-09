document.addEventListener('DOMContentLoaded', function () {

    function base64ToArrayBuffer(base64) {
        try {
            const binaryString = atob(base64);

            const len = binaryString.length;
            const bytes = new Uint8Array(len);
            for (let i = 0; i < len; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }
            console.log(bytes.buffer);
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

    async function decryptAndShowImage(photoId) {
        try {
            const response = await fetch(`/photos/decrypt/${photoId}`);
            if (!response.ok) {
                throw new Error('Failed to fetch photo');
            }

            const data = await response.json();
            const { encryptedContent, encryptedKey, encryptedIv, signature } = data;
            console.log('Data received from server:', data);

            if (!encryptedContent || !encryptedKey || !encryptedIv || !signature) {
                throw new Error('Missing encrypted data components');
            }

            // ff
            const userEmail = document.querySelector('meta[name="user-email"]').getAttribute('content');
            if (!userEmail) {
                throw new Error('User email not found');
            }
            const encPrivateKey = localStorage.getItem(`${userEmail}_encPrivateKey`);
            if (!encPrivateKey) {
                throw new Error('No private key found in local storage OURRR');
            }

            const encPrivateKeyBuffer = base64ToArrayBuffer(encPrivateKey);
            if (!encPrivateKeyBuffer) {
                throw new Error('Failed to convert private key to ArrayBuffer OURRR');
            }

            const privateKey = await crypto.subtle.importKey(
                "pkcs8",
                encPrivateKeyBuffer, {
                name: "RSA-OAEP",
                hash: "SHA-256"
            },
                true,
                ["decrypt"]
            );

            // /f

            console.log('Private key properties:', {
                type: privateKey.type,
                extractable: privateKey.extractable,
                algorithm: privateKey.algorithm,
                usages: privateKey.usages
            });
            console.log('Private key imported successfully');

            const aesKeyBuffer = await crypto.subtle.decrypt(
                { name: "RSA-OAEP" },
                privateKey,
                base64ToArrayBuffer(encryptedKey)
            );
            console.log("PLEASE");
            console.log(aesKeyBuffer);
            // /ff
            if (!aesKeyBuffer) {
                throw new Error('Failed to decrypt AES key');
            }

            console.log('AES key decrypted successfully');



            // LA CLES DECHIFFRE SYMMETRIQUE


            const aesKey = await crypto.subtle.importKey(
                "raw",
                aesKeyBuffer,
                { name: "AES-CBC" }, // Assurez-vous que cela correspond au mode AES utilisÃ©
                true,
                ["encrypt", "decrypt"]
            );

            const aesIvDecrypted  = await crypto.subtle.decrypt(
                { name: "RSA-OAEP" },
                privateKey,
                base64ToArrayBuffer(encryptedIv)
            );
            console.log("decrypter IV ");

            let aesIvDecryptedFinal = new Uint8Array(aesIvDecrypted);     // Array buffer a unitArray

            console.log(1);
            const decryptedContent = await crypto.subtle.decrypt({
                name: "AES-CBC",
                iv: aesIvDecryptedFinal
            }, aesKey, base64ToArrayBuffer(encryptedContent));

            console.log(2);

            if (!decryptedContent) {
                throw new Error('Failed to decrypt content');
            }
            
            console.log('Content decrypted successfully');
            console.log(4);

            /* Verify the signature
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
            console.log(5);


            const isSignatureValid = await crypto.subtle.verify(
                {
                    name: "ECDSA",
                    hash: { name: "SHA-256" }
                },
                publicKey,
                base64ToArrayBuffer(signature),
                decryptedContent
            );
            console.log(6);


            if (!isSignatureValid) {
                throw new Error('Signature verification failed');
            }
            */
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

    

    // Decrypt images on page load
    document.querySelectorAll('img[data-photo-id]').forEach(img => {
        const photoId = img.getAttribute('data-photo-id');
        decryptAndShowImage(photoId);
    });
    // ici

});