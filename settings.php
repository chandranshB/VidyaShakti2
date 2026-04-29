<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        $pdo->beginTransaction();

        // 1. Update Phone
        $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$phone, $user_id]);

        // 2. Handle Password Change (if requested)
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("All password fields are required to change your password.");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match.");
            }

            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $hash = $stmt->fetchColumn();

            if (!password_verify($current_password, $hash) && $current_password !== $hash) {
                // Check plaintext for legacy users just in case
                throw new Exception("Current password is incorrect.");
            }

            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user_id]);
        }

        $pdo->commit();
        $success_msg = "Security settings updated successfully.";

    } catch (Exception $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { // Unique constraint violation
            $error_msg = "That phone number is already registered to another account.";
        } else {
            $error_msg = $e->getMessage();
        }
    }
}

// Fetch current basic user data
$stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
    <div>
        <h2 style="margin-bottom: 0.5rem;">Settings & Security</h2>
        <p style="margin: 0; color: var(--text-secondary);">Manage your credentials and secure your account.</p>
    </div>
</div>

<?php if ($success_msg): ?>
    <div class="badge badge-success mb-3" style="font-size: 1rem; padding: 0.75rem 1rem; display: flex; gap: 0.5rem; width: fit-content;">
        <i data-feather="check-circle" style="width: 18px; height: 18px;"></i>
        <?= htmlspecialchars($success_msg) ?>
    </div>
<?php endif; ?>

<?php if ($error_msg): ?>
    <div class="badge badge-danger mb-3" style="font-size: 1rem; padding: 0.75rem 1rem; display: flex; gap: 0.5rem; width: fit-content;">
        <i data-feather="alert-circle" style="width: 18px; height: 18px;"></i>
        <?= htmlspecialchars($error_msg) ?>
    </div>
<?php endif; ?>

<div class="surface-card" style="padding: 2rem; max-width: 800px;">
    <form method="POST">
        <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; color: var(--text-primary);">Login Credentials</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. 9876543210" required>
            </div>
        </div>

        <h3 style="font-size: 1.1rem; font-weight: 600; margin: 2rem 0 1.5rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; color: var(--text-primary);">Change Password</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="Required if changing password">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="New Password">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password">
            </div>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
            <button type="submit" class="btn btn-primary" style="height: 50px; font-size: 1.05rem; padding: 0 2rem;">
                <i data-feather="shield"></i> Update Security
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
