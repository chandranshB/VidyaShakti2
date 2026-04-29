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
    ];
    $metadata_json = json_encode($meta_data);

    try {
        $update_query = "UPDATE users SET metadata = ?";
        $params = [$metadata_json];

        // Handle Profile Image Upload (Base64)
        if (!empty($cropped_image)) {
            $image_parts = explode(";base64,", $cropped_image);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1] ?? 'jpeg';
            $image_base64 = base64_decode($image_parts[1]);

            $fileName = 'user_' . $user_id . '_' . time() . '.' . $image_type;
            $filePath = 'uploads/profiles/' . $fileName;

            if (!is_dir('uploads/profiles')) {
                mkdir('uploads/profiles', 0777, true);
            }

            if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                unlink($user['profile_image']);
            }

            file_put_contents($filePath, $image_base64);

            $update_query = "UPDATE users SET profile_image = ?, metadata = ?";
            $params = [$filePath, $metadata_json];
            
            $_SESSION['profile_image'] = $filePath; // Update session instantly
        }

        $update_query .= " WHERE id = ?";
        $params[] = $user_id;

        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute($params);

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
    <!-- Avatar Section -->
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
    </div>

    <!-- Details Section -->
    <div class="surface-card" style="padding: 2rem;">
        <div class="tabs-container">
            <button type="button" class="tab-btn active" onclick="switchTab('personal')">Personal Details</button>
            <button type="button" class="tab-btn" onclick="switchTab('academic')">Academic History</button>
        </div>

        <form method="POST" id="profileForm">
            <input type="hidden" name="cropped_image" id="croppedImageData">
            
            <!-- Tab 2: Personal -->
            <div id="tab-personal" class="tab-content active">
                <div class="form-grid">
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
                </div>

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
                        <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($meta['state'] ?? '') ?>">
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
            </div>

            <!-- Tab 3: Academic -->
            <div id="tab-academic" class="tab-content">
                <h3 class="section-title" style="margin-top: 0;">10th Details</h3>
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

                <h3 class="section-title">12th Details</h3>
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
            </div>

            <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 50px; font-size: 1.05rem;">
                    <i data-feather="save"></i> Save All Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cropper Modal -->
<div id="cropperModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="surface-card" style="width: 90%; max-width: 500px; padding: 1.5rem; background: var(--bg-surface);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="margin: 0; font-size: 1.2rem;">Crop Image</h3>
            <button type="button" class="icon-btn" onclick="closeCropper()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                <i data-feather="x"></i>
            </button>
        </div>
        
        <div style="width: 100%; max-height: 400px; overflow: hidden; background: #000; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
            <img id="imageToCrop" style="max-width: 100%; display: block;">
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeCropper()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="applyCrop()">Apply & Compress</button>
        </div>
    </div>
</div>

<!-- Cropper.js Script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<!-- Add Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    let cropper;
    const imageUpload = document.getElementById('imageUpload');
    const imageToCrop = document.getElementById('imageToCrop');
    const cropperModal = document.getElementById('cropperModal');
    const avatarPreview = document.getElementById('avatarPreview');
    const croppedImageData = document.getElementById('croppedImageData');

    // Handle File Selection
    imageUpload.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const file = files[0];
            const reader = new FileReader();
            
            reader.onload = function(event) {
                imageToCrop.src = event.target.result;
                openCropper();
            };
            reader.readAsDataURL(file);
        }
    });

    function openCropper() {
        cropperModal.style.display = 'flex';
        
        // Destroy old cropper if exists
        if (cropper) {
            cropper.destroy();
        }
        
        // Initialize new cropper
        cropper = new Cropper(imageToCrop, {
            aspectRatio: 230 / 200, // Government standard ratio
            viewMode: 1, // Restrict crop box to not exceed size of canvas
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
        imageUpload.value = ''; // Reset file input
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function applyCrop() {
        if (!cropper) return;

        // Render the cropped area to a canvas, compressing it to exactly 230x200
        const canvas = cropper.getCroppedCanvas({
            width: 230,
            height: 200,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        // Compress to JPEG on the fly and ensure size is under 50KB
        // 50KB = 51200 bytes. Base64 length * 0.75 gives approx bytes.
        let quality = 0.9;
        let compressedBase64 = canvas.toDataURL('image/jpeg', quality);

        while (compressedBase64.length > 68000 && quality > 0.1) {
            quality -= 0.1;
            compressedBase64 = canvas.toDataURL('image/jpeg', quality);
        }

        // Preview the image locally
        if (avatarPreview.tagName === 'IMG') {
            avatarPreview.src = compressedBase64;
        } else {
            // Replace div with img if they didn't have an avatar previously
            const img = document.createElement('img');
            img.src = compressedBase64;
            img.id = 'avatarPreview';
            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
            avatarPreview.parentNode.replaceChild(img, avatarPreview);
        }

        // Store the base64 string in the hidden input to send on form submit
        croppedImageData.value = compressedBase64;
        
        // Close modal
        closeCropper();
    }

    function switchTab(tabId) {
        // Update Buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');

        // Update Content
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById('tab-' + tabId).classList.add('active');
    }

    // Initialize Flatpickr for Date of Birth
    flatpickr("#dobPicker", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        maxDate: "today",
        disableMobile: false, // Let mobile use native, robust OS popup
        monthSelectorType: "dropdown",
        yearSelectorType: "static"
    });
</script>

<?php include 'includes/footer.php'; ?>
