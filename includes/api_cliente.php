<?php


function api_config(): array
{
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    $local = __DIR__ . '/../config/api.local.php';
    if (is_file($local)) {
        $c = require $local;
        if (is_array($c)) {
            return $cfg = [
                'url'     => rtrim((string)($c['url']   ?? ''), '/'),
                'token'   => (string)($c['token'] ?? ''),
                'timeout' => (int)($c['timeout'] ?? 30),
            ];
        }
    }

    return $cfg = [
        'url'     => rtrim((string)getenv('SOAT_API_URL'), '/'),
        'token'   => (string)getenv('SOAT_API_TOKEN'),
        'timeout' => (int)(getenv('SOAT_API_TIMEOUT') ?: 30),
    ];
}

function api_disponible(): bool
{
    $cfg = api_config();
    return $cfg['url'] !== '' && $cfg['token'] !== '';
}

/**

 *
 * @return array{ok: 
 */
function api_consultar_soat(string $placa, string $cedula = ''): array
{
    $cfg = api_config();

    if ($cfg['url'] === '' || $cfg['token'] === '') {
        return ['ok' => false, 'data' => null, 'error' => 'API no configurada', 'http' => 0];
    }

    $placa = strtoupper(trim($placa));
    $endpoint = $cfg['url'] . '/?placa=' . urlencode($placa);

    $headers = [
        'Authorization: Bearer ' . $cfg['token'],
        'Accept: application/json',
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_HTTPGET        => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $cfg['timeout'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $respuesta = curl_exec($ch);
    $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($respuesta === false) {
        return ['ok' => false, 'data' => null,
                'error' => 'Error de conexión: ' . $curlError, 'http' => 0];
    }

    $json = json_decode($respuesta, true);
    if (!is_array($json)) {
        $muestra = mb_substr(strip_tags((string)$respuesta), 0, 120);
        return ['ok' => false, 'data' => null,
                'error' => "Respuesta no válida (HTTP $httpCode): $muestra", 'http' => $httpCode];
    }

    if ($httpCode === 401) {
        return ['ok' => false, 'data' => $json,
                'error' => 'Token de API inválido o expirado', 'http' => 401];
    }

    if ($httpCode === 404 || (isset($json['error']) && stripos($json['error'], 'no encontrado') !== false)) {
        return ['ok' => false, 'data' => $json,
                'error' => 'Vehículo no encontrado. Verifica la placa.', 'http' => $httpCode];
    }

    if ($httpCode !== 200) {
        return ['ok' => false, 'data' => $json,
                'error' => $json['error'] ?? $json['message'] ?? "HTTP $httpCode",
                'http' => $httpCode];
    }

    if (!isset($json['caracteristicasVehiculo']) && !isset($json['cotizacionSoatDescuento'])) {
        return ['ok' => false, 'data' => $json,
                'error' => 'La API no devolvió los datos esperados del vehículo.',
                'http' => $httpCode];
    }

    return ['ok' => true, 'data' => $json, 'error' => null, 'http' => 200];
}

/**

 *
 * @param array  $apiData
 * @param string $placa
 * @param string $cedula
 * @return array
 */
function api_mapear_a_vehiculo(array $apiData, string $placa, string $cedula = ''): array
{
    $car = $apiData['caracteristicasVehiculo']     ?? [];
    $cot = $apiData['cotizacionSoatDescuento']    ?? [];
    $vals = $cot['valoresNumericos']              ?? [];
    $props = $apiData['propietarios']             ?? [];
    $lic = $apiData['licenciaTransito']           ?? [];
    $tec = $apiData['identificacionTecnica']      ?? [];
    $soatArr = $apiData['soatDetallado']          ?? [];


    $propActual = null;
    foreach ($props as $p) {
        if (isset($p['finPropiedad']) && stripos($p['finPropiedad'], 'actual') !== false) {
            $propActual = $p;
            break;
        }
    }
    if (!$propActual && !empty($props)) {
        $propActual = $props[0];
    }
    $propActual = $propActual ?? [];


    $nombreCompleto = trim($propActual['nombre'] ?? '');
    $partes = preg_split('/\s+/', $nombreCompleto);
    $nombres = '';
    $primerApellido = '';
    $segundoApellido = '';
    if (count($partes) >= 3) {

        $nombres = implode(' ', array_slice($partes, 0, count($partes) - 2));
        $primerApellido = $partes[count($partes) - 2] ?? '';
        $segundoApellido = $partes[count($partes) - 1] ?? '';
    } elseif (count($partes) === 2) {
        $nombres = $partes[0];
        $primerApellido = $partes[1];
    } elseif (count($partes) === 1) {
        $nombres = $partes[0];
    }


    $oficial     = (int)($vals['oficial']      ?? 0);
    $descuento   = (int)($vals['descuento']    ?? 0);
    $conDescuento = (int)($vals['conDescuento'] ?? 0);


    $soatActual = null;
    if (!empty($soatArr)) {
        usort($soatArr, function ($a, $b) {
            return strcmp($b['fechaInicioVigencia'] ?? '', $a['fechaInicioVigencia'] ?? '');
        });
        $soatActual = $soatArr[0];
    }
    $soatActual = $soatActual ?? [];

    return [
        'marca'      => $car['marca']      ?? '',
        'linea'      => $car['linea']      ?? '',
        'modelo'     => $car['modelo']     ?? '',
        'clase'      => $car['clase']      ?? '',
        'carroceria' => $car['carroceria'] ?? '',
        'color'      => $car['color']      ?? '',
        'cc'         => (int)($car['cilindraje'] ?? 0),
        'combustible' => $car['combustible'] ?? '',
        'servicio'   => $car['servicio']   ?? '',
        'modalidad'  => $car['modalidad']  ?? '',
        'estado'     => $car['estado']     ?? '',


        'valor_soat_oficial'        => $oficial,
        'valor_soat_descuento'      => $descuento,
        'valor_soat_con_descuento'  => $conDescuento,
        'codigo_categoria'          => $cot['codigoCategoria']       ?? '',
        'descripcion_categoria'     => $cot['descripcionCategoria']  ?? '',


        'numero_poliza'      => $soatActual['numeroPoliza']       ?? '',
        'entidad_emisora'    => $soatActual['entidadEmisora']     ?? '',
        'vigencia_desde'     => $soatActual['fechaInicioVigencia'] ?? '',
        'vigencia_hasta'     => $soatActual['fechaFinVigencia']    ?? '',
        'estado_vigencia'    => $soatActual['estadoVigencia']     ?? '',


        'numero_licencia'    => $lic['numeroLicencia'] ?? '',
        'fecha_matricula'    => $lic['fechaMatricula'] ?? '',
        'autoridad'          => $lic['autoridad']      ?? '',


        'vin'    => $tec['vin']    ?? '',
        'motor'  => $tec['motor']  ?? '',
        'chasis' => $tec['chasis'] ?? '',


        'categoria'  => '',

        // Propietario
        'propietario' => [
            'nombres'          => $nombres,
            'primer_apellido'  => $primerApellido,
            'segundo_apellido' => $segundoApellido,
            'nombre_completo'  => $nombreCompleto,
            'documento'        => $propActual['documentoCensurado'] ?? $cedula,
            'tipo_doc'         => $propActual['tipoDoc']            ?? '',
            'tipo_propietario' => $propActual['tipoPropietario']    ?? '',
            'inicio_propiedad' => $propActual['inicioPropiedad']    ?? '',
            'fin_propiedad'    => $propActual['finPropiedad']       ?? '',
            'departamento'     => '',
            'ciudad'           => $lic['autoridad'] ?? '',
        ],

        'raw_api' => $apiData,
    ];
}


function api_deducir_categoria(string $clase, int $cc): string
{
    $c = strtoupper($clase);
    $esMoto = str_contains($c, 'MOTO');

    if ($esMoto) {
        if ($cc < 100)  return 'moto_menos_100';
        if ($cc <= 200) return 'moto_100_200';
        return 'moto_mas_200';
    }
    if (str_contains($c, 'TAXI') || str_contains($c, 'MICROBUS')) return 'taxi_microbus';
    if (str_contains($c, 'CAMIONETA') || $cc > 1500)             return 'camioneta';
    return 'carro_familiar';
}
