<?php
require_once __DIR__ . '/Database.php';

class ReusableRecord
{
    public static function listCustomers(string $search = '', int $limit = 250): array
    {
        self::backfillIfEmpty();

        $safeLimit = max(25, min($limit, 1000));
        $pdo = Database::getConnection();
        $params = [];
        $where = '';
        if (trim($search) !== '') {
            $where = 'WHERE LOWER(c.customer_name) LIKE :term';
            $params[':term'] = '%' . mb_strtolower(trim($search)) . '%';
        }

        $stmt = $pdo->prepare(
            'SELECT c.*, COUNT(l.id) AS location_count
             FROM customer_records c
             LEFT JOIN location_records l ON l.customer_record_id = c.id
             ' . $where . '
             GROUP BY c.id
             ORDER BY c.last_used_at DESC, c.customer_name ASC
             LIMIT ' . $safeLimit
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function listLocations(string $search = '', int $limit = 250): array
    {
        self::backfillIfEmpty();

        $safeLimit = max(25, min($limit, 1000));
        $pdo = Database::getConnection();
        $params = [];
        $where = '';
        if (trim($search) !== '') {
            $where = 'WHERE LOWER(l.location_name) LIKE :term1 OR LOWER(COALESCE(c.customer_name, "")) LIKE :term2';
            $term = '%' . mb_strtolower(trim($search)) . '%';
            $params[':term1'] = $term;
            $params[':term2'] = $term;
        }

        $stmt = $pdo->prepare(
            'SELECT l.*, c.customer_name
             FROM location_records l
             LEFT JOIN customer_records c ON l.customer_record_id = c.id
             ' . $where . '
             ORDER BY l.last_used_at DESC, l.location_name ASC
             LIMIT ' . $safeLimit
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function updateCustomer(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid customer record selected.');
        }

        $name = trim((string)($data['customer_name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Customer name is required.');
        }

        $pdo = Database::getConnection();
        $current = self::findCustomerById($id);
        if (!$current) {
            throw new InvalidArgumentException('Customer record no longer exists.');
        }

        $oldKey = (string)$current['customer_key'];
        $newKey = self::buildKey($name);

        $existingStmt = $pdo->prepare('SELECT id FROM customer_records WHERE customer_key = :customer_key AND id <> :id LIMIT 1');
        $existingStmt->execute([
            ':customer_key' => $newKey,
            ':id' => $id,
        ]);
        $existing = $existingStmt->fetch();
        if ($existing) {
            throw new InvalidArgumentException('Another customer already has that name. Use merge instead.');
        }

        $stmt = $pdo->prepare(
            'UPDATE customer_records
             SET customer_key = :customer_key,
                 customer_name = :customer_name,
                 default_contact = :default_contact,
                 default_phone = :default_phone,
                 default_email = :default_email,
                 updated_at = NOW(),
                 last_used_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':customer_key' => $newKey,
            ':customer_name' => mb_substr($name, 0, 255),
            ':default_contact' => mb_substr(trim((string)($data['default_contact'] ?? '')), 0, 150),
            ':default_phone' => mb_substr(trim((string)($data['default_phone'] ?? '')), 0, 100),
            ':default_email' => mb_substr(trim((string)($data['default_email'] ?? '')), 0, 255),
            ':id' => $id,
        ]);

        if ($oldKey !== $newKey) {
            $updateCallsStmt = $pdo->prepare('UPDATE service_calls SET customer = :new_name WHERE LOWER(TRIM(customer)) = :old_key');
            $updateCallsStmt->execute([
                ':new_name' => $name,
                ':old_key' => $oldKey,
            ]);
        }
    }

    public static function mergeCustomers(int $sourceId, int $targetId): void
    {
        if ($sourceId <= 0 || $targetId <= 0 || $sourceId === $targetId) {
            throw new InvalidArgumentException('Select two different customer records to merge.');
        }

        $pdo = Database::getConnection();
        $source = self::findCustomerById($sourceId);
        $target = self::findCustomerById($targetId);
        if (!$source || !$target) {
            throw new InvalidArgumentException('One or both customer records no longer exist.');
        }

        $pdo->beginTransaction();
        try {
            $updateTarget = $pdo->prepare(
                'UPDATE customer_records
                 SET default_contact = CASE WHEN (default_contact IS NULL OR default_contact = "") THEN :source_contact ELSE default_contact END,
                     default_phone = CASE WHEN (default_phone IS NULL OR default_phone = "") THEN :source_phone ELSE default_phone END,
                     default_email = CASE WHEN (default_email IS NULL OR default_email = "") THEN :source_email ELSE default_email END,
                     updated_at = NOW(),
                     last_used_at = NOW()
                 WHERE id = :target_id'
            );
            $updateTarget->execute([
                ':source_contact' => (string)($source['default_contact'] ?? ''),
                ':source_phone' => (string)($source['default_phone'] ?? ''),
                ':source_email' => (string)($source['default_email'] ?? ''),
                ':target_id' => $targetId,
            ]);

            $moveLocations = $pdo->prepare('UPDATE location_records SET customer_record_id = :target_id, updated_at = NOW() WHERE customer_record_id = :source_id');
            $moveLocations->execute([
                ':target_id' => $targetId,
                ':source_id' => $sourceId,
            ]);

            $renameCalls = $pdo->prepare('UPDATE service_calls SET customer = :target_name WHERE LOWER(TRIM(customer)) = :source_key');
            $renameCalls->execute([
                ':target_name' => (string)$target['customer_name'],
                ':source_key' => (string)$source['customer_key'],
            ]);

            $deleteSource = $pdo->prepare('DELETE FROM customer_records WHERE id = :source_id');
            $deleteSource->execute([':source_id' => $sourceId]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function updateLocation(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid location record selected.');
        }

        $name = trim((string)($data['location_name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Location name is required.');
        }

        $pdo = Database::getConnection();
        $current = self::findLocationById($id);
        if (!$current) {
            throw new InvalidArgumentException('Location record no longer exists.');
        }

        $oldKey = (string)$current['location_key'];
        $newKey = self::buildKey($name);

        $existingStmt = $pdo->prepare('SELECT id FROM location_records WHERE location_key = :location_key AND id <> :id LIMIT 1');
        $existingStmt->execute([
            ':location_key' => $newKey,
            ':id' => $id,
        ]);
        $existing = $existingStmt->fetch();
        if ($existing) {
            throw new InvalidArgumentException('Another location already has that name. Use merge instead.');
        }

        $customerRecordId = isset($data['customer_record_id']) && (int)$data['customer_record_id'] > 0
            ? (int)$data['customer_record_id']
            : null;

        $stmt = $pdo->prepare(
            'UPDATE location_records
             SET location_key = :location_key,
                 location_name = :location_name,
                 customer_record_id = :customer_record_id,
                 default_contact = :default_contact,
                 default_phone = :default_phone,
                 default_email = :default_email,
                 updated_at = NOW(),
                 last_used_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':location_key' => $newKey,
            ':location_name' => mb_substr($name, 0, 255),
            ':customer_record_id' => $customerRecordId,
            ':default_contact' => mb_substr(trim((string)($data['default_contact'] ?? '')), 0, 150),
            ':default_phone' => mb_substr(trim((string)($data['default_phone'] ?? '')), 0, 100),
            ':default_email' => mb_substr(trim((string)($data['default_email'] ?? '')), 0, 255),
            ':id' => $id,
        ]);

        if ($oldKey !== $newKey) {
            $updateCallsStmt = $pdo->prepare('UPDATE service_calls SET location = :new_name WHERE LOWER(TRIM(location)) = :old_key');
            $updateCallsStmt->execute([
                ':new_name' => $name,
                ':old_key' => $oldKey,
            ]);
        }
    }

    public static function mergeLocations(int $sourceId, int $targetId): void
    {
        if ($sourceId <= 0 || $targetId <= 0 || $sourceId === $targetId) {
            throw new InvalidArgumentException('Select two different location records to merge.');
        }

        $pdo = Database::getConnection();
        $source = self::findLocationById($sourceId);
        $target = self::findLocationById($targetId);
        if (!$source || !$target) {
            throw new InvalidArgumentException('One or both location records no longer exist.');
        }

        $pdo->beginTransaction();
        try {
            $updateTarget = $pdo->prepare(
                'UPDATE location_records
                 SET customer_record_id = CASE WHEN customer_record_id IS NULL THEN :source_customer_id ELSE customer_record_id END,
                     default_contact = CASE WHEN (default_contact IS NULL OR default_contact = "") THEN :source_contact ELSE default_contact END,
                     default_phone = CASE WHEN (default_phone IS NULL OR default_phone = "") THEN :source_phone ELSE default_phone END,
                     default_email = CASE WHEN (default_email IS NULL OR default_email = "") THEN :source_email ELSE default_email END,
                     updated_at = NOW(),
                     last_used_at = NOW()
                 WHERE id = :target_id'
            );
            $updateTarget->execute([
                ':source_customer_id' => $source['customer_record_id'] !== null ? (int)$source['customer_record_id'] : null,
                ':source_contact' => (string)($source['default_contact'] ?? ''),
                ':source_phone' => (string)($source['default_phone'] ?? ''),
                ':source_email' => (string)($source['default_email'] ?? ''),
                ':target_id' => $targetId,
            ]);

            $renameCalls = $pdo->prepare('UPDATE service_calls SET location = :target_name WHERE LOWER(TRIM(location)) = :source_key');
            $renameCalls->execute([
                ':target_name' => (string)$target['location_name'],
                ':source_key' => (string)$source['location_key'],
            ]);

            $deleteSource = $pdo->prepare('DELETE FROM location_records WHERE id = :source_id');
            $deleteSource->execute([':source_id' => $sourceId]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function syncFromServiceCall(array $callData): void
    {
        $customerName = trim((string)($callData['customer'] ?? ''));
        $locationName = trim((string)($callData['location'] ?? ''));

        if ($customerName === '' && $locationName === '') {
            return;
        }

        $contact = trim((string)($callData['contact'] ?? ''));
        $phone = trim((string)($callData['phone'] ?? ''));
        $email = trim((string)($callData['email'] ?? ''));

        $pdo = Database::getConnection();
        $customerId = null;

        if ($customerName !== '') {
            $customerKey = self::buildKey($customerName);
            $stmt = $pdo->prepare(
                'INSERT INTO customer_records
                 (customer_key, customer_name, default_contact, default_phone, default_email, created_at, updated_at, last_used_at)
                 VALUES
                 (:customer_key, :customer_name, :default_contact, :default_phone, :default_email, NOW(), NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                 customer_name = VALUES(customer_name),
                 default_contact = CASE WHEN VALUES(default_contact) <> "" THEN VALUES(default_contact) ELSE default_contact END,
                 default_phone = CASE WHEN VALUES(default_phone) <> "" THEN VALUES(default_phone) ELSE default_phone END,
                 default_email = CASE WHEN VALUES(default_email) <> "" THEN VALUES(default_email) ELSE default_email END,
                 updated_at = NOW(),
                 last_used_at = NOW()'
            );
            $stmt->execute([
                ':customer_key' => $customerKey,
                ':customer_name' => $customerName,
                ':default_contact' => $contact,
                ':default_phone' => $phone,
                ':default_email' => $email,
            ]);

            $idStmt = $pdo->prepare('SELECT id FROM customer_records WHERE customer_key = :customer_key LIMIT 1');
            $idStmt->execute([':customer_key' => $customerKey]);
            $row = $idStmt->fetch();
            if ($row) {
                $customerId = (int)$row['id'];
            }
        }

        if ($locationName !== '') {
            $locationKey = self::buildKey($locationName);
            $stmt = $pdo->prepare(
                'INSERT INTO location_records
                 (location_key, location_name, customer_record_id, default_contact, default_phone, default_email, created_at, updated_at, last_used_at)
                 VALUES
                 (:location_key, :location_name, :customer_record_id, :default_contact, :default_phone, :default_email, NOW(), NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                 location_name = VALUES(location_name),
                 customer_record_id = CASE WHEN VALUES(customer_record_id) IS NOT NULL THEN VALUES(customer_record_id) ELSE customer_record_id END,
                 default_contact = CASE WHEN VALUES(default_contact) <> "" THEN VALUES(default_contact) ELSE default_contact END,
                 default_phone = CASE WHEN VALUES(default_phone) <> "" THEN VALUES(default_phone) ELSE default_phone END,
                 default_email = CASE WHEN VALUES(default_email) <> "" THEN VALUES(default_email) ELSE default_email END,
                 updated_at = NOW(),
                 last_used_at = NOW()'
            );
            $stmt->execute([
                ':location_key' => $locationKey,
                ':location_name' => $locationName,
                ':customer_record_id' => $customerId,
                ':default_contact' => $contact,
                ':default_phone' => $phone,
                ':default_email' => $email,
            ]);
        }
    }

    public static function getFormData(int $limit = 150): array
    {
        self::backfillIfEmpty();

        $safeLimit = max(10, min($limit, 500));
        $pdo = Database::getConnection();

        $customerStmt = $pdo->query(
            'SELECT c.id, c.customer_name, c.default_contact, c.default_phone, c.default_email,
                    (
                        SELECT lr.location_name
                        FROM location_records lr
                        WHERE lr.customer_record_id = c.id
                        ORDER BY lr.last_used_at DESC, lr.id DESC
                        LIMIT 1
                    ) AS preferred_location
             FROM customer_records c
             ORDER BY c.last_used_at DESC, c.customer_name ASC
             LIMIT ' . $safeLimit
        );
        $customers = $customerStmt->fetchAll();

        $locationStmt = $pdo->query(
            'SELECT l.location_name, l.default_contact, l.default_phone, l.default_email,
                    c.customer_name
             FROM location_records l
             LEFT JOIN customer_records c ON l.customer_record_id = c.id
             ORDER BY l.last_used_at DESC, l.location_name ASC
             LIMIT ' . $safeLimit
        );
        $locations = $locationStmt->fetchAll();

        $customerNames = [];
        $customerProfiles = [];
        $customerLocations = [];
        foreach ($customers as $customer) {
            $name = trim((string)($customer['customer_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $customerNames[] = $name;
            $customerProfiles[self::buildKey($name)] = [
                'contact' => (string)($customer['default_contact'] ?? ''),
                'phone' => (string)($customer['default_phone'] ?? ''),
                'email' => (string)($customer['default_email'] ?? ''),
                'location' => (string)($customer['preferred_location'] ?? ''),
            ];
        }

        $locationNames = [];
        $locationProfiles = [];
        $locationProfilesByCustomer = [];
        foreach ($locations as $location) {
            $name = trim((string)($location['location_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $customerName = trim((string)($location['customer_name'] ?? ''));
            $customerKey = self::buildKey($customerName);
            $locationKey = self::buildKey($name);

            $locationNames[] = $name;
            if (!isset($locationProfiles[$locationKey])) {
                $locationProfiles[$locationKey] = [
                    'customer' => $customerName,
                    'contact' => (string)($location['default_contact'] ?? ''),
                    'phone' => (string)($location['default_phone'] ?? ''),
                    'email' => (string)($location['default_email'] ?? ''),
                ];
            }

            if ($customerKey !== '') {
                if (!isset($customerLocations[$customerKey])) {
                    $customerLocations[$customerKey] = [];
                }
                $customerLocations[$customerKey][] = $name;

                if (!isset($locationProfilesByCustomer[$customerKey])) {
                    $locationProfilesByCustomer[$customerKey] = [];
                }
                if (!isset($locationProfilesByCustomer[$customerKey][$locationKey])) {
                    $locationProfilesByCustomer[$customerKey][$locationKey] = [
                        'customer' => $customerName,
                        'contact' => (string)($location['default_contact'] ?? ''),
                        'phone' => (string)($location['default_phone'] ?? ''),
                        'email' => (string)($location['default_email'] ?? ''),
                    ];
                }
            }

            if (empty($locationProfiles[$locationKey]['customer']) && $customerName !== '') {
                $locationProfiles[$locationKey]['customer'] = $customerName;
            }
            if (empty($locationProfiles[$locationKey]['contact']) && !empty($location['default_contact'])) {
                $locationProfiles[$locationKey]['contact'] = (string)$location['default_contact'];
            }
            if (empty($locationProfiles[$locationKey]['phone']) && !empty($location['default_phone'])) {
                $locationProfiles[$locationKey]['phone'] = (string)$location['default_phone'];
            }
            if (empty($locationProfiles[$locationKey]['email']) && !empty($location['default_email'])) {
                $locationProfiles[$locationKey]['email'] = (string)$location['default_email'];
            }
        }

        // Fallback for environments where reusable tables are not yet fully populated.
        if (empty($customerNames) || empty($locationNames)) {
            $fallbackRows = $pdo->query(
                'SELECT customer, location, contact, phone, email
                 FROM service_calls
                 ORDER BY updated_at DESC, id DESC
                 LIMIT ' . ($safeLimit * 3)
            )->fetchAll();

            foreach ($fallbackRows as $row) {
                $customerName = trim((string)($row['customer'] ?? ''));
                if ($customerName !== '') {
                    $customerKey = self::buildKey($customerName);
                    $customerNames[] = $customerName;
                    if (!isset($customerProfiles[$customerKey])) {
                        $customerProfiles[$customerKey] = [
                            'contact' => trim((string)($row['contact'] ?? '')),
                            'phone' => trim((string)($row['phone'] ?? '')),
                            'email' => trim((string)($row['email'] ?? '')),
                            'location' => trim((string)($row['location'] ?? '')),
                        ];
                    }
                }

                $locationName = trim((string)($row['location'] ?? ''));
                if ($locationName !== '') {
                    $locationKey = self::buildKey($locationName);
                    $locationNames[] = $locationName;
                    if (!isset($locationProfiles[$locationKey])) {
                        $locationProfiles[$locationKey] = [
                            'customer' => trim((string)($row['customer'] ?? '')),
                            'contact' => trim((string)($row['contact'] ?? '')),
                            'phone' => trim((string)($row['phone'] ?? '')),
                            'email' => trim((string)($row['email'] ?? '')),
                        ];
                    }

                    if ($customerName !== '') {
                        if (!isset($customerLocations[$customerKey])) {
                            $customerLocations[$customerKey] = [];
                        }
                        $customerLocations[$customerKey][] = $locationName;

                        if (!isset($locationProfilesByCustomer[$customerKey])) {
                            $locationProfilesByCustomer[$customerKey] = [];
                        }
                        if (!isset($locationProfilesByCustomer[$customerKey][$locationKey])) {
                            $locationProfilesByCustomer[$customerKey][$locationKey] = [
                                'customer' => $customerName,
                                'contact' => trim((string)($row['contact'] ?? '')),
                                'phone' => trim((string)($row['phone'] ?? '')),
                                'email' => trim((string)($row['email'] ?? '')),
                            ];
                        }
                    }
                }
            }
        }

        foreach ($customerLocations as $customerKey => $locationsForCustomer) {
            $customerLocations[$customerKey] = array_values(array_unique($locationsForCustomer));
        }

        return [
            'customer_names' => array_values(array_unique($customerNames)),
            'location_names' => array_values(array_unique($locationNames)),
            'customer_profiles' => $customerProfiles,
            'location_profiles' => $locationProfiles,
            'customer_locations' => $customerLocations,
            'location_profiles_by_customer' => $locationProfilesByCustomer,
        ];
    }

    private static function backfillIfEmpty(): void
    {
        $pdo = Database::getConnection();
        $count = (int)$pdo->query('SELECT COUNT(*) FROM customer_records')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = $pdo->query(
            'SELECT customer, location, contact, phone, email
             FROM service_calls
             ORDER BY updated_at DESC, id DESC'
        )->fetchAll();

        foreach ($rows as $row) {
            self::syncFromServiceCall($row);
        }
    }

    public static function findCustomerById(int $id): array|null
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM customer_records WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findLocationById(int $id): array|null
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM location_records WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function buildKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}