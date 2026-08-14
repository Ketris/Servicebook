<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/ReusableRecord.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();

$search = trim((string)($_GET['search'] ?? ''));
$success = '';
$error = '';

$formatRecordSummary = static function (array $record, array $fieldMap): string {
    $parts = [];
    foreach ($fieldMap as $field => $label) {
        $value = trim((string)($record[$field] ?? ''));
        $parts[] = $label . '=' . ($value !== '' ? $value : '-');
    }

    return implode('; ', $parts);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $error = 'Your session expired. Please reload and try again.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));

        try {
            if ($action === 'update_customer') {
                $customerId = (int)($_POST['customer_id'] ?? 0);
                $customerName = trim((string)($_POST['customer_name'] ?? ''));
                $beforeCustomer = ReusableRecord::findCustomerById($customerId) ?? [];
                $afterCustomer = [
                    'customer_name' => $customerName,
                    'default_contact' => trim((string)($_POST['default_contact'] ?? '')),
                    'default_phone' => trim((string)($_POST['default_phone'] ?? '')),
                    'default_email' => trim((string)($_POST['default_email'] ?? '')),
                ];
                ReusableRecord::updateCustomer($customerId, [
                    'customer_name' => $customerName,
                    'default_contact' => $afterCustomer['default_contact'],
                    'default_phone' => $afterCustomer['default_phone'],
                    'default_email' => $afterCustomer['default_email'],
                ]);
                $success = 'Customer record updated.';
                ServiceCall::logSystemEvent(
                    $user,
                    'customer_record',
                    $formatRecordSummary($beforeCustomer, [
                        'customer_name' => 'Name',
                        'default_contact' => 'Contact',
                        'default_phone' => 'Phone',
                        'default_email' => 'Email',
                    ]),
                    $formatRecordSummary($afterCustomer, [
                        'customer_name' => 'Name',
                        'default_contact' => 'Contact',
                        'default_phone' => 'Phone',
                        'default_email' => 'Email',
                    ]),
                    'Updated customer record ID ' . $customerId
                );
            } elseif ($action === 'merge_customer') {
                $sourceId = (int)($_POST['source_customer_id'] ?? 0);
                $targetId = (int)($_POST['target_customer_id'] ?? 0);
                ReusableRecord::mergeCustomers($sourceId, $targetId);
                $success = 'Customer records merged.';
                ServiceCall::logSystemEvent($user, 'customer_merge', (string)$sourceId, (string)$targetId, 'Merged customer records');
            } elseif ($action === 'update_location') {
                $locationId = (int)($_POST['location_id'] ?? 0);
                $locationName = trim((string)($_POST['location_name'] ?? ''));
                $beforeLocation = ReusableRecord::findLocationById($locationId) ?? [];
                $afterLocation = [
                    'location_name' => $locationName,
                    'customer_record_id' => (int)($_POST['customer_record_id'] ?? 0),
                    'default_contact' => trim((string)($_POST['default_contact'] ?? '')),
                    'default_phone' => trim((string)($_POST['default_phone'] ?? '')),
                    'default_email' => trim((string)($_POST['default_email'] ?? '')),
                ];
                ReusableRecord::updateLocation($locationId, [
                    'location_name' => $locationName,
                    'customer_record_id' => $afterLocation['customer_record_id'],
                    'default_contact' => $afterLocation['default_contact'],
                    'default_phone' => $afterLocation['default_phone'],
                    'default_email' => $afterLocation['default_email'],
                ]);
                $success = 'Location record updated.';
                ServiceCall::logSystemEvent(
                    $user,
                    'location_record',
                    $formatRecordSummary($beforeLocation, [
                        'location_name' => 'Name',
                        'customer_record_id' => 'Customer ID',
                        'default_contact' => 'Contact',
                        'default_phone' => 'Phone',
                        'default_email' => 'Email',
                    ]),
                    $formatRecordSummary($afterLocation, [
                        'location_name' => 'Name',
                        'customer_record_id' => 'Customer ID',
                        'default_contact' => 'Contact',
                        'default_phone' => 'Phone',
                        'default_email' => 'Email',
                    ]),
                    'Updated location record ID ' . $locationId
                );
            } elseif ($action === 'merge_location') {
                $sourceId = (int)($_POST['source_location_id'] ?? 0);
                $targetId = (int)($_POST['target_location_id'] ?? 0);
                ReusableRecord::mergeLocations($sourceId, $targetId);
                $success = 'Location records merged.';
                ServiceCall::logSystemEvent($user, 'location_merge', (string)$sourceId, (string)$targetId, 'Merged location records');
            } else {
                $error = 'Unknown records action.';
            }

            if ($success !== '') {
                Logger::info('Admin updated reusable records', [
                    'admin_user_id' => $user['id'] ?? null,
                    'action' => $action,
                ]);
            }
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            $error = 'Unable to save reusable record changes right now.';
            Logger::error('Unexpected admin reusable record error', [
                'admin_user_id' => $user['id'] ?? null,
                'action' => $action ?? '',
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}

$customers = ReusableRecord::listCustomers($search, 300);
$locations = ReusableRecord::listLocations($search, 300);

Template::render('pages/admin_records', [
    'title' => 'Reusable Records',
    'user' => $user,
    'search' => $search,
    'customers' => $customers,
    'locations' => $locations,
    'success' => $success,
    'error' => $error,
], 'layouts/app');
