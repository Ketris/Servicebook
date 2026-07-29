# Service Call Manager

### Phase 1 Functional Specification

Version 1.1

## Current Implementation Snapshot (2026-07-21)

The following are implemented in the current build:

* CSV and print exports for calls, search, technician queue, and activity.
* Saved views, role defaults, and recent-view shortcuts on the call list.
* Bulk call updates (status and assignment).
* Reusable customer/location records with admin merge/update tools.
* Technician dashboard with claim actions, quick updates, and team availability.
* Activity log pagination and filtering (search, actor, event type, field, date range).
* System-level admin events in activity history.

Notable current behavior:

* Status includes `Cancelled` as a closed state.
* Priority is not used.

## Project Goal

The purpose of this project is **not** to create a complete field service management system.

The objective of Phase 1 is simply to replace an existing handwritten service-call book with a lightweight web application accessible from desktops inside the office and tablets used by office staff.

The application should prioritize:

* simplicity
* speed
* reliability
* minimal clicks
* responsive design
* ease of future expansion

The workflow should closely resemble the existing paper notebook while adding searchability and preventing duplicate information.

---

# Technology Stack

Backend

* PHP 8.x
* MySQL
* PDO (prepared statements only)

Frontend

* Bootstrap 5
* Vanilla JavaScript
* HTML5

Authentication

* PHP Sessions
* Password hashing using password_hash()

Deployment

* Apache
* Internal network only (internet access may be added later)

---

# Design Philosophy

The software should feel like an office tool rather than enterprise software.

Avoid:

* unnecessary animations
* dashboards full of widgets
* excessive menus
* complicated workflows

Every common task should require as few clicks as possible.

---

# Primary Workflow

Incoming service call

↓

Office employee creates new service call

↓

Assign technician

↓

Update status throughout day

↓

Mark complete or cancel

Nothing more is required for Phase 1.

---

# User Roles

## Office Staff

Can

* Create calls
* Edit calls
* Search calls
* Assign technicians
* Add notes
* Change status
* View all calls

---

## Administrator

Everything Office Staff can do plus

* Manage users
* Manage technicians
* Manage customers
* Manage locations
* System configuration

---

# Service Call Fields

Each service call contains:

## Required

Job Number

Auto-generated integer.

Example

24017

---

Date/Time Received

Automatically filled.

Editable.

---

Customer Name

Free text for Phase 1.

(Customer database comes later.)

---

Location

Free text.

Usually city or wash name.

---

Reported Issue

Multi-line text.

Should allow several paragraphs.

---

Assigned Technician

Dropdown.

---

Status

Dropdown.

Values

* New
* Dispatched
* In Progress
* Waiting Parts
* On Hold
* Complete
* Cancelled

Default

New

---

## Optional

Customer PO Number

Text

---

Customer Contact

Text

---

Phone Number

Text

---

Email

Text

---

Internal Notes

Long text.

Office only.

---

# Main Screen

The home page should immediately display open service calls.

Columns

Job #

Received

Customer

Location

Technician

Status

Reported Issue (truncated)

PO Number

Each row should be clickable.

Clicking opens the complete job.

---

Toolbar

Top of page

Buttons

New Call

Search

Open Calls

Closed Today

Closed This Week

Administration

Logout

---

# New Call Page

Large simple form.

Tab order should be logical.

The cursor should start in Customer Name.

Save button should return to the main list.

Cancel returns without saving.

---

# Edit Call Page

Allow modification of every field.

Display:

Created Date

Last Modified

Created By

Last Modified By

Eventually these become part of an audit log.

---

# Search

Simple search box.

Searches:

* Job Number
* Customer
* Location
* PO Number
* Reported Issue

Results displayed in the same table format.

---

# Technician Management

Simple table.

Fields

Name

Phone

Active

Inactive technicians remain in history.

---

# User Management

Fields

Username

Display Name

Password

Role

Active

Roles

Administrator

Office Staff

Passwords must never be stored in plain text.

---

# Job Number Generation

Job numbers should auto-increment.

Never reused.

Never editable.

---

# Database Tables

users

```
id
username
password_hash
display_name
role
active
created_at
```

technicians

```
id
name
phone
active
created_at
```

service_calls

```
id
job_number
received_date
customer
location
contact
phone
email
po_number
reported_issue
internal_notes
assigned_tech
status
created_by
created_at
updated_at
```

---

# Validation Rules

Customer

Required

Location

Required

Reported Issue

Required

Technician

Optional

PO

Optional

Status

Must be one of allowed values.

---


# Future Compatibility

Although not implemented in Phase 1, the database should be designed so the following can be added without major restructuring:

* Customer database
* Multiple locations per customer
* Equipment database
* Work order printing
* Technician mobile interface
* Attachments
* Photos
* Email notifications
* SMS notifications
* Parts tracking
* Invoice integration
* Advanced audit analytics and retention controls
* Calendar view
* Dispatch board
* GPS location
* Time tracking
* Service history
* Customer portal

---

# Coding Standards

Use:

* prepared SQL statements only
* object-oriented PHP where appropriate
* reusable components
* separate business logic from HTML
* descriptive variable names
* Bootstrap utility classes
* comments explaining business logic instead of obvious code

Never:

* concatenate SQL strings
* duplicate code unnecessarily
* hardcode HTML repeatedly
* trust user input
* expose SQL errors to users

---

# UI Principles

The interface should feel similar to using a paper service book.

Requirements

* Large readable fonts
* Tablet-friendly controls
* Responsive layout
* Minimal scrolling
* Fast page loads
* High contrast
* Easy to use while answering the phone

---

# Out of Scope (Phase 1)

Do **not** implement:

* Customer accounts
* Equipment inventory
* Billing
* Invoicing
* Inventory management
* Technician GPS
* Scheduling
* Calendar views
* Route optimization
* Push notifications
* External APIs
* Mobile apps
* Cloud hosting
* Offline synchronization

The focus of Phase 1 is solely replacing the handwritten service-call book with a clean, reliable web application.