# Dolibarr Setup with PostgreSQL and Apache2 on Linux

This guide provides instructions for setting up **Dolibarr**, an open-source ERP and CRM software, with **PostgreSQL** and **Apache2** on a Linux system.

## Prerequisites

Before starting, ensure that you have the following installed:

- **Ubuntu/Debian-based Linux system** (other distributions may require adjustments)
- **Apache2** web server
- **PostgreSQL** database
- **PHP** (with required extensions for Dolibarr)
- **Dolibarr** source files

## Step 1: Update System

### First, update your system's package list:
- sudo apt update && sudo apt upgrade -y

### Step 2: Install Apache2
- sudo apt install apache2 -y

### After installation, verify that Apache2 is running:
- sudo systemctl status apache2

### You can also start or restart Apache2 if needed:
- sudo systemctl start apache2
- sudo systemctl restart apache2

### Step 3: Install PHP and Extensions
- sudo apt install php libapache2-mod-php php-cli php-common php-pgsql php-gd php-xml php-json php-mbstring php-curl php-intl php-zip -y

### Step 4: Install PostgreSQL
- sudo apt install postgresql postgresql-contrib -y

### After installation, check the status of the PostgreSQL service:
- sudo systemctl status postgresql

### Step 4.1: Create a PostgreSQL Database and User
#### Switch to the postgres user:
- sudo -i -u postgres

### Create a new PostgreSQL user for Dolibarr:
#### createuser -P dolibarr_user

### Create a new PostgreSQL database:
#### createdb -O dolibarr_user dolibarr_db
Exit the postgres user:
exit

### Step 5: Download and Install Dolibarr
### Step 5.1: Download Dolibarr
Download the latest version of Dolibarr from the official website.
Alternatively, use wget to download the tarball:
- wget https://sourceforge.net/projects/dolibarr/files/latest/download -O dolibarr.tar.gz

### Step 5.2: Extract the Dolibarr Files
Extract the downloaded file:
- tar -xvzf dolibarr.tar.gz

### Move the extracted files to the Apache2 web directory:
- sudo mv dolibarr /var/www/html/

### Set the proper ownership and permissions:
- sudo chown -R www-data:www-data /var/www/html/dolibarr
- sudo chmod -R 755 /var/www/html/dolibarr

### Step 6: Configure Apache2 for Dolibarr
Create a new configuration file for Dolibarr:
- sudo nano /etc/apache2/sites-available/dolibarr.conf
Add the following content to the file:
<VirtualHost *:80>
    ServerAdmin admin@example.com
    DocumentRoot /var/www/html/dolibarr/htdocs
    ServerName dolibarr.example.com
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined

    <Directory /var/www/html/dolibarr/htdocs/>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
Save and close the file.

### Enable the Dolibarr site and rewrite module:
- sudo a2ensite dolibarr.conf
- sudo a2enmod rewrite
### Reload Apache2 for the changes to take effect:
- sudo systemctl reload apache2

### Step 7: Install and Configure Dolibarr

    Open your web browser and go to http://your-server-ip/dolibarr/htdocs/install/index.php to start the web-based installation.

    Follow the on-screen instructions:
        Select PostgreSQL as the database.
        Enter your PostgreSQL credentials (database name, username, and password).
        Complete the setup process.

### Step 8: Secure Installation

### After installation, for security reasons, remove the install directory:
- sudo rm -rf /var/www/html/dolibarr/htdocs/install/

### Step 9: Access Dolibarr

You can now access Dolibarr by visiting:
http://your-server-ip/dolibarr/htdocs/

Log in using the admin credentials you created during the installation process.

## Troubleshooting
1. Apache2 Not Starting

If Apache2 fails to start, check the logs:

bash

sudo journalctl -xe

2. PostgreSQL Connection Issues

Ensure that PostgreSQL is running:

bash

- sudo systemctl status postgresql

If you cannot connect, check the PostgreSQL logs or ensure that your credentials are correct.
Conclusion

You have successfully installed Dolibarr with PostgreSQL and Apache2 on a Linux system. For more information, visit the Dolibarr documentation.

markdown


### Key Points Covered:
- Installation of **Apache2**, **PostgreSQL**, and **PHP**.
- Configuration of **Dolibarr** for a web-based ERP system.
- Basic troubleshooting for common issues.

You can adapt this README as needed based on your server environment and requirements.


```bash
