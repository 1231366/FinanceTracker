<?php
header("Content-Type: application/json");
require_once '../config/database.php';
require_once 'security.php';

// Importa a chave do ficheiro externo
if (file_exists('config_keys.php')) {
    require_once 'config_keys.php';
} else {
    // Caso o ficheiro não exista (ex: ambiente de produção/GitHub)
    define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');
}

define('GROQ_MODEL', 'llama-3.1-8b-instant');

$database = new Database();
$db = $database->getConnection();

// --- FUNÇÃO PARA OBTER PREÇOS REAIS ---
function getActualMarketPrices($assets) {
    $prices = [];
    $cryptos = [];
    foreach ($assets as $a) {
        if ($a['asset_type'] === 'crypto') $cryptos[] = strtolower($a['symbol']);
    }

    if (!empty($cryptos)) {
        $ids = implode(',', $cryptos);
        $url = "https://api.coingecko.com/api/v3/simple/price?ids={$ids}&vs_currencies=eur";
        $res = @file_get_contents($url);
        $data = json_decode($res, true);
        foreach ($cryptos as $id) {
            if (isset($data[$id])) $prices[strtoupper($id)] = $data[$id]['eur'];
        }
    }
    return $prices;
}

$portfolioContext = "";
$totalInvested = 0;
$totalCurrentValue = 0;

try {
    $stmt = $db->prepare("SELECT * FROM portfolio");
    $stmt->execute();
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $livePrices = getActualMarketPrices($assets);

    foreach ($assets as $a) {
        $sym = strtoupper($a['symbol']);
        $qty = (float)decryptData($a['quantity']);
        $buyPrice = (float)decryptData($a['buy_price']);
        $currentPrice = $livePrices[$sym] ?? $buyPrice;
        
        $totalInvested += ($qty * $buyPrice);
        $totalCurrentValue += ($qty * $currentPrice);
        $pnl = ($buyPrice > 0) ? (($currentPrice - $buyPrice) / $buyPrice) * 100 : 0;
        
        $portfolioContext .= "- {$sym}: Qtd {$qty} | Custo: €" . number_format($buyPrice, 2) . " | Mercado: €" . number_format($currentPrice, 2) . " (PnL: " . number_format($pnl, 2) . "%)\n";
    }
} catch (Exception $e) { $portfolioContext = "Erro ao carregar dados."; }

$input = json_decode(file_get_contents("php://input"), true);
$userMsg = $input['message'] ?? "Análise de portfólio.";

$systemPrompt = "És o WealthAI Guru. Tens acesso a dados reais.
Responde de forma curta e executiva (PT-PT). Usa negritos em valores.
Total Investido: €" . number_format($totalInvested, 2) . "
Valor de Mercado: €" . number_format($totalCurrentValue, 2) . "
Detalhamento:
{$portfolioContext}";

if (empty(GROQ_API_KEY)) {
    echo json_encode(["chat_response" => "Erro: API Key não configurada no servidor."]);
    exit;
}

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . GROQ_API_KEY, "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => GROQ_MODEL,
    "messages" => [["role" => "system", "content" => $systemPrompt], ["role" => "user", "content" => $userMsg]],
    "temperature" => 0.4
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$resData = json_decode($response, true);
$aiText = $resData['choices'][0]['message']['content'] ?? "Sem resposta da IA.";

echo json_encode(["chat_response" => $aiText]);