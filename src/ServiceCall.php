<?php
require_once __DIR__ . '/Database.php';

class ServiceCall
{
    public static function getStatusOptions(): array
    {
        return [
            'New',
            'Dispatched',
            'In Progress',
            'Waiting Parts',
            'On Hold',
            'Complete',
        ];
    }

    public static function getPriorityOptions(): array
    {
        return [
            'Low',
            'Normal',
            'High',
            'Emergency',
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
            $conditions[] = 'sc.status <> :status_complete';
            $params[':status_complete'] = 'Complete';
        } elseif ($statusFilter === 'complete') {
            $conditions[] = 'sc.status = :status_complete';
            $params[':status_complete'] = 'Complete';
        } elseif ($statusFilter === 'completed_today') {
            $conditions[] = 'sc.status = :status_complete';
            $conditions[] = 'DATE(sc.updated_at) = CURDATE()';
            $params[':status_complete'] = 'Complete';
        } elseif ($statusFilter === 'completed_week') {
            $conditions[] = 'sc.status = :status_complete';
            $conditions[] = 'YEARWEEK(sc.updated_at, 1) = YEARWEEK(CURDATE(), 1)';
            $params[':status_complete'] = 'Complete';
        } elseif ($statusFilter === 'incomplete') {
            $conditions[] = 'sc.status <> :status_complete';
            $params[':status_complete'] = 'Complete';
        } elseif ($statusFilter === 'unassigned') {
            $conditions[] = 'sc.assigned_tech IS NULL';
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
                SUM(CASE WHEN status <> 'Complete' THEN 1 ELSE 0 END) AS open_calls,
                SUM(CASE WHEN assigned_tech IS NULL AND status <> 'Complete' THEN 1 ELSE 0 END) AS unassigned_open_calls,
                SUM(CASE WHEN status = 'Complete' AND DATE(updated_at) = CURDATE() THEN 1 ELSE 0 END) AS completed_today,
                SUM(CASE WHEN status = 'Complete' AND YEARWEEK(updated_at, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) AS completed_this_week
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

    public static function findRecentActivity(int $limit = 100): array
    {
        $safeLimit = max(1, min($limit, 500));
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT h.*, sc.job_number, sc.customer, sc.location
             FROM service_call_history h
             LEFT JOIN service_calls sc ON h.service_call_id = sc.id
             ORDER BY h.created_at DESC, h.id DESC
             LIMIT ' . $safeLimit
        );
        return $stmt->fetchAll();
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
                 (job_number, received_date, customer, location, contact, phone, email, po_number, reported_issue, internal_notes, assigned_tech, status, priority, created_by, created_at, updated_at)
                 VALUES
                 (:job_number, :received_date, :customer, :location, :contact, :phone, :email, :po_number, :reported_issue, :internal_notes, :assigned_tech, :status, :priority, :created_by, :created_at, :updated_at)'
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
                ':priority' => $data['priority'],
                ':created_by' => $data['created_by'],
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
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
                 priority = :priority,
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
            ':priority' => $data['priority'],
            ':updated_at' => $now,
            ':id' => $id,
        ]);

        self::logFieldChanges($id, $oldCall, $data, $actor);
        return $id;
    }

    private static function logFieldChanges(int $serviceCallId, ?array $oldCall, array $data, ?array $actor): void
    {
        $fields = ['received_date', 'customer', 'location', 'contact', 'phone', 'email', 'po_number', 'reported_issue', 'internal_notes', 'assigned_tech', 'status', 'priority'];
        foreach ($fields as $field) {
            $oldValue = self::normalizeHistoryValue($field, $oldCall[$field] ?? null);
            $newValue = self::normalizeHistoryValue($field, $data[$field] ?? null);
            if ($oldValue === $newValue) {
                continue;
            }
            self::logChange($serviceCallId, $actor, $field, $oldValue, $newValue);
        }
    }

    private static function logChange(int $serviceCallId, ?array $actor, string $fieldName, ?string $oldValue, ?string $newValue, ?string $note = null): void
    {
        if ($serviceCallId <= 0) {
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
