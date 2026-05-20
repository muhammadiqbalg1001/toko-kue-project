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
    $input = json_decode(file_get_contents("php://input"), true);
    $id_to_delete = isset($input['id']) ? $input['id'] : '';

    if (empty($id_to_delete)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "ID user wajib dikirim!"
        ]);
        exit;
    }

    if ($id_to_delete == $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!"
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT photo FROM users WHERE id = ?");
        $stmt->execute([$id_to_delete]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "User tidak ditemukan di database!"
            ]);
            exit;
        }

        if (!empty($user['photo'])) {
            $photo_path = __DIR__ . '/../uploads/users/' . $user['photo'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }

        $delete_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->execute([$id_to_delete]);

        http_response_code(200);
        echo json_encode([
            "status" => "error",
            "message" => "User dan datanya berhasil dihapus permanen!"
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Terjadi kesalahan sistem saat menghapus data."
        ]);
    }
    exit;
}

?>