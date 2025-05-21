# WebSysProject
Final Term Project Websys 2
# Mini-Mart Inventory System

A web-based inventory management system built with PHP and MySQL, designed for small to medium-sized retail stores.

## Features

- **User Authentication & Authorization**
  - Secure login with session management
  - Role-based access control (Admin, Manager, Staff)
  - Persistent login sessions with secure cookies
  - Password hashing for security

- **Inventory Management**
  - Product tracking with SKU
  - Stock level monitoring
  - Low stock alerts
  - Category management

- **Transaction Management**
  - Stock in/out recording
  - Transaction history
  - User activity tracking
  - Notes and documentation

- **User Management**
  - User profile management
  - Role assignment
  - Account status tracking
  - Last login monitoring

- **Reporting**
  - Current inventory status
  - Low stock reports
  - Transaction history
  - Category-wise inventory
  - Export to CSV
  - Printable reports

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP (recommended) or similar PHP development environment

## Installation

1. **Set up your web server**
   - Install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Start Apache and MySQL services

2. **Clone the repository**
   ```bash
   cd C:\xampp\htdocs
   git clone [your-repository-url] websysproject
   ```

3. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database
   - Import the database schema from `sql/database.sql`
   - Add session token columns by running:
     ```sql
     ALTER TABLE users
     ADD COLUMN session_token VARCHAR(64) NULL,
     ADD COLUMN token_expiry DATETIME NULL,
     ADD INDEX idx_session_token (session_token),
     ADD INDEX idx_token_expiry (token_expiry);
     ```

4. **Configure Database Connection**
   - Navigate to `includes/db_connect.php`
   - Update the database credentials if needed:
     ```php
     $host = 'localhost';
     $username = 'your_username';
     $password = 'your_password';
     $database = 'your_database';
     ```

5. **Set up the application**
   - Access the application through your web browser: `http://localhost/websysproject`
   - Register an admin account through the registration page
   - Log in with your credentials

## Directory Structure

```
websysproject/
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── auth_check.php
│   ├── db_connect.php
│   ├── header.php
│   └── sidebar.php
├── sql/
│   └── add_session_token.sql
├── index.php
├── login.php
├── register.php
├── dashboard.php
├── products.php
├── transactions.php
├── users.php
├── reports.php
└── profile.php
```

## Security Features

- Password hashing using PHP's `password_hash()`
- Prepared statements to prevent SQL injection
- Session-based authentication
- HTTP-only cookies for session management
- XSS prevention through `htmlspecialchars()`
- CSRF protection
- Secure cookie settings (HTTP-only, Secure, SameSite)

## User Roles

1. **Admin**
   - Full system access
   - User management
   - System configuration

2. **Manager**
   - Inventory management
   - Report generation
   - Transaction management

3. **Staff**
   - Basic inventory operations
   - Transaction recording
   - Profile management

## Session Management

The system implements a dual-layer session management:
- PHP Sessions for immediate authentication
- Secure cookies for "Remember Me" functionality
- 10-minute session timeout with automatic renewal on activity

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the repository or contact the system administrator.
