<?php
header('Content-Type: application/json');

// Ambil IP client
$client_ip = $_SERVER['REMOTE_ADDR'];

// ✅ Ganti ini untuk simulasi 200 atau 401
$allowed_ips = ['172.17.0.1']; // atau ['8.8.8.8'] untuk memaksa 401

if (!in_array($client_ip, $allowed_ips)) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => "401 Unauthorized - IP $client_ip tidak diizinkan.",
        'your_ip' => $client_ip
    ]);
    exit;
}

// Koneksi DB
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

// Validasi payload
if (
    !isset($data['event']) ||
    !isset($data['payload']['message']['message_id']) ||
    !isset($data['payload']['status']) ||
    !isset($data['payload']['output'])
) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Payload tidak lengkap.',
        'received' => $data
    ]);
    exit;
}

$message_id            = $data['payload']['message']['message_id'];
$postal_status         = $data['payload']['status'];
$postal_status_message = $data['payload']['output'];

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
