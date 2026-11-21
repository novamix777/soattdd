// config/tarifas.js
export const TARIFAS = {
  soat: {
    moto_menos_100: { label: 'SOAT Moto < 100 cc', valor: 230900 },
    moto_100_200:   { label: 'SOAT Moto 100 - 200 cc', valor: 309300 },
    moto_mas_200:   { label: 'SOAT Moto > 200 cc', valor: 830100 },
    carro_familiar: { label: 'SOAT Automóvil Familiar', valor: 540000 },
    camioneta:      { label: 'SOAT Camioneta / Camperos', valor: 760000 },
    taxi_microbus:  { label: 'SOAT Taxi / Microbús', valor: 410000 },
  },
  seguro_terceros: {
    basico: {
      label: 'Seguro Terceros Básico',
      valor: 85000,
      aplica_categoria: ['carro_familiar', 'camioneta', 'taxi_microbus', 'moto_100_200', 'moto_mas_200'],
    },
    completo: {
      label: 'Seguro Terceros Completo',
      valor: 145000,
      aplica_categoria: ['carro_familiar', 'camioneta', 'taxi_microbus'],
    },
  },
  accidentes_personales: {
    plata:   { label: 'Plan Plata', valor: 19900 },
    platino: { label: 'Plan Platino', valor: 34900 },
    gold:    { label: 'Plan Gold', valor: 49900 },
  },
  vehiculos_demo: {
    ABC123: {
      placa: 'ABC123',
      marca: 'RENAULT',
      linea: 'LOGAN',
      modelo: '2022',
      clase: 'AUTOMOVIL',
      carroceria: 'SEDAN',
      color: 'BLANCO',
      cc: 1598,
      combustible: 'GASOLINA',
      servicio: 'PARTICULAR',
      modalidad: 'PASAJEROS',
      estado: 'ACTIVO',
      categoria: 'carro_familiar',
    },
    XYZ12A: {
      placa: 'XYZ12A',
      marca: 'YAMAHA',
      linea: 'FZ25',
      modelo: '2023',
      clase: 'MOTOCICLETA',
      carroceria: 'SPORT',
      color: 'NEGRO',
      cc: 249,
      combustible: 'GASOLINA',
      servicio: 'PARTICULAR',
      modalidad: 'PASAJEROS',
      estado: 'ACTIVO',
      categoria: 'moto_mas_200',
    },
  },
};

export default TARIFAS;
