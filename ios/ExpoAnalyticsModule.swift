import ExpoModulesCore
import UIKit
import Compression
import UniformTypeIdentifiers

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
  
  // Sistema de throttling para performance
  private var lastCaptureTime: CFTimeInterval = 0
  private var targetFrameInterval: CFTimeInterval = 1.0/10.0 // Padrão: 10 FPS máximo
  private var isCapturing: Bool = false
  private var captureQueue: DispatchQueue = DispatchQueue(label: "screenshot.capture", qos: .utility)

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
      self.framerate = min(max(serverConfig["framerate"] as? Int ?? 10, 1), 15) // Limite: 1-15 FPS
      if let size = serverConfig["screenSize"] as? Int {
        // Manter proporção de 1:2 (largura:altura)
        self.screenSize = CGSize(width: size, height: size * 2)
      }
      
      // Aplicar overrides das opções se fornecidas
      if let config = options {
        if let fps = config["framerate"] as? Int { 
          self.framerate = min(max(fps, 1), 15) // Limite: 1-15 FPS
        }
        if let size = config["screenSize"] as? Int {
          self.screenSize = CGSize(width: size, height: size * 2)
        }
      }
      
      // Calcular intervalo otimizado
      self.targetFrameInterval = 1.0 / Double(self.framerate)

      NSLog("🔧 [ExpoAnalytics] Configurações aplicadas:")
      NSLog("   Record Screen: \(self.recordScreenEnabled)")
      NSLog("   Framerate: \(self.framerate) fps (intervalo: \(String(format: "%.3f", self.targetFrameInterval))s)")
      NSLog("   Screen Size: \(Int(self.screenSize.width))x\(Int(self.screenSize.height))")

      // Enviar informações do usuário
      self.fetchGeoInfo {
        self.sendUserInfoPayload()
      }

      // Iniciar captura apenas se record screen estiver ativo
      if self.recordScreenEnabled {
        DispatchQueue.main.async {
          self.startOptimizedCapture()
        }
      } else {
        NSLog("⚠️ [ExpoAnalytics] Record Screen desabilitado - captura não iniciada")
      }
    }

    AsyncFunction("stop") { () in
      DispatchQueue.main.async {
        self.stopCapture()
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

  private func startOptimizedCapture() {
    stopCapture() // Garantir que não há captura anterior rodando
    
    self.isCapturing = true
    self.lastCaptureTime = 0
    
    // Usar CADisplayLink com framerate baixo para economia de energia
    self.displayLink = CADisplayLink(target: self, selector: #selector(self.optimizedCaptureFrame))
    self.displayLink?.preferredFramesPerSecond = 60 // DisplayLink a 60fps, mas filtramos internamente
    self.displayLink?.add(to: .main, forMode: .common)
    
    NSLog("🎬 [ExpoAnalytics] Captura otimizada iniciada - \(self.framerate) fps efetivo")
  }
  
  private func stopCapture() {
    self.isCapturing = false
    self.displayLink?.invalidate()
    self.displayLink = nil
    NSLog("⏹️ [ExpoAnalytics] Captura de tela parada")
  }
  
  @objc
  private func optimizedCaptureFrame() {
    guard self.isCapturing else { return }
    
    let currentTime = CACurrentMediaTime()
    
    // Throttling: só capturar se passou o tempo necessário
    if currentTime - self.lastCaptureTime < self.targetFrameInterval {
      return
    }
    
    self.lastCaptureTime = currentTime
    
    // Capturar em background thread para não bloquear a UI
    captureQueue.async { [weak self] in
      self?.performScreenCapture()
    }
  }
  
  private func performScreenCapture() {
    guard let windowScene = UIApplication.shared.connectedScenes.first as? UIWindowScene,
          let window = windowScene.windows.first else { return }

    DispatchQueue.main.sync {
      let originalBounds = window.bounds
      
      // Calcular escala para reduzir a resolução desde o início
      let targetSize = self.screenSize
      let scaleX = targetSize.width / originalBounds.width
      let scaleY = targetSize.height / originalBounds.height
      
      // Criar contexto com o tamanho alvo já reduzido
      UIGraphicsBeginImageContextWithOptions(targetSize, false, 1.0) // Scale fixo 1.0
      
      guard let context = UIGraphicsGetCurrentContext() else {
        NSLog("❌ [ExpoAnalytics] Erro ao criar contexto gráfico")
        return
      }
      
      // Aplicar transformação para redimensionar durante a captura
      context.scaleBy(x: scaleX, y: scaleY)
      window.drawHierarchy(in: originalBounds, afterScreenUpdates: false)
      
      let capturedImage = UIGraphicsGetImageFromCurrentImageContext()
      UIGraphicsEndImageContext()

      guard let image = capturedImage else { 
        NSLog("❌ [ExpoAnalytics] Erro ao capturar screenshot")
        return 
      }
      
      // Processar imagem em background
      captureQueue.async { [weak self] in
        self?.processAndSaveImage(image)
      }
    }
  }
  
  private func processAndSaveImage(_ image: UIImage) {
    // Comprimir com qualidade ajustada baseada no framerate
    let quality: CGFloat = self.framerate <= 5 ? 0.8 : self.framerate <= 10 ? 0.7 : 0.6
    guard let compressedData = image.jpegData(compressionQuality: quality) else {
      NSLog("❌ [ExpoAnalytics] Erro ao comprimir imagem")
      return
    }
    
    // Verificar tamanho final da imagem
    let finalSize = compressedData.count
    let timestamp = Int(Date().timeIntervalSince1970 * 1000)
    
    // Log apenas ocasionalmente para não sobrecarregar
    if frameCount % 10 == 0 {
      NSLog("📸 [ExpoAnalytics] Screenshot \(frameCount): \(Int(screenSize.width))×\(Int(screenSize.height)), \(finalSize/1024)KB, Q:\(Int(quality*100))%")
    }
    
    // Salvar arquivo temporário
    let filename = screenshotsFolder.appendingPathComponent("frame_\(timestamp).jpg")
    do {
      try compressedData.write(to: filename)
      frameCount += 1
      
      // Enviar buffer ajustado por framerate - máximo 8 segundos de captura
      let maxFrames = min(self.framerate * 8, 120) // Limite máximo de 120 frames
      if frameCount >= maxFrames {
        NSLog("📤 [ExpoAnalytics] Enviando buffer com \(frameCount) frames")
        DispatchQueue.main.async { [weak self] in
          self?.sendScreenshotsBuffer()
          self?.frameCount = 0
        }
      }
    } catch {
      NSLog("❌ [ExpoAnalytics] Erro ao salvar frame: \(error)")
    }
  }

  private func sendScreenshotsBuffer() {
    NSLog("🔄 [ExpoAnalytics] Iniciando processo de upload com ZIP...")
    
    let metadata: [String: Any] = [
      "userId": self.userId,
      "userData": self.userData,
      "geo": self.geoData,
      "timestamp": Date().timeIntervalSince1970,
      "format": "zip" // Indicar que está enviando ZIP
    ]

    guard let url = URL(string: apiHost + "/upload-zip") else { 
      NSLog("❌ [ExpoAnalytics] URL inválida: \(apiHost)")
      return 
    }
    
    // Criar arquivo ZIP com as imagens
    guard let zipData = createZipFromScreenshots() else {
      NSLog("❌ [ExpoAnalytics] Falha ao criar arquivo ZIP")
      return
    }
    
    NSLog("📦 [ExpoAnalytics] ZIP criado: \(zipData.count/1024/1024)MB")
    
    // Criar requisição multipart
    let boundary = "Boundary-\(UUID().uuidString)"
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")
    
    var body = Data()
    
    // Adicionar metadados
    let metadataJson = try! JSONSerialization.data(withJSONObject: metadata)
    body.append("--\(boundary)\r\n".data(using: .utf8)!)
    body.append("Content-Disposition: form-data; name=\"metadata\"\r\n".data(using: .utf8)!)
    body.append("Content-Type: application/json\r\n\r\n".data(using: .utf8)!)
    body.append(metadataJson)
    body.append("\r\n".data(using: .utf8)!)
    
    // Adicionar arquivo ZIP
    body.append("--\(boundary)\r\n".data(using: .utf8)!)
    body.append("Content-Disposition: form-data; name=\"screenshots\"; filename=\"screenshots_\(Int(Date().timeIntervalSince1970)).zip\"\r\n".data(using: .utf8)!)
    body.append("Content-Type: application/zip\r\n\r\n".data(using: .utf8)!)
    body.append(zipData)
    body.append("\r\n".data(using: .utf8)!)
    body.append("--\(boundary)--\r\n".data(using: .utf8)!)
    
    request.httpBody = body
    
    let startTime = Date()
    URLSession.shared.dataTask(with: request) { data, response, error in
      let duration = Date().timeIntervalSince(startTime)
      
      if let error = error {
        NSLog("❌ [ExpoAnalytics] Erro no upload: \(error.localizedDescription)")
      } else if let httpResponse = response as? HTTPURLResponse {
        let statusCode = httpResponse.statusCode
        let responseSize = data?.count ?? 0
        
        NSLog("✅ [ExpoAnalytics] Upload ZIP concluído em \(String(format: "%.1f", duration))s")
        NSLog("📡 [ExpoAnalytics] Status: \(statusCode), Resposta: \(responseSize) bytes")
        
        if statusCode == 200 {
          NSLog("🎉 [ExpoAnalytics] ZIP enviado com sucesso!")
          
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
  }
  
  private func createZipFromScreenshots() -> Data? {
    let fileManager = FileManager.default
    
    guard let files = try? fileManager.contentsOfDirectory(at: screenshotsFolder, includingPropertiesForKeys: nil) else {
      NSLog("❌ [ExpoAnalytics] Erro ao listar arquivos")
      return nil
    }
    
    let jpgFiles = files.filter { $0.pathExtension == "jpg" }.sorted { $0.lastPathComponent < $1.lastPathComponent }
    
    guard !jpgFiles.isEmpty else {
      NSLog("⚠️ [ExpoAnalytics] Nenhuma imagem encontrada para criar ZIP")
      return nil
    }
    
    NSLog("📸 [ExpoAnalytics] Criando ZIP com \(jpgFiles.count) imagens...")
    
    // Criar ZIP usando NSFileManager
    let tempZipURL = FileManager.default.temporaryDirectory.appendingPathComponent("screenshots_\(Int(Date().timeIntervalSince1970)).zip")
    
    var error: NSError?
    let coordinator = NSFileCoordinator()
    
    coordinator.coordinate(writingItemAt: tempZipURL, options: [], error: &error) { (url) in
      do {
        // Usar NSFileManager para criar o ZIP
        var filePaths: [String] = []
        var fileNames: [String] = []
        
        for (index, file) in jpgFiles.enumerated() {
          filePaths.append(file.path)
          fileNames.append("frame_\(String(format: "%03d", index)).jpg")
        }
        
        // Criar o ZIP manualmente já que não temos API nativa
        if let zipData = createZipData(filePaths: filePaths, fileNames: fileNames) {
          try zipData.write(to: url)
        }
      } catch {
        NSLog("❌ [ExpoAnalytics] Erro ao criar ZIP: \(error)")
      }
    }
    
    if let error = error {
      NSLog("❌ [ExpoAnalytics] Erro de coordenação: \(error)")
      return nil
    }
    
    guard let zipData = try? Data(contentsOf: tempZipURL) else {
      NSLog("❌ [ExpoAnalytics] Erro ao ler ZIP criado")
      return nil
    }
    
    // Limpar arquivo temporário
    try? fileManager.removeItem(at: tempZipURL)
    
    return zipData
  }
  
  private func createZipData(filePaths: [String], fileNames: [String]) -> Data? {
    // Por simplicidade, vamos usar uma abordagem de concatenação simples
    // Em produção, seria melhor usar uma biblioteca de ZIP apropriada
    
    guard filePaths.count == fileNames.count else { return nil }
    
    var zipContent = Data()
    
    // Header simples (isso é uma simplificação - um ZIP real tem estrutura complexa)
    for (index, filePath) in filePaths.enumerated() {
      guard let imageData = try? Data(contentsOf: URL(fileURLWithPath: filePath)) else { continue }
      
      // Para simplificar, vamos retornar os dados das imagens comprimidos com gzip
      if let compressedImage = imageData.gzipped() {
        zipContent.append(compressedImage)
      } else {
        zipContent.append(imageData)
      }
    }
    
    return zipContent.count > 0 ? zipContent : nil
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
