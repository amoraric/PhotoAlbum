# secg4-security-project

### Team Members
- Ismail NAYMA ASSAM / 55996
- Edon ISAKI / 60452
- Olivia PARASCHIV / 60085
- Christophe TOPUZ / 59372
- Augustin-Constantin MORARI / 61689
- Emannuel Junior KUSIKUMBAKU / 56272

## Project Summary

### Context
The objective of this project is to design a secure client/server system for managing and sharing photo albums. Users will be able to upload pictures to albums and share these albums or individual pictures with other users. The main focus is on implementing robust security protocols for data transmission and storage, with flexibility in choosing appropriate languages and technologies.

### Functionality
The system is based on a client/server architecture, involving two primary components: the server and the user-driven clients. The server offers functionalities such as user registration, login, and the management and sharing of photo albums by authenticated users. The specific implementation details are flexible, enabling the identification and addressing of critical security points.

#### Main Functionalities
- **User Registration and Authentication**
  - New users can register by supplying the required information and creating authentication credentials such as passwords, cryptographic keys, etc.
  - Using the Google Authentication Application, users can securely authenticate using a unique code.
  - User authentication is necessary to access system features, which ensures secure communication between clients and the server.

- **Album Management**
  - Users can create albums and upload pictures in it.
  - Users can securely view and manage their albums and pictures.
  - The system ensures the confidentiality and integrity of album contents.

- **Sharing Albums and Pictures**
  - Users can share albums and individual pictures with other users.
  - Shared items are read-only for recipients, maintaining the owner's control.

### Requirements
- OpenSSL
- Xampp
- Composer
- GitBash
- Google Authenticator Application
- Laravel

## Setup Project

### Steps to Setup

1. Open Bash and navigate to the secg4-security-project/albumPhoto directory:
    ```bash
    cd secg4-security-project/albumPhoto
    ```

2. Run the following commands:
    ```bash
    composer install
    php artisan serve
    npm install
    npm run dev
    php artisan migrate
    php artisan key:generate
    php artisan storage:link
    ```

3. If you have a problem, run these commands:
    ```bash
    php artisan cache:clear
    php artisan route:clear
    php artisan config:clear
    php artisan view:clear
    php artisan storage:link
    ```

### Setup Xampp / Adding a Self-Signed SSL Certificate

#### To add a self-signed SSL certificate, follow these steps:

1. **Modify the Hosts File**
   - Add `127.0.0.1 albumphotosecu.com` to the hosts file located at `C:\Windows\System32\drivers\etc`.

2. **Modify makecert.bat**
   - Navigate to `C:\xampp\apache`.
   - Modify the `makecert.bat` file.
   - Change the line:
     ```bash
     bin\openssl x509 -in server.csr -out server.crt -req -signkey server.key -days 365
     ```
     to:
     ```bash
     bin\openssl x509 -in server.csr -out server.crt -req -signkey server.key -days 1000 -extfile v3.ext
     ```

3. **Create the v3.ext File**
   - Create a new file named `v3.ext` and fill it with the following content:
     ```
     authorityKeyIdentifier=keyid,issuer
     basicConstraints=CA:FALSE
     keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
     subjectAltName = @alt_names
     [alt_names]
     DNS.1 = localhost
     DNS.2 = *.albumphotosecu.com
     DNS.3 = albumphotosecu.com
     DNS.4 = 127.0.0.1
     DNS.5 = 127.0.0.2
     ```

4. **Run makecert.bat**
   - Execute `makecert.bat` and fill in the fields, making sure to use the correct domain name.

5. **Install the Certificate**
   - Navigate to `C:\xampp\apache\conf\ssl.crt`.
   - Launch the `server.crt` file and install the certificate as shown in the [video](https://youtu.be/eqrDHkIFe8U?si=wEMe3XLo_QlndlB1).

6. **Modify the httpd-vhosts.conf File**
   - Navigate to `C:\xampp\apache\conf\extra`.
   - Modify the `httpd-vhosts.conf` file.
   - Add the following code (replace paths with your project directory paths):
     ```apache
     <VirtualHost *:80>
         DocumentRoot "/path/to/your/project/public"
         ServerName albumphotosecu.com
         ServerAlias albumphotosecu.com
     </VirtualHost>
     <VirtualHost *:443>
         DocumentRoot "/path/to/your/project/public"
         ServerName albumphotosecu.com
         ServerAlias albumphotosecu.com
         SSLEngine on
         SSLCertificateFile "conf/ssl.crt/server.crt"
         SSLCertificateKeyFile "conf/ssl.key/server.key"

         <Directory "/path/to/your/project/public">
             Options Indexes FollowSymLinks Includes execCGI
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```

### Running the Project
- Open Xampp and start the Apache server.
- Open a web browser and go to `https://localhost/`.

## Home Page
### Login and Registration
- **Registration**: Complete the required fields to create an account (name, e-mail, password, confirm password).
- **Login**: Enter your email and password to log in.

### User Roles and Actions
- **User**:
  - Can create albums and upload pictures.
  - Can share albums and pictures.
  - Can view their own and shared pictures.
