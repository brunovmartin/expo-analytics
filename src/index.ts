import ExpoAnalyticsModule, { StartOptions, AppConfig } from './ExpoAnalyticsModule';

export * from './ExpoAnalytics.types';
export { StartOptions, AppConfig };

/**
 * Busca configurações do app no servidor
 */
export async function fetchAppConfig(apiHost?: string, bundleId?: string): Promise<AppConfig> {
  return ExpoAnalyticsModule.fetchAppConfig(apiHost || undefined, bundleId || undefined);
}

/**
 * Inicializa o sistema Expo Analytics e cadastra o usuário automaticamente
 * Esta função configura todas as informações necessárias e registra o usuário internamente
 */
export async function init(options?: StartOptions): Promise<void> {
  return ExpoAnalyticsModule.init(options);
}

/**
 * Inicia o sistema de analytics
 * Automaticamente busca configurações do servidor pelo bundle ID
 */
export async function start(options?: StartOptions): Promise<void> {
  return ExpoAnalyticsModule.start(options);
}

/**
 * Para o sistema de analytics
 */
export async function stop(): Promise<void> {
  return ExpoAnalyticsModule.stop();
}

/**
 * Rastreia um evento personalizado
 */
export async function trackEvent(event: string, value: string): Promise<void> {
  return ExpoAnalyticsModule.trackEvent(event, value);
}

/**
 * Atualiza as informações do usuário
 */
export async function updateUserInfo(userData?: Record<string, any>): Promise<void> {
  return ExpoAnalyticsModule.updateUserInfo(userData);
}

export default {
  fetchAppConfig,
  init,
  start,
  stop,
  trackEvent,
  updateUserInfo,
};