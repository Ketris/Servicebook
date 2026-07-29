# Servicebook Phase 1

A lightweight service call manager built with PHP, MySQL, Bootstrap 5, and vanilla JavaScript.

## Setup

1. Open `install.php` in your browser.
2. Enter SQL server address, database name/login credentials, and initial site title.
3. Review the installer summary and explicitly confirm before setup runs.
4. The installer detects existing installations and lets you either continue to login or rerun setup to verify/update configuration.
5. Visit `public/login.php` and sign in with:
   - Username: `admin`
   - Temporary password shown by the installer after first setup.

## Notes

- The installer creates the initial admin user automatically and shows a one-time temporary password on first run.
- Login attempts are rate-limited per user and lock for 15 minutes after repeated failures.
- CSRF protection is enforced on form submissions in public and admin pages.
- Administrators can clear lockouts and trigger temporary password resets from User Management.
- Application events and errors are logged to `storage/logs/app.log`.
- Technician users land on a dedicated My Jobs dashboard after login, with quick status updates and claim actions for unassigned work.
- Main call list supports quick filters for Completed Today and Completed This Week.
- Edit call view shows Last Modified timestamp and actor.
- Administration includes an Activity Log page for recent service call changes.
- Administrators can upload or remove a title image logo in System Settings; when present, the logo replaces the text site title.
- Job numbers are generated sequentially and cannot be edited.
- The main page shows open calls by default.
- The application uses prepared statements and password hashing.
