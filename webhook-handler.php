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

// === Tambahan PHPMailer ===
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    $message_id = $data['payload']['message']['message_id'];
    $postal_status = $data['payload']['status'];
    $postal_status_message = $data['payload']['output'];
} elseif (
    isset($data['ref']) &&
    isset($data['message'])
) {
    $message_id = $data['ref'];
    $postal_status = 'success';
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

        // Kirim email notifikasi jika update berhasil
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'yourgmail@gmail.com';          // Ganti
            $mail->Password   = 'sandi_aplikasi_gmail';         // Ganti
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('yourgmail@gmail.com', 'Webhook Notifier');
            $mail->addAddress('tujuan@email.com', 'Admin');     // Ganti tujuan email

            $mail->isHTML(true);
            $mail->Subject = 'Notifikasi Webhook: Data Diperbarui';
            $mail->Body    = "
                <h3>Notifikasi Webhook</h3>
                <p><strong>Message ID:</strong> {$message_id}</p>
                <p><strong>Status:</strong> {$postal_status}</p>
                <p><strong>Pesan:</strong> {$postal_status_message}</p>
            ";

            $mail->send();
        } catch (Exception $e) {
            // Log error email jika perlu
            // file_put_contents('email_error.log', $e->getMessage(), FILE_APPEND);
        }

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
