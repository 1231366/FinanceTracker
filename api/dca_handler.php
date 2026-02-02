<?php
header("Content-Type: application/json");
require_once '../config/database.php';
require_once 'security.php';

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';
    $assetId = $data['id'] ?? null;

    if (!$assetId) exit(json_encode(["status" => "error", "message" => "ID em falta"]));

    if ($action === 'toggle_dca') {
        // Ativar/Desativar ou Atualizar valor do DCA
        try {
            $stmt = $db->prepare("UPDATE portfolio SET is_dca = :is_dca, dca_monthly_amount = :amount WHERE id = :id");
            $stmt->execute([
                ':is_dca' => $data['active'] ? 1 : 0,
                ':amount' => $data['amount'] ?? 0,
                ':id' => $assetId
            ]);
            echo json_encode(["status" => "success"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    } 
    
    if ($action === 'execute_dca') {
        // Simular a compra mensal baseada no preço atual de mercado
        try {
            $stmt = $db->prepare("SELECT * FROM portfolio WHERE id = :id");
            $stmt->execute([':id' => $assetId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($asset && $asset['is_dca']) {
                $currentPrice = floatval($data['current_price']);
                $monthlyInvest = floatval($asset['dca_monthly_amount']);
                
                // Decriptar dados atuais
                $oldQty = floatval(decryptData($asset['quantity']));
                $oldAvgPrice = floatval(decryptData($asset['buy_price']));

                // Cálculo de DCA
                $newQtyAdded = $monthlyInvest / $currentPrice;
                $totalQty = $oldQty + $newQtyAdded;
                
                // Novo Preço Médio = (Custo Total Anterior + Novo Investimento) / Nova Quantidade Total
                $totalSpent = ($oldQty * $oldAvgPrice) + $monthlyInvest;
                $newAvgPrice = $totalSpent / $totalQty;

                // Encriptar novos valores e atualizar data
                $update = $db->prepare("UPDATE portfolio SET quantity = :qty, buy_price = :price, last_dca_date = CURDATE() WHERE id = :id");
                $update->execute([
                    ':qty'   => encryptData($totalQty),
                    ':price' => encryptData($newAvgPrice),
                    ':id'    => $assetId
                ]);

                // Registar no histórico para que o gráfico reflita o aumento de património
                $hist = $db->prepare("INSERT INTO asset_history (asset_id, quantity_change) VALUES (:id, :qty)");
                $hist->execute([':id' => $assetId, ':qty' => $newQtyAdded]);

                echo json_encode(["status" => "executed", "added_qty" => $newQtyAdded, "new_total" => $totalQty]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }
}
?>