document.addEventListener('DOMContentLoaded', function() {
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
            const userEmail = "user5@gmail.com";  // Replace with dynamic user email retrieval if needed
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

            const aesIv = base64ToArrayBuffer(encryptedIv);
            if (!aesIv) {
                throw new Error('Failed to convert AES IV to ArrayBuffer');
            }

            const decryptedContent = await crypto.subtle.decrypt({
                name: "AES-CBC",
                iv: aesIv
            }, aesKeyBuffer, base64ToArrayBuffer(encryptedContent));
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
                base64ToArrayBuffer(signature),
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

    document.querySelector('#photoForm').addEventListener('submit', async function(event) {
        event.preventDefault();

        const photoFile = document.querySelector('#photoFile').files[0];
        const albumId = document.querySelector('#albumSelect').value;
        const photoName = document.querySelector('#photoName').value;
        const storeUrl = document.querySelector('#photoForm').getAttribute('data-store-url');
        const publicKeyPem = document.querySelector('#photoForm').getAttribute('data-public-enc-key');

        if (!photoFile || !albumId || !photoName) {
            alert("All fields are required.");
            return;
        }
        console.log("pre");

        const reader = new FileReader();
        reader.onload = async function(e) {
            const photoContent = e.target.result;
            console.log(1);

            const aesKey = await crypto.subtle.generateKey({
                    name: "AES-CBC",
                    length: 256
                },
                true,
                ["encrypt", "decrypt"]
            );
            const aesIv = crypto.getRandomValues(new Uint8Array(16));

            const encryptedContent = await crypto.subtle.encrypt({
                    name: "AES-CBC",
                    iv: aesIv
                },
                aesKey,
                photoContent
            );
            console.log(2);

            const rawAesKey = await crypto.subtle.exportKey("raw", aesKey);
            const rawAesIv = aesIv.buffer;
            console.log(3);

            const publicKey = await crypto.subtle.importKey(
                "spki",
                pemToArrayBuffer(publicKeyPem), {
                    name: "RSA-OAEP",
                    hash: {
                        name: "SHA-256"
                    }
                },
                true,
                ["encrypt"]
            );

            console.log(5);

            const encryptedKey = await crypto.subtle.encrypt({
                    name: "RSA-OAEP"
                },
                publicKey,
                rawAesKey
            );

            // debut
            const ouruserEmail = "user5@gmail.com";  // Replace with dynamic user email retrieval if needed
            const ourencPrivateKey = localStorage.getItem(`${ouruserEmail}_encPrivateKey`);
            if (!ourencPrivateKey) {
                throw new Error('No private key found in local storage OURRR');
            }

            const ourencPrivateKeyBuffer = base64ToArrayBuffer(ourencPrivateKey);
            if (!ourencPrivateKeyBuffer) {
                throw new Error('Failed to convert private key to ArrayBuffer OURRR');
            }

            const ourprivateKey = await crypto.subtle.importKey(
                "pkcs8",
                ourencPrivateKeyBuffer, {
                    name: "RSA-OAEP",
                    hash: "SHA-256"
                },
                true,
                ["decrypt"]
            );
            // fin

            const encrypted_iv = await crypto.subtle.encrypt({
                    name: "RSA-OAEP"
                },
                publicKey,
                rawAesIv
            );
            console.log(6);
            console.log("le form:");
            console.log('album_id =' + albumId);
            console.log('photo_name' + photoName);

            const userEmail = "user5@gmail.com"; 
            const signPrivateKeyKeyName = userEmail + '_signPrivateKey';
            console.log("private = " + signPrivateKeyKeyName);
            const signPrivateKey = localStorage.getItem(signPrivateKeyKeyName);
            console.log(signPrivateKey);

            const privateKeyBuffer = base64ToArrayBuffer(signPrivateKey);

            const privateKeyImported = await crypto.subtle.importKey(
                "pkcs8", 
                privateKeyBuffer, {
                    name: "ECDSA",
                    namedCurve: "P-256"
                },
                true,
                ["sign"]
            );

            const signature = await crypto.subtle.sign({
                    name: "ECDSA",
                    hash: {
                        name: "SHA-256"
                    }
                },
                privateKeyImported,
                encryptedContent
            );

            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('photo', new Blob([encryptedContent]), photoFile.name);
            formData.append('album_id', albumId);
            formData.append('photo_name', photoName);
            formData.append('encrypted_key', arrayBufferToBase64(encryptedKey));
            formData.append('encrypted_iv', arrayBufferToBase64(encrypted_iv));
            formData.append('signature', arrayBufferToBase64(signature));

            console.log(7);

            const response = await fetch(storeUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                alert('Photo uploaded successfully!');
            } else {
                console.log("fail");
                alert('Failed to upload photo');
            }
            console.log(8);
        };
        console.log(9);

        reader.readAsArrayBuffer(photoFile);
        console.log(10);

    });
    console.log(11);
});
