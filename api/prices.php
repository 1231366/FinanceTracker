<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$symbols = $_GET['symbols'] ?? '';
$type = $_GET['type'] ?? 'crypto';

if (!$symbols) exit(json_encode([]));

$results = [];

if ($type === 'crypto') {
    $url = "https://api.coingecko.com/api/v3/simple/price?ids=$symbols&vs_currencies=eur";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WealthAI/1.0');
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    echo json_encode($res ?: []);
} else {
    $url = "https://query2.finance.yahoo.com/v1/finance/quote?symbols=$symbols";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['quoteResponse']['result'])) {
        foreach ($response['quoteResponse']['result'] as $quote) {
            // Normalizar a chave para maiúsculas para o frontend encontrar
            $results[strtoupper($quote['symbol'])] = [
                "eur" => $quote['regularMarketPrice']
            ];
        }
    }
    echo json_encode($results);
}