import React, { useState } from 'react';
import { 
  View, 
  Text, 
  TouchableOpacity, 
  Alert, 
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  AlertButton
} from 'react-native';
// Import correto para a pasta example/
import * as ExpoAnalytics from 'expo-analytics';

interface ScreenshotResult {
  success: boolean;
  message?: string;
  error?: string;
  alertShown?: boolean;
  alertTitle?: string;
  alertMessage?: string;
  width?: number;
  height?: number;
  size?: number;
}

export default function AlertCaptureExample() {
  const [loading, setLoading] = useState(false);
  const [lastResult, setLastResult] = useState<ScreenshotResult | null>(null);

  // Função para testar captura manual com alerta personalizado
  const testManualAlertCapture = async () => {
    setLoading(true);
    
    // 1. Mostrar alerta personalizado
    Alert.alert(
      "Alerta Personalizado",
      "Esta é uma mensagem de alerta personalizada que será capturada no screenshot!",
      [
        {
          text: "Cancelar",
          style: "cancel"
        },
        {
          text: "OK",
          onPress: () => console.log('Usuário clicou OK')
        }
      ]
    );
    
    // 2. Aguardar um pouco e tirar screenshot
    setTimeout(async () => {
      try {
        console.log('📸 Tirando screenshot com alerta personalizado...');
        
        const result = await (ExpoAnalytics as any).takeScreenshot(480, 960, 0.8);
        
        const enhancedResult = {
          ...result,
          alertShown: true,
          alertTitle: "Alerta Personalizado",
          alertMessage: "Captura manual com alerta personalizado"
        };
        
        setLastResult(enhancedResult);
        console.log('✅ Screenshot manual com alerta:', enhancedResult);
        
      } catch (error) {
        console.error('❌ Erro na captura manual:', error);
        setLastResult({
          success: false,
          error: error instanceof Error ? error.message : 'Erro desconhecido'
        });
      } finally {
        setLoading(false);
      }
    }, 1500); // Aguardar 1.5s para o alerta aparecer completamente
  };

  // Função para testar diferentes tipos de alerta
  const testDifferentAlertTypes = async () => {
    const alertTypes: Array<{
      title: string;
      message: string;
      buttons: AlertButton[];
    }> = [
      {
        title: "Alerta Simples",
        message: "Apenas uma mensagem básica",
        buttons: [{ text: "OK" }]
      },
      {
        title: "Confirmar Ação",
        message: "Você tem certeza que deseja continuar?",
        buttons: [
          { text: "Cancelar", style: "cancel" },
          { text: "Sim", style: "destructive" }
        ]
      },
      {
        title: "Múltiplas Opções",
        message: "Escolha uma das opções abaixo:",
        buttons: [
          { text: "Opção 1" },
          { text: "Opção 2" },
          { text: "Cancelar", style: "cancel" }
        ]
      }
    ];

    for (let i = 0; i < alertTypes.length; i++) {
      const alertConfig = alertTypes[i];
      
      await new Promise<void>((resolve) => {
        Alert.alert(
          alertConfig.title,
          alertConfig.message,
          alertConfig.buttons.map(btn => ({
            ...btn,
            onPress: () => resolve()
          }))
        );
        
        // Tirar screenshot após 1 segundo
        setTimeout(async () => {
          try {
            await (ExpoAnalytics as any).takeScreenshot(320, 640, 0.7);
            console.log(`📸 Screenshot do alerta ${i + 1}/${alertTypes.length} capturado`);
          } catch (error) {
            console.error(`❌ Erro no screenshot ${i + 1}:`, error);
          }
        }, 1000);
      });
      
      // Aguardar entre alertas
      await new Promise(resolve => setTimeout(resolve, 500));
    }
    
    setLastResult({
      success: true,
      message: `${alertTypes.length} alertas diferentes foram testados e capturados!`
    });
  };

  const renderResult = () => {
    if (!lastResult) return null;
    
    return (
      <View style={styles.resultContainer}>
        <Text style={styles.resultTitle}>📊 Último Resultado:</Text>
        
        <View style={[
          styles.resultBox, 
          { backgroundColor: lastResult.success ? '#e8f5e8' : '#fdeaea' }
        ]}>
          <Text style={styles.resultText}>
            Status: {lastResult.success ? '✅ Sucesso' : '❌ Erro'}
          </Text>
          
          {lastResult.message && (
            <Text style={styles.resultText}>
              Mensagem: {lastResult.message}
            </Text>
          )}
          
          {lastResult.error && (
            <Text style={styles.errorText}>
              Erro: {lastResult.error}
            </Text>
          )}
          
          {lastResult.alertShown && (
            <>
              <Text style={styles.resultText}>
                🚨 Alerta Capturado: Sim
              </Text>
              {lastResult.alertTitle && (
                <Text style={styles.resultText}>
                  Título: {lastResult.alertTitle}
                </Text>
              )}
              {lastResult.alertMessage && (
                <Text style={styles.resultText}>
                  Mensagem: {lastResult.alertMessage}
                </Text>
              )}
            </>
          )}
          
          {lastResult.width && lastResult.height && (
            <Text style={styles.resultText}>
              Dimensões: {lastResult.width}x{lastResult.height}
            </Text>
          )}
          
          {lastResult.size && (
            <Text style={styles.resultText}>
              Tamanho: {Math.round(lastResult.size / 1024)}KB
            </Text>
          )}
        </View>
      </View>
    );
  };

  return (
    <ScrollView style={styles.container}>
      <Text style={styles.title}>📸 Teste de Captura de Alertas</Text>
      
      <Text style={styles.description}>
        Este exemplo demonstra como os alertas agora aparecem nos screenshots
        capturados pelo ExpoAnalytics.
      </Text>

      <View style={styles.buttonContainer}>
        <TouchableOpacity
          style={[styles.button, styles.primaryButton]}
          onPress={testManualAlertCapture}
          disabled={loading}
        >
          <Text style={styles.buttonText}>
            ✋ Teste Manual com Alerta
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.button, styles.tertiaryButton]}
          onPress={testDifferentAlertTypes}
          disabled={loading}
        >
          <Text style={styles.buttonText}>
            🎯 Testar Diferentes Tipos
          </Text>
        </TouchableOpacity>
      </View>

      {loading && (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#007AFF" />
          <Text style={styles.loadingText}>
            Processando screenshot...
          </Text>
        </View>
      )}

      {renderResult()}

      <View style={styles.infoBox}>
        <Text style={styles.infoTitle}>ℹ️ Como Funciona:</Text>
        <Text style={styles.infoText}>
          • O sistema agora captura TODAS as janelas visíveis{'\n'}
          • Inclui alertas, dialogs e overlays{'\n'}
          • Janelas são ordenadas por prioridade (windowLevel){'\n'}
          • Screenshots incluem conteúdo em primeiro plano
        </Text>
      </View>

      <View style={styles.infoBox}>
        <Text style={styles.infoTitle}>🔧 Configurações:</Text>
        <Text style={styles.infoText}>
          • Resolução: 480x960 (padrão){'\n'}
          • Compressão: 0.8 (80% qualidade){'\n'}
          • Formato: JPEG{'\n'}
          • Capture automático durante sessões ativas
        </Text>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
    padding: 20,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    textAlign: 'center',
    marginBottom: 16,
    color: '#333',
  },
  description: {
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 24,
    color: '#666',
    lineHeight: 22,
  },
  buttonContainer: {
    marginBottom: 24,
  },
  button: {
    padding: 16,
    borderRadius: 8,
    marginBottom: 12,
    alignItems: 'center',
  },
  primaryButton: {
    backgroundColor: '#007AFF',
  },
  secondaryButton: {
    backgroundColor: '#34C759',
  },
  tertiaryButton: {
    backgroundColor: '#FF9500',
  },
  buttonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
  loadingContainer: {
    alignItems: 'center',
    padding: 20,
    backgroundColor: 'white',
    borderRadius: 8,
    marginBottom: 20,
  },
  loadingText: {
    marginTop: 8,
    color: '#666',
  },
  resultContainer: {
    marginBottom: 20,
  },
  resultTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 8,
    color: '#333',
  },
  resultBox: {
    padding: 16,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#ddd',
  },
  resultText: {
    fontSize: 14,
    marginBottom: 4,
    color: '#333',
  },
  errorText: {
    fontSize: 14,
    marginBottom: 4,
    color: '#d32f2f',
  },
  infoBox: {
    backgroundColor: 'white',
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
    borderLeftWidth: 4,
    borderLeftColor: '#007AFF',
  },
  infoTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 8,
    color: '#333',
  },
  infoText: {
    fontSize: 14,
    color: '#666',
    lineHeight: 20,
  },
}); 