<?php
session_start();
require_once '../config/db.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Constants
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('PROFILE_DIR', UPLOAD_DIR . '/profiles');
define('PROFILE_URL', '../uploads/profiles');
define('DEFAULT_AVATAR', '../assets/images/default-avatar.png');

// Create required directories
foreach ([UPLOAD_DIR, PROFILE_DIR] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$error_message = null;
$success_message = null;

// Fetch user data
function fetchUserData($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id, username, email, password, profile_photo FROM pengguna WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$user = fetchUserData($pdo, $user_id);

if (!$user) {
    $_SESSION['error'] = "User not found!";
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password'];
    $profile_photo = $user['profile_photo'];

    // Handle photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
            $upload_path = PROFILE_DIR . '/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                // Delete old photo
                if ($profile_photo && file_exists(PROFILE_DIR . '/' . $profile_photo)) {
                    unlink(PROFILE_DIR . '/' . $profile_photo);
                }
                $profile_photo = $new_filename;
            } else {
                $error_message = "Failed to upload file. Please check directory permissions.";
            }
        } else {
            $error_message = "Invalid file type. Allowed types: " . implode(', ', $allowed);
        }
    }

    if (!isset($error_message)) {
        try {
            $params = [
                'username' => $new_username,
                'email' => $new_email,
                'profile_photo' => $profile_photo,
                'user_id' => $user_id
            ];

            $sql = "UPDATE pengguna SET 
                    username = :username, 
                    email = :email,
                    profile_photo = :profile_photo";

            if (!empty($new_password)) {
                $sql .= ", password = :password";
                $params['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = :user_id";
            
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute($params)) {
                $_SESSION['username'] = $new_username;
                $success_message = "Profile updated successfully!";
                $user = fetchUserData($pdo, $user_id);
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Get profile photo URL
$profile_photo_url = $user['profile_photo'] && file_exists(PROFILE_DIR . '/' . $user['profile_photo'])
    ? PROFILE_URL . '/' . $user['profile_photo']
    : DEFAULT_AVATAR;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
 :root {
    --primary-color: #4f46e5;
    --secondary-color: #3730a3;
    --background-color: #f3f4f6;
    --accent-color: #facc15;
    --text-color: #1f2937;
    --error-color: #f87171;
    --success-color: #34d399;
    --card-background: #ffffff;
    --button-gradient: linear-gradient(135deg, #4f46e5, #3730a3);
    --input-gradient: linear-gradient(135deg, #f8fafc, #f1f5f9);
    --hover-effect: 0px 10px 20px rgba(0, 0, 0, 0.15);
    --border-radius: 15px;
    --padding-horizontal: 15px;
}

body {
    font-family: 'Roboto', sans-serif;
    background: var(--background-color);
    color: var(--text-color);
    margin: 0;
    padding: 50px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    transition: background 0.5s ease;
}

.container {
    max-width: 800px;
    width: 100%;
    margin: 0 auto;
    padding: 0 20px;
}

.back-button {
    background: var(--button-gradient);
    color: white;
    padding: 12px 25px;
    border-radius: var(--border-radius);
    text-decoration: none;
    box-shadow: var(--hover-effect);
    transition: transform 0.3s ease, background-color 0.3s ease;
    font-weight: 600;
    font-size: 16px;
}

.back-button:hover {
    background: var(--secondary-color);
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(79, 70, 229, 0.3);
}

.profile-container {
    background: var(--card-background);
    padding: 50px 40px;
    border-radius: 25px;
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.15);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.profile-container:hover {
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    transform: translateY(-5px);
}

.profile-header h2 {
    font-size: 30px;
    margin-bottom: 25px;
    color: var(--primary-color);
    font-weight: 700;
    letter-spacing: 0.5px;
}

.profile-photo-container {
    position: relative;
    width: 140px;
    height: 140px;
    margin: 0 auto 20px;
    box-shadow: var(--hover-effect);
    border-radius: 50%;
    background: var(--input-gradient);
    transition: box-shadow 0.3s ease;
}

.profile-photo-container:hover {
    box-shadow: 0 12px 30px rgba(79, 70, 229, 0.3);
}

.profile-photo {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid var(--primary-color);
}

.photo-upload-label {
    background: var(--button-gradient);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    bottom: 8px;
    right: 8px;
    transition: background-color 0.3s;
    box-shadow: var(--hover-effect);
}

.photo-upload-label:hover {
    background: var(--secondary-color);
}

.form-group label {
    font-weight: 600;
    color: var(--text-color);
    display: inline-block;
    margin-bottom: 10px;
}

.form-group input {
    width: 100%;
    padding: 14px 20px;
    border: 1px solid #d1d5db;
    border-radius: var(--border-radius);
    font-size: 16px;
    background: var(--input-gradient);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-group input:focus {
    border-color: var(--accent-color);
    box-shadow: 0 8px 16px rgba(250, 204, 21, 0.3);
    outline: none;
}

.btn-update {
    background: var(--button-gradient);
    color: white;
    padding: 16px;
    border: none;
    border-radius: var(--border-radius);
    font-size: 18px;
    font-weight: 700;
    width: 100%;
    cursor: pointer;
    transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
    box-shadow: 0px 10px 25px rgba(79, 70, 229, 0.3);
}

.btn-update:hover {
    background: var(--secondary-color);
    box-shadow: 0px 12px 30px rgba(79, 70, 229, 0.4);
    transform: translateY(-4px);
}

.message {
    padding: 15px;
    margin: 20px 0;
    border-radius: 10px;
    text-align: center;
    font-weight: 600;
}

.success {
    background: #d1fae5;
    color: var(--success-color);
    border: 1px solid var(--success-color);
}

.error {
    background: #fee2e2;
    color: var(--error-color);
    border: 1px solid var(--error-color);
}

#profile_photo {
    display: none;
}


    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="profile-container">
            <div class="profile-header">
                <h2>Profile Edit</h2>
                <div class="profile-photo-container">
                    <img src="<?php echo htmlspecialchars($profile_photo_url); ?>" 
                         alt="Profile Photo" class="profile-photo" id="preview-photo">
                    <label for="profile_photo" class="photo-upload-label">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
                <p>Logged in as: <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
            </div>

            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_photo" id="profile_photo" accept="image/*">
                
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">New Password:</label>
                    <input type="password" name="password" id="password" 
                           placeholder="Leave blank to keep current password">
                </div>
                
                <button type="submit" class="btn-update">Update Profile</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-photo').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>