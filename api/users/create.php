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
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'admin';
    $is_admin = 1;

    if (empty($name) || empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Nama, username, dan password wajib diisi!"
        ]);
        exit;
    }

    try {
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        if ($check_stmt->fetch()) {
            http_response_code(409);
            echo json_encode([
                "status" => "error",
                "message" => "Username sudah dipakai! Silahkan masukkan username lain."
            ]);
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $insert_stmt = $pdo->prepare("INSERT INTO users (name, username, password, role, is_admin) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->execute([
            $name,
            $username,
            $password_hash,
            $role,
            $is_admin
        ]);

        $new_user_id = $pdo->lastInsertId();
        $photo_path = null;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/users/';
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);

            $new_filename = $username . '_' . $new_user_id . '_' . time() . '.' . strtolower($file_extension);
            $target_file = $upload_dir . $new_filename;

            $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                    $photo_path = $new_filename;

                    $upload_stmt = $pdo->prepare("UPDATE users SET photo = ? WHERE id = ?");
                    $upload_stmt->execute([$photo_path, $new_user_id]);
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

        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "User baru berhasil ditambahkan!",
            "data" => [
                "id" => $new_user_id,
                "name" => $name,
                "username" => $username,
                "role" => $role,
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