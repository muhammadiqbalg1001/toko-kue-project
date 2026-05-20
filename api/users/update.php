<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-CredentialsL true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

if ($_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Akses ditolak! Hanya Super Admin yang dapat melihat daftar user."
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $new_password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';

    if (empty($id) || empty($name) || empty($username) || empty($role)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "ID, Nama, Username, dan Role wajib diisi!"
        ]);
        exit;
    }

    try {
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->execute([$username, $id]);
        if ($check_stmt->fetch()) {
            http_response_code(409);
            echo json_encode([
                "status" => "error",
                "message" => "Username sudah dipakai! Silahkan masukkan username lain."
            ]);
            exit;
        }

        $old_stmt = $pdo->prepare("SELECT photo, password FROM users WHERE id = ?");
        $old_stmt->execute([$id]);
        $old_user = $old_stmt->fetch();

        if (!$old_user) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "User tidak ditemukan!"
            ]);
            exit;
        }

        $photo_path = $old_user['photo'];
        $password_hash = $old_user['password'];

        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        }

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/users/';
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);

            $new_filename = $username . '_' . $id . '_' . time() . '.' . strtolower($file_extension);
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
                    "message" => "Akun berhasil dibuat, namun format foto ditolak. Harus JPG, PNG, atau WEBP!"
                ]);
                exit;
            }
        }

        $update_stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, role = ?, password = ?, photo = ? WHERE id = ?");
        $update_stmt->execute([$name, $username, $role, $password_hash, $photo_path, $id]);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Data user berhasil diperbarui!"
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Terjadi kesalahan sistem."
        ]);
    }
    exit;
}

?>