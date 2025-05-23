import * as React from 'react';

import { ExpoAnalyticsViewProps } from './ExpoAnalytics.types';

export default function ExpoAnalyticsView(props: ExpoAnalyticsViewProps) {
  return (
    <div>
      <iframe
        style={{ flex: 1 }}
        src={props.url}
        onLoad={() => props.onLoad({ nativeEvent: { url: props.url } })}
      />
    </div>
  );
}
