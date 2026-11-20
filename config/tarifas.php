<?php


return [


    'soat' => [
        'moto_menos_100' => [
            'label'      => 'Motos de menos de 100 cc',
            'cc_min'     => 0,
            'cc_max'     => 99,
            'valor'      => 256300,
            'variacion'  => '5.17%',
        ],
        'moto_100_200' => [
            'label'      => 'Motos entre 100 y 200 cc',
            'cc_min'     => 100,
            'cc_max'     => 200,
            'valor'      => 343500,
            'variacion'  => '5.17%',
        ],
        'moto_mas_200' => [
            'label'      => 'Motos de más de 200 cc',
            'cc_min'     => 201,
            'cc_max'     => 999999,
            'valor'      => 761500,
            'variacion'  => '0.38%',
        ],
        'taxi_microbus' => [
            'label'      => 'Taxis y microbuses (según modelo)',
            'cc_min'     => null,
            'cc_max'     => null,
            'valor'      => 282100,
            'variacion'  => '5.17%',
        ],
        'carro_familiar' => [
            'label'      => 'Carros familiares (menos 1.500 cc)',
            'cc_min'     => 0,
            'cc_max'     => 1500,
            'valor'      => 447300,
            'variacion'  => '0.38%',
        ],
        'camioneta' => [
            'label'      => 'Camionetas (menos de 2.500 cc)',
            'cc_min'     => 0,
            'cc_max'     => 2500,
            'valor'      => 792900,
            'variacion'  => '0.38%',
        ],
    ],

    'vehiculos_demo' => [
        'VYG60F' => [
            'marca'     => 'BAJAJ',
            'linea'     => 'CT100KSSPOKE',
            'modelo'    => '2022',
            'clase'     => 'MOTOCICLETA',
            'cc'        => 100,
            'categoria' => 'moto_100_200',
        ],
        'ABC123' => [
            'marca'     => 'YAMAHA',
            'linea'     => 'XTZ125',
            'modelo'    => '2023',
            'clase'     => 'MOTOCICLETA',
            'cc'        => 125,
            'categoria' => 'moto_100_200',
        ],
        'XYZ789' => [
            'marca'     => 'KAWASAKI',
            'linea'     => 'NINJA 300',
            'modelo'    => '2021',
            'clase'     => 'MOTOCICLETA',
            'cc'        => 296,
            'categoria' => 'moto_mas_200',
        ],
    ],


    'seguro_terceros' => [
        'moto_opcion_1' => [
            'label'            => 'Opción Motos 1',
            'valor_asegurado'  => 10000000,
            'valor'            => 58100,
            'aplica_categoria' => ['moto_menos_100', 'moto_100_200', 'moto_mas_200'],
        ],
        'moto_opcion_2' => [
            'label'            => 'Opción Motos 2',
            'valor_asegurado'  => 25000000,
            'valor'            => 68000,
            'aplica_categoria' => ['moto_menos_100', 'moto_100_200', 'moto_mas_200'],
        ],
    ],

    'accidentes_personales' => [
        'plan_plata' => [
            'label' => 'PLAN PLATA',
            'valor' => 19900,
        ],
    ],

];
