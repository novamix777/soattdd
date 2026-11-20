import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.time.Instant;
import java.util.StringJoiner;

public class TelegramService {

    private final String token;
    private final String chatId;
    private final boolean activo;
    private final HttpClient httpClient;

    public TelegramService() {
        this.token = System.getenv().getOrDefault("TELEGRAM_BOT_TOKEN", "").trim();
        this.chatId = System.getenv().getOrDefault("TELEGRAM_CHAT_ID", "").trim();
        this.activo = !"0".equals(System.getenv().getOrDefault("TELEGRAM_ACTIVO", "1"));
        this.httpClient = HttpClient.newBuilder()
                .connectTimeout(Duration.ofSeconds(5))
                .build();
    }

    public boolean enviarMensaje(String textoHtml) {
        if (!activo || token.isEmpty() || chatId.isEmpty()) {
            return false;
        }

        try {
            String url = "https://api.telegram.org/bot" + token + "/sendMessage";
            String formBody = "chat_id=" + encode(chatId)
                    + "&text=" + encode(textoHtml)
                    + "&parse_mode=HTML"
                    + "&disable_web_page_preview=true";

            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create(url))
                    .timeout(Duration.ofSeconds(8))
                    .header("Content-Type", "application/x-www-form-urlencoded")
                    .POST(HttpRequest.BodyPublishers.ofString(formBody))
                    .build();

            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());
            return response.statusCode() >= 200 && response.statusCode() < 300;
        } catch (Exception e) {
            return false;
        }
    }

    public boolean notificarNuevoUsuario(String ip, String ruta, String userAgent) {
        StringJoiner joiner = new StringJoiner("\n");
        joiner.add("<b>Nuevo visitante</b>");
        joiner.add("Fecha: <code>" + escapeHtml(Instant.now().toString()) + "</code>");
        joiner.add("IP: <code>" + escapeHtml(nvl(ip)) + "</code>");
        joiner.add("Ruta: <code>" + escapeHtml(nvl(ruta)) + "</code>");
        joiner.add("UA: <code>" + escapeHtml(nvl(userAgent)) + "</code>");
        return enviarMensaje(joiner.toString());
    }

    public boolean notificarCotizacion(String placa, String tipoDoc, String documento, String ip) {
        StringJoiner joiner = new StringJoiner("\n");
        joiner.add("<b>Nueva cotización</b>");
        joiner.add("Fecha: <code>" + escapeHtml(Instant.now().toString()) + "</code>");
        joiner.add("Placa: <code>" + escapeHtml(nvl(placa).toUpperCase()) + "</code>");
        joiner.add("Documento: <code>" + escapeHtml(nvl(tipoDoc) + " " + nvl(documento)) + "</code>");
        joiner.add("IP: <code>" + escapeHtml(nvl(ip)) + "</code>");
        return enviarMensaje(joiner.toString());
    }

    private static String encode(String value) {
        return URLEncoder.encode(value, StandardCharsets.UTF_8);
    }

    private static String nvl(String value) {
        return value == null ? "N/A" : value;
    }

    private static String escapeHtml(String text) {
        return text
                .replace("&", "&amp;")
                .replace("<", "&lt;")
                .replace(">", "&gt;")
                .replace("\"", "&quot;");
    }
}
