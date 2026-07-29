# Servicebook Phase 1

A lightweight service call manager built with PHP, MySQL, Bootstrap 5, and vanilla JavaScript.

## Setup

1. Create a MySQL database named `servicebook` or update `src/config.php` with your database name.
2. Configure database credentials in `src/config.php`.
3. Run `install.php` in your browser or from CLI once to create tables and the initial administrator account.
4. Visit `public/login.php` and sign in with:
   - Username: `admin`
   - Password: `admin123`

## Notes

- The installer creates the initial admin user automatically.
- Job numbers are generated sequentially and cannot be edited.
- The main page shows open calls by default.
- The application uses prepared statements and password hashing.
