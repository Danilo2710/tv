<?php
// proxy.php — versión optimizada con soporte de Range y streaming fluido
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");

if (!isset($_GET['url'])) {
    http_response_code(400);
    echo "Falta el parámetro 'url'";
    exit;
}

$url = urldecode($_GET['url']);
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo "URL inválida";
    exit;
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_BUFFERSIZE, 1024 * 8);
curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0');

// Soporte de rango para adelantar/retroceder
$headers = [];
if (isset($_SERVER['HTTP_RANGE'])) {
    $headers[] = "Range: " . $_SERVER['HTTP_RANGE'];
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Reenviar encabezados importantes (tipo, rango, longitud, etc.)
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) {
    $len = strlen($header);
    $header = trim($header);
    if (preg_match('/^(Content-Type|Content-Length|Accept-Ranges|Content-Range|Content-Disposition|Cache-Control|Last-Modified|ETag):/i', $header)) {
        header($header, false);
    }
    if (preg_match('/^HTTP\/.* ([0-9]{3}) /', $header, $matches)) {
        http_response_code((int)$matches[1]);
    }
    return $len;
});

// Transmitir el flujo directamente (sin cargar en memoria)
curl_exec($ch);
curl_close($ch);
