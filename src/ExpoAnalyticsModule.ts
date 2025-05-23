import { NativeModule, requireNativeModule } from 'expo';
import { ExpoAnalyticsModuleEvents } from './ExpoAnalytics.types';

export interface StartOptions {
  apiHost?: string;
  userId?: string;
  framerate?: number;
  screenSize?: number;
  userData?: Record<string, any>;
}

export interface AppConfig {
  recordScreen: boolean;
  framerate: number;
  screenSize: number;
}

declare class ExpoAnalyticsModule extends NativeModule<ExpoAnalyticsModuleEvents> {
  /**
   * Busca configurações do app no servidor pelo bundle ID
   */
  fetchAppConfig(apiHost: string, bundleId?: string): Promise<AppConfig>;
  
  /**
   * Inicia a captura de screenshots e análise
   */
  start(options?: StartOptions): Promise<void>;
  
  /**
   * Para a captura de screenshots
   */
  stop(): Promise<void>;
  
  /**
   * Rastreia um evento personalizado
   */
  trackEvent(event: string, value: string): Promise<void>;
  
  /**
   * Atualiza as informações do usuário
   */
  updateUserInfo(userData?: Record<string, any>): Promise<void>;
}

// This call loads the native module object from the JSI.
export default requireNativeModule<ExpoAnalyticsModule>('ExpoAnalytics');
