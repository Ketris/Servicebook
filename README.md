# Servicebook

Servicebook is a lightweight internal service-call application for office staff, administrators, and technicians.

It is designed to replace a handwritten dispatch notebook while preserving speed, simplicity, and reliability.

## Current Status

Current as of 2026-08-07.

Implemented highlights:
- Service call intake, editing, assignment, and status workflow
- Role-based access for Administrator, Office Staff, and Technician
- Technician dashboard with quick updates and claim actions for unassigned work
- Open/unassigned/closed filters and dedicated search
- Pagination on call list, search, and activity history
- Saved views, recent views, bulk updates, CSV export, and print views
- Reusable customer and location records with admin merge/edit tools
- Activity logging for call-level changes and system-level admin actions
- Authentication hardening (CSRF, rate limiting/lockouts, session hardening)
- Installer hardening with sanitized user-facing errors
- Daily application log rotation with retention pruning
- Backup and restore system with:
  - automatic cadence (daily, weekly, monthly)
  - retention policy
  - manual backup creation
  - backup download
  - restore from stored backup
  - restore from uploaded compressed backup

## Stack

- PHP 8.x
- MySQL
- PDO (prepared statements)
- Bootstrap 5
- Vanilla JavaScript
- Apache

## Quick Start

1. Configure Apache to serve this project.
2. Open install.php in your browser.
3. Enter database connection details and site title.
4. Complete setup and sign in at public/login.php.
5. Save the temporary admin password shown at install time.

## Security and Operations Notes

- If DocumentRoot cannot be changed to public/ after installation is complete, root .htaccess includes protection rules for sensitive paths.
- Branding uploads are restricted by file type and size and protected from script execution.
- Backup storage is protected by storage/backups/.htaccess deny rules.
- Session cookies use HttpOnly, SameSite=Lax, and HTTPS-aware Secure behavior.
- Login lockout is enforced after repeated failures.
- Installer and runtime errors shown to users are sanitized; details are logged server-side.

## Backup and Restore

Backups are stored as compressed JSON files in storage/backups with file names like:
- backup-YYYYMMDD-HHMMSS-manual.json.gz
- backup-YYYYMMDD-HHMMSS-auto.json.gz

Admin settings allow:
- enabling/disabling automatic backups
- cadence selection (daily/weekly/monthly)
- retention in days
- one-click manual backups
- restore from stored backups
- restore from uploaded .json.gz backups

## Repository Notes

- Generated logs are ignored by git.
- Generated backup artifacts are ignored by git.
- The protection file storage/backups/.htaccess remains tracked.

## Project Structure

- public/: user-facing routes (login, calls, search, technician dashboard)
- admin/: admin routes (users, technicians, records, settings, activity)
- src/: core classes (auth, database, models, backup manager, logging, helpers)
- templates/: layouts and page templates
- storage/: logs and backups

## Next Documentation

- Current implementation direction: FUTURE_PLANS.md
- Current functional baseline: PROJECT_SPEC.md
