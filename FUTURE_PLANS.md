# Servicebook Future Plan

Last updated: 2026-08-07

This roadmap reflects current implemented functionality and focuses on practical next steps for an internal service operations tool.

## Planning Principles

- Keep workflows fast for office staff and technicians.
- Prefer reliability and maintainability over feature sprawl.
- Avoid scheduling-heavy expansion unless business needs change.
- Ship incremental improvements with clear operational value.

## Current Baseline

Already implemented:
- call intake/edit/search/assignment/status lifecycle
- technician dashboard and claim flow
- saved views, recent views, exports, print views, bulk updates
- reusable customer/location records with admin tools
- activity filtering, pagination, and system-event logging
- security hardening and installer hardening
- daily log rotation/retention
- full backup and restore controls in admin settings

## Near-Term Priorities

1. Attachments for service calls
- Add secure file attachments to call records.
- Enforce file type/size limits and upload protections.
- Log attachment uploads/deletes in activity history.

2. Internal note history improvements
- Improve visibility of operational notes over time.
- Add clearer chronology for office-to-technician context.

3. Regression safety checks
- Add lightweight automated checks for:
  - authentication/lockout behavior
  - call create/edit paths
  - admin user validation
  - backup create/restore flows

4. Deployment consistency
- Improve environment/config handling for repeatable installs.
- Document upgrade/maintenance routine for internal operators.

## Mid-Term Priorities

1. Reporting depth
- Add practical management summaries from existing data.
- Expand technician workload and completion trend visibility.
- Keep reporting informational and operationally useful.

2. Query/index tuning
- Review high-traffic list/search paths with real data volume.
- Add indexes and query improvements where impact is proven.

3. Schema discipline
- Introduce a clear migration workflow that balances safety and iteration speed.
- Keep install/update behavior predictable across environments.

## Deferred / Optional

These are intentionally deferred unless business requirements change:
- scheduling/calendar workflows
- SMS/push notifications
- public APIs or external integrations
- native mobile app
- billing/invoicing features

## Delivery Heuristic

Use this order for new work:
1. Features that reduce daily manual friction.
2. Features that improve reliability/traceability.
3. Features that improve reporting and oversight.
4. Platform expansion only after the first three stay stable.

## Change Control Notes

- Prefer isolated, reviewable commits per feature.
- Keep security and data integrity protections default-on.
- Preserve simple workflows over complex abstractions.
