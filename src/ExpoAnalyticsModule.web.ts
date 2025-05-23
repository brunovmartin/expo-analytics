import { registerWebModule, NativeModule } from 'expo';

import { ExpoAnalyticsModuleEvents } from './ExpoAnalytics.types';

class ExpoAnalyticsModule extends NativeModule<ExpoAnalyticsModuleEvents> {
  PI = Math.PI;
  async setValueAsync(value: string): Promise<void> {
    this.emit('onChange', { value });
  }
  hello() {
    return 'Hello world! 👋';
  }
}

export default registerWebModule(ExpoAnalyticsModule, 'ExpoAnalyticsModule');
