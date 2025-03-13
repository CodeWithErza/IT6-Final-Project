<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid px-4">
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card profile-card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <!-- User Info -->
                    <div class="text-center mb-4">
                        <div class="profile-avatar mb-3">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <span class="badge role-badge"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                    </div>

                    <!-- User Details -->
                    <div class="user-details mb-4">
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Username:</strong>
                            </div>
                            <div class="col-sm-8">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Role:</strong>
                            </div>
                            <div class="col-sm-8">
                                <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <strong>Last Login:</strong>
                            </div>
                            <div class="col-sm-8">
                                <?php echo $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password Form -->
                    <div class="change-password-section">
                        <h5 class="mb-3">Change Password</h5>
                        <form action="/ERC-POS/handlers/users/update_password.php" method="POST" class="change-password-form">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-card {
    border: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

.profile-card .card-header {
    background-color: var(--primary-color);
    color: white;
    border-bottom: none;
    padding: 1rem 1.5rem;
}

.profile-avatar {
    font-size: 5rem;
    color: var(--primary-color);
    opacity: 0.8;
}

.role-badge {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.user-details {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
}

.change-password-section {
    border-top: 1px solid #dee2e6;
    padding-top: 1.5rem;
    margin-top: 1.5rem;
}

.change-password-section h5 {
    color: var(--accent-color);
    font-weight: 600;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(210, 102, 95, 0.25);
}

.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(155, 29, 166, 0.3);
}

/* Alert Styles */
.alert {
    border: none;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.alert-success {
    background-color: rgba(40, 167, 69, 0.15);
    color: #28a745;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background-color: rgba(210, 102, 95, 0.15);
    color: var(--primary-color);
    border-left: 4px solid var(--primary-color);
}
</style>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 