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
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $cropped_image = $_POST['cropped_image'] ?? '';

    if (empty($name) || empty($email)) {
        $error_msg = "Name and Email are required.";
    } else {
        try {
            // Password Update Logic
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $password_updated = false;

            if (!empty($current_password) || !empty($new_password)) {
                if ($user['password'] !== $current_password) { // Using plaintext for current implementation context
                    $error_msg = "Current password is incorrect.";
                } elseif ($new_password !== $confirm_password) {
                    $error_msg = "New passwords do not match.";
                } elseif (strlen($new_password) < 6) {
                    $error_msg = "New password must be at least 6 characters.";
                } else {
                    $password_updated = true;
                }
            }

            if (!$error_msg) {
                $update_query = "UPDATE users SET name = ?, email = ?, phone = ?";
                $params = [$name, $email, $phone];

                if ($password_updated) {
                    $update_query .= ", password = ?";
                    $params[] = $new_password;
                }

                $update_query .= " WHERE id = ?";
                $params[] = $user_id;

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

                    // If password was updated, we add it, otherwise we just add profile_image
                    $update_query = "UPDATE users SET name = ?, email = ?, phone = ?, profile_image = ?";
                    $params = [$name, $email, $phone, $filePath];
                    
                    if ($password_updated) {
                        $update_query .= ", password = ?";
                        $params[] = $new_password;
                    }

                    $update_query .= " WHERE id = ?";
                    $params[] = $user_id;
                    
                    $_SESSION['profile_image'] = $filePath;
                }

                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->execute($params);

                $_SESSION['user_name'] = $name;
                $success_msg = "Profile updated successfully!" . ($password_updated ? " Password changed." : "");
                
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $error_msg = "Email or Phone number is already registered to another account.";
            } else {
                $error_msg = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<!-- Add Cropper.js CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">

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
        
        <div class="avatar-preview-container" style="position: relative; width: 150px; height: 150px; margin: 0 auto 1.5rem auto; border-radius: 50%; overflow: hidden; border: 3px solid var(--border-color);">
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
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Allowed: JPG, PNG, WebP. Max size: 5MB.</p>
    </div>

    <!-- Details Section -->
    <div class="surface-card">
        <form method="POST" id="profileForm">
            <input type="hidden" name="cropped_image" id="croppedImageData">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label>Phone Number (Login ID)</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. 9876543210" required>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.4rem;">This is used to log into your account.</small>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 2rem 0;">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem;">Change Password</h3>
            
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="Required if changing password">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="New Password">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i data-feather="save"></i> Save Profile
            </button>
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
            aspectRatio: 1, // Perfect square
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

        // Render the cropped area to a canvas, compressing it to 400x400
        const canvas = cropper.getCroppedCanvas({
            width: 400,
            height: 400,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        // Compress to WebP or JPEG on the fly (0.8 quality = 80%)
        // This dramatically reduces file size before it ever hits the server
        const compressedBase64 = canvas.toDataURL('image/jpeg', 0.8);

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
</script>

<?php include 'includes/footer.php'; ?>
