document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('#photoForm').addEventListener('submit', async function (event) {
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
        reader.onload = async function (e) {
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
            const ouruserEmail = document.querySelector('meta[name="user-email"]').getAttribute('content');
            if (!ouruserEmail) {
                throw new Error('User email not found');
            }
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

            const userEmail = document.querySelector('meta[name="user-email"]').getAttribute('content');
            if (!userEmail) {
                throw new Error('User email not found');
            } const signPrivateKeyKeyName = userEmail + '_signPrivateKey';
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
                location.reload();

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