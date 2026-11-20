<?php





header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");


function detectar_actividad_sospechosa() {
    $payloads_maliciosos = ['union select', '<script>', '../', 'etc/passwd', 'eval(', 'base64_decode'];
    

    $data_a_revisar = json_encode($_GET) . json_encode($_POST) . $_SERVER['REQUEST_URI'];
    
    foreach ($payloads_maliciosos as $patron) {
        if (stripos($data_a_revisar, $patron) !== false) {

            header('HTTP/1.1 403 Forbidden');
            exit('Acceso bloqueado por políticas de seguridad.');
        }
    }
}


detectar_actividad_sospechosa();