# Servicebook Phase 1

A lightweight service call manager built with PHP, MySQL, Bootstrap 5, and vanilla JavaScript.

## Setup

1. Create a MySQL database named `servicebook` or update `src/config.php` with your database name.
2. Configure database credentials in `src/config.php`.
3. Run `install.php` in your browser or from CLI once to create tables and the initial administrator account.
4. Visit `public/login.php` and sign in with:
   - Username: `admin`
   - Temporary password shown by the installer after first setup.

## Notes

- The installer creates the initial admin user automatically and shows a one-time temporary password on first run.
- Login attempts are rate-limited per user and lock for 15 minutes after repeated failures.
- CSRF protection is enforced on form submissions in public and admin pages.
- Administrators can clear lockouts and trigger temporary password resets from User Management.
- Application events and errors are logged to `storage/logs/app.log`.
- Main call list supports quick filters for Completed Today and Completed This Week.
- Edit call view shows Last Modified timestamp and actor.
- Administration includes an Activity Log page for recent service call changes.
- Job numbers are generated sequentially and cannot be edited.
- The main page shows open calls by default.
- The application uses prepared statements and password hashing.
