import { NativeModule, requireNativeModule } from 'expo';
import { ExpoAnalyticsModuleEvents } from './ExpoAnalytics.types';

export interface StartOptions {
  apiHost?: string;
  userId?: string;
  framerate?: number;
  userData?: Record<string, any>;
}

declare class ExpoAnalyticsModule extends NativeModule<ExpoAnalyticsModuleEvents> {
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
