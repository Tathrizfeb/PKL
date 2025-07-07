<?php 
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$port = getenv('DB_PORT');

$conn = pg_connect("host=$host dbname=$db user=$user password=$pass port=$port");

if (!$conn) {
    die("Koneksi gagal.");
}

$filename = "export_notification_log_" . date("Y-m-d_H-i-s") . ".csv";
$filepath = __DIR__ . "/exports/" . $filename;

if (!is_dir(__DIR__ . "/exports")) {
    mkdir(__DIR__ . "/exports", 0777, true);
}

$query  = 'SELECT * FROM notification_log ORDER BY id';
$result = pg_query($conn, $query);

$file = fopen($filepath, 'w');

$columns = pg_num_fields($result);
$headers = [];
for ($i = 0; $i < $columns; $i++) {
    $headers[] = pg_field_name($result, $i);
}
fputcsv($file, $headers);

while ($row = pg_fetch_assoc($result)) {
    fputcsv($file, $row);
}

fclose($file);
pg_close($conn);

echo "Export selesai: $filename\n";
?>