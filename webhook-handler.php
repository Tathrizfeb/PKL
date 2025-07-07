<?php
header('Content-Type: application/json');

// Ambil IP client
$client_ip = $_SERVER['REMOTE_ADDR'];
$allowed_ips = ['172.17.0.1']; // Ubah jika perlu

if (!in_array($client_ip, $allowed_ips)) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => "401 Unauthorized - IP $client_ip tidak diizinkan.",
        'your_ip' => $client_ip
    ]);
    exit;
}

// Koneksi ke DB
try {
    $dsn = 'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME');
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Ambil dan decode JSON
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Payload bukan JSON yang valid.'
    ]);
    exit;
}

// Coba baca dari 2 jenis struktur payload
if (
    isset($data['payload']['message']['message_id']) &&
    isset($data['payload']['status']) &&
    isset($data['payload']['output'])
) {
    // Struktur lama
    $message_id = $data['payload']['message']['message_id'];
    $postal_status = $data['payload']['status'];
    $postal_status_message = $data['payload']['output'];
} elseif (
    isset($data['ref']) &&
    isset($data['message'])
) {
    // Struktur flat (dari Postman kamu)
    $message_id = $data['ref'];
    $postal_status = 'success'; // Default
    $postal_status_message = $data['message'];
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Payload tidak lengkap atau tidak sesuai format.',
        'received' => $data
    ]);
    exit;
}

// Jalankan update
try {
    $stmt = $pdo->prepare("
        UPDATE notification_log 
        SET postal_status = :status, postal_status_message = :message 
        WHERE postal_message_id = :message_id
    ");

    $stmt->execute([
        ':status'     => $postal_status,
        ':message'    => $postal_status_message,
        ':message_id' => $message_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Data berhasil diperbarui.',
            'updated_message_id' => $message_id
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Message ID tidak ditemukan.',
            'message_id' => $message_id
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
