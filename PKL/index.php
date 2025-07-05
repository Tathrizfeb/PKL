<?php
// Redirect otomatis jika pakai parameter
if (isset($_GET['to'])) {
    switch ($_GET['to']) {
        case 'dashboard':
            $target = 'ip-block-dashboard.php';
            break;
        case 'cekip':
            $target = 'cek-ip.php';
            break;
        default:
            $target = 'webhook-handler.php';
    }
    header("Location: /$target");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>🔁 Redirect Panel</title>
    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            padding-top: 80px;
            background-color: #f8f9fa;
        }
        h1 {
            margin-bottom: 40px;
            color: #333;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            margin: 12px;
            font-size: 18px;
            border-radius: 10px;
            text-decoration: none;
            color: white;
            background-color: #007BFF;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🔧 Silakan Pilih Tujuan</h1>
    <a class="button" href="?to=webhook">Webhook Handler</a>
    <a class="button" href="?to=dashboard">Dashboard IP Blok</a>
    <a class="button" href="?to=cekip">Cek IP Client</a>
</body>
</html>
