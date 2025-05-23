package expo.modules.analytics

import expo.modules.kotlin.modules.Module
import expo.modules.kotlin.modules.ModuleDefinition
import java.net.URL

class ExpoAnalyticsModule : Module() {
  override fun definition() = ModuleDefinition {
    Name("ExpoAnalytics")
    Function("getTheme") {
      return@Function "system"
    }
  }
}
