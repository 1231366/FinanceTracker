<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/database.php';
require_once 'security.php';

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    if (empty($data['symbol']) || empty($data['quantity'])) {
        echo json_encode(["status" => "error", "message" => "Missing fields"]);
        exit;
    }

    $symbol = strtoupper(trim($data['symbol']));
    $newQty = floatval($data['quantity']);
    $newPrice = floatval($data['price']);

    try {
        // 1. Verificar se o ativo já existe
        $check = $db->prepare("SELECT id, quantity, buy_price FROM portfolio WHERE symbol = :symbol LIMIT 1");
        $check->execute([':symbol' => $symbol]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 2. Lógica de Preço Médio Ponderado
            $oldQty = floatval(decryptData($existing['quantity']));
            $oldPrice = floatval(decryptData($existing['buy_price']));

            $totalQty = $oldQty + $newQty;
            // Fórmul: ((Q1 * P1) + (Q2 * P2)) / Total Qty
            $avgPrice = (($oldQty * $oldPrice) + ($newQty * $newPrice)) / $totalQty;

            $update = $db->prepare("UPDATE portfolio SET quantity = :qty, buy_price = :price WHERE id = :id");
            $update->execute([
                ':qty'   => encryptData($totalQty),
                ':price' => encryptData($avgPrice),
                ':id'    => $existing['id']
            ]);
        } else {
            // 3. Inserir novo se não existir
            $query = "INSERT INTO portfolio (symbol, asset_name, image_url, quantity, buy_price, asset_type) 
                      VALUES (:symbol, :name, :image, :qty, :price, :type)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':symbol' => $symbol,
                ':name'   => $data['name'] ?? '',
                ':image'  => $data['thumb'] ?? '',
                ':qty'    => encryptData($newQty),
                ':price'  => encryptData($newPrice),
                ':type'   => $data['type'] ?? 'stock'
            ]);
        }
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

if ($method === 'GET') {
    $stmt = $db->prepare("SELECT * FROM portfolio ORDER BY symbol ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['quantity'] = decryptData($row['quantity']);
        $row['buy_price'] = decryptData($row['buy_price']);
    }
    echo json_encode($rows);
}