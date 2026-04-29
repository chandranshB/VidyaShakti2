<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cropped_image = $_POST['cropped_image'] ?? '';
    $cropped_signature = $_POST['cropped_signature'] ?? '';
    $name = trim($_POST['name'] ?? $user['name']);
    $email = trim($_POST['email'] ?? $user['email']);

    // Collect metadata fields
    $meta_data = [
        'dob' => $_POST['dob'] ?? '',
        'blood_group' => $_POST['blood_group'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'category' => $_POST['category'] ?? '',
        'religion' => $_POST['religion'] ?? '',
        'aadhar' => $_POST['aadhar'] ?? '',
        'abc_id' => $_POST['abc_id'] ?? '',
        'address' => $_POST['address'] ?? '',
        'district' => $_POST['district'] ?? '',
        'state' => $_POST['state'] ?? '',
        'pin' => $_POST['pin'] ?? '',
        'country' => $_POST['country'] ?? '',
        
        'school_10th' => $_POST['school_10th'] ?? '',
        'board_10th' => $_POST['board_10th'] ?? '',
        'year_10th' => $_POST['year_10th'] ?? '',
        'perc_10th' => $_POST['perc_10th'] ?? '',
        
        'school_12th' => $_POST['school_12th'] ?? '',
        'board_12th' => $_POST['board_12th'] ?? '',
        'year_12th' => $_POST['year_12th'] ?? '',
        'perc_12th' => $_POST['perc_12th'] ?? '',

        // Parent Details
        'father_name' => $_POST['father_name'] ?? '',
        'father_dob' => $_POST['father_dob'] ?? '',
        'father_mobile' => $_POST['father_mobile'] ?? '',
        'father_email' => $_POST['father_email'] ?? '',
        'father_occupation' => $_POST['father_occupation'] ?? '',
        'father_qualification' => $_POST['father_qualification'] ?? '',
        'father_income' => $_POST['father_income'] ?? '',

        'mother_name' => $_POST['mother_name'] ?? '',
        'mother_dob' => $_POST['mother_dob'] ?? '',
        'mother_mobile' => $_POST['mother_mobile'] ?? '',
        'mother_email' => $_POST['mother_email'] ?? '',
        'mother_occupation' => $_POST['mother_occupation'] ?? '',
        'mother_qualification' => $_POST['mother_qualification'] ?? '',
        'mother_income' => $_POST['mother_income'] ?? '',

        'grandparents_living' => $_POST['grandparents_living'] ?? 'NO',
        'joint_family' => $_POST['joint_family'] ?? 'NO',
    ];
    $metadata_json = json_encode($meta_data);

    try {
        // Build dynamic SET clauses
        $set_parts = ["name = ?", "email = ?", "metadata = ?"];
        $params = [$name, $email, $metadata_json];

        // Use phone number for clean, predictable filenames
        $phone = $user['phone'] ?? $user_id;

        // Handle Profile Image Upload (Base64)
        if (!empty($cropped_image)) {
            $image_base64 = base64_decode(explode(";base64,", $cropped_image)[1]);
            $filePath = 'uploads/profiles/' . $phone . '_photo.jpeg';

            if (!is_dir('uploads/profiles')) {
                mkdir('uploads/profiles', 0777, true);
            }

            // Delete old file if it exists (handles both old and new naming)
            if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                unlink($user['profile_image']);
            }

            file_put_contents($filePath, $image_base64);
            $set_parts[] = "profile_image = ?";
            $params[] = $filePath;
            $_SESSION['profile_image'] = $filePath;
        }

        // Handle Signature Image Upload (Base64)
        if (!empty($cropped_signature)) {
            $sig_base64 = base64_decode(explode(";base64,", $cropped_signature)[1]);
            $sigPath = 'uploads/signatures/' . $phone . '_sign.jpeg';

            if (!is_dir('uploads/signatures')) {
                mkdir('uploads/signatures', 0777, true);
            }

            // Delete old file if it exists
            if (!empty($user['signature_image']) && file_exists($user['signature_image'])) {
                unlink($user['signature_image']);
            }

            file_put_contents($sigPath, $sig_base64);
            $set_parts[] = "signature_image = ?";
            $params[] = $sigPath;
        }

        $params[] = $user_id;
        $update_query = "UPDATE users SET " . implode(", ", $set_parts) . " WHERE id = ?";

        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute($params);

        $_SESSION['user_name'] = $name;
        $success_msg = "Profile updated successfully!";
        
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
    } catch (PDOException $e) {
        $error_msg = "Database error: " . $e->getMessage();
    }
}
$meta = json_decode($user['metadata'] ?? '{}', true) ?: [];
?>
<?php include 'includes/header.php'; ?>

<!-- Add Cropper.js CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<!-- Add Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
.tabs-container {
    display: flex;
    gap: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 2rem;
    overflow-x: auto;
}
.tab-btn {
    background: none;
    border: none;
    padding: 0.75rem 0.25rem;
    color: var(--text-secondary);
    font-size: 1.05rem;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all var(--transition);
    white-space: nowrap;
}
.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}
.tab-btn:hover:not(.active) {
    color: var(--text-primary);
}
.tab-content {
    display: none;
    animation: fadeIn 0.3s ease-out forwards;
}
.tab-content.active {
    display: block;
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}
.form-group.full-width {
    grid-column: 1 / -1;
}
.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 1.5rem 0 1rem 0;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 0.5rem;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
}

/* ── Real-time Validation Styles ── */
.form-group { position: relative; }

.form-group .validation-msg {
    display: none;
    font-size: 0.78rem;
    font-weight: 500;
    margin-top: 0.35rem;
    padding-left: 0.15rem;
    align-items: center;
    gap: 0.3rem;
    animation: fadeIn 0.25s ease-out;
}
.form-group .validation-msg svg { width: 13px; height: 13px; flex-shrink: 0; }

.form-group.is-valid .form-control {
    border-color: var(--success);
    background: var(--input-bg);
}
.form-group.is-valid .form-control:focus {
    box-shadow: 0 0 0 4px rgba(30, 142, 62, 0.1);
    border-color: var(--success);
}
.form-group.is-valid .validation-msg {
    display: flex;
    color: var(--success);
}

.form-group.is-invalid .form-control {
    border-color: var(--danger);
    background: var(--input-bg);
    animation: fieldShake 0.4s ease;
}
.form-group.is-invalid .form-control:focus {
    box-shadow: 0 0 0 4px rgba(217, 48, 37, 0.1);
    border-color: var(--danger);
}
.form-group.is-invalid .validation-msg {
    display: flex;
    color: var(--danger);
}

@keyframes fieldShake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-4px); }
    40% { transform: translateX(4px); }
    60% { transform: translateX(-3px); }
    80% { transform: translateX(2px); }
}

/* Validation status icon inside input */
.form-group .field-status-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    width: 18px; height: 18px;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.form-group.is-valid .field-status-icon,
.form-group.is-invalid .field-status-icon { opacity: 1; }

/* Profile Completion Bar */
.completion-bar-wrap {
    margin-bottom: 1.5rem;
    padding: 1rem 1.25rem;
    background: var(--bg-surface);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
}
.completion-bar-wrap .completion-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}
.completion-bar-wrap .completion-label span:last-child {
    font-variant-numeric: tabular-nums;
}
.completion-bar-track {
    width: 100%;
    height: 6px;
    background: var(--input-bg);
    border-radius: 999px;
    overflow: hidden;
}
.completion-bar-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
    transition: width 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
    width: 0%;
}

/* Perfected Apple-like Flatpickr Theme */
.flatpickr-calendar {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(25px) saturate(200%) !important;
    -webkit-backdrop-filter: blur(25px) saturate(200%) !important;
    border: 1px solid rgba(128, 128, 128, 0.15) !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
    border-radius: 20px !important;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
}

[data-theme="dark"] .flatpickr-calendar {
    background: rgba(30, 30, 32, 0.75) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4) !important;
}

.flatpickr-calendar::before, .flatpickr-calendar::after {
    display: none !important; /* Remove triangle pointers */
}

.flatpickr-month, .flatpickr-current-month, .flatpickr-weekday {
    color: var(--text-primary) !important;
    fill: var(--text-primary) !important;
}

.flatpickr-day {
    color: var(--text-primary) !important;
    border-radius: 50% !important; /* Circular days */
    border: none !important;
    font-weight: 500;
}

.flatpickr-day.today {
    border: 1px solid var(--primary) !important;
    font-weight: 700;
}

.flatpickr-day:hover, .flatpickr-day.prevMonthDay:hover, .flatpickr-day.nextMonthDay:hover {
    background: var(--input-bg-hover) !important;
}

.flatpickr-day.selected {
    background: var(--primary) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3) !important;
    font-weight: 700;
    border-color: transparent !important;
}

/* Fix dropdown and year input colors */
.flatpickr-current-month .flatpickr-monthDropdown-months {
    color: var(--text-primary) !important;
    font-weight: 600;
    background: transparent !important;
    appearance: none;
}
.flatpickr-current-month .flatpickr-monthDropdown-months option {
    background: var(--bg-surface) !important;
    color: var(--text-primary) !important;
}

.flatpickr-current-month .numInputWrapper input.cur-year {
    color: var(--text-primary) !important;
    font-weight: 600;
}
.flatpickr-current-month .numInputWrapper span {
    border: none !important;
}
.flatpickr-current-month .numInputWrapper span:hover {
    background: var(--input-bg-hover) !important;
}

.flatpickr-months .flatpickr-prev-month, .flatpickr-months .flatpickr-next-month {
    color: var(--text-secondary) !important;
    fill: var(--text-secondary) !important;
}

.flatpickr-months .flatpickr-prev-month:hover, .flatpickr-months .flatpickr-next-month:hover {
    color: var(--text-primary) !important;
    fill: var(--text-primary) !important;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
    <div>
        <h2 style="margin-bottom: 0.5rem;">Edit Profile</h2>
        <p style="margin: 0;">Manage your personal information and avatar.</p>
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

<div class="split-layout">
    <!-- Avatar & Signature Section -->
    <div class="surface-card" style="text-align: center;">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem;">Profile Picture</h3>
        
        <div class="avatar-preview-container" style="position: relative; width: 161px; height: 140px; margin: 0 auto 1.5rem auto; border-radius: var(--radius-md); overflow: hidden; border: 3px solid var(--border-color); box-shadow: var(--shadow-sm);">
            <?php if (!empty($user['profile_image'])): ?>
                <img src="<?= htmlspecialchars($user['profile_image']) ?>" id="avatarPreview" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <div id="avatarPreview" style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: 600;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            
            <label for="imageUpload" style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); color: white; padding: 0.5rem; cursor: pointer; font-size: 0.8rem; font-weight: 500; transition: background 0.2s;">
                Change
            </label>
            <input type="file" id="imageUpload" accept="image/png, image/jpeg, image/webp" style="display: none;">
        </div>

        <div style="border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 1.5rem;">
            <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Signature</h3>
            <div style="position: relative; width: 200px; height: 80px; margin: 0 auto 1rem auto; border-radius: var(--radius-sm); overflow: hidden; border: 2px dashed var(--border-color); background: var(--input-bg);">
                <?php if (!empty($user['signature_image'])): ?>
                    <img src="<?= htmlspecialchars($user['signature_image']) ?>" id="signaturePreview" style="width: 100%; height: 100%; object-fit: contain; padding: 4px;">
                <?php else: ?>
                    <div id="signaturePreview" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); font-size: 0.8rem; gap: 0.4rem;">
                        <i data-feather="edit-3" style="width: 14px; height: 14px;"></i> No signature
                    </div>
                <?php endif; ?>
                
                <label for="signatureUpload" style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); color: white; padding: 0.3rem; cursor: pointer; font-size: 0.75rem; font-weight: 500; transition: background 0.2s;">
                    Upload
                </label>
                <input type="file" id="signatureUpload" accept="image/png, image/jpeg, image/webp" style="display: none;">
            </div>
            <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0;">Upload a clear scan of your signature</p>
        </div>
    </div>

    <!-- Details Section -->
    <div class="surface-card" style="padding: 2rem;">
        <?php if ($_SESSION['user_role'] === 'student'): ?>
        <div class="completion-bar-wrap" id="completionBarWrap">
            <div class="completion-bar-header">
                <span>Profile Completion</span>
                <span id="completionPercent">0%</span>
            </div>
            <div class="completion-bar-track">
                <div class="completion-bar-fill" id="completionBarFill"></div>
            </div>
        </div>

        <div class="tabs-container">
            <button type="button" class="tab-btn active" onclick="switchTab('personal')">Personal & Academic</button>
            <button type="button" class="tab-btn" onclick="switchTab('parents')">Parents' Details</button>
        </div>
        <?php endif; ?>

        <form method="POST" id="profileForm">
            <input type="hidden" name="cropped_image" id="croppedImageData">
            <input type="hidden" name="cropped_signature" id="croppedSignatureData">
            
            <!-- Tab: Personal -->
            <div id="tab-personal" class="tab-content active">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="text" name="dob" id="dobPicker" class="form-control" value="<?= htmlspecialchars($meta['dob'] ?? '') ?>" placeholder="Select Date">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="Male" <?= ($meta['gender'] ?? 'Male') == 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($meta['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <?php if ($_SESSION['user_role'] === 'student'): ?>
                    <div class="form-group">
                        <label>Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">Select</option>
                            <?php foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                                <option value="<?= $bg ?>" <?= ($meta['blood_group'] ?? '') == $bg ? 'selected' : '' ?>><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="">Select</option>
                            <?php foreach(['UR', 'EWS', 'OBC', 'SC', 'ST'] as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($meta['category'] ?? '') == $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Religion</label>
                        <select name="religion" class="form-control">
                            <option value="">Select</option>
                            <?php foreach(['Hindu', 'Muslim', 'Sikh', 'Christian', 'Jain', 'Bodh', 'Other'] as $rel): ?>
                                <option value="<?= $rel ?>" <?= ($meta['religion'] ?? '') == $rel ? 'selected' : '' ?>><?= $rel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Aadhar No.</label>
                        <input type="text" name="aadhar" class="form-control" value="<?= htmlspecialchars($meta['aadhar'] ?? '') ?>" placeholder="12-digit Aadhar">
                    </div>
                    <div class="form-group">
                        <label>ABC ID</label>
                        <input type="text" name="abc_id" class="form-control" value="<?= htmlspecialchars($meta['abc_id'] ?? '') ?>" placeholder="Academic Bank of Credits">
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($_SESSION['user_role'] === 'student'): ?>
                <h3 class="section-title">Address</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Permanent Address</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($meta['address'] ?? '') ?>" placeholder="Full Address">
                    </div>
                    <div class="form-group">
                        <label>District</label>
                        <input type="text" name="district" class="form-control" value="<?= htmlspecialchars($meta['district'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <select name="state" class="form-control">
                            <option value="">Select State</option>
                            <?php
                            $states = [
                                'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar',
                                'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana',
                                'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala',
                                'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya',
                                'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
                                'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana',
                                'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
                                // Union Territories
                                'Andaman and Nicobar Islands', 'Chandigarh',
                                'Dadra and Nagar Haveli and Daman and Diu', 'Delhi',
                                'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry'
                            ];
                            foreach ($states as $st): ?>
                                <option value="<?= $st ?>" <?= ($meta['state'] ?? '') == $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>PIN Code</label>
                        <input type="text" name="pin" class="form-control" value="<?= htmlspecialchars($meta['pin'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($meta['country'] ?? 'India') ?>">
                    </div>
                </div>

                <h3 class="section-title">Class 10th Details</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>School Name</label>
                        <input type="text" name="school_10th" class="form-control" value="<?= htmlspecialchars($meta['school_10th'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Board</label>
                        <input type="text" name="board_10th" class="form-control" value="<?= htmlspecialchars($meta['board_10th'] ?? '') ?>" placeholder="e.g. CBSE">
                    </div>
                    <div class="form-group">
                        <label>Passing Year</label>
                        <input type="text" name="year_10th" class="form-control" value="<?= htmlspecialchars($meta['year_10th'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Percentage / CGPA</label>
                        <input type="text" name="perc_10th" class="form-control" value="<?= htmlspecialchars($meta['perc_10th'] ?? '') ?>">
                    </div>
                </div>

                <h3 class="section-title">Class 12th Details</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>School Name</label>
                        <input type="text" name="school_12th" class="form-control" value="<?= htmlspecialchars($meta['school_12th'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Board</label>
                        <input type="text" name="board_12th" class="form-control" value="<?= htmlspecialchars($meta['board_12th'] ?? '') ?>" placeholder="e.g. CBSE">
                    </div>
                    <div class="form-group">
                        <label>Passing Year</label>
                        <input type="text" name="year_12th" class="form-control" value="<?= htmlspecialchars($meta['year_12th'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Percentage / CGPA</label>
                        <input type="text" name="perc_12th" class="form-control" value="<?= htmlspecialchars($meta['perc_12th'] ?? '') ?>">
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($_SESSION['user_role'] === 'student'): ?>
            <!-- Tab: Parents' Details -->
            <div id="tab-parents" class="tab-content">
                <!-- Father's Details -->
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-feather="user" style="width: 18px; height: 18px; color: white;"></i>
                    </div>
                    <h3 style="margin: 0; font-size: 1.05rem;">Father's Details</h3>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Father's Full Name</label>
                        <input type="text" name="father_name" class="form-control" value="<?= htmlspecialchars($meta['father_name'] ?? '') ?>" placeholder="As per official records">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="text" name="father_dob" class="form-control flatpickr-parent" value="<?= htmlspecialchars($meta['father_dob'] ?? '') ?>" placeholder="Select Date">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 0.85rem; font-weight: 500;">+91</span>
                            <input type="tel" name="father_mobile" class="form-control" value="<?= htmlspecialchars($meta['father_mobile'] ?? '') ?>" placeholder="10-digit number" maxlength="10" style="padding-left: 48px;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="father_email" class="form-control" value="<?= htmlspecialchars($meta['father_email'] ?? '') ?>" placeholder="father@email.com">
                    </div>
                    <div class="form-group">
                        <label>Occupation</label>
                        <select name="father_occupation" class="form-control">
                            <option value="">Select Occupation</option>
                            <?php foreach(['Government Job', 'Private Job', 'Business / Self-Employed', 'Agriculture / Farming', 'Defence / Armed Forces', 'Doctor / Medical', 'Engineer / Technical', 'Teacher / Professor', 'Lawyer / Legal', 'Retired', 'Daily Wage Worker', 'Not Employed', 'Others'] as $occ): ?>
                                <option value="<?= $occ ?>" <?= ($meta['father_occupation'] ?? '') == $occ ? 'selected' : '' ?>><?= $occ ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Highest Qualification</label>
                        <select name="father_qualification" class="form-control">
                            <option value="">Select Qualification</option>
                            <?php foreach(['Below 10th', '10th Pass', '12th Pass', 'ITI / Diploma', 'B.A.', 'B.Sc.', 'B.Com.', 'B.Tech / B.E.', 'BBA / BCA', 'M.A.', 'M.Sc.', 'M.Com.', 'M.Tech / M.E.', 'MBA', 'Ph.D.', 'Medical (MBBS/MD)', 'Law (LLB/LLM)', 'Other'] as $qual): ?>
                                <option value="<?= $qual ?>" <?= ($meta['father_qualification'] ?? '') == $qual ? 'selected' : '' ?>><?= $qual ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Annual Income</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 0.9rem; font-weight: 600;">₹</span>
                            <input type="number" name="father_income" class="form-control" value="<?= htmlspecialchars($meta['father_income'] ?? '') ?>" placeholder="e.g. 500000" style="padding-left: 32px;">
                        </div>
                    </div>
                </div>

                <!-- Mother's Details -->
                <div style="display: flex; align-items: center; gap: 0.75rem; margin: 2rem 0 1.25rem 0;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), var(--primary)); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-feather="user" style="width: 18px; height: 18px; color: white;"></i>
                    </div>
                    <h3 style="margin: 0; font-size: 1.05rem;">Mother's Details</h3>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Mother's Full Name</label>
                        <input type="text" name="mother_name" class="form-control" value="<?= htmlspecialchars($meta['mother_name'] ?? '') ?>" placeholder="As per official records">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="text" name="mother_dob" class="form-control flatpickr-parent" value="<?= htmlspecialchars($meta['mother_dob'] ?? '') ?>" placeholder="Select Date">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 0.85rem; font-weight: 500;">+91</span>
                            <input type="tel" name="mother_mobile" class="form-control" value="<?= htmlspecialchars($meta['mother_mobile'] ?? '') ?>" placeholder="10-digit number" maxlength="10" style="padding-left: 48px;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="mother_email" class="form-control" value="<?= htmlspecialchars($meta['mother_email'] ?? '') ?>" placeholder="mother@email.com">
                    </div>
                    <div class="form-group">
                        <label>Occupation</label>
                        <select name="mother_occupation" class="form-control">
                            <option value="">Select Occupation</option>
                            <?php foreach(['Government Job', 'Private Job', 'Business / Self-Employed', 'Homemaker', 'Agriculture / Farming', 'Defence / Armed Forces', 'Doctor / Medical', 'Engineer / Technical', 'Teacher / Professor', 'Lawyer / Legal', 'Retired', 'Daily Wage Worker', 'Not Employed', 'Others'] as $occ): ?>
                                <option value="<?= $occ ?>" <?= ($meta['mother_occupation'] ?? '') == $occ ? 'selected' : '' ?>><?= $occ ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Highest Qualification</label>
                        <select name="mother_qualification" class="form-control">
                            <option value="">Select Qualification</option>
                            <?php foreach(['Below 10th', '10th Pass', '12th Pass', 'ITI / Diploma', 'B.A.', 'B.Sc.', 'B.Com.', 'B.Tech / B.E.', 'BBA / BCA', 'M.A.', 'M.Sc.', 'M.Com.', 'M.Tech / M.E.', 'MBA', 'Ph.D.', 'Medical (MBBS/MD)', 'Law (LLB/LLM)', 'Other'] as $qual): ?>
                                <option value="<?= $qual ?>" <?= ($meta['mother_qualification'] ?? '') == $qual ? 'selected' : '' ?>><?= $qual ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Annual Income</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 0.9rem; font-weight: 600;">₹</span>
                            <input type="number" name="mother_income" class="form-control" value="<?= htmlspecialchars($meta['mother_income'] ?? '') ?>" placeholder="e.g. 300000" style="padding-left: 32px;">
                        </div>
                    </div>
                </div>

                <!-- Family Information -->
                <h3 class="section-title" style="margin-top: 2rem;">Family Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Are grandparents living with your family?</label>
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); border: 1.5px solid <?= ($meta['grandparents_living'] ?? 'NO') == 'YES' ? 'var(--primary)' : 'var(--border-color)' ?>; background: <?= ($meta['grandparents_living'] ?? 'NO') == 'YES' ? 'rgba(var(--primary-rgb), 0.08)' : 'transparent' ?>; transition: all 0.2s;" onclick="selectToggle(this, 'grandparents_living', 'YES')">
                                <input type="radio" name="grandparents_living" value="YES" <?= ($meta['grandparents_living'] ?? 'NO') == 'YES' ? 'checked' : '' ?> style="display: none;">
                                <span style="font-size: 0.9rem; font-weight: 500;">Yes</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); border: 1.5px solid <?= ($meta['grandparents_living'] ?? 'NO') == 'NO' ? 'var(--primary)' : 'var(--border-color)' ?>; background: <?= ($meta['grandparents_living'] ?? 'NO') == 'NO' ? 'rgba(var(--primary-rgb), 0.08)' : 'transparent' ?>; transition: all 0.2s;" onclick="selectToggle(this, 'grandparents_living', 'NO')">
                                <input type="radio" name="grandparents_living" value="NO" <?= ($meta['grandparents_living'] ?? 'NO') == 'NO' ? 'checked' : '' ?> style="display: none;">
                                <span style="font-size: 0.9rem; font-weight: 500;">No</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Is your family a joint family?</label>
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); border: 1.5px solid <?= ($meta['joint_family'] ?? 'NO') == 'YES' ? 'var(--primary)' : 'var(--border-color)' ?>; background: <?= ($meta['joint_family'] ?? 'NO') == 'YES' ? 'rgba(var(--primary-rgb), 0.08)' : 'transparent' ?>; transition: all 0.2s;" onclick="selectToggle(this, 'joint_family', 'YES')">
                                <input type="radio" name="joint_family" value="YES" <?= ($meta['joint_family'] ?? 'NO') == 'YES' ? 'checked' : '' ?> style="display: none;">
                                <span style="font-size: 0.9rem; font-weight: 500;">Yes</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); border: 1.5px solid <?= ($meta['joint_family'] ?? 'NO') == 'NO' ? 'var(--primary)' : 'var(--border-color)' ?>; background: <?= ($meta['joint_family'] ?? 'NO') == 'NO' ? 'rgba(var(--primary-rgb), 0.08)' : 'transparent' ?>; transition: all 0.2s;" onclick="selectToggle(this, 'joint_family', 'NO')">
                                <input type="radio" name="joint_family" value="NO" <?= ($meta['joint_family'] ?? 'NO') == 'NO' ? 'checked' : '' ?> style="display: none;">
                                <span style="font-size: 0.9rem; font-weight: 500;">No</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 50px; font-size: 1.05rem;">
                    <i data-feather="save"></i> Save All Changes
                </button>
            </div>
        </form>
    </div>
</div>

<<!-- Cropper Modal (Profile Image) -->
<div id="cropperModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="surface-card" style="width: 90%; max-width: 500px; padding: 1.5rem; background: var(--bg-surface);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="margin: 0; font-size: 1.2rem;" id="cropperModalTitle">Crop Image</h3>
            <button type="button" class="icon-btn" onclick="closeCropper()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                <i data-feather="x"></i>
            </button>
        </div>
        
        <div style="width: 100%; max-height: 400px; overflow: hidden; background: #000; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
            <img id="imageToCrop" style="max-width: 100%; display: block;">
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeCropper()">Cancel</button>
            <button type="button" class="btn btn-primary" id="cropperApplyBtn" onclick="applyCrop()">Apply & Compress</button>
        </div>
    </div>
</div>

<!-- Cropper.js Script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<!-- Add Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    let cropper;
    let currentCropMode = 'avatar'; // 'avatar' or 'signature'
    const imageUpload = document.getElementById('imageUpload');
    const signatureUpload = document.getElementById('signatureUpload');
    const imageToCrop = document.getElementById('imageToCrop');
    const cropperModal = document.getElementById('cropperModal');
    const avatarPreview = document.getElementById('avatarPreview');
    const croppedImageData = document.getElementById('croppedImageData');
    const croppedSignatureData = document.getElementById('croppedSignatureData');

    // Handle Profile Image Selection
    imageUpload.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            currentCropMode = 'avatar';
            const reader = new FileReader();
            reader.onload = function(event) {
                imageToCrop.src = event.target.result;
                openCropper();
            };
            reader.readAsDataURL(files[0]);
        }
    });

    // Handle Signature Image Selection
    signatureUpload.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            currentCropMode = 'signature';
            const reader = new FileReader();
            reader.onload = function(event) {
                imageToCrop.src = event.target.result;
                openCropper();
            };
            reader.readAsDataURL(files[0]);
        }
    });

    function openCropper() {
        cropperModal.style.display = 'flex';
        document.getElementById('cropperModalTitle').textContent =
            currentCropMode === 'avatar' ? 'Crop Profile Photo' : 'Crop Signature';
        
        if (cropper) {
            cropper.destroy();
        }

        const ratio = currentCropMode === 'avatar' ? (230 / 200) : (300 / 100);
        
        cropper = new Cropper(imageToCrop, {
            aspectRatio: ratio,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.9,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
        });
    }

    function closeCropper() {
        cropperModal.style.display = 'none';
        if (currentCropMode === 'avatar') {
            imageUpload.value = '';
        } else {
            signatureUpload.value = '';
        }
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function applyCrop() {
        if (!cropper) return;

        if (currentCropMode === 'avatar') {
            const canvas = cropper.getCroppedCanvas({
                width: 230, height: 200,
                imageSmoothingEnabled: true, imageSmoothingQuality: 'high',
            });
            let quality = 0.9;
            let compressedBase64 = canvas.toDataURL('image/jpeg', quality);
            while (compressedBase64.length > 68000 && quality > 0.1) {
                quality -= 0.1;
                compressedBase64 = canvas.toDataURL('image/jpeg', quality);
            }
            if (avatarPreview.tagName === 'IMG') {
                avatarPreview.src = compressedBase64;
            } else {
                const img = document.createElement('img');
                img.src = compressedBase64;
                img.id = 'avatarPreview';
                img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                avatarPreview.parentNode.replaceChild(img, avatarPreview);
            }
            croppedImageData.value = compressedBase64;
        } else {
            // Signature crop
            const canvas = cropper.getCroppedCanvas({
                width: 300, height: 100,
                imageSmoothingEnabled: true, imageSmoothingQuality: 'high',
            });
            let quality = 0.9;
            let compressedBase64 = canvas.toDataURL('image/jpeg', quality);
            while (compressedBase64.length > 68000 && quality > 0.1) {
                quality -= 0.1;
                compressedBase64 = canvas.toDataURL('image/jpeg', quality);
            }
            const sigPreview = document.getElementById('signaturePreview');
            if (sigPreview.tagName === 'IMG') {
                sigPreview.src = compressedBase64;
            } else {
                const img = document.createElement('img');
                img.src = compressedBase64;
                img.id = 'signaturePreview';
                img.style.cssText = 'width: 100%; height: 100%; object-fit: contain; padding: 4px;';
                sigPreview.parentNode.replaceChild(img, sigPreview);
            }
            croppedSignatureData.value = compressedBase64;
        }
        closeCropper();
    }

    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById('tab-' + tabId).classList.add('active');
    }

    // Initialize Flatpickr for Date of Birth
    flatpickr("#dobPicker", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        maxDate: "today",
        disableMobile: false,
        monthSelectorType: "dropdown",
        yearSelectorType: "static"
    });

    // Initialize Flatpickr for parent DOB fields
    document.querySelectorAll('.flatpickr-parent').forEach(el => {
        flatpickr(el, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            maxDate: "today",
            disableMobile: false,
            monthSelectorType: "dropdown",
            yearSelectorType: "static"
        });
    });

    // Interactive toggle button for Yes/No radio groups
    function selectToggle(el, groupName, value) {
        const allLabels = el.parentElement.querySelectorAll('label');
        allLabels.forEach(label => {
            label.style.borderColor = 'var(--border-color)';
            label.style.background = 'transparent';
            label.querySelector('input').checked = false;
        });
        el.style.borderColor = 'var(--primary)';
        el.style.background = 'rgba(var(--primary-rgb), 0.08)';
        el.querySelector('input').checked = true;
    }

    // ── Real-time Form Validation Engine ──
    (function() {
        const form = document.getElementById('profileForm');
        if (!form) return;

        // SVG icons for inline messages
        const checkSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        const xSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';

        // Validation rules – keyed by input "name" attribute
        const rules = {
            name:           { required: true, minLen: 2, label: 'Full Name' },
            email:          { required: true, pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, label: 'Email', patternMsg: 'Enter a valid email address' },
            aadhar:         { pattern: /^\d{12}$/, label: 'Aadhar', patternMsg: 'Must be exactly 12 digits' },
            abc_id:         { pattern: /^\d{12}$/, label: 'ABC ID', patternMsg: 'Must be exactly 12 digits' },
            pin:            { pattern: /^\d{6}$/, label: 'PIN Code', patternMsg: 'Must be exactly 6 digits' },
            district:       { minLen: 2, label: 'District' },
            address:        { minLen: 5, label: 'Address' },
            country:        { minLen: 2, label: 'Country' },
            school_10th:    { minLen: 2, label: 'School Name' },
            board_10th:     { minLen: 2, label: 'Board' },
            year_10th:      { pattern: /^(19|20)\d{2}$/, label: 'Year', patternMsg: 'Enter a valid 4-digit year' },
            perc_10th:      { pattern: /^(\d{1,2}(\.\d{1,2})?|100(\.0{1,2})?)$/, label: 'Percentage', patternMsg: 'Enter 0–100 (e.g. 87.5)' },
            school_12th:    { minLen: 2, label: 'School Name' },
            board_12th:     { minLen: 2, label: 'Board' },
            year_12th:      { pattern: /^(19|20)\d{2}$/, label: 'Year', patternMsg: 'Enter a valid 4-digit year' },
            perc_12th:      { pattern: /^(\d{1,2}(\.\d{1,2})?|100(\.0{1,2})?)$/, label: 'Percentage', patternMsg: 'Enter 0–100 (e.g. 87.5)' },
            father_name:    { minLen: 2, label: "Father's Name" },
            father_mobile:  { pattern: /^\d{10}$/, label: 'Mobile', patternMsg: 'Must be exactly 10 digits' },
            father_email:   { pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, label: 'Email', patternMsg: 'Enter a valid email' },
            mother_name:    { minLen: 2, label: "Mother's Name" },
            mother_mobile:  { pattern: /^\d{10}$/, label: 'Mobile', patternMsg: 'Must be exactly 10 digits' },
            mother_email:   { pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, label: 'Email', patternMsg: 'Enter a valid email' },
            father_income:  { pattern: /^\d+$/, label: 'Income', patternMsg: 'Enter a numeric value' },
            mother_income:  { pattern: /^\d+$/, label: 'Income', patternMsg: 'Enter a numeric value' },
        };

        // Inject validation-msg container for every validatable field
        Object.keys(rules).forEach(name => {
            const el = form.querySelector('[name="' + name + '"]');
            if (!el) return;
            const group = el.closest('.form-group');
            if (!group || group.querySelector('.validation-msg')) return;
            const msg = document.createElement('div');
            msg.className = 'validation-msg';
            group.appendChild(msg);
        });

        // Core validate function
        function validate(el) {
            const name = el.getAttribute('name');
            const rule = rules[name];
            if (!rule) return true;
            const val = el.value.trim();
            const group = el.closest('.form-group');
            const msgEl = group ? group.querySelector('.validation-msg') : null;

            // Skip validation on empty optional fields
            if (!rule.required && val === '') {
                clearState(group, msgEl);
                return true;
            }

            // Required check
            if (rule.required && val === '') {
                setInvalid(group, msgEl, rule.label + ' is required');
                return false;
            }

            // Min length
            if (rule.minLen && val.length > 0 && val.length < rule.minLen) {
                setInvalid(group, msgEl, 'At least ' + rule.minLen + ' characters');
                return false;
            }

            // Pattern
            if (rule.pattern && val.length > 0 && !rule.pattern.test(val)) {
                setInvalid(group, msgEl, rule.patternMsg || 'Invalid format');
                return false;
            }

            // Passed
            if (val.length > 0) {
                setValid(group, msgEl, 'Looks good');
            } else {
                clearState(group, msgEl);
            }
            return true;
        }

        function setValid(group, msgEl, text) {
            if (!group) return;
            group.classList.remove('is-invalid');
            group.classList.add('is-valid');
            if (msgEl) msgEl.innerHTML = checkSvg + ' ' + text;
            updateCompletion();
        }
        function setInvalid(group, msgEl, text) {
            if (!group) return;
            group.classList.remove('is-valid');
            group.classList.add('is-invalid');
            if (msgEl) msgEl.innerHTML = xSvg + ' ' + text;
            updateCompletion();
        }
        function clearState(group, msgEl) {
            if (!group) return;
            group.classList.remove('is-valid', 'is-invalid');
            if (msgEl) msgEl.innerHTML = '';
            updateCompletion();
        }

        // Attach listeners with debounce for 'input', immediate for 'blur'
        let debounceTimers = {};
        Object.keys(rules).forEach(name => {
            const el = form.querySelector('[name="' + name + '"]');
            if (!el) return;

            el.addEventListener('blur', () => validate(el));
            el.addEventListener('input', () => {
                clearTimeout(debounceTimers[name]);
                debounceTimers[name] = setTimeout(() => validate(el), 350);
            });
        });

        // Select fields – validate on change
        form.querySelectorAll('select[name]').forEach(sel => {
            sel.addEventListener('change', () => {
                const group = sel.closest('.form-group');
                if (!group) return;
                let msgEl = group.querySelector('.validation-msg');
                if (!msgEl) {
                    msgEl = document.createElement('div');
                    msgEl.className = 'validation-msg';
                    group.appendChild(msgEl);
                }
                if (sel.value) {
                    setValid(group, msgEl, 'Selected');
                } else {
                    clearState(group, msgEl);
                }
            });
        });

        // Prevent submit if any invalid fields
        form.addEventListener('submit', function(e) {
            let hasError = false;
            Object.keys(rules).forEach(name => {
                const el = form.querySelector('[name="' + name + '"]');
                if (el && !validate(el)) hasError = true;
            });
            if (hasError) {
                e.preventDefault();
                // Scroll to first error
                const firstErr = form.querySelector('.is-invalid');
                if (firstErr) {
                    // Make sure we're on the right tab
                    const parentTab = firstErr.closest('.tab-content');
                    if (parentTab && !parentTab.classList.contains('active')) {
                        const tabId = parentTab.id.replace('tab-', '');
                        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                        parentTab.classList.add('active');
                        // Highlight the correct tab button
                        document.querySelectorAll('.tab-btn').forEach(btn => {
                            if (btn.textContent.toLowerCase().includes(tabId === 'personal' ? 'personal' : 'parent')) {
                                btn.classList.add('active');
                            }
                        });
                    }
                    firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        // ── Profile Completion Tracker ──
        function updateCompletion() {
            const allFields = form.querySelectorAll('input[name]:not([type="hidden"]):not([type="radio"]):not([type="file"]), select[name]');
            let filled = 0, total = 0;
            allFields.forEach(f => {
                if (f.name === 'cropped_image' || f.name === 'cropped_signature') return;
                total++;
                if (f.value && f.value.trim() !== '') filled++;
            });
            const pct = total > 0 ? Math.round((filled / total) * 100) : 0;
            const bar = document.getElementById('completionBarFill');
            const label = document.getElementById('completionPercent');
            if (bar) bar.style.width = pct + '%';
            if (label) label.textContent = pct + '%';
        }

        // Run once on page load
        updateCompletion();

        // Also validate pre-filled fields on load (mark them green)
        setTimeout(() => {
            Object.keys(rules).forEach(name => {
                const el = form.querySelector('[name="' + name + '"]');
                if (el && el.value.trim() !== '') validate(el);
            });
            // Mark filled selects
            form.querySelectorAll('select[name]').forEach(sel => {
                if (sel.value) {
                    const group = sel.closest('.form-group');
                    if (group) {
                        let msgEl = group.querySelector('.validation-msg');
                        if (!msgEl) {
                            msgEl = document.createElement('div');
                            msgEl.className = 'validation-msg';
                            group.appendChild(msgEl);
                        }
                        setValid(group, msgEl, 'Selected');
                    }
                }
            });
        }, 300);
    })();
</script>

<?php include 'includes/footer.php'; ?>
