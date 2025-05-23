import { NativeModule, requireNativeModule } from 'expo';

import { ExpoAnalyticsModuleEvents } from './ExpoAnalytics.types';

declare class ExpoAnalyticsModule extends NativeModule<ExpoAnalyticsModuleEvents> {
  PI: number;
  hello(): string;
  setValueAsync(value: string): Promise<void>;
}

// This call loads the native module object from the JSI.
export default requireNativeModule<ExpoAnalyticsModule>('ExpoAnalytics');
