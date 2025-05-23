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

// Estrutura para criar ZIP manual (formato simplificado mas funcional)
struct ZipLocalFileHeader {
    static let signature: UInt32 = 0x04034b50
    let versionToExtract: UInt16 = 20
    let generalPurposeFlag: UInt16 = 0
    let compressionMethod: UInt16 = 0 // No compression
    let lastModTime: UInt16 = 0
    let lastModDate: UInt16 = 0
    let crc32: UInt32
    let compressedSize: UInt32
    let uncompressedSize: UInt32
    let filenameLength: UInt16
    let extraFieldLength: UInt16 = 0
    
    func write(to data: inout Data) {
        withUnsafeBytes(of: ZipLocalFileHeader.signature.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: versionToExtract.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: generalPurposeFlag.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: compressionMethod.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: lastModTime.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: lastModDate.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: crc32.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: compressedSize.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: uncompressedSize.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: filenameLength.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: extraFieldLength.littleEndian) { data.append(contentsOf: $0) }
    }
}

struct ZipCentralDirectoryHeader {
    static let signature: UInt32 = 0x02014b50
    let versionMadeBy: UInt16 = 20
    let versionToExtract: UInt16 = 20
    let generalPurposeFlag: UInt16 = 0
    let compressionMethod: UInt16 = 0
    let lastModTime: UInt16 = 0
    let lastModDate: UInt16 = 0
    let crc32: UInt32
    let compressedSize: UInt32
    let uncompressedSize: UInt32
    let filenameLength: UInt16
    let extraFieldLength: UInt16 = 0
    let commentLength: UInt16 = 0
    let diskNumber: UInt16 = 0
    let internalAttributes: UInt16 = 0
    let externalAttributes: UInt32 = 0
    let relativeOffset: UInt32
    
    func write(to data: inout Data) {
        withUnsafeBytes(of: ZipCentralDirectoryHeader.signature.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: versionMadeBy.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: versionToExtract.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: generalPurposeFlag.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: compressionMethod.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: lastModTime.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: lastModDate.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: crc32.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: compressedSize.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: uncompressedSize.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: filenameLength.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: extraFieldLength.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: commentLength.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: diskNumber.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: internalAttributes.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: externalAttributes.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: relativeOffset.littleEndian) { data.append(contentsOf: $0) }
    }
}

struct ZipEndOfCentralDirectory {
    static let signature: UInt32 = 0x06054b50
    let diskNumber: UInt16 = 0
    let centralDirDisk: UInt16 = 0
    let centralDirRecordsOnDisk: UInt16
    let centralDirRecords: UInt16
    let centralDirSize: UInt32
    let centralDirOffset: UInt32
    let commentLength: UInt16 = 0
    
    func write(to data: inout Data) {
        withUnsafeBytes(of: ZipEndOfCentralDirectory.signature.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: diskNumber.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: centralDirDisk.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: centralDirRecordsOnDisk.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: centralDirRecords.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: centralDirSize.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: centralDirOffset.littleEndian) { data.append(contentsOf: $0) }
        withUnsafeBytes(of: commentLength.littleEndian) { data.append(contentsOf: $0) }
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
      self.sendUserInfoPayload()

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
        "userData": self.userData
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

      self.sendUserInfoPayload()
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
    
    // Preparar arrays para createZipData
    var filePaths: [String] = []
    var fileNames: [String] = []
    
    for (index, file) in jpgFiles.enumerated() {
      filePaths.append(file.path)
      fileNames.append("frame_\(String(format: "%03d", index)).jpg")
    }
    
    // Criar o ZIP real
    guard let zipData = createZipData(filePaths: filePaths, fileNames: fileNames) else {
      NSLog("❌ [ExpoAnalytics] Erro ao criar ZIP real")
      return nil
    }
    
    NSLog("✅ [ExpoAnalytics] ZIP real criado com sucesso: \(zipData.count/1024)KB")
    return zipData
  }
  
  private func createZipData(filePaths: [String], fileNames: [String]) -> Data? {
    guard filePaths.count == fileNames.count else { return nil }
    
    var zipData = Data()
    var centralDirectoryEntries: [(header: ZipCentralDirectoryHeader, filename: String)] = []
    var currentOffset: UInt32 = 0
    
    // Função para calcular CRC32
    func crc32(_ data: Data) -> UInt32 {
      return data.withUnsafeBytes { bytes in
        var crc: UInt32 = 0xFFFFFFFF
        let table: [UInt32] = [
          0x00000000, 0x77073096, 0xEE0E612C, 0x990951BA, 0x076DC419, 0x706AF48F,
          0xE963A535, 0x9E6495A3, 0x0EDB8832, 0x79DCB8A4, 0xE0D5E91E, 0x97D2D988,
          0x09B64C2B, 0x7EB17CBD, 0xE7B82D07, 0x90BF1D91, 0x1DB71064, 0x6AB020F2,
          0xF3B97148, 0x84BE41DE, 0x1ADAD47D, 0x6DDDE4EB, 0xF4D4B551, 0x83D385C7,
          0x136C9856, 0x646BA8C0, 0xFD62F97A, 0x8A65C9EC, 0x14015C4F, 0x63066CD9,
          0xFA0F3D63, 0x8D080DF5, 0x3B6E20C8, 0x4C69105E, 0xD56041E4, 0xA2677172,
          0x3C03E4D1, 0x4B04D447, 0xD20D85FD, 0xA50AB56B, 0x35B5A8FA, 0x42B2986C,
          0xDBBBC9D6, 0xACBCF940, 0x32D86CE3, 0x45DF5C75, 0xDCD60DCF, 0xABD13D59,
          0x26D930AC, 0x51DE003A, 0xC8D75180, 0xBFD06116, 0x21B4F4B5, 0x56B3C423,
          0xCFBA9599, 0xB8BDA50F, 0x2802B89E, 0x5F058808, 0xC60CD9B2, 0xB10BE924,
          0x2F6F7C87, 0x58684C11, 0xC1611DAB, 0xB6662D3D, 0x76DC4190, 0x01DB7106,
          0x98D220BC, 0xEFD5102A, 0x71B18589, 0x06B6B51F, 0x9FBFE4A5, 0xE8B8D433,
          0x7807C9A2, 0x0F00F934, 0x9609A88E, 0xE10E9818, 0x7F6A0DBB, 0x086D3D2D,
          0x91646C97, 0xE6635C01, 0x6B6B51F4, 0x1C6C6162, 0x856530D8, 0xF262004E,
          0x6C0695ED, 0x1B01A57B, 0x8208F4C1, 0xF50FC457, 0x65B0D9C6, 0x12B7E950,
          0x8BBEB8EA, 0xFCB9887C, 0x62DD1DDF, 0x15DA2D49, 0x8CD37CF3, 0xFBD44C65,
          0x4DB26158, 0x3AB551CE, 0xA3BC0074, 0xD4BB30E2, 0x4ADFA541, 0x3DD895D7,
          0xA4D1C46D, 0xD3D6F4FB, 0x4369E96A, 0x346ED9FC, 0xAD678846, 0xDA60B8D0,
          0x44042D73, 0x33031DE5, 0xAA0A4C5F, 0xDD0D7CC9, 0x5005713C, 0x270241AA,
          0xBE0B1010, 0xC90C2086, 0x5768B525, 0x206F85B3, 0xB966D409, 0xCE61E49F,
          0x5EDEF90E, 0x29D9C998, 0xB0D09822, 0xC7D7A8B4, 0x59B33D17, 0x2EB40D81,
          0xB7BD5C3B, 0xC0BA6CAD, 0xEDB88320, 0x9ABFB3B6, 0x03B6E20C, 0x74B1D29A,
          0xEAD54739, 0x9DD277AF, 0x04DB2615, 0x73DC1683, 0xE3630B12, 0x94643B84,
          0x0D6D6A3E, 0x7A6A5AA8, 0xE40ECF0B, 0x9309FF9D, 0x0A00AE27, 0x7D079EB1,
          0xF00F9344, 0x8708A3D2, 0x1E01F268, 0x6906C2FE, 0xF762575D, 0x806567CB,
          0x196C3671, 0x6E6B06E7, 0xFED41B76, 0x89D32BE0, 0x10DA7A5A, 0x67DD4ACC,
          0xF9B9DF6F, 0x8EBEEFF9, 0x17B7BE43, 0x60B08ED5, 0xD6D6A3E8, 0xA1D1937E,
          0x38D8C2C4, 0x4FDFF252, 0xD1BB67F1, 0xA6BC5767, 0x3FB506DD, 0x48B2364B,
          0xD80D2BDA, 0xAF0A1B4C, 0x36034AF6, 0x41047A60, 0xDF60EFC3, 0xA867DF55,
          0x316E8EEF, 0x4669BE79, 0xCB61B38C, 0xBC66831A, 0x256FD2A0, 0x5268E236,
          0xCC0C7795, 0xBB0B4703, 0x220216B9, 0x5505262F, 0xC5BA3BBE, 0xB2BD0B28,
          0x2BB45A92, 0x5CB36A04, 0xC2D7FFA7, 0xB5D0CF31, 0x2CD99E8B, 0x5BDEAE1D,
          0x9B64C2B0, 0xEC63F226, 0x756AA39C, 0x026D930A, 0x9C0906A9, 0xEB0E363F,
          0x72076785, 0x05005713, 0x95BF4A82, 0xE2B87A14, 0x7BB12BAE, 0x0CB61B38,
          0x92D28E9B, 0xE5D5BE0D, 0x7CDCEFB7, 0x0BDBDF21, 0x86D3D2D4, 0xF1D4E242,
          0x68DDB3F8, 0x1FDA836E, 0x81BE16CD, 0xF6B9265B, 0x6FB077E1, 0x18B74777,
          0x88085AE6, 0xFF0F6A70, 0x66063BCA, 0x11010B5C, 0x8F659EFF, 0xF862AE69,
          0x616BFFD3, 0x166CCF45, 0xA00AE278, 0xD70DD2EE, 0x4E048354, 0x3903B3C2,
          0xA7672661, 0xD06016F7, 0x4969474D, 0x3E6E77DB, 0xAED16A4A, 0xD9D65ADC,
          0x40DF0B66, 0x37D83BF0, 0xA9BCAE53, 0xDEBB9EC5, 0x47B2CF7F, 0x30B5FFE9,
          0xBDBDF21C, 0xCABAC28A, 0x53B39330, 0x24B4A3A6, 0xBAD03605, 0xCDD70693,
          0x54DE5729, 0x23D967BF, 0xB3667A2E, 0xC4614AB8, 0x5D681B02, 0x2A6F2B94,
          0xB40BBE37, 0xC30C8EA1, 0x5A05DF1B, 0x2D02EF8D
        ]
        
        for byte in bytes.bindMemory(to: UInt8.self) {
          crc = table[Int((crc ^ UInt32(byte)) & 0xFF)] ^ (crc >> 8)
        }
        return crc ^ 0xFFFFFFFF
      }
    }
    
    // Processar cada arquivo
    for (index, filePath) in filePaths.enumerated() {
      guard let fileData = try? Data(contentsOf: URL(fileURLWithPath: filePath)) else { continue }
      
      let fileName = fileNames[index]
      let fileNameData = fileName.data(using: .utf8) ?? Data()
      let fileCRC = crc32(fileData)
      
      // Criar Local File Header
      let localHeader = ZipLocalFileHeader(
        crc32: fileCRC,
        compressedSize: UInt32(fileData.count),
        uncompressedSize: UInt32(fileData.count),
        filenameLength: UInt16(fileNameData.count)
      )
      
      // Escrever Local File Header
      let localHeaderOffset = currentOffset
      localHeader.write(to: &zipData)
      currentOffset += 30 // Tamanho fixo do header
      
      // Escrever nome do arquivo
      zipData.append(fileNameData)
      currentOffset += UInt32(fileNameData.count)
      
      // Escrever dados do arquivo
      zipData.append(fileData)
      currentOffset += UInt32(fileData.count)
      
      // Preparar entrada do Central Directory
      let centralHeader = ZipCentralDirectoryHeader(
        crc32: fileCRC,
        compressedSize: UInt32(fileData.count),
        uncompressedSize: UInt32(fileData.count),
        filenameLength: UInt16(fileNameData.count),
        relativeOffset: localHeaderOffset
      )
      
      centralDirectoryEntries.append((header: centralHeader, filename: fileName))
    }
    
    // Escrever Central Directory
    let centralDirOffset = currentOffset
    var centralDirSize: UInt32 = 0
    
    for entry in centralDirectoryEntries {
      entry.header.write(to: &zipData)
      let fileNameData = entry.filename.data(using: .utf8) ?? Data()
      zipData.append(fileNameData)
      centralDirSize += 46 + UInt32(fileNameData.count) // Tamanho do header + nome
    }
    
    // Escrever End of Central Directory
    let endOfCentralDir = ZipEndOfCentralDirectory(
      centralDirRecordsOnDisk: UInt16(centralDirectoryEntries.count),
      centralDirRecords: UInt16(centralDirectoryEntries.count),
      centralDirSize: centralDirSize,
      centralDirOffset: centralDirOffset
    )
    
    endOfCentralDir.write(to: &zipData)
    
    NSLog("📦 [ExpoAnalytics] ZIP real criado com \(centralDirectoryEntries.count) arquivos, tamanho: \(zipData.count) bytes")
    
    return zipData
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

  private func sendUserInfoPayload() {
    let payload: [String: Any] = [
      "userId": self.userId,
      "userData": self.userData,
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
