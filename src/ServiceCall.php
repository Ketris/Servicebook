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
        } elseif (in_array($statusFilter, self::getStatusOptions(), true)) {
            $conditions[] = 'sc.status = :status';
            $params[':status'] = $statusFilter;
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $conditions[] = '(sc.job_number LIKE :term
                OR sc.customer LIKE :term
                OR sc.location LIKE :term
                OR sc.po_number LIKE :term
                OR sc.reported_issue LIKE :term)';
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
        $receivedDate = (new DateTime($data['received_date']))->format('Y-m-d H:i:s');

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
        $end = $date->modify('first day of next month')->format('Y-m-01 00:00:00');

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
}
