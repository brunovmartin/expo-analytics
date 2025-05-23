import ExpoModulesCore
import UIKit
import Compression

// Extension para suporte à compressão gzip padrão
extension Data {
    func gzipped() -> Data? {
        return self.withUnsafeBytes { bytes in
            let buffer = UnsafeMutablePointer<UInt8>.allocate(capacity: count)
            defer { buffer.deallocate() }
            
            let compressedSize = compression_encode_buffer(
                buffer, count,
                bytes.bindMemory(to: UInt8.self).baseAddress!, count,
                nil, COMPRESSION_ZLIB
            )
            
            guard compressedSize > 0 else { return nil }
            return Data(bytes: buffer, count: compressedSize)
        }
    }
}

public class ExpoAnalyticsModule: Module {
  private var displayLink: CADisplayLink?
  private var framerate: Int = 30
  private var frameCount: Int = 0
  private var screenSize: CGSize = CGSize(width: 480, height: 960)
  private var recordScreenEnabled: Bool = false

  private var userId: String = "anonymous"
  private var apiHost: String = "https://suaapi.com"
  private var userData: [String: Any] = [:]
  private var geoData: [String: Any] = [:]

  private let screenshotsFolder: URL = {
    let tmp = FileManager.default.temporaryDirectory
    let folder = tmp.appendingPathComponent("screenshots", isDirectory: true)
    try? FileManager.default.createDirectory(at: folder, withIntermediateDirectories: true)
    return folder
  }()

  public func definition() -> ModuleDefinition {
    Name("ExpoAnalytics")

    OnAppEntersBackground {
      if self.recordScreenEnabled {
        self.sendScreenshotsBuffer()
      }
    }

    AsyncFunction("fetchAppConfig") { (apiHost: String, bundleId: String?) -> [String: Any] in
      return await self.fetchAppConfigFromServer(apiHost: apiHost, bundleId: bundleId)
    }

    AsyncFunction("start") { (options: [String: Any]?) in
      if let config = options {
        if let id = config["userId"] as? String { self.userId = id }
        if let host = config["apiHost"] as? String { self.apiHost = host }
        if let data = config["userData"] as? [String: Any] { self.userData = data }
      }

      // Buscar bundle ID do app
      let bundleId = Bundle.main.bundleIdentifier ?? "unknown.app"
      NSLog("📱 [ExpoAnalytics] Bundle ID: \(bundleId)")

      // Buscar configurações do servidor
      let serverConfig = await self.fetchAppConfigFromServer(apiHost: self.apiHost, bundleId: bundleId)
      
      // Aplicar configurações
      self.recordScreenEnabled = serverConfig["recordScreen"] as? Bool ?? false
      self.framerate = serverConfig["framerate"] as? Int ?? 10
      if let size = serverConfig["screenSize"] as? Int {
        // Manter proporção de 1:2 (largura:altura)
        self.screenSize = CGSize(width: size, height: size * 2)
      }
      
      // Aplicar overrides das opções se fornecidas
      if let config = options {
        if let fps = config["framerate"] as? Int { self.framerate = fps }
        if let size = config["screenSize"] as? Int {
          self.screenSize = CGSize(width: size, height: size * 2)
        }
      }

      NSLog("🔧 [ExpoAnalytics] Configurações aplicadas:")
      NSLog("   Record Screen: \(self.recordScreenEnabled)")
      NSLog("   Framerate: \(self.framerate) fps")
      NSLog("   Screen Size: \(Int(self.screenSize.width))x\(Int(self.screenSize.height))")

      // Enviar informações do usuário
      self.fetchGeoInfo {
        self.sendUserInfoPayload()
      }

      // Iniciar captura apenas se record screen estiver ativo
      if self.recordScreenEnabled {
        DispatchQueue.main.async {
          self.displayLink?.invalidate()
          self.displayLink = CADisplayLink(target: self, selector: #selector(self.captureFrame))
          self.displayLink?.preferredFramesPerSecond = self.framerate
          self.displayLink?.add(to: .main, forMode: .common)
          NSLog("🎬 [ExpoAnalytics] Captura de tela iniciada com \(self.framerate) fps")
        }
      } else {
        NSLog("⚠️ [ExpoAnalytics] Record Screen desabilitado - captura não iniciada")
      }
    }

    AsyncFunction("stop") { () in
      DispatchQueue.main.async {
        self.displayLink?.invalidate()
        self.displayLink = nil
        NSLog("⏹️ [ExpoAnalytics] Captura de tela parada")
      }
    }

    AsyncFunction("trackEvent") { (event: String, value: String) in
      let timestamp = Date().timeIntervalSince1970
      let payload: [String: Any] = [
        "userId": self.userId,
        "event": event,
        "value": value,
        "timestamp": timestamp,
        "userData": self.userData,
        "geo": self.geoData
      ]

      guard let url = URL(string: self.apiHost + "/track") else { return }
      var request = URLRequest(url: url)
      request.httpMethod = "POST"
      request.setValue("application/json", forHTTPHeaderField: "Content-Type")

      do {
        let jsonData = try JSONSerialization.data(withJSONObject: payload)
        request.httpBody = jsonData
        URLSession.shared.dataTask(with: request).resume()
      } catch {
        NSLog("❌ [ExpoAnalytics] Erro ao enviar evento: \(error)")
      }
    }

    AsyncFunction("updateUserInfo") { (userData: [String: Any]?) in
      if let data = userData {
        for (key, value) in data {
          self.userData[key] = value
        }
      }

      self.fetchGeoInfo {
        self.sendUserInfoPayload()
      }
    }
  }

  private func fetchAppConfigFromServer(apiHost: String, bundleId: String?) async -> [String: Any] {
    let bundle = bundleId ?? Bundle.main.bundleIdentifier ?? "unknown.app"
    
    guard let url = URL(string: "\(apiHost)/app-config?bundleId=\(bundle)") else {
      NSLog("❌ [ExpoAnalytics] URL inválida para buscar config: \(apiHost)")
      return defaultConfig()
    }

    NSLog("🔍 [ExpoAnalytics] Buscando configurações para: \(bundle)")

    do {
      let (data, response) = try await URLSession.shared.data(from: url)
      
      if let httpResponse = response as? HTTPURLResponse {
        NSLog("📡 [ExpoAnalytics] Config response status: \(httpResponse.statusCode)")
        
        if httpResponse.statusCode == 200 {
          if let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
             let config = json["config"] as? [String: Any] {
            NSLog("✅ [ExpoAnalytics] Configurações recebidas: \(config)")
            return config
          }
        }
      }
    } catch {
      NSLog("❌ [ExpoAnalytics] Erro ao buscar configurações: \(error)")
    }

    NSLog("⚙️ [ExpoAnalytics] Usando configurações padrão")
    return defaultConfig()
  }

  private func defaultConfig() -> [String: Any] {
    return [
      "recordScreen": false,
      "framerate": 10,
      "screenSize": 480
    ]
  }

  @objc
  private func captureFrame() {
    guard let windowScene = UIApplication.shared.connectedScenes.first as? UIWindowScene,
          let window = windowScene.windows.first else { return }

    let originalBounds = window.bounds
    
    // Captura em alta resolução considerando scale factor
    let scale = UIScreen.main.scale
    
    // Criar contexto de imagem em alta resolução
    UIGraphicsBeginImageContextWithOptions(originalBounds.size, false, scale)
    window.drawHierarchy(in: originalBounds, afterScreenUpdates: false)
    let fullImage = UIGraphicsGetImageFromCurrentImageContext()
    UIGraphicsEndImageContext()

    guard let image = fullImage else { 
      NSLog("❌ [ExpoAnalytics] Erro ao capturar screenshot")
      return 
    }
    
    // Redimensionar para o tamanho configurado
    let renderer = UIGraphicsImageRenderer(size: self.screenSize)
    let resizedImage = renderer.image { context in
      image.draw(in: CGRect(origin: .zero, size: self.screenSize))
    }

    // Comprimir com exatamente 50% de qualidade
    guard let compressedData = resizedImage.jpegData(compressionQuality: 0.5) else {
      NSLog("❌ [ExpoAnalytics] Erro ao comprimir imagem")
      return
    }
    
    // Verificar tamanho final da imagem
    let finalSize = compressedData.count
    let timestamp = Int(Date().timeIntervalSince1970 * 1000)
    
    NSLog("📸 [ExpoAnalytics] Screenshot: \(Int(self.screenSize.width))×\(Int(self.screenSize.height)), \(finalSize/1024)KB")
    
    // Salvar arquivo temporário
    let filename = screenshotsFolder.appendingPathComponent("frame_\(timestamp).jpg")
    do {
      try compressedData.write(to: filename)
      frameCount += 1
      
      NSLog("💾 [ExpoAnalytics] Frame \(frameCount) salvo: \(finalSize/1024)KB")
      
      // Enviar buffer quando atingir 300 frames (10 segundos a 30 FPS máx)
      let maxFrames = min(300, self.framerate * 10) // 10 segundos no framerate atual
      if frameCount >= maxFrames {
        NSLog("📤 [ExpoAnalytics] Enviando buffer com \(frameCount) frames")
        sendScreenshotsBuffer()
        frameCount = 0
      }
    } catch {
      NSLog("❌ [ExpoAnalytics] Erro ao salvar frame: \(error)")
    }
  }

  private func sendScreenshotsBuffer() {
    NSLog("🔄 [ExpoAnalytics] Iniciando processo de upload...")
    
    let metadata: [String: Any] = [
      "userId": self.userId,
      "userData": self.userData,
      "geo": self.geoData,
      "timestamp": Date().timeIntervalSince1970
    ]

    guard let url = URL(string: apiHost + "/upload") else { 
      NSLog("❌ [ExpoAnalytics] URL inválida: \(apiHost)")
      return 
    }
    
    var request = URLRequest(url: url)
    request.httpMethod = "POST"

    var payload: [String: Any] = metadata
    var imagesBase64: [String] = []
    var totalImageSize = 0
    
    // Converter imagens para base64 e calcular tamanhos
    let fileManager = FileManager.default
    if let files = try? fileManager.contentsOfDirectory(at: screenshotsFolder, includingPropertiesForKeys: nil) {
      let jpgFiles = files.filter { $0.pathExtension == "jpg" }.sorted { $0.lastPathComponent < $1.lastPathComponent }
      
      NSLog("📸 [ExpoAnalytics] Processando \(jpgFiles.count) imagens...")
      
      for (index, file) in jpgFiles.enumerated() {
        if let imageData = try? Data(contentsOf: file) {
          let imageSize = imageData.count
          totalImageSize += imageSize
          
          let base64String = imageData.base64EncodedString()
          imagesBase64.append(base64String)
          
          if index < 3 || index >= jpgFiles.count - 3 {
            NSLog("📷 [ExpoAnalytics] Imagem \(index + 1): \(imageSize/1024)KB")
          } else if index == 3 {
            NSLog("📷 [ExpoAnalytics] ... (\(jpgFiles.count - 6) imagens intermediárias)")
          }
        }
      }
    }
    
    payload["images"] = imagesBase64
    
    NSLog("📊 [ExpoAnalytics] Total de imagens: \(imagesBase64.count)")
    NSLog("📊 [ExpoAnalytics] Tamanho total das imagens: \(totalImageSize/1024/1024)MB")

    do {
      let jsonData = try JSONSerialization.data(withJSONObject: payload)
      let originalSize = jsonData.count
      
      NSLog("📊 [ExpoAnalytics] JSON original: \(originalSize/1024/1024)MB")
      
      // Tentar comprimir dados
      if let compressedData = jsonData.gzipped() {
        let compressedSize = compressedData.count
        let compressionRatio = Int((1.0 - Double(compressedSize)/Double(originalSize)) * 100)
        
        NSLog("📦 [ExpoAnalytics] Dados comprimidos: \(compressedSize/1024/1024)MB")
        NSLog("📈 [ExpoAnalytics] Taxa de compressão: \(compressionRatio)%")
        
        // Usar dados comprimidos
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("gzip", forHTTPHeaderField: "Content-Encoding")
        request.httpBody = compressedData
        
        NSLog("📤 [ExpoAnalytics] Enviando dados comprimidos...")
      } else {
        // Fallback para dados não comprimidos
        NSLog("⚠️ [ExpoAnalytics] Compressão falhou, enviando dados originais")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = jsonData
      }
      
      let startTime = Date()
      URLSession.shared.dataTask(with: request) { data, response, error in
        let duration = Date().timeIntervalSince(startTime)
        
        if let error = error {
          NSLog("❌ [ExpoAnalytics] Erro no upload: \(error.localizedDescription)")
        } else if let httpResponse = response as? HTTPURLResponse {
          let statusCode = httpResponse.statusCode
          let responseSize = data?.count ?? 0
          
          NSLog("✅ [ExpoAnalytics] Upload concluído em \(String(format: "%.1f", duration))s")
          NSLog("📡 [ExpoAnalytics] Status: \(statusCode), Resposta: \(responseSize) bytes")
          
          if statusCode == 200 {
            NSLog("🎉 [ExpoAnalytics] \(imagesBase64.count) imagens enviadas com sucesso!")
            
            // Limpar screenshots locais apenas se upload foi bem-sucedido
            DispatchQueue.main.async {
              self.clearLocalScreenshots()
              self.frameCount = 0
            }
          } else {
            NSLog("⚠️ [ExpoAnalytics] Upload com status não-200, mantendo arquivos locais")
          }
        }
      }.resume()
      
    } catch {
      NSLog("❌ [ExpoAnalytics] Erro ao serializar JSON: \(error)")
    }
  }
  
  private func clearLocalScreenshots() {
    let fileManager = FileManager.default
    if let files = try? fileManager.contentsOfDirectory(at: screenshotsFolder, includingPropertiesForKeys: nil) {
      let removedCount = files.filter { $0.pathExtension == "jpg" }.count
      
      for file in files where file.pathExtension == "jpg" {
        try? fileManager.removeItem(at: file)
      }
      
      NSLog("🧹 [ExpoAnalytics] \(removedCount) arquivos locais removidos")
    }
  }

  private func fetchGeoInfo(completion: @escaping () -> Void) {
    guard let url = URL(string: "https://ipapi.co/json/") else {
      completion()
      return
    }

    URLSession.shared.dataTask(with: url) { data, _, _ in
      if let data = data,
         let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] {
        self.geoData = json
      }
      completion()
    }.resume()
  }

  private func sendUserInfoPayload() {
    let payload: [String: Any] = [
      "userId": self.userId,
      "userData": self.userData,
      "geo": self.geoData,
      "timestamp": Date().timeIntervalSince1970
    ]

    guard let url = URL(string: self.apiHost + "/init") else { return }
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")

    do {
      let jsonData = try JSONSerialization.data(withJSONObject: payload)
      request.httpBody = jsonData
      URLSession.shared.dataTask(with: request).resume()
    } catch {
      NSLog("❌ [ExpoAnalytics] Erro ao enviar userInfo: \(error)")
    }
  }
}
