// Reexport the native module. On web, it will be resolved to ExpoAnalyticsModule.web.ts
// and on native platforms to ExpoAnalyticsModule.ts
export { default } from './ExpoAnalyticsModule';
export { default as ExpoAnalyticsView } from './ExpoAnalyticsView';
export * from  './ExpoAnalytics.types';
