<?php
require 'koneksi.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['event']) || $data['event'] !== 'MessageSent') {
    echo json_encode(["info" => "Event not processed"]);
    exit;
}

$msg_id  = $data['payload']['message']['message_id'];
$status  = $data['payload']['status'];
$output  = $data['payload']['output'];
$finish  = $data['payload']['timestamp'];

$sql = "UPDATE notification_log SET 
            postal_status = :status,
            postal_status_message = :output,
            \"finishDate\" = to_timestamp(:finishDate),
            sent = TRUE,
            done = TRUE,
            error = FALSE
        WHERE postal_message_id = :msg_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':status'     => $status,
    ':output'     => $output,
    ':finishDate' => $finish,
    ':msg_id'     => $msg_id
]);

$rowCount = $stmt->rowCount();

if ($rowCount > 0) {
    echo json_encode([
        "success" => true,
        "updated_rows" => $rowCount,
        "message" => "Data berhasil diupdate"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "updated_rows" => 0,
        "message" => "Data tidak ditemukan"
    ]);
}
?>
