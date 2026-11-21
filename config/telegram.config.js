
export function getTelegramConfig() {
  const botToken = String(process.env.TELEGRAM_BOT_TOKEN ?? '').trim();
  const chatId = String(process.env.TELEGRAM_CHAT_ID ?? '').trim();
  const activo = String(process.env.TELEGRAM_ACTIVO ?? '1').trim() !== '0';

  return {
    activo,
    botToken,
    chatId,
  };
}

export default getTelegramConfig;
