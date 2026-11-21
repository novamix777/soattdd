import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class SoatApiClient {

    private final String baseUrl;
    private final String token;
    private final int timeoutSeconds;
    private final HttpClient httpClient;

    public SoatApiClient() {
        String envUrl = System.getenv("SOAT_API_URL");
        String envToken = System.getenv("SOAT_API_TOKEN");
        String envTimeout = System.getenv("SOAT_API_TIMEOUT");

        this.baseUrl = envUrl != null ? envUrl.trim().replaceAll("/+$", "") : "";
        this.token = envToken != null ? envToken.trim() : "";

        int parsedTimeout = 30;
        if (envTimeout != null && !envTimeout.isBlank()) {
            try {
                parsedTimeout = Math.max(1, Integer.parseInt(envTimeout.trim()));
            } catch (NumberFormatException ignored) {
            }
        }
        this.timeoutSeconds = parsedTimeout;

        this.httpClient = HttpClient.newBuilder()
                .connectTimeout(Duration.ofSeconds(10))
                .followRedirects(HttpClient.Redirect.NORMAL)
                .build();
    }

    public SoatApiClient(String baseUrl, String token, int timeoutSeconds) {
        this.baseUrl = baseUrl != null ? baseUrl.trim().replaceAll("/+$", "") : "";
        this.token = token != null ? token.trim() : "";
        this.timeoutSeconds = Math.max(1, timeoutSeconds);
        this.httpClient = HttpClient.newBuilder()
                .connectTimeout(Duration.ofSeconds(10))
                .followRedirects(HttpClient.Redirect.NORMAL)
                .build();
    }

    public boolean isDisponible() {
        return !baseUrl.isEmpty() && !token.isEmpty();
    }

    public static class ResultadoConsulta {
        public final boolean ok;
        public final Map<String, Object> data;
        public final String rawJson;
        public final String error;
        public final int http;

        public ResultadoConsulta(boolean ok, Map<String, Object> data, String rawJson, String error, int http) {
            this.ok = ok;
            this.data = data;
            this.rawJson = rawJson;
            this.error = error;
            this.http = http;
        }

        public static ResultadoConsulta fallo(String error, int http, Map<String, Object> data, String rawJson) {
            return new ResultadoConsulta(false, data, rawJson, error, http);
        }

        public static ResultadoConsulta exito(Map<String, Object> data, String rawJson) {
            return new ResultadoConsulta(true, data, rawJson, null, 200);
        }
    }

    public ResultadoConsulta consultarSoat(String placa, String cedula) {
        if (baseUrl.isEmpty() || token.isEmpty()) {
            return ResultadoConsulta.fallo("API no configurada", 0, null, null);
        }

        String placaLimpia = placa != null ? placa.trim().toUpperCase(Locale.ROOT) : "";
        String encodedPlaca = URLEncoder.encode(placaLimpia, StandardCharsets.UTF_8);
        String endpoint = baseUrl + "/?placa=" + encodedPlaca;

        try {
            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create(endpoint))
                    .timeout(Duration.ofSeconds(timeoutSeconds))
                    .header("Authorization", "Bearer " + token)
                    .header("Accept", "application/json")
                    .GET()
                    .build();

            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString(StandardCharsets.UTF_8));
            int httpCode = response.statusCode();
            String body = response.body() != null ? response.body() : "";

            Map<String, Object> jsonMap = parseJsonObjectSafe(body);
            if (jsonMap == null) {
                String muestra = body.replaceAll("<[^>]*>", "").trim();
                if (muestra.length() > 120) {
                    muestra = muestra.substring(0, 120);
                }
                return ResultadoConsulta.fallo(
                        "Respuesta no válida (HTTP " + httpCode + "): " + muestra,
                        httpCode,
                        null,
                        body
                );
            }

            if (httpCode == 401) {
                return ResultadoConsulta.fallo("Token de API inválido o expirado", 401, jsonMap, body);
            }

            String errMsg = str(jsonMap.get("error"));
            if (httpCode == 404 || (!errMsg.isEmpty() && errMsg.toLowerCase(Locale.ROOT).contains("no encontrado"))) {
                return ResultadoConsulta.fallo("Vehículo no encontrado. Verifica la placa.", httpCode, jsonMap, body);
            }

            if (httpCode != 200) {
                String msg = !errMsg.isEmpty() ? errMsg : str(jsonMap.get("message"));
                if (msg.isEmpty()) msg = "HTTP " + httpCode;
                return ResultadoConsulta.fallo(msg, httpCode, jsonMap, body);
            }

            boolean tieneCaracteristicas = jsonMap.containsKey("caracteristicasVehiculo");
            boolean tieneCotizacion = jsonMap.containsKey("cotizacionSoatDescuento");
            if (!tieneCaracteristicas && !tieneCotizacion) {
                return ResultadoConsulta.fallo(
                        "La API no devolvió los datos esperados del vehículo.",
                        httpCode,
                        jsonMap,
                        body
                );
            }

            return ResultadoConsulta.exito(jsonMap, body);
        } catch (Exception e) {
            String msg = e.getMessage() != null ? e.getMessage() : e.getClass().getSimpleName();
            return ResultadoConsulta.fallo("Error de conexión: " + msg, 0, null, null);
        }
    }

    @SuppressWarnings("unchecked")
    public Map<String, Object> mapearAVehiculo(Map<String, Object> apiData, String placa, String cedula) {
        Map<String, Object> safeData = apiData != null ? apiData : Collections.emptyMap();

        Map<String, Object> car = asMap(safeData.get("caracteristicasVehiculo"));
        Map<String, Object> cot = asMap(safeData.get("cotizacionSoatDescuento"));
        Map<String, Object> vals = asMap(cot.get("valoresNumericos"));
        List> props = asListOfMaps(safeData.get("propietarios"));
        Map<String, Object> lic = asMap(safeData.get("licenciaTransito"));
        Map<String, Object> tec = asMap(safeData.get("identificacionTecnica"));
        List> soatArr = asListOfMaps(safeData.get("soatDetallado"));

        Map<String, Object> propActual = null;
        for (Map<String, Object> p : props) {
            String fin = str(p.get("finPropiedad"));
            if (!fin.isEmpty() && fin.toLowerCase(Locale.ROOT).contains("actual")) {
                propActual = p;
                break;
            }
        }
        if (propActual == null && !props.isEmpty()) {
            propActual = props.get(0);
        }
        if (propActual == null) {
            propActual = Collections.emptyMap();
        }

        String nombreCompleto = str(propActual.get("nombre")).trim();
        String[] partes = nombreCompleto.isEmpty() ? new String[0] : nombreCompleto.split("\\s+");
        String nombres = "";
        String primerApellido = "";
        String segundoApellido = "";

        if (partes.length >= 3) {
            nombres = String.join(" ", Arrays.copyOfRange(partes, 0, partes.length - 2));
            primerApellido = partes[partes.length - 2];
            segundoApellido = partes[partes.length - 1];
        } else if (partes.length == 2) {
            nombres = partes[0];
            primerApellido = partes[1];
        } else if (partes.length == 1) {
            nombres = partes[0];
        }

        int oficial = toInt(vals.get("oficial"));
        int descuento = toInt(vals.get("descuento"));
        int conDescuento = toInt(vals.get("conDescuento"));

        Map<String, Object> soatActual = Collections.emptyMap();
        if (!soatArr.isEmpty()) {
            List> ordenados = new ArrayList<>(soatArr);
            ordenados.sort((a, b) -> str(b.get("fechaInicioVigencia"))
                    .compareTo(str(a.get("fechaInicioVigencia"))));
            soatActual = ordenados.get(0);
        }

        String clase = str(car.get("clase"));
        int cc = toInt(car.get("cilindraje"));

        Map<String, Object> propietario = new LinkedHashMap<>();
        propietario.put("nombres", nombres);
        propietario.put("primer_apellido", primerApellido);
        propietario.put("segundo_apellido", segundoApellido);
        propietario.put("nombre_completo", nombreCompleto);
        String docCensurado = str(propActual.get("documentoCensurado"));
        propietario.put("documento", !docCensurado.isEmpty() ? docCensurado : (cedula != null ? cedula : ""));
        propietario.put("tipo_doc", str(propActual.get("tipoDoc")));
        propietario.put("tipo_propietario", str(propActual.get("tipoPropietario")));
        propietario.put("inicio_propiedad", str(propActual.get("inicioPropiedad")));
        propietario.put("fin_propiedad", str(propActual.get("finPropiedad")));
        propietario.put("departamento", "");
        propietario.put("ciudad", str(lic.get("autoridad")));

        Map<String, Object> resultado = new LinkedHashMap<>();
        resultado.put("placa", placa != null ? placa.trim().toUpperCase(Locale.ROOT) : "");
        resultado.put("marca", str(car.get("marca")));
        resultado.put("linea", str(car.get("linea")));
        resultado.put("modelo", str(car.get("modelo")));
        resultado.put("clase", clase);
        resultado.put("carroceria", str(car.get("carroceria")));
        resultado.put("color", str(car.get("color")));
        resultado.put("cc", cc);
        resultado.put("combustible", str(car.get("combustible")));
        resultado.put("servicio", str(car.get("servicio")));
        resultado.put("modalidad", str(car.get("modalidad")));
        resultado.put("estado", str(car.get("estado")));

        resultado.put("valor_soat_oficial", oficial);
        resultado.put("valor_soat_descuento", descuento);
        resultado.put("valor_soat_con_descuento", conDescuento);
        resultado.put("codigo_categoria", str(cot.get("codigoCategoria")));
        resultado.put("descripcion_categoria", str(cot.get("descripcionCategoria")));

        resultado.put("numero_poliza", str(soatActual.get("numeroPoliza")));
        resultado.put("entidad_emisora", str(soatActual.get("entidadEmisora")));
        resultado.put("vigencia_desde", str(soatActual.get("fechaInicioVigencia")));
        resultado.put("vigencia_hasta", str(soatActual.get("fechaFinVigencia")));
        resultado.put("estado_vigencia", str(soatActual.get("estadoVigencia")));

        resultado.put("numero_licencia", str(lic.get("numeroLicencia")));
        resultado.put("fecha_matricula", str(lic.get("fechaMatricula")));
        resultado.put("autoridad", str(lic.get("autoridad")));

        resultado.put("vin", str(tec.get("vin")));
        resultado.put("motor", str(tec.get("motor")));
        resultado.put("chasis", str(tec.get("chasis")));

        resultado.put("categoria", deducirCategoria(clase, cc));
        resultado.put("propietario", propietario);
        resultado.put("raw_api", safeData);

        return resultado;
    }

    public String deducirCategoria(String clase, int cc) {
        String c = clase != null ? clase.toUpperCase(Locale.ROOT) : "";
        boolean esMoto = c.contains("MOTO");

        if (esMoto) {
            if (cc < 100) return "moto_menos_100";
            if (cc <= 200) return "moto_100_200";
            return "moto_mas_200";
        }
        if (c.contains("TAXI") || c.contains("MICROBUS")) {
            return "taxi_microbus";
        }
        if (c.contains("CAMIONETA") || cc > 1500) {
            return "camioneta";
        }
        return "carro_familiar";
    }

    // --- Helpers internos ---

    private static String str(Object val) {
        return val == null ? "" : String.valueOf(val);
    }

    private static int toInt(Object val) {
        if (val == null) return 0;
        if (val instanceof Number) return ((Number) val).intValue();
        try {
            return Integer.parseInt(String.valueOf(val).replaceAll("[^\\d-]", ""));
        } catch (NumberFormatException e) {
            return 0;
        }
    }

    @SuppressWarnings("unchecked")
    private static Map<String, Object> asMap(Object val) {
        return (val instanceof Map) ? (Map<String, Object>) val : Collections.emptyMap();
    }

    @SuppressWarnings("unchecked")
    private static List> asListOfMaps(Object val) {
        if (!(val instanceof List)) return Collections.emptyList();
        List> out = new ArrayList<>();
        for (Object item : (List<?>) val) {
            if (item instanceof Map) {
                out.add((Map<String, Object>) item);
            }
        }
        return out;
    }

    /**
     * Parser JSON ligero para objetos/arrays/primitivos sin dependencias externas.
     * Si usas Jackson o Gson en tu proyecto, puedes reemplazar este método por tu ObjectMapper/Gson.
     */
    private static Map<String, Object> parseJsonObjectSafe(String raw) {
        if (raw == null) return null;
        String trimmed = raw.trim();
        if (!trimmed.startsWith("{")) return null;
        try {
            Object parsed = new SimpleJsonParser(trimmed).parseValue();
            return (parsed instanceof Map) ? (Map<String, Object>) parsed : null;
        } catch (Exception e) {
            return null;
        }
    }

    private static final class SimpleJsonParser {
        private final String s;
        private int pos = 0;

        SimpleJsonParser(String s) {
            this.s = s;
        }

        Object parseValue() {
            skipWhitespace();
            if (pos >= s.length()) throw new IllegalArgumentException("EOF");
            char c = s.charAt(pos);
            if (c == '{') return parseObject();
            if (c == '[') return parseArray();
            if (c == '"') return parseString();
            if (c == 't') { expect("true"); return Boolean.TRUE; }
            if (c == 'f') { expect("false"); return Boolean.FALSE; }
            if (c == 'n') { expect("null"); return null; }
            return parseNumber();
        }

        private Map<String, Object> parseObject() {
            expectChar('{');
            Map<String, Object> map = new LinkedHashMap<>();
            skipWhitespace();
            if (peek('}')) {
                pos++;
                return map;
            }
            while (true) {
                skipWhitespace();
                String key = parseString();
                skipWhitespace();
                expectChar(':');
                Object val = parseValue();
                map.put(key, val);
                skipWhitespace();
                if (peek('}')) {
                    pos++;
                    break;
                }
                expectChar(',');
            }
            return map;
        }

        private List<Object> parseArray() {
            expectChar('[');
            List<Object> list = new ArrayList<>();
            skipWhitespace();
            if (peek(']')) {
                pos++;
                return list;
            }
            while (true) {
                list.add(parseValue());
                skipWhitespace();
                if (peek(']')) {
                    pos++;
                    break;
                }
                expectChar(',');
            }
            return list;
        }

        private String parseString() {
            expectChar('"');
            StringBuilder sb = new StringBuilder();
            while (pos < s.length()) {
                char c = s.charAt(pos++);
                if (c == '"') return sb.toString();
                if (c == '\\' && pos < s.length()) {
                    char esc = s.charAt(pos++);
                    switch (esc) {
                        case '"': sb.append('"'); break;
                        case '\\': sb.append('\\'); break;
                        case '/': sb.append('/'); break;
                        case 'b': sb.append('\b'); break;
                        case 'f': sb.append('\f'); break;
                        case 'n': sb.append('\n'); break;
                        case 'r': sb.append('\r'); break;
                        case 't': sb.append('\t'); break;
                        case 'u':
                            if (pos + 4 <= s.length()) {
                                int code = Integer.parseInt(s.substring(pos, pos + 4), 16);
                                sb.append((char) code);
                                pos += 4;
                            }
                            break;
                        default: sb.append(esc); break;
                    }
                } else {
                    sb.append(c);
                }
            }
            throw new IllegalArgumentException("Cadena sin cerrar");
        }

        private Number parseNumber() {
            int start = pos;
            Matcher m = Pattern.compile("^-?\\d+(?:\\.\\d+)?(?:[eE][+-]?\\d+)?").matcher(s.substring(pos));
            if (!m.find()) throw new IllegalArgumentException("Número inválido");
            String numStr = m.group();
            pos += numStr.length();
            if (numStr.contains(".") || numStr.contains("e") || numStr.contains("E")) {
                return Double.parseDouble(numStr);
            }
            long l = Long.parseLong(numStr);
            if (l >= Integer.MIN_VALUE && l <= Integer.MAX_VALUE) {
                return (int) l;
            }
            return l;
        }

        private void skipWhitespace() {
            while (pos < s.length() && Character.isWhitespace(s.charAt(pos))) {
                pos++;
            }
        }

        private boolean peek(char expected) {
            return pos < s.length() && s.charAt(pos) == expected;
        }

        private void expectChar(char expected) {
            skipWhitespace();
            if (pos >= s.length() || s.charAt(pos) != expected) {
                throw new IllegalArgumentException("Se esperaba '" + expected + "'");
            }
            pos++;
        }

        private void expect(String literal) {
            if (!s.startsWith(literal, pos)) {
                throw new IllegalArgumentException("Se esperaba " + literal);
            }
            pos += literal.length();
        }
    }
}
