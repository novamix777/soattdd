<?php

require_once __DIR__ . '/api_cliente.php';


function cargar_tarifas(): array
{
    return require __DIR__ . '/../config/tarifas.php';
}


function buscar_vehiculo_v2(string $placa, string $cedula): array
{
    $placa = strtoupper(trim($placa));
    $apiError = null;


    if (api_disponible()) {
        $r = api_consultar_soat($placa, $cedula);

        if ($r['ok']) {
            $vehiculo = api_mapear_a_vehiculo($r['data'], $placa, $cedula);


            $vehiculo['categoria'] = api_deducir_categoria(
                $vehiculo['clase'],
                $vehiculo['cc']
            );


            if (empty($vehiculo['valor_soat_con_descuento'])) {
                $t = obtener_tarifa_soat($vehiculo['categoria']);
                if ($t) {
                    $oficial = (int)$t['valor'];
                    $desc    = (int)round($oficial * 0.30);
                    $vehiculo['valor_soat_oficial']       = $oficial;
                    $vehiculo['valor_soat_descuento']     = $desc;
                    $vehiculo['valor_soat_con_descuento'] = $oficial - $desc;
                }
            }

            return ['vehiculo' => $vehiculo, 'error' => null, 'origen' => 'api'];
        }

        $apiError = $r['error'];


        if ($r['http'] === 404) {
            return [
                'vehiculo' => null,
                'error'    => "No encontramos el vehículo con placa \"$placa\". Verifica e intenta de nuevo.",
                'origen'   => 'api',
            ];
        }
    }


    $tarifas = cargar_tarifas();
    $local   = $tarifas['vehiculos_demo'][$placa] ?? null;

    if ($local !== null) {
        $local['propietario'] = [
            'nombres'          => 'MARIA',
            'primer_apellido'  => 'GUZMAN',
            'segundo_apellido' => 'FRANCO',
            'nombre_completo'  => 'MARIA GUZMAN FRANCO',
            'documento'        => $cedula,
            'tipo_doc'         => 'CC',
            'tipo_propietario' => 'PROPIO',
            'inicio_propiedad' => '',
            'fin_propiedad'    => 'Actual',
            'departamento'     => '',
            'ciudad'           => '',
        ];

   
        $t = obtener_tarifa_soat($local['categoria'] ?? '');
        if ($t) {
            $oficial = (int)$t['valor'];
            $desc    = (int)round($oficial * 0.30);
            $local['valor_soat_oficial']       = $oficial;
            $local['valor_soat_descuento']     = $desc;
            $local['valor_soat_con_descuento'] = $oficial - $desc;
        }

        return ['vehiculo' => $local, 'error' => null, 'origen' => 'local'];
    }

    return [
        'vehiculo' => null,
        'error'    => $apiError ?? "No encontramos datos para la placa \"$placa\".",
        'origen'   => api_disponible() ? 'api' : 'local',
    ];
}


function buscar_vehiculo(string $placa): ?array
{
    $cedula = $_SESSION['cotizacion']['documento'] ?? '';
    $r = buscar_vehiculo_v2($placa, $cedula);
    return $r['vehiculo'];
}


function obtener_tarifa_soat(string $categoria): ?array
{
    $tarifas = cargar_tarifas();
    return $tarifas['soat'][$categoria] ?? null;
}

function obtener_opciones_terceros(string $categoria): array
{
    $tarifas = cargar_tarifas();
    $opciones = [];

    foreach ($tarifas['seguro_terceros'] as $id => $opcion) {
        if (in_array($categoria, $opcion['aplica_categoria'], true)) {
            $opciones[$id] = $opcion;
        }
    }

    return $opciones;
}


function obtener_planes_accidentes(): array
{
    $tarifas = cargar_tarifas();
    return $tarifas['accidentes_personales'];
}


function calcular_resumen(
    string $categoria,
    ?string $opcionTerceros,
    ?string $planAccidentes,
    ?array $vehiculo = null
): array {
    // Base SOAT: si el vehículo trae valores de la API, los usamos
    if ($vehiculo !== null && !empty($vehiculo['valor_soat_con_descuento'])) {
        $subtotalSoat = (int)$vehiculo['valor_soat_con_descuento'];
        $oficial      = (int)($vehiculo['valor_soat_oficial'] ?? 0);
        $descuento    = (int)($vehiculo['valor_soat_descuento'] ?? 0);
        $soat = [
            'label'         => $vehiculo['descripcion_categoria'] ?? 'SOAT',
            'valor'         => $subtotalSoat,
            'valor_oficial' => $oficial,
            'descuento'     => $descuento,
        ];
    } else {
      
        $soat = obtener_tarifa_soat($categoria);
        if ($soat === null) {
            throw new InvalidArgumentException("Categoría de vehículo no reconocida: $categoria");
        }
        $subtotalSoat = (int)$soat['valor'];
        $oficial      = $subtotalSoat;
        $descuento    = 0;
    }

    $terceros = null;
    $subtotalTerceros = 0;
    if ($opcionTerceros !== null) {
        $opcionesDisponibles = obtener_opciones_terceros($categoria);
        if (isset($opcionesDisponibles[$opcionTerceros])) {
            $terceros = $opcionesDisponibles[$opcionTerceros];
            $subtotalTerceros = $terceros['valor'];
        }
    }


    $accidentes = null;
    $subtotalAccidentes = 0;
    if ($planAccidentes !== null) {
        $planes = obtener_planes_accidentes();
        if (isset($planes[$planAccidentes])) {
            $accidentes = $planes[$planAccidentes];
            $subtotalAccidentes = $accidentes['valor'];
        }
    }

    $total = $subtotalSoat + $subtotalTerceros + $subtotalAccidentes;

    return [
        'soat'                => $soat,
        'terceros'            => $terceros,
        'accidentes'          => $accidentes,
        'subtotal_soat'       => $subtotalSoat,
        'valor_soat_oficial'  => $oficial,
        'descuento_soat'      => $descuento,
        'subtotal_terceros'   => $subtotalTerceros,
        'subtotal_accidentes' => $subtotalAccidentes,
        'total'               => $total,
    ];
}


function formato_cop(int $valor): string
{
    return '$' . number_format($valor, 0, ',', '.');
}


function placa_valida(string $placa): bool
{
    $placa = strtoupper(trim($placa));
    return (bool) preg_match('/^(?:[A-Z]{3}\d{3}|[A-Z]{3}\d{2}[A-Z0-9])$/', $placa);
}


function documento_valido(string $documento): bool
{
    $documento = trim($documento);
    return (bool) preg_match('/^[0-9]{6,12}$/', $documento);
}