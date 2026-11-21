
export function getApiConfig() {
  const rawUrl = String(process.env.SOAT_API_URL ?? '').trim();
  const token = String(process.env.SOAT_API_TOKEN ?? '').trim();
  const parsedTimeout = Number(process.env.SOAT_API_TIMEOUT ?? 30);

  return {
    url: rawUrl.replace(/\/+$/, ''),
    token,
    timeout: Number.isFinite(parsedTimeout) && parsedTimeout > 0 ? Math.trunc(parsedTimeout) : 30,
  };
}

export default getApiConfig;
