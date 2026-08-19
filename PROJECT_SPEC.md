# Servicebook Functional Baseline

Version 2.0
Last updated: 2026-08-07

## Purpose

Servicebook is an internal web application that replaces a handwritten service-call notebook.

Primary goals:
- fast call intake
- easy assignment and status updates
- clear visibility for office staff and technicians
- low-friction administration
- secure and reliable daily operation

This project is intentionally not a full enterprise field-service platform.

## Technology

Backend:
- PHP 8.x
- MySQL
- PDO with prepared statements

Frontend:
- Bootstrap 5
- Vanilla JavaScript

Deployment:
- Apache
- Internal network by default

## User Roles

Administrator:
- full access
- user management
- technician management
- reusable records management
- system settings and backup/restore controls
- activity oversight

Office Staff:
- create/edit/search service calls
- assign technicians
- update status
- use saved views, exports, and bulk actions

Technician:
- view assigned and unassigned eligible work
- claim unassigned jobs
- perform quick status updates from dashboard and edit screens

## Core Workflow

1. Office receives call and creates a service call.
2. Office assigns technician and updates status over time.
3. Technician updates/claims work as needed.
4. Call is closed as Complete or Cancelled.

## Service Call Data

Required:
- job number (auto-generated, non-editable)
- received date
- customer
- location
- reported issue
- status

Optional:
- assigned technician
- PO number
- contact
- phone
- email
- internal notes

Status values:
- New
- Dispatched
- In Progress
- Waiting Parts
- On Hold
- Complete
- Cancelled

## Implemented Features

Call management:
- create/edit calls
- optimistic concurrency guard for updates
- server-side pagination on list and search
- open/unassigned/closed quick filters
- dedicated search page

Productivity:
- saved views
- recent views
- bulk status/assignment updates
- CSV export and print-friendly views for key screens

Technician workflow:
- technician dashboard
- quick status updates
- claim action for unassigned calls

Records:
- reusable customer records
- reusable location records
- merge/edit admin tooling
- call form autocomplete support

Administration:
- user management with role validation
- technician role requires linked technician record
- temporary password reset and lockout clearing
- technician management
- settings for site title and branding image

Security:
- CSRF protection
- password hashing
- login failure counters and lockout window
- hardened session cookie behavior
- sanitized installer/runtime user-facing errors
- protected directories via .htaccess where needed

Audit and logging:
- call change history
- system-level admin events
- activity filtering and pagination
- daily application log rotation with retention pruning

Backup and restore:
- compressed JSON snapshot backups (.json.gz)
- automatic backups by cadence (daily/weekly/monthly)
- retention-day pruning, with pruning outcomes recorded in the application log
- manual backup creation
- backup download
- restore from stored backup
- restore from uploaded backup file
- automatic backups log a system-level activity entry on both success and failure (with error detail on failure), matching manual backup/restore logging
- settings page surfaces the last automatic backup attempt (timestamp, outcome, error if failed)

## Operational Constraints

- Internal deployment model is primary.
- Scheduling/calendar workflows remain out of scope unless business need changes.
- Priority field is not in active use.
- Keep UX focused on speed and minimal clicks.

## Non-Goals (Current Phase)

- invoicing and billing
- inventory management
- route optimization and GPS tracking
- external customer portal
- public API surface
- native mobile app
- cloud-first architecture

## Quality Bar

- prepared statements only for SQL
- no raw SQL error exposure to end users
- business logic separated from templates/controllers where practical
- role and input validation enforced server-side
- maintainable incremental changes over heavy rewrites
