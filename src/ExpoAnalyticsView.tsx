import { requireNativeView } from 'expo';
import * as React from 'react';

import { ExpoAnalyticsViewProps } from './ExpoAnalytics.types';

const NativeView: React.ComponentType<ExpoAnalyticsViewProps> =
  requireNativeView('ExpoAnalytics');

export default function ExpoAnalyticsView(props: ExpoAnalyticsViewProps) {
  return <NativeView {...props} />;
}
