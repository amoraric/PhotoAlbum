
document.addEventListener('DOMContentLoaded', function() {
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

        const reader = new FileReader();
        reader.onload = async function(e) {
            try {
                const photoContent = e.target.result;
                console.log('Photo content read successfully');

                const aesKey = await crypto.subtle.generateKey({
                    name: "AES-CBC",
                    length: 256
                }, true, ["encrypt", "decrypt"]);
                console.log('AES key generated successfully');

                const aesIv = crypto.getRandomValues(new Uint8Array(16));
                console.log('AES IV generated successfully');

                const encryptedContent = await crypto.subtle.encrypt({
                    name: "AES-CBC",
                    iv: aesIv
                }, aesKey, photoContent);
                console.log('Photo content encrypted successfully');

                const publicKeyArrayBuffer = pemToArrayBuffer(publicKeyPem);
                console.log('Public key converted to ArrayBuffer');

                const publicKey = await crypto.subtle.importKey(
                    "spki",
                    publicKeyArrayBuffer, {
                        name: "RSA-OAEP",
                        hash: "SHA-256"
                    },
                    true,
                    ["encrypt"]
                );
                console.log('Public key imported successfully');

                const aesKeyRaw = await crypto.subtle.exportKey("raw", aesKey);
                console.log('AES key exported successfully');

                const encryptedKey = await crypto.subtle.encrypt({
                    name: "RSA-OAEP"
                }, publicKey, aesKeyRaw);
                console.log('AES key encrypted with public key successfully');

                const encryptedIv = await crypto.subtle.encrypt({
                    name: "RSA-OAEP"
                }, publicKey, aesIv);
                console.log('AES IV encrypted with public key successfully');

                const userEmail = document.querySelector('meta[name="user-email"]').getAttribute('content');
                if (!userEmail) {
                throw new Error('User email not found');
                }
                console.log(userEmail);
                const signPrivateKeyKeyName = userEmail + '_signPrivateKey';
                const signPrivateKey = localStorage.getItem(signPrivateKeyKeyName);

                if (!signPrivateKey) {
                    throw new Error('No private key found in local storage');
                }
                console.log('Private key retrieved from local storage');

                const signPrivateKeyArrayBuffer = base64ToArrayBuffer(signPrivateKey);
                const signPrivateKeyImported = await crypto.subtle.importKey(
                    "pkcs8",
                    signPrivateKeyArrayBuffer, {
                        name: "ECDSA",
                        namedCurve: "P-256"
                    },
                    true,
                    ["sign"]
                );
                console.log('Private key imported successfully');

                const signature = await crypto.subtle.sign({
                    name: "ECDSA",
                    hash: {
                        name: "SHA-256"
                    }
                }, signPrivateKeyImported, encryptedContent);
                console.log('Content signed successfully');

                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                formData.append('photo', new Blob([encryptedContent]), photoFile.name);
                formData.append('album_id', albumId);
                formData.append('photo_name', photoName);
                formData.append('encrypted_key', btoa(String.fromCharCode.apply(null, new Uint8Array(encryptedKey))));
                console.log(btoa(String.fromCharCode.apply(null, new Uint8Array(encryptedKey))));
                formData.append('encrypted_iv', btoa(String.fromCharCode.apply(null, encryptedIv)));
                console.log(btoa(String.fromCharCode.apply(null, encryptedIv)));
                formData.append('signature', btoa(String.fromCharCode.apply(null, new Uint8Array(signature))));
                console.log(btoa(String.fromCharCode.apply(null, new Uint8Array(signature))));

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
                    alert('Failed to upload photo');
                    console.error('Upload failed', response);
                }
            } catch (error) {
                console.error('An error occurred during the encryption or upload process.', error);
                alert('An error occurred during the encryption or upload process.');
            }
        };

        reader.readAsArrayBuffer(photoFile);
    });
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
    const binaryString = atob(base64);
    const len = binaryString.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
}
