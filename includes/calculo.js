// calculo.js
import {
  apiDisponible,
  apiConsultarSoat,
  apiMapearAVehiculo,
  apiDeducirCategoria,
} from './api_cliente.js';

export const TARIFAS_DEFAULT = {
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

export function cargarTarifas(tarifasOverride = null) {
  if (tarifasOverride && typeof tarifasOverride === 'object') {
    return tarifasOverride;
  }
  return TARIFAS_DEFAULT;
}

export function obtenerTarifaSoat(categoria, tarifasOverride = null) {
  const tarifas = cargarTarifas(tarifasOverride);
  return tarifas?.soat?.[categoria] ?? null;
}

export function obtenerOpcionesTerceros(categoria, tarifasOverride = null) {
  const tarifas = cargarTarifas(tarifasOverride);
  const todas = tarifas?.seguro_terceros ?? {};
  const opciones = {};

  for (const [id, opcion] of Object.entries(todas)) {
    const categorias = Array.isArray(opcion?.aplica_categoria) ? opcion.aplica_categoria : [];
    if (categorias.includes(categoria)) {
      opciones[id] = opcion;
    }
  }

  return opciones;
}

export function obtenerPlanesAccidentes(tarifasOverride = null) {
  const tarifas = cargarTarifas(tarifasOverride);
  return tarifas?.accidentes_personales ?? {};
}

export async function buscarVehiculoV2(placa, cedula = '', opciones = {}) {
  const placaLimpia = String(placa ?? '').trim().toUpperCase();
  const cedulaLimpia = String(cedula ?? '').trim();
  const tarifas = cargarTarifas(opciones.tarifas);
  let apiError = null;

  if (apiDisponible()) {
    const r = await apiConsultarSoat(placaLimpia, cedulaLimpia);

    if (r.ok && r.data) {
      const vehiculo = apiMapearAVehiculo(r.data, placaLimpia, cedulaLimpia);
      vehiculo.categoria = apiDeducirCategoria(vehiculo.clase || '', Number(vehiculo.cc || 0));

      if (!vehiculo.valor_soat_con_descuento) {
        const t = obtenerTarifaSoat(vehiculo.categoria, tarifas);
        if (t) {
          const oficial = Number(t.valor) || 0;
          const desc = Math.round(oficial * 0.3);
          vehiculo.valor_soat_oficial = oficial;
          vehiculo.valor_soat_descuento = desc;
          vehiculo.valor_soat_con_descuento = oficial - desc;
        }
      }

      return { vehiculo, error: null, origen: 'api' };
    }

    apiError = r.error || null;

    if (r.http === 404) {
      return {
        vehiculo: null,
        error: `No encontramos el vehículo con placa "${placaLimpia}". Verifica e intenta de nuevo.`,
        origen: 'api',
      };
    }
  }

  const demoBase = tarifas?.vehiculos_demo?.[placaLimpia] ?? null;
  if (demoBase) {
    const local = {
      ...demoBase,
      propietario: {
        nombres: 'MARIA',
        primer_apellido: 'GUZMAN',
        segundo_apellido: 'FRANCO',
        nombre_completo: 'MARIA GUZMAN FRANCO',
        documento: cedulaLimpia,
        tipo_doc: 'CC',
        tipo_propietario: 'PROPIO',
        inicio_propiedad: '',
        fin_propiedad: 'Actual',
        departamento: '',
        ciudad: '',
      },
    };

    const t = obtenerTarifaSoat(local.categoria || '', tarifas);
    if (t) {
      const oficial = Number(t.valor) || 0;
      const desc = Math.round(oficial * 0.3);
      local.valor_soat_oficial = oficial;
      local.valor_soat_descuento = desc;
      local.valor_soat_con_descuento = oficial - desc;
    }

    return { vehiculo: local, error: null, origen: 'local' };
  }

  return {
    vehiculo: null,
    error: apiError ?? `No encontramos datos para la placa "${placaLimpia}".`,
    origen: apiDisponible() ? 'api' : 'local',
  };
}

export async function buscarVehiculo(placa, cedula = '', opciones = {}) {
  const r = await buscarVehiculoV2(placa, cedula, opciones);
  return r.vehiculo;
}

export function calcularResumen(
  categoria,
  opcionTerceros = null,
  planAccidentes = null,
  vehiculo = null,
  tarifasOverride = null
) {
  let soat;
  let subtotalSoat = 0;
  let oficial = 0;
  let descuento = 0;

  if (vehiculo && Number(vehiculo.valor_soat_con_descuento) > 0) {
    subtotalSoat = Math.trunc(Number(vehiculo.valor_soat_con_descuento));
    oficial = Math.trunc(Number(vehiculo.valor_soat_oficial ?? 0));
    descuento = Math.trunc(Number(vehiculo.valor_soat_descuento ?? 0));
    soat = {
      label: vehiculo.descripcion_categoria || 'SOAT',
      valor: subtotalSoat,
      valor_oficial: oficial,
      descuento,
    };
  } else {
    const tarifaBase = obtenerTarifaSoat(categoria, tarifasOverride);
    if (!tarifaBase) {
      throw new Error(`Categoría de vehículo no reconocida: ${categoria}`);
    }
    subtotalSoat = Math.trunc(Number(tarifaBase.valor) || 0);
    oficial = subtotalSoat;
    descuento = 0;
    soat = { ...tarifaBase, valor: subtotalSoat };
  }

  let terceros = null;
  let subtotalTerceros = 0;
  if (opcionTerceros !== null && opcionTerceros !== '') {
    const opcionesDisponibles = obtenerOpcionesTerceros(categoria, tarifasOverride);
    if (Object.prototype.hasOwnProperty.call(opcionesDisponibles, opcionTerceros)) {
      terceros = opcionesDisponibles[opcionTerceros];
      subtotalTerceros = Math.trunc(Number(terceros.valor) || 0);
    }
  }

  let accidentes = null;
  let subtotalAccidentes = 0;
  if (planAccidentes !== null && planAccidentes !== '') {
    const planes = obtenerPlanesAccidentes(tarifasOverride);
    if (Object.prototype.hasOwnProperty.call(planes, planAccidentes)) {
      accidentes = planes[planAccidentes];
      subtotalAccidentes = Math.trunc(Number(accidentes.valor) || 0);
    }
  }

  const total = subtotalSoat + subtotalTerceros + subtotalAccidentes;

  return {
    soat,
    terceros,
    accidentes,
    subtotal_soat: subtotalSoat,
    valor_soat_oficial: oficial,
    descuento_soat: descuento,
    subtotal_terceros: subtotalTerceros,
    subtotal_accidentes: subtotalAccidentes,
    total,
  };
}

export function formatoCop(valor) {
  const num = Math.trunc(Number(valor) || 0);
  return '$' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

export function placaValida(placa) {
  const limpia = String(placa ?? '').trim().toUpperCase();
  return /^(?:[A-Z]{3}\d{3}|[A-Z]{3}\d{2}[A-Z0-9])$/.test(limpia);
}

export function documentoValido(documento) {
  const limpio = String(documento ?? '').trim();
  return /^[0-9]{6,12}$/.test(limpio);
}
 
