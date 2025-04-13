<?php
require_once '../../helpers/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get expense ID
$expense_id = $_GET['id'] ?? '';
if (!$expense_id) {
    http_response_code(400);
    exit('Expense ID is required');
}

try {
    // Get expense data with all necessary information
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            u.username as created_by_name
        FROM expenses e
        LEFT JOIN users u ON e.created_by = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$expense_id]);
    $expense = $stmt->fetch();

    if (!$expense) {
        http_response_code(404);
        exit('Expense not found');
    }

    // Get settings
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

    // Generate receipt HTML
    $receipt_html = '
        <div class="receipt-container bg-light p-3 rounded">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <img src="/ERC-POS/assets/images/ERC Logo.png" alt="Business Logo" style="max-width: 40px;">
                    <div class="ms-2">
                        <h5 class="mb-0">' . htmlspecialchars($settings['business_name'] ?? 'ERC Carinderia') . '</h5>
                        <div class="badge bg-primary">Expense Record #' . $expense['id'] . '</div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Date</div>
                    <strong>' . date('M d, Y', strtotime($expense['expense_date'])) . '</strong>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <div class="bg-white rounded p-2">
                        <span class="badge bg-' . match($expense['expense_type']) {
                            'ingredient' => 'primary',
                            'utility' => 'info',
                            'salary' => 'success',
                            'rent' => 'warning',
                            'equipment' => 'secondary',
                            'maintenance' => 'dark',
                            default => 'light text-dark'
                        } . ' mb-1">' . ucfirst($expense['expense_type']) . '</span>
                        <div class="small text-muted">Recorded by ' . htmlspecialchars($expense['created_by_name']) . '</div>
                    </div>
                </div>
                ' . ($expense['supplier'] || $expense['invoice_number'] ? '
                <div class="col-sm-6">
                    <div class="bg-white rounded p-2">
                        ' . ($expense['supplier'] ? '
                        <div class="small text-muted">Supplier/Vendor</div>
                        <div class="mb-1"><strong>' . htmlspecialchars($expense['supplier']) . '</strong></div>
                        ' : '') . '
                        ' . ($expense['invoice_number'] ? '
                        <div class="small text-muted">Invoice #</div>
                        <div><strong>' . htmlspecialchars($expense['invoice_number']) . '</strong></div>
                        ' : '') . '
                    </div>
                </div>
                ' : '') . '
            </div>

            <div class="bg-white rounded p-2 mb-3">
                <div class="small fw-bold mb-2">Items/Details</div>
                ' . (!empty($items) ? '
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            ' . implode('', array_map(function($item) {
                                return '
                                <tr>
                                    <td>
                                        <div class="small">' . htmlspecialchars($item['description']) . '</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">' . 
                                            htmlspecialchars($item['quantity']) . ' ' . 
                                            htmlspecialchars($item['unit']) . ' × ₱' . 
                                            number_format($item['price'], 2) . 
                                        '</div>
                                    </td>
                                    <td class="text-end align-middle">₱' . number_format($item['quantity'] * $item['price'], 2) . '</td>
                                </tr>';
                            }, $items)) . '
                        </tbody>
                    </table>
                </div>
                ' : '
                <div class="small">' . htmlspecialchars($expense['description']) . '</div>
                ') . '
            </div>

            <div class="bg-white rounded p-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-bold">Total Amount</div>
                    <div class="text-primary fw-bold">₱' . number_format($expense['amount'], 2) . '</div>
                </div>
                
                ' . ($expense['notes'] ? '
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

    echo $receipt_html;
} catch (Exception $e) {
    http_response_code(500);
    exit('Error loading expense details: ' . $e->getMessage());
} 