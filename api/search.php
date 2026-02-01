<?php
header("Content-Type: application/json");

$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

function fetchData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    // Simular um browser real para evitar bloqueios de APIs de Stocks
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$results = [];

// --- BUSCA CRYPTO (CoinGecko) ---
$cryptoJson = fetchData("https://api.coingecko.com/api/v3/search?query=" . urlencode($query));
$cryptoData = json_decode($cryptoJson, true);

if (isset($cryptoData['coins'])) {
    foreach (array_slice($cryptoData['coins'], 0, 3) as $coin) {
        $results[] = [
            'symbol' => strtoupper($coin['symbol']),
            'name' => $coin['name'],
            'thumb' => $coin['thumb'],
            'type' => 'crypto'
        ];
    }
}

// --- BUSCA STOCKS (Yahoo Finance - Query2 API) ---
$stocksJson = fetchData("https://query2.finance.yahoo.com/v1/finance/search?q=" . urlencode($query) . "&quotesCount=5&newsCount=0");
$stocksData = json_decode($stocksJson, true);

if (isset($stocksData['quotes'])) {
    foreach ($stocksData['quotes'] as $quote) {
        // Apenas Stocks (EQUITY) ou ETFs
        if (isset($quote['symbol']) && ($quote['quoteType'] === 'EQUITY' || $quote['quoteType'] === 'ETF')) {
            $symbol = $quote['symbol'];
            $results[] = [
                'symbol' => $symbol,
                'name' => $quote['shortname'] ?? $quote['longname'] ?? $symbol,
                // Truque: Usar o logo da TradingView que é público e fiável para stocks
                'thumb' => "https://s3-symbol-logo.tradingview.com/" . strtolower(explode('.', $symbol)[0]) . "--big.svg",
                'type' => 'stock'
            ];
        }
    }
}

// Validar se as imagens existem, caso contrário, fallback
foreach ($results as &$res) {
    if (empty($res['thumb'])) {
        $res['thumb'] = "https://cdn-icons-png.flaticon.com/512/2534/2534354.png";
    }
}

echo json_encode($results);