# Future Development Plan

This document outlines sensible next-step features and refinements for Servicebook based on the current application scope: internal service-call intake, technician assignment, technician dashboards, admin management, search, settings, and activity logging.

## Goals

- Improve the day-to-day workflow for office staff, technicians, and administrators.
- Reduce repetitive manual entry and follow-up work.
- Expand reporting and operational visibility.
- Strengthen maintainability and scalability without overcomplicating the current app.

## Guiding Approach

- Prioritize features that build directly on existing workflows.
- Favor operational improvements before larger platform expansion.
- Keep the app effective as an internal web tool before considering mobile or public integrations.
- Treat this as a planning guide, not a fixed implementation commitment.

## Implementation Status Snapshot (2026-07-21)

Status legend:
- Completed: Implemented and in active use.
- Partial: Implemented in meaningful part, with follow-on work still possible.
- Planned: Not yet implemented.

Completed
- CSV exports and print-friendly views for call lists, search, technician queues, and activity history.
- Saved views/filters, role default views, and recent-view shortcuts.
- Bulk call updates (status + assignment) with confirmation and audit tracking.
- Reusable customer/location records with autocomplete and merge/update management.
- Technician dashboard workflow improvements (availability/load cards, quick status updates, claim actions, richer job cards).
- Expanded activity logging for system-level events (user admin actions, settings updates, records management).
- Activity log pagination.
- Activity log filtering/search (actor, event type, field, date range, free-text query), including filtered export/print.

Partial
- Stronger admin visibility is implemented substantially, but retention controls and deeper analytics are still open.
- Performance/pagination has started (activity), but call/search views can still be paginated later.
- Logging/monitoring has expanded significantly, but alerting and deeper diagnostics remain optional future work.

## Immediate Engineering TODO (2026-07-30)

1. Session hardening for authentication flows (in progress)
- Set stricter session cookie params (HttpOnly, SameSite, HTTPS-aware secure flag).
- Keep strict session mode enabled to reduce fixation risks.

2. Installer error handling hardening (in progress)
- Stop exposing raw SQL exception text to users during installer connectivity checks.
- Log full exception details server-side for troubleshooting.

3. Admin user-management input validation (in progress)
- Enforce allowed role values server-side.
- Require technician linkage when role is Technician.
- Require stronger password length for created/updated passwords.

4. Follow-up quality pass (planned)
- Add lightweight regression checks for login, installer validation, and admin user edits.
- Capture any remaining high-value hardening opportunities discovered in this pass.

## Phase 1: High-Value Workflow Improvements

These items should deliver immediate value with minimal disruption to the current app model.

### 1. Exports and Print-Friendly Views (Completed)

- Add CSV export for call lists, search results, technician queues, and activity history.
- Add print-friendly summaries for daily dispatch or technician job lists.

Why it fits:
The app already stores structured service-call and activity data, but there is no easy way to share, archive, or review that data outside the browser.

### 2. Saved Filters and Custom Views (Completed)

- Allow users to save common filters such as unassigned calls or closed-today calls.
- Add quick access to recently used searches.
- Let administrators define default views for office staff or technicians if needed.

Why it fits:
Search and filtering already exist, so saved views would make the current workflow faster without changing the data model significantly.

### 3. Bulk Actions (Completed)

- Support multi-select actions for reassigning calls, updating statuses, or archiving cleanup items.
- Add confirmation prompts and audit logging for all bulk updates.
- Consider an undo-safe pattern for high-impact actions where practical.

Why it fits:
The current workflow likely handles calls one at a time. Bulk actions would reduce repetitive work for office staff and administrators.

### 4. Customer and Location Reuse (Partial)

- Add reusable customer and location records to reduce duplicate entry.
- Support autocomplete during new-call and edit-call workflows.
- Add quick access to prior calls for the same customer or location.

Why it fits:
The app currently centers on service calls rather than customer history, so structured reuse would improve consistency and speed.

### 5. Technician Workflow Refinements (Completed)

- Add clearer technician availability indicators.
- Improve job detail visibility from the technician dashboard.
- Add quicker inline updates for common status changes.

Why it fits:
Technician-facing functionality already exists, so this is a direct improvement to a core surface rather than a new product area.

## Phase 2: Service Lifecycle Enhancements

These features deepen the app’s usefulness across the full lifecycle of a service call.

### 1. Scheduling  -- low priority / not currently required

- Add scheduled date and time fields for planned service work.
- Provide queue views for upcoming appointments.
- Consider calendar-style views only if list-based scheduling becomes insufficient.

Why it fits:
Current call management appears focused on intake and status tracking. Scheduling would make the app more useful for planned work, not just reactive dispatch.

### 2. Time Tracking  -- low priority / not currently required

- Track start time, completion time, and optional labor duration.
- Allow manual adjustment where technicians do not work directly from the system in real time.
- Use this data later for reporting and workload analysis.

Why it fits:
Time-based metrics are a natural extension of service-call status management and will support future operational reporting.

### 3. Internal Notes and Communication History

- Add call-level notes for customer contact attempts, technician updates, and office follow-up.
- Distinguish between operational notes and customer-facing details if that distinction matters later.
- Keep note history visible so context is not lost during reassignment.

Why it fits:
Activity logging exists, but teams often also need human-readable running notes that are not limited to structured field changes.

### 4. Attachments

- Support attaching photos, invoices, forms, or supporting documents to a service call.
- Enforce file-size and file-type restrictions.
- Record upload activity in the audit trail.

Why it fits:
Many service workflows eventually need proof-of-work or supporting documentation tied to the job record.

### 5. Recurring Work and Templates

- Add reusable templates for common issue types or standard job setups.
- Support recurring calls for repeat maintenance visits.
- Allow default technician, priority, or location fields where useful.

Why it fits:
This would reduce repetitive data entry and support recurring operational patterns without changing the app’s core identity.

### 6. Notifications

- Add email notifications for assignment, reassignment, and status changes.
- Add admin-configurable notification rules.
- Consider SMS or push notifications later only if email proves insufficient.

Why it fits:
The current app tracks work well, but it does not appear to actively notify users when work changes.

## Phase 3: Reporting and Oversight

Once workflow depth improves, the next value area is operational visibility.

### 1. Management Dashboard

- Show open calls, overdue items, unassigned work, and recent completions.
- Highlight aging calls by priority.
- Surface technician workload at a glance.

Why it fits:
The app already captures much of the needed state; a dashboard would make the data more actionable.

### 2. Technician Performance Reporting

- Report on calls completed, average turnaround time, and current backlog.
- Compare workload distribution across technicians.
- Keep these metrics informational rather than punitive unless business rules clearly require otherwise.

Why it fits:
This builds on assignment and completion data already central to the app.

### 3. SLA and Aging Indicators

- Add priority-based aging thresholds.
- Flag calls that have remained open or idle too long.
- Make overdue conditions visible in search results, dashboards, and technician views.

Why it fits:
The current workflow would benefit from clearer escalation signals as usage volume grows.

### 4. Customer and Location History Views

- Show all past calls for a customer or site.
- Highlight repeat issue patterns.
- Support better context during intake and assignment.

Why it fits:
This is the natural follow-on to introducing reusable customer and location data.

### 5. Stronger Administrative Visibility (Partial)

- Expand reporting around user actions, resets, lockouts, and changes to system settings.
- Improve audit usability with better filtering and search.
- Consider retention controls for activity history if data volume grows.

Why it fits:
Administrative oversight already exists in basic form through the activity log and can be made more useful over time.

## Phase 4: Engineering and Platform Refinements

These items are important for long-term reliability and future expansion.

### 1. Performance and Pagination (Partial)

- Add pagination to large result sets such as search, activity history, and call lists.
- Review query efficiency as call volume grows.
- Add indexes where needed based on real usage patterns.

Why it fits:
The current app is small and practical, but list-heavy internal tools often hit performance issues as history accumulates.

### 2. Configuration and Deployment Improvements

- Move toward environment-based configuration where sensible.
- Clarify deployment expectations for development, staging, and production.
- Make installation and updates easier to repeat safely.

Why it fits:
This will help future maintenance without changing user-facing workflows.

### 3. Schema Change Discipline

- Introduce a simple migration strategy for database changes.
- Track schema evolution in a repeatable way.
- Keep installation and upgrade paths aligned.

Why it fits:
Feature growth will become harder to manage if schema updates remain ad hoc.

### 4. Automated Testing

- Add coverage for authentication, call creation, editing, assignment, status updates, and admin workflows.
- Prioritize tests for core business rules before UI details.
- Use lightweight tooling that matches the app’s current simplicity.

Why it fits:
As the feature set expands, manual verification alone will become slower and riskier.

### 5. Logging and Monitoring (Partial)

- Improve error visibility and operational diagnostics.
- Add clearer logging around failures in authentication, file uploads, and major workflow actions.
- Consider alerting only if the app becomes operationally critical enough to justify it.

Why it fits:
The existing logging foundation can be expanded into more useful operational feedback.

### 6. API and Integration Readiness

- Defer a public or internal API until the web workflow is stable and reporting needs are clearer.
- If needed later, start with read-only integration points before full remote write access.
- Use any API work to support clearly identified needs such as mobile access or data synchronization.

Why it fits:
API and mobile expansion make sense only after the current core workflow is mature.

## Suggested Delivery Order

1. Finish remaining Phase 1 partials (customer/location call-history shortcuts), list pagination for high-volume screens, and attachments.
2. notes/communication depth, and notifications.
3. recurring work.
4. Dashboards, aging/SLA views, and technician reporting depth.
5. Performance tuning, testing, deployment cleanup, and migration discipline.
6. API or mobile expansion only when usage volume and business needs justify it.

## Areas to Avoid Prematurely Expanding

- A mobile app before the browser workflow is fully tuned.
- Complex integrations before the data model is stable.
- Overengineered architecture changes without proven operational need.
- Reporting features that depend on data the app does not yet capture reliably.

## Review Checklist for Future Planning

- Does the feature solve a real friction point in the current workflow?
- Does it build on existing call, technician, admin, search, or activity functionality?
- Does it preserve the app’s strength as a simple internal service operations tool?
- Can it be introduced without making the system significantly harder to maintain?
- Does it create new data or reporting opportunities that justify its complexity?