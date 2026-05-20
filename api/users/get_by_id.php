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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? trim($_GET['id']) : '';

    if (empty($id)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Parameter ID user tidak ditemukan!"
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, username, role, photo, is_admin FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Data user tidak ditemukan!"
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "data" => $user
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Terjadi kesalahan server internal."
        ]);
    }
    exit;
}
?>