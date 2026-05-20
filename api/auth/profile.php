<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-CredentialsL true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Akses ditolak! Anda belum login."
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT id, name, username, role, photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    echo json_encode([
        "status" => "success",
        "data" => $user
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $new_password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($name)) {
        http_response_code(400);
        json_encode([
            "status" => "error",
            "message" => "Nama tidak boleh kosong!"
        ]);
    }

    $stmt = $pdo->prepare("SELECT username, photo, password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old_user = $stmt->fetch();

    $username = $old_user['username'];
    $photo_path = $old_user['photo'];
    $password_hash = $old_user['password'];

    if (!empty($new_password)) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/users';

        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_filename = $username . '_' . $user_id . '_' . time() . '.' . strtolower($file_extension);
        $target_file = $upload_dir . $new_filename;

        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_types)) {
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_path = $new_filename;

                if (!empty($old_user['photo']) && file_exists($upload_dir . $old_user['photo'])) {
                    unlink($upload_dir . $old_user['photo']);
                }
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Format foto harus JPG, PNG, atau WEBP!"
            ]);
        }
    }

    try {
        $update_stmt = $pdo->prepare("UPDATE users SET name = ?, password = ?, photo = ? WHERE id = ?");
        $update_stmt->execute([
            $name,
            $password_hash,
            $photo_path,
            $user_id
        ]);

        $_SESSION['name'] = $name;

        echo json_encode([
            "status" => "success",
            "message" => "Profil berhasil diperbarui!",
            "data" => [
                "name" => $name,
                "photo" => $photo_path
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Terjadi kesalahan sistem saat menyimpan data."
        ]);
    }
    exit;
}
?>