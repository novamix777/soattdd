// includes/security.js
import crypto from 'node:crypto';

/**
 * Escapa caracteres especiales para evitar inyección HTML/XSS.
 */
export function escapeHtml(valor) {
  return String(valor ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/**
 * Limpia y normaliza cadenas de texto de entrada.
 */
export function limpiarTexto(valor, maxLen = 200) {
  return String(valor ?? '')
    .replace(/[\u0000-\u001F\u007F]/g, '')
    .trim()
    .slice(0, maxLen);
}

/**
 * Normaliza y valida una placa vehicular colombiana (carro o moto).
 */
export function sanitizarPlaca(placa) {
  const limpia = String(placa ?? '')
    .toUpperCase()
    .replace(/[^A-Z0-9]/g, '')
    .slice(0, 7);

  const valida = /^(?:[A-Z]{3}\d{3}|[A-Z]{3}\d{2}[A-Z0-9])$/.test(limpia);
  return { valida, valor: limpia };
}

/**
 * Sanitiza y valida un número de documento.
 */
export function sanitizarDocumento(documento) {
  const limpio = String(documento ?? '')
    .replace(/\D/g, '')
    .slice(0, 15);

  const valida = /^\d{6,15}$/.test(limpio);
  return { valida, valor: limpio };
}

/**
 * Valida tipos de documento permitidos.
 */
export function tipoDocumentoValido(tipo) {
  const permitidos = ['CC', 'CE', 'NIT', 'TI', 'PS', 'PPT'];
  return permitidos.includes(String(tipo ?? '').trim().toUpperCase());
}

/**
 * Enmascara un número de tarjeta dejando solo los últimos 4 dígitos.
 */
export function enmascararTarjeta(numero) {
  const digitos = String(numero ?? '').replace(/\D/g, '');
  if (digitos.length < 4) return '****';
  return '•••• ' + digitos.slice(-4);
}

/**
 * Valida método HTTP permitido en un endpoint serverless.
 */
export function exigirMetodo(req, res, metodosPermitidos = ['POST']) {
  const permitidos = metodosPermitidos.map((m) => String(m).toUpperCase());
  const metodoActual = String(req.method ?? '').toUpperCase();

  if (!permitidos.includes(metodoActual)) {
    res.setHeader('Allow', permitidos.join(', '));
    res.status(405).json({ ok: false, error: 'Método no permitido' });
    return false;
  }
  return true;
}

/**
 * Valida que la petición venga de un origen permitido (si se configura ALLOWED_ORIGINS).
 * Si ALLOWED_ORIGINS está vacío, permite el mismo host de la petición.
 */
export function origenPermitido(req) {
  const origin = req.headers?.origin;
  if (!origin) return true;

  const envOrigins = String(process.env.ALLOWED_ORIGINS ?? '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);

  if (envOrigins.length > 0) {
    return envOrigins.includes(origin);
  }

  const host = req.headers?.host;
  if (!host) return false;

  try {
    const parsed = new URL(origin);
    return parsed.host === host;
  } catch {
    return false;
  }
}

/**
 * Cifra un texto con AES-256-CBC y devuelve Base64 (IV + Ciphertext).
 * La clave secreta debe venir de una variable de entorno (nunca hardcodeada).
 */
export function cifrarAes256Cbc(textoPlano, claveSecreta) {
  if (!claveSecreta) {
    throw new Error('Clave de cifrado no configurada');
  }
  const key = crypto.createHash('sha256').update(String(claveSecreta), 'utf8').digest();
  const iv = crypto.randomBytes(16);
  const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
  const cifrado = Buffer.concat([
    cipher.update(String(textoPlano), 'utf8'),
    cipher.final(),
  ]);

  return Buffer.concat([iv, cifrado]).toString('base64');
}

/**
 * Descifra un payload Base64 (IV + Ciphertext) cifrado con cifrarAes256Cbc.
 */
export function descifrarAes256Cbc(payloadBase64, claveSecreta) {
  if (!claveSecreta) {
    throw new Error('Clave de cifrado no configurada');
  }
  const buffer = Buffer.from(String(payloadBase64 ?? '').trim(), 'base64');
  if (buffer.length <= 16) {
    throw new Error('Payload cifrado inválido');
  }

  const iv = buffer.subarray(0, 16);
  const cifrado = buffer.subarray(16);
  const key = crypto.createHash('sha256').update(String(claveSecreta), 'utf8').digest();

  const decipher = crypto.createDecipheriv('aes-256-cbc', key, iv);
  const plano = Buffer.concat([
    decipher.update(cifrado),
    decipher.final(),
  ]);

  return plano.toString('utf8');
}
