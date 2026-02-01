<?php
header("Content-Type: application/json");
require_once '../config/database.php';
require_once 'security.php';

$database = new Database();
$db = $database->getConnection();

$symbol = $_GET['symbol'] ?? '';

// 1. Ir buscar o ID do ativo
$stmt = $db->prepare("SELECT id FROM portfolio WHERE symbol = :symbol LIMIT 1");
$stmt->execute([':symbol' => $symbol]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) exit(json_encode(["prices" => []]));

// 2. Ir buscar o histórico de compras (acumulação)
$query = "SELECT quantity_change, transaction_date FROM asset_history 
          WHERE asset_id = :id ORDER BY transaction_date ASC";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $asset['id']]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$prices = [];
$runningTotal = 0;

foreach ($history as $row) {
    $runningTotal += (float)$row['quantity_change'];
    // Formato ApexCharts: [timestamp_ms, quantidade_acumulada]
    $prices[] = [
        strtotime($row['transaction_date']) * 1000, 
        $runningTotal
    ];
}

echo json_encode(["prices" => $prices]);