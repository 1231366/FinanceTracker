<?php
header("Content-Type: application/json");

$initial = $_GET['initial'] ?? 0;
$monthly = $_GET['monthly'] ?? 100;
$rate = ($_GET['rate'] ?? 8) / 100 / 12; // Taxa mensal
$months = ($_GET['years'] ?? 10) * 12;

$projection = [];
$balance = $initial;

for ($i = 0; $i <= $months; $i++) {
    if ($i % 12 == 0) {
        $projection[] = [
            "year" => $i / 12,
            "value" => round($balance, 2)
        ];
    }
    $balance = ($balance + $monthly) * (1 + $rate);
}

echo json_encode($projection);
?>