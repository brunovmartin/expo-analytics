import type { StyleProp, ViewStyle } from 'react-native';

export type UserData = Record<string, any>;

export type GeoData = {
  ip?: string;
  city?: string;
  region?: string;
  country?: string;
  country_name?: string;
  latitude?: number;
  longitude?: number;
  [key: string]: any;
};

export type AnalyticsEventPayload = {
  event: string;
  value: string;
  timestamp: number;
  userId: string;
  userData: UserData;
  geo: GeoData;
};

export type ExpoAnalyticsModuleEvents = {
  onScreenshotCaptured?: (params: ScreenshotCapturedPayload) => void;
  onAnalyticsError?: (params: AnalyticsErrorPayload) => void;
};

export type ScreenshotCapturedPayload = {
  frameCount: number;
  timestamp: number;
};

export type AnalyticsErrorPayload = {
  error: string;
  timestamp: number;
};

export type ExpoAnalyticsViewProps = {
  enabled?: boolean;
  onAnalyticsData?: (event: { nativeEvent: AnalyticsEventPayload }) => void;
  style?: StyleProp<ViewStyle>;
};
