async function generateKeyPairs() {
    try {
        const encKeyPair = await crypto.subtle.generateKey(
            {
                name: "RSA-OAEP",
                modulusLength: 2048,
                publicExponent: new Uint8Array([1, 0, 1]),
                hash: "SHA-256"
            },
            true,
            ["encrypt", "decrypt"]
        );

        const signKeyPair = await crypto.subtle.generateKey(
            {
                name: "RSASSA-PKCS1-v1_5",
                modulusLength: 2048,
                publicExponent: new Uint8Array([1, 0, 1]),
                hash: "SHA-256"
            },
            true,
            ["sign", "verify"]
        );

        const publicKeyEnc = await crypto.subtle.exportKey("spki", encKeyPair.publicKey);
        const publicKeySign = await crypto.subtle.exportKey("spki", signKeyPair.publicKey);

        const publicKeyEncBase64 = btoa(String.fromCharCode(...new Uint8Array(publicKeyEnc)));
        const publicKeySignBase64 = btoa(String.fromCharCode(...new Uint8Array(publicKeySign)));

        localStorage.setItem("public_key_encryption", publicKeyEncBase64);
        localStorage.setItem("public_key_signature", publicKeySignBase64);
    } catch (error) {
        console.error("Error generating key pairs:", error);
    }
}

document.addEventListener('DOMContentLoaded', async (event) => {
    await generateKeyPairs();

    document.getElementById('registerSubmit').addEventListener('click', function() {
        const publicKeyEncryption = localStorage.getItem("public_key_encryption");
        const publicKeySignature = localStorage.getItem("public_key_signature");

        console.log("Public Key Encryption:", publicKeyEncryption);
        console.log("Public Key Signature:", publicKeySignature);

        document.getElementById("inputPublicKeyEncryption").value = publicKeyEncryption;
        document.getElementById("inputPublicKeySignature").value = publicKeySignature;

        document.getElementById("registerForm").submit();
    });
});
