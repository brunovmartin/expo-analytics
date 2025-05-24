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
  fetchAppConfig(apiHost?: string, bundleId?: string): Promise<AppConfig>;
  
  /**
   * Inicializa o sistema e cadastra o usuário automaticamente
   */
  init(options?: StartOptions): Promise<void>;
  
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
  
  /**
   * Captura screenshot manual e envia para o servidor
   */
  takeScreenshot(width?: number, height?: number, compression?: number): Promise<{
    success: boolean;
    message?: string;
    width?: number;
    height?: number;
    size?: number;
    error?: string;
  }>;
}

// This call loads the native module object from the JSI.
export default requireNativeModule<ExpoAnalyticsModule>('ExpoAnalytics');
