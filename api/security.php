<?php
// api/security.php

// NUNCA mudes esta chave depois de teres dados na BD, ou não conseguirás ler nada!
define('ENCRYPTION_KEY', 'W3althA1_Secure_Key_2026_!#@Admin'); 
define('METHOD', 'aes-256-cbc');

function encryptData($value) {
    if (empty($value)) return null;
    $ivLength = openssl_cipher_iv_length(METHOD);
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt((string)$value, METHOD, ENCRYPTION_KEY, 0, $iv);
    // Concatenamos o IV com o dado para persistência
    return base64_encode($encrypted . '::' . bin2hex($iv));
}

function decryptData($value) {
    if (empty($value)) return "0";
    $decoded = base64_decode($value);
    if (!strpos($decoded, '::')) return $value; // Fallback para dados não encriptados
    
    list($encrypted_data, $ivHex) = explode('::', $decoded, 2);
    $iv = hex2bin($ivHex);
    return openssl_decrypt($encrypted_data, METHOD, ENCRYPTION_KEY, 0, $iv);
}