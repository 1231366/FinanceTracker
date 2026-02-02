<?php
header("Content-Type: application/json");
require_once '../config/database.php';
require_once 'security.php';

if (file_exists('config_keys.php')) { require_once 'config_keys.php'; } 
else { define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: ''); }

define('GROQ_MODEL', 'llama-3.1-8b-instant');

$database = new Database();
$db = $database->getConnection();

function getLiveMarketData($assets) {
    $prices = [];
    $mapper = [
        'BTC' => 'bitcoin', 'ETH' => 'ethereum', 'SOL' => 'solana',
        'ADA' => 'cardano', 'DOT' => 'polkadot', 'XRP' => 'ripple',
        'MATIC' => 'polygon-ecosystem-index'
    ];

    $idsToFetch = [];
    foreach ($assets as $a) {
        $sym = strtoupper($a['symbol']);
        if ($a['asset_type'] === 'crypto') {
            $idsToFetch[] = isset($mapper[$sym]) ? $mapper[$sym] : strtolower($a['asset_name']);
        }
    }

    if (!empty($idsToFetch)) {
        $url = "https://api.coingecko.com/api/v3/simple/price?ids=" . implode(',', array_unique($idsToFetch)) . "&vs_currencies=eur";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);

        foreach ($assets as $a) {
            $sym = strtoupper($a['symbol']);
            $id = isset($mapper[$sym]) ? $mapper[$sym] : strtolower($a['asset_name']);
            if (isset($data[$id]['eur'])) { $prices[$sym] = $data[$id]['eur']; }
        }
    }
    return $prices;
}

$portfolioContext = "";
$totalInvested = 0;
$totalMarketValue = 0;

try {
    $stmt = $db->prepare("SELECT * FROM portfolio");
    $stmt->execute();
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $livePrices = getLiveMarketData($assets);

    foreach ($assets as $a) {
        $sym = strtoupper($a['symbol']);
        $qty = (float)decryptData($a['quantity']);
        $buyPrice = (float)decryptData($a['buy_price']);
        
        // NOVIDADE: Contexto de DCA para a IA
        $dcaStatus = $a['is_dca'] ? " [Plano DCA Ativo: €" . $a['dca_monthly_amount'] . "/mês]" : " [Sem DCA]";
        
        $currentPrice = isset($livePrices[$sym]) ? $livePrices[$sym] : $buyPrice;
        $invested = $qty * $buyPrice;
        $marketVal = $qty * $currentPrice;
        
        $totalInvested += $invested;
        $totalMarketValue += $marketVal;
        
        $pnl_eur = $marketVal - $invested;
        $pnl_pct = ($invested > 0) ? ($pnl_eur / $invested) * 100 : 0;
        
        $portfolioContext .= "- {$sym}: Qtd {$qty} | Custo: €" . number_format($buyPrice, 2) . " | Mercado: €" . number_format($currentPrice, 2) . " | PnL: " . number_format($pnl_pct, 2) . "% {$dcaStatus}\n";
    }
} catch (Exception $e) { $portfolioContext = "Erro DB."; }

$input = json_decode(file_get_contents("php://input"), true);
$userMsg = $input['message'] ?? "Faz uma análise do meu lucro real.";

$systemPrompt = "És o WealthAI Guru. Tens acesso a preços em tempo real.
DADOS REAIS:
- Investimento: €" . number_format($totalInvested, 2) . "
- Valor Mercado: €" . number_format($totalMarketValue, 2) . "
- Ganho/Perda: €" . number_format($totalMarketValue - $totalInvested, 2) . "

PORTFÓLIO E PLANOS DCA:
{$portfolioContext}

REGRAS:
1. Fala do lucro/prejuízo REAL.
2. Usa Português de Portugal.
3. Sê curto e agressivo (máx 4 frases).
4. Usa negritos para valores.
5. Se o utilizador tiver DCA ativo, valida se o valor faz sentido para o mercado atual.";

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . GROQ_API_KEY, "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => GROQ_MODEL,
    "messages" => [["role" => "system", "content" => $systemPrompt], ["role" => "user", "content" => $userMsg]],
    "temperature" => 0.2
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
$response = curl_exec($ch);
$resData = json_decode($response, true);
$aiText = $resData['choices'][0]['message']['content'] ?? "Sem resposta.";

echo json_encode(["chat_response" => $aiText]);
?>