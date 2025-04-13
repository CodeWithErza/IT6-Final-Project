<?php
require_once '../../helpers/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get expense data from POST
$expense_json = $_POST['expense'] ?? '';
if (empty($expense_json)) {
    http_response_code(400);
    exit('Expense data is required');
}

// Decode JSON data
$expense = json_decode($expense_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit('Invalid expense data format: ' . json_last_error_msg());
}

// Validate required fields
if (!isset($expense['date']) || !isset($expense['description']) || !isset($expense['amount'])) {
    http_response_code(400);
    exit('Missing required expense data fields');
}

try {
    // Get business settings
    $stmt = $conn->prepare("
        SELECT setting_name, setting_value 
        FROM settings 
        WHERE setting_group IN ('business', 'receipt')
    ");
    $stmt->execute();
    $settings_result = $stmt->fetchAll();

    // Convert settings to associative array
    $settings = [];
    foreach ($settings_result as $setting) {
        $settings[$setting['setting_name']] = $setting['setting_value'];
    }

    // Parse items list if it exists
    $items = [];
    if (!empty($expense['items_list'])) {
        $items = json_decode($expense['items_list'], true) ?: [];
    }

    // Generate HTML for expense report
    $html = '
        <div class="receipt-container bg-light p-3 rounded">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <img src="/ERC-POS/assets/images/ERC Logo.png" alt="Business Logo" style="max-width: 40px;">
                    <div class="ms-2">
                        <h5 class="mb-0">' . htmlspecialchars($settings['business_name'] ?? 'ERC Carinderia') . '</h5>
                        <div class="badge bg-' . ($expense['source'] === 'inventory' ? 'success' : 'primary') . '">
                            ' . ucfirst($expense['source']) . ' Expense Record
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Date</div>
                    <strong>' . date('M d, Y h:i A', strtotime($expense['date'])) . '</strong>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <div class="bg-white rounded p-2">
                        <span class="badge bg-' . match($expense['type']) {
                            'stock_in' => 'success',
                            'adjustment' => 'secondary',
                            'ingredient' => 'primary',
                            'utility' => 'info',
                            'salary' => 'success',
                            'rent' => 'warning',
                            'equipment' => 'secondary',
                            'maintenance' => 'dark',
                            default => 'light text-dark'
                        } . ' mb-1">' . ucfirst(str_replace('_', ' ', $expense['type'])) . '</span>
                        <div class="small text-muted">Recorded by ' . htmlspecialchars($expense['created_by']) . '</div>
                    </div>
                </div>
                ' . ($expense['supplier'] || isset($expense['invoice']) ? '
                <div class="col-sm-6">
                    <div class="bg-white rounded p-2">
                        ' . ($expense['supplier'] ? '
                        <div class="small text-muted">Supplier/Vendor</div>
                        <div class="mb-1"><strong>' . htmlspecialchars($expense['supplier']) . '</strong></div>
                        ' : '') . '
                        ' . (isset($expense['invoice']) ? '
                        <div class="small text-muted">Invoice #</div>
                        <div><strong>' . htmlspecialchars($expense['invoice']) . '</strong></div>
                        ' : '') . '
                    </div>
                </div>
                ' : '') . '
            </div>

            <div class="bg-white rounded p-2 mb-3">
                ' . ($expense['source'] === 'inventory' ? '
                <div class="small fw-bold mb-2">Item Details</div>
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="mb-1">' . htmlspecialchars($expense['details']['item_name']) . '</div>
                        <div class="small text-muted">
                            Quantity: ' . number_format($expense['details']['quantity']) . ' units<br>
                            Unit Price: ₱' . number_format($expense['details']['unit_price'], 2) . '
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">₱' . number_format($expense['amount'], 2) . '</div>
                    </div>
                </div>
                ' : '
                <div class="small fw-bold mb-2">Description</div>
                <div class="small">' . htmlspecialchars($expense['description']) . '</div>
                ') . '
            </div>

            <div class="bg-white rounded p-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-bold">Total Amount</div>
                    <div class="text-primary fw-bold">₱' . number_format($expense['amount'], 2) . '</div>
                </div>
                
                ' . (isset($expense['notes']) && $expense['notes'] ? '
                <div class="mt-2 pt-2 border-top">
                    <div class="text-muted small">' . nl2br(htmlspecialchars($expense['notes'])) . '</div>
                </div>
                ' : '') . '
            </div>

            <div class="text-center mt-2">
                <span class="text-muted" style="font-size: 0.7rem;">Generated on ' . date('Y-m-d g:i A') . '</span>
            </div>
        </div>
    ';

    echo $html;
} catch (Exception $e) {
    http_response_code(500);
    exit('Error loading expense details: ' . $e->getMessage());
} 