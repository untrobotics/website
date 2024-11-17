# Getting Started with UNT Robotics Web Platform

This guide will help you set up and run the UNT Robotics web platform locally for development.

## Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB
- Apache or Nginx web server
- NodeJS

## Environment Setup

1. **Clone the Repository**

   ```bash
   git clone https://github.com/UNTRobotics/website.git
   cd website
   ```

2. **Database Configuration**
   - Create a new MySQL database
   - Copy `config.sample.php` to `config.php`
   - Update database credentials in `config.php`:

     ```php
     define('DATABASE_HOST', 'localhost');
     define('DATABASE_USER', 'your_username');
     define('DATABASE_PASSWORD', 'your_password');
     define('DATABASE_NAME', 'untrobotics');
     ```

3. **Import Database Schema**
   - Import all SQL files from the schema directory:

     ```bash
     mysql -u your_username -p your_database < schema/botathon_registration.sql
     mysql -u your_username -p your_database < schema/dues_payments.sql
     # ... (repeat for other .sql files)
     ```

4. **Configure External Services**
   Update `config.php` with the following API credentials:
   - PayPal API credentials
   - Discord bot token
   - SendGrid API key
   - Printful API key
   ```php
   define('PAYPAL_BUSINESS_ID', 'your_paypal_id');
   define('DISCORD_BOT_TOKEN', 'your_discord_token');
   define('SENDGRID_API_KEY', 'your_sendgrid_key');
   ```

5. **Web Server Configuration**
   - For Apache, ensure mod_rewrite is enabled
   - Configure your virtual host to point to the project's public directory
   - Sample Apache configuration:

     ```apache
     <VirtualHost *:80>
         ServerName untrobotics.local
         DocumentRoot /path/to/website/public
         
         <Directory /path/to/website/public>
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```

6. **File Permissions**

   ```bash
   # Set proper permissions on storage directories
   chmod -R 775 storage/
   chown -R www-data:www-data storage/
   ```

## Running the Application

1. **Start your web server and MySQL**

   ```bash
   sudo service apache2 start
   sudo service mysql start
   ```

2. **Access the Application**
   - Visit http://localhost or your configured domain
   - The default admin credentials are:
     - Username: admin
     - Password: Change this on first login!

## Key Features

- **Membership Management**: Track member dues and registrations
- **Event Management**: Handle event registrations like Botathon
- **Payment Processing**: Integration with PayPal for merchandise and dues
- **Discord Integration**: Automatic role management and notifications
- **Merchandise Store**: Integration with Printful for merchandise fulfillment

## Troubleshooting

1. **Permission Issues**
   - Ensure storage directories are writable by web server
   - Check log files in `/var/log/apache2/` or equivalent

2. **Database Connection Issues**
   - Verify MySQL is running: `sudo service mysql status`
   - Check database credentials in config.php
   - Ensure database user has proper permissions

3. **Payment Integration Issues**
   - Verify PayPal API credentials
   - Check IPN (Instant Payment Notification) URL configuration
   - Review payment logs in `/logs` directory

## Development Guidelines

1. **Code Style**
   - Follow PSR-12 coding standards
   - Use meaningful variable and function names
   - Comment complex logic

2. **Testing**
   - Test payment flows using PayPal sandbox
   - Test Discord integrations in a test server
   - Verify email notifications using SendGrid sandbox

3. **Security**
   - Never commit sensitive credentials
   - Use prepared statements for database queries
   - Validate and sanitize all user input

## Additional Resources

- [UNT Robotics Website](https://untrobotics.com)
- [Discord Server](https://discord.gg/untrobotics)
- [PayPal Developer Documentation](https://developer.paypal.com/docs)
- [Discord API Documentation](https://discord.com/developers/docs)
- [Printful API Documentation](https://www.printful.com/docs)