<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/ReusableRecord.php';

class ServiceCall
{
    private const CLOSED_STATUSES = ['Complete', 'Cancelled'];

    public static function getStatusOptions(): array
    {
        return [
            'New',
            'Dispatched',
            'In Progress',
            'Waiting Parts',
            'On Hold',
            'Complete',
            'Cancelled',
        ];
    }

    public static function findAll(?string $search = null, ?string $statusFilter = 'all'): array
    {
        $pdo = Database::getConnection();
        $baseQuery = 'SELECT sc.*, t.name AS assigned_tech_name FROM service_calls sc
            LEFT JOIN technicians t ON sc.assigned_tech = t.id';
        $conditions = [];
        $params = [];

        $statusFilter = trim((string)$statusFilter);

        if ($statusFilter === 'open') {
            $conditions[] = self::notClosedStatusesSql('sc.status');
        } elseif ($statusFilter === 'complete') {
            $conditions[] = self::closedStatusesSql('sc.status');
        } elseif ($statusFilter === 'completed_today') {
            $conditions[] = self::closedStatusesSql('sc.status');
            $conditions[] = 'DATE(sc.updated_at) = CURDATE()';
        } elseif ($statusFilter === 'completed_week') {
            $conditions[] = self::closedStatusesSql('sc.status');
            $conditions[] = 'YEARWEEK(sc.updated_at, 1) = YEARWEEK(CURDATE(), 1)';
        } elseif ($statusFilter === 'incomplete') {
            $conditions[] = self::notClosedStatusesSql('sc.status');
        } elseif ($statusFilter === 'unassigned') {
            $conditions[] = 'sc.assigned_tech IS NULL';
            $conditions[] = self::notClosedStatusesSql('sc.status');
        } elseif (in_array($statusFilter, self::getStatusOptions(), true)) {
            $conditions[] = 'sc.status = :status';
            $params[':status'] = $statusFilter;
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%' . strtolower(trim($search)) . '%';
            $conditions[] = '(LOWER(CONCAT(
                COALESCE(sc.job_number, ""), " ",
                COALESCE(sc.customer, ""), " ",
                COALESCE(sc.location, ""), " ",
                COALESCE(sc.po_number, ""), " ",
                COALESCE(sc.reported_issue, "")
            )) LIKE :term)';
            $params[':term'] = $term;
        }

        $query = $baseQuery;
        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= ' ORDER BY sc.received_date DESC, sc.job_number DESC';

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getSummaryStats(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT
                COUNT(*) AS total_calls,
                SUM(CASE WHEN " . self::notClosedStatusesSql('status') . " THEN 1 ELSE 0 END) AS open_calls,
                SUM(CASE WHEN assigned_tech IS NULL AND " . self::notClosedStatusesSql('status') . " THEN 1 ELSE 0 END) AS unassigned_open_calls,
                SUM(CASE WHEN " . self::closedStatusesSql('status') . " AND DATE(updated_at) = CURDATE() THEN 1 ELSE 0 END) AS completed_today,
                SUM(CASE WHEN " . self::closedStatusesSql('status') . " AND YEARWEEK(updated_at, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) AS completed_this_week
             FROM service_calls"
        );
        $row = $stmt->fetch() ?: [];

        return [
            'total_calls' => (int)($row['total_calls'] ?? 0),
            'open_calls' => (int)($row['open_calls'] ?? 0),
            'unassigned_open_calls' => (int)($row['unassigned_open_calls'] ?? 0),
            'completed_today' => (int)($row['completed_today'] ?? 0),
            'completed_this_week' => (int)($row['completed_this_week'] ?? 0),
        ];
    }

    public static function getTechnicianDashboardStats(int|null $technicianId): array
    {
        if ($technicianId === null || $technicianId <= 0) {
            return [
                'active_jobs' => 0,
                'in_progress_jobs' => 0,
                'needs_attention_jobs' => 0,
                'completed_today' => 0,
                'unassigned_open_calls' => self::getSummaryStats()['unassigned_open_calls'],
            ];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT
                SUM(CASE WHEN assigned_tech = :technician_id AND " . self::notClosedStatusesSql('status') . " THEN 1 ELSE 0 END) AS active_jobs,
                SUM(CASE WHEN assigned_tech = :technician_id AND status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_jobs,
                SUM(CASE WHEN assigned_tech = :technician_id AND " . self::notClosedStatusesSql('status') . " AND status IN ('Waiting Parts', 'On Hold') THEN 1 ELSE 0 END) AS needs_attention_jobs,
                SUM(CASE WHEN assigned_tech = :technician_id AND " . self::closedStatusesSql('status') . " AND DATE(updated_at) = CURDATE() THEN 1 ELSE 0 END) AS completed_today,
                SUM(CASE WHEN assigned_tech IS NULL AND " . self::notClosedStatusesSql('status') . " THEN 1 ELSE 0 END) AS unassigned_open_calls
             FROM service_calls"
        );
        $stmt->execute([':technician_id' => $technicianId]);
        $row = $stmt->fetch() ?: [];

        return [
            'active_jobs' => (int)($row['active_jobs'] ?? 0),
            'in_progress_jobs' => (int)($row['in_progress_jobs'] ?? 0),
            'needs_attention_jobs' => (int)($row['needs_attention_jobs'] ?? 0),
            'completed_today' => (int)($row['completed_today'] ?? 0),
            'unassigned_open_calls' => (int)($row['unassigned_open_calls'] ?? 0),
        ];
    }

    public static function findActiveByTechnician(int|null $technicianId, int $limit = 8): array
    {
        if ($technicianId === null || $technicianId <= 0) {
            return [];
        }

        $safeLimit = max(1, min($limit, 500));
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT sc.*, t.name AS assigned_tech_name
             FROM service_calls sc
             LEFT JOIN technicians t ON sc.assigned_tech = t.id
             WHERE sc.assigned_tech = :technician_id
                             AND " . self::notClosedStatusesSql('sc.status') . "
             ORDER BY
               CASE sc.status
                 WHEN 'In Progress' THEN 1
                 WHEN 'Dispatched' THEN 2
                 WHEN 'Waiting Parts' THEN 3
                 WHEN 'On Hold' THEN 4
                 ELSE 5
               END,
               sc.received_date ASC
             LIMIT {$safeLimit}"
        );
        $stmt->execute([':technician_id' => $technicianId]);
        return $stmt->fetchAll();
    }

    public static function findClaimableOpenJobs(int $limit = 6): array
    {
        $safeLimit = max(1, min($limit, 500));
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT sc.*, t.name AS assigned_tech_name
             FROM service_calls sc
             LEFT JOIN technicians t ON sc.assigned_tech = t.id
             WHERE sc.assigned_tech IS NULL
                             AND " . self::notClosedStatusesSql('sc.status') . "
             ORDER BY
               sc.received_date ASC
             LIMIT {$safeLimit}"
        );
        return $stmt->fetchAll();
    }

    public static function findRecentActivity(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        $safeLimit = max(1, min($limit, 500));
        $safeOffset = max(0, $offset);
        [$conditions, $params] = self::buildRecentActivityConditions($filters);

        $query = 'SELECT h.*, sc.job_number, sc.customer, sc.location
             FROM service_call_history h
             LEFT JOIN service_calls sc ON h.service_call_id = sc.id';
        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $query .= ' ORDER BY h.created_at DESC, h.id DESC
             LIMIT ' . $safeLimit . ' OFFSET ' . $safeOffset;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countRecentActivity(array $filters = []): int
    {
        [$conditions, $params] = self::buildRecentActivityConditions($filters);

        $query = 'SELECT COUNT(*) AS activity_count
             FROM service_call_history h
             LEFT JOIN service_calls sc ON h.service_call_id = sc.id';
        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        return (int)($row['activity_count'] ?? 0);
    }

    public static function logSystemEvent(
        ?array $actor,
        string $fieldName,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $note = null
    ): void {
        self::logChange(null, $actor, $fieldName, $oldValue, $newValue, $note);
    }

    public static function getTechnicianWorkloadSummary(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT
                t.id,
                t.name,
                SUM(CASE WHEN " . self::notClosedStatusesSql('sc.status') . " THEN 1 ELSE 0 END) AS open_jobs,
                SUM(CASE WHEN sc.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_jobs,
                SUM(CASE WHEN " . self::notClosedStatusesSql('sc.status') . " AND sc.status IN ('Waiting Parts', 'On Hold') THEN 1 ELSE 0 END) AS needs_attention_open
             FROM technicians t
             LEFT JOIN service_calls sc ON sc.assigned_tech = t.id
             WHERE t.active = 1
             GROUP BY t.id, t.name
             ORDER BY open_jobs ASC, t.name ASC"
        );
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $openJobs = (int)($row['open_jobs'] ?? 0);
            if ($openJobs === 0) {
                $row['availability'] = 'Available';
            } elseif ($openJobs <= 3) {
                $row['availability'] = 'Normal Load';
            } else {
                $row['availability'] = 'Heavy Load';
            }
        }

        return $rows;
    }

    public static function findById(int $id): array|null
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT sc.*, t.name AS assigned_tech_name
             FROM service_calls sc
             LEFT JOIN technicians t ON sc.assigned_tech = t.id
             WHERE sc.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findRelatedCalls(int $currentId, ?string $customer, ?string $location, int $limit = 8): array
    {
        $customerKey = self::normalizeLookupValue($customer);
        $locationKey = self::normalizeLookupValue($location);
        if ($customerKey === null && $locationKey === null) {
            return [];
        }

        $safeLimit = max(1, min($limit, 25));
        $conditions = ['sc.id <> :current_id'];
        $params = [':current_id' => $currentId];

        if ($customerKey !== null && $locationKey !== null) {
            $conditions[] = '(LOWER(TRIM(sc.customer)) = :customer_key OR LOWER(TRIM(sc.location)) = :location_key)';
            $params[':customer_key'] = $customerKey;
            $params[':location_key'] = $locationKey;
        } elseif ($customerKey !== null) {
            $conditions[] = 'LOWER(TRIM(sc.customer)) = :customer_key';
            $params[':customer_key'] = $customerKey;
        } else {
            $conditions[] = 'LOWER(TRIM(sc.location)) = :location_key';
            $params[':location_key'] = $locationKey;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT sc.*, t.name AS assigned_tech_name
             FROM service_calls sc
             LEFT JOIN technicians t ON sc.assigned_tech = t.id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY sc.received_date DESC, sc.id DESC
             LIMIT ' . $safeLimit
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $matches = [];
            if ($customerKey !== null && self::normalizeLookupValue($row['customer'] ?? null) === $customerKey) {
                $matches[] = 'Customer';
            }
            if ($locationKey !== null && self::normalizeLookupValue($row['location'] ?? null) === $locationKey) {
                $matches[] = 'Location';
            }
            $row['match_label'] = empty($matches) ? 'Related' : implode(' + ', $matches);
        }
        unset($row);

        return $rows;
    }

    public static function findHistory(int $serviceCallId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM service_call_history WHERE service_call_id = :service_call_id ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([':service_call_id' => $serviceCallId]);
        return $stmt->fetchAll();
    }

    public static function save(array $data, int|null $id = null, ?array $actor = null): int
    {
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        $receivedDate = self::normalizeReceivedDate($data['received_date'] ?? '');

        if ($id === null) {
            $jobNumber = self::generateJobNumber($receivedDate);
            $stmt = $pdo->prepare(
                'INSERT INTO service_calls
                 (job_number, received_date, customer, location, contact, phone, email, po_number, reported_issue, internal_notes, assigned_tech, status, created_by, created_at, updated_at)
                 VALUES
                 (:job_number, :received_date, :customer, :location, :contact, :phone, :email, :po_number, :reported_issue, :internal_notes, :assigned_tech, :status, :created_by, :created_at, :updated_at)'
            );
            $stmt->execute([
                ':job_number' => $jobNumber,
                ':received_date' => $receivedDate,
                ':customer' => $data['customer'],
                ':location' => $data['location'],
                ':contact' => $data['contact'],
                ':phone' => $data['phone'],
                ':email' => $data['email'],
                ':po_number' => $data['po_number'],
                ':reported_issue' => $data['reported_issue'],
                ':internal_notes' => $data['internal_notes'],
                ':assigned_tech' => $data['assigned_tech'] ?: null,
                ':status' => $data['status'],
                ':created_by' => $data['created_by'],
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            ReusableRecord::syncFromServiceCall($data);
            $newId = (int)$pdo->lastInsertId();
            self::logChange($newId, $actor, 'created', null, 'created', 'Service call created');
            return $newId;
        }

        $oldCall = self::findById($id);
        $stmt = $pdo->prepare(
            'UPDATE service_calls SET
                 received_date = :received_date,
                 customer = :customer,
                 location = :location,
                 contact = :contact,
                 phone = :phone,
                 email = :email,
                 po_number = :po_number,
                 reported_issue = :reported_issue,
                 internal_notes = :internal_notes,
                 assigned_tech = :assigned_tech,
                 status = :status,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':received_date' => $receivedDate,
            ':customer' => $data['customer'],
            ':location' => $data['location'],
            ':contact' => $data['contact'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':po_number' => $data['po_number'],
            ':reported_issue' => $data['reported_issue'],
            ':internal_notes' => $data['internal_notes'],
            ':assigned_tech' => $data['assigned_tech'] ?: null,
            ':status' => $data['status'],
            ':updated_at' => $now,
            ':id' => $id,
        ]);

        ReusableRecord::syncFromServiceCall($data);

        self::logFieldChanges($id, $oldCall, $data, $actor);
        return $id;
    }

    public static function bulkUpdate(array $serviceCallIds, array $changes, array $actor): int
    {
        $normalizedIds = [];
        foreach ($serviceCallIds as $serviceCallId) {
            $intId = (int)$serviceCallId;
            if ($intId > 0) {
                $normalizedIds[$intId] = true;
            }
        }

        $ids = array_keys($normalizedIds);
        if (empty($ids)) {
            throw new InvalidArgumentException('Select at least one call for a bulk update.');
        }

        $status = null;
        if (array_key_exists('status', $changes)) {
            $status = trim((string)$changes['status']);
            if (!in_array($status, self::getStatusOptions(), true)) {
                throw new InvalidArgumentException('Invalid status selected for bulk update.');
            }
        }

        $assignedTech = null;
        $hasAssignedTech = false;
        if (array_key_exists('assigned_tech', $changes)) {
            $hasAssignedTech = true;
            $assignedTech = $changes['assigned_tech'] === null || $changes['assigned_tech'] === ''
                ? null
                : (int)$changes['assigned_tech'];
            if ($assignedTech !== null && $assignedTech <= 0) {
                throw new InvalidArgumentException('Invalid technician selected for bulk assignment.');
            }
        }

        if ($status === null && !$hasAssignedTech) {
            throw new InvalidArgumentException('No bulk update change was selected.');
        }

        $count = 0;
        foreach ($ids as $id) {
            $existing = self::findById($id);
            if (!$existing) {
                continue;
            }

            $data = self::buildTechnicianSaveData($existing);
            if ($status !== null) {
                $data['status'] = $status;
            }
            if ($hasAssignedTech) {
                $data['assigned_tech'] = $assignedTech;
            }

            self::save($data, $id, $actor);
            $count++;
        }

        return $count;
    }

    public static function claimForTechnician(int $serviceCallId, int $technicianId, array $actor): void
    {
        if ($technicianId <= 0) {
            throw new InvalidArgumentException('Your account must be linked to a technician profile before claiming jobs.');
        }

        $call = self::findById($serviceCallId);
        if (!$call) {
            throw new InvalidArgumentException('The selected job could not be found.');
        }
        if (!empty($call['assigned_tech']) || self::isClosedStatus((string)($call['status'] ?? ''))) {
            throw new InvalidArgumentException('This job cannot be claimed right now.');
        }

        $data = self::buildTechnicianSaveData($call);
        $data['assigned_tech'] = $technicianId;
        $data['internal_notes'] = self::appendTechnicianNote((string)($call['internal_notes'] ?? ''), 'claimed this job', $actor);

        self::save($data, $serviceCallId, $actor);
    }

    public static function updateAssignedTechnicianJob(int $serviceCallId, int $technicianId, string $status, string $technicianNote, array $actor): void
    {
        if (!in_array($status, self::getStatusOptions(), true)) {
            throw new InvalidArgumentException('Invalid status selected.');
        }

        $call = self::findById($serviceCallId);
        if (!$call) {
            throw new InvalidArgumentException('The selected job could not be found.');
        }
        if ((int)($call['assigned_tech'] ?? 0) !== $technicianId) {
            throw new InvalidArgumentException('This job is not assigned to you.');
        }

        $data = self::buildTechnicianSaveData($call);
        $data['status'] = $status;
        if ($technicianNote !== '') {
            $data['internal_notes'] = self::appendTechnicianNote((string)($call['internal_notes'] ?? ''), $technicianNote, $actor);
        }

        self::save($data, $serviceCallId, $actor);
    }

    public static function delete(int $serviceCallId, array $actor): bool
    {
        $call = self::findById($serviceCallId);
        if (!$call) {
            throw new InvalidArgumentException('The selected job could not be found.');
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $deleteHistoryStmt = $pdo->prepare('DELETE FROM service_call_history WHERE service_call_id = :id');
            $deleteHistoryStmt->execute([':id' => $serviceCallId]);

            $deleteCallStmt = $pdo->prepare('DELETE FROM service_calls WHERE id = :id LIMIT 1');
            $deleteCallStmt->execute([':id' => $serviceCallId]);

            $deleted = $deleteCallStmt->rowCount() > 0;
            $pdo->commit();

            if ($deleted) {
                Logger::warning('Service call permanently deleted', [
                    'service_call_id' => $serviceCallId,
                    'job_number' => $call['job_number'] ?? null,
                    'deleted_by_user_id' => $actor['id'] ?? null,
                    'deleted_by_name' => $actor['display_name'] ?? $actor['username'] ?? 'System',
                ]);
            }

            return $deleted;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private static function logFieldChanges(int $serviceCallId, ?array $oldCall, array $data, ?array $actor): void
    {
        $fields = ['received_date', 'customer', 'location', 'contact', 'phone', 'email', 'po_number', 'reported_issue', 'internal_notes', 'assigned_tech', 'status'];
        foreach ($fields as $field) {
            $oldValue = self::normalizeHistoryValue($field, $oldCall[$field] ?? null);
            $newValue = self::normalizeHistoryValue($field, $data[$field] ?? null);
            if ($oldValue === $newValue) {
                continue;
            }
            self::logChange($serviceCallId, $actor, $field, $oldValue, $newValue);
        }
    }

    private static function logChange(?int $serviceCallId, ?array $actor, string $fieldName, ?string $oldValue, ?string $newValue, ?string $note = null): void
    {
        if ($serviceCallId !== null && $serviceCallId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO service_call_history (service_call_id, changed_by_user_id, changed_by_name, field_name, old_value, new_value, note, created_at)
             VALUES (:service_call_id, :changed_by_user_id, :changed_by_name, :field_name, :old_value, :new_value, :note, NOW())'
        );
        $stmt->execute([
            ':service_call_id' => $serviceCallId,
            ':changed_by_user_id' => $actor['id'] ?? null,
            ':changed_by_name' => $actor['display_name'] ?? $actor['username'] ?? 'System',
            ':field_name' => $fieldName,
            ':old_value' => $oldValue,
            ':new_value' => $newValue,
            ':note' => $note,
        ]);
    }

    private static function normalizeHistoryValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($field === 'assigned_tech') {
            return self::resolveTechnicianName((int)$value);
        }

        if ($field === 'received_date') {
            try {
                return (new DateTime((string)$value))->format('Y-m-d H:i:s');
            } catch (Exception $exception) {
                return (string)$value;
            }
        }

        return (string)$value;
    }

    private static function resolveTechnicianName(int|null $technicianId): ?string
    {
        if ($technicianId === null || $technicianId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT name FROM technicians WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $technicianId]);
        $row = $stmt->fetch();
        return $row ? (string)$row['name'] : (string)$technicianId;
    }

    private static function buildTechnicianSaveData(array $call): array
    {
        return [
            'received_date' => date('Y-m-d\TH:i', strtotime((string)$call['received_date'])),
            'customer' => $call['customer'],
            'location' => $call['location'],
            'contact' => $call['contact'],
            'phone' => $call['phone'],
            'email' => $call['email'],
            'po_number' => $call['po_number'],
            'reported_issue' => $call['reported_issue'],
            'internal_notes' => $call['internal_notes'] ?? '',
            'assigned_tech' => $call['assigned_tech'],
            'status' => $call['status'],
            'created_by' => $call['created_by'],
        ];
    }

    private static function closedStatusesSql(string $column): string
    {
        return $column . " IN ('Complete', 'Cancelled')";
    }

    private static function normalizeLookupValue(?string $value): ?string
    {
        $normalized = strtolower(trim((string)$value));
        return $normalized === '' ? null : $normalized;
    }

    private static function notClosedStatusesSql(string $column): string
    {
        return $column . " NOT IN ('Complete', 'Cancelled')";
    }

    private static function isClosedStatus(string $status): bool
    {
        return in_array($status, self::CLOSED_STATUSES, true);
    }

    private static function buildRecentActivityConditions(array $filters): array
    {
        $conditions = [];
        $params = [];

        $eventType = trim((string)($filters['event_type'] ?? 'all'));
        if ($eventType === 'service_call') {
            $conditions[] = 'h.service_call_id IS NOT NULL';
        } elseif ($eventType === 'system') {
            $conditions[] = 'h.service_call_id IS NULL';
        }

        $fieldName = trim((string)($filters['field_name'] ?? ''));
        if ($fieldName !== '') {
            $conditions[] = 'h.field_name = :activity_field_name';
            $params[':activity_field_name'] = $fieldName;
        }

        $actor = trim((string)($filters['actor'] ?? ''));
        if ($actor !== '') {
            $conditions[] = 'LOWER(COALESCE(h.changed_by_name, "")) LIKE :activity_actor';
            $params[':activity_actor'] = '%' . strtolower($actor) . '%';
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $conditions[] = 'h.created_at >= :activity_date_from';
            $params[':activity_date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $date = DateTime::createFromFormat('Y-m-d', $dateTo);
            if ($date instanceof DateTime) {
                $date->modify('+1 day');
                $conditions[] = 'h.created_at < :activity_date_to_exclusive';
                $params[':activity_date_to_exclusive'] = $date->format('Y-m-d') . ' 00:00:00';
            }
        }

        $query = trim((string)($filters['query'] ?? ''));
        if ($query !== '') {
            $conditions[] = 'LOWER(CONCAT(
                COALESCE(sc.job_number, ""), " ",
                COALESCE(sc.customer, ""), " ",
                COALESCE(sc.location, ""), " ",
                COALESCE(h.changed_by_name, ""), " ",
                COALESCE(h.field_name, ""), " ",
                COALESCE(h.old_value, ""), " ",
                COALESCE(h.new_value, ""), " ",
                COALESCE(h.note, "")
            )) LIKE :activity_query';
            $params[':activity_query'] = '%' . strtolower($query) . '%';
        }

        return [$conditions, $params];
    }

    private static function appendTechnicianNote(string $existingNotes, string $note, array $actor): string
    {
        $timestamp = date('Y-m-d H:i');
        $prefix = (string)($actor['display_name'] ?? $actor['username'] ?? 'Technician');
        $entry = "[{$timestamp}] {$prefix}: {$note}";
        return trim($existingNotes === '' ? $entry : $existingNotes . "\n\n" . $entry);
    }

    private static function generateJobNumber(string $receivedDate): string
    {
        $date = new DateTime($receivedDate);
        $monthCode = $date->format('my');
        $start = $date->format('Y-m-01 00:00:00');
        $end = (clone $date)->modify('first day of next month')->format('Y-m-01 00:00:00');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS monthly_count FROM service_calls
             WHERE received_date >= :start AND received_date < :end'
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        $row = $stmt->fetch();
        $sequence = ($row && $row['monthly_count']) ? ((int)$row['monthly_count'] + 1) : 1;

        return sprintf('%s-%03d', $monthCode, $sequence);
    }

    private static function normalizeReceivedDate(mixed $value): string
    {
        $input = trim((string)$value);
        if ($input === '') {
            throw new InvalidArgumentException('Date / Time Received is required.');
        }

        $formats = ['Y-m-d\\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $input);
            if ($date instanceof DateTime) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        try {
            return (new DateTime($input))->format('Y-m-d H:i:s');
        } catch (Exception $exception) {
            throw new InvalidArgumentException('Date / Time Received is invalid.');
        }
    }
}
