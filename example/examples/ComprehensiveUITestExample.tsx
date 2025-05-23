import React, { useState } from 'react';
import { 
  View, 
  Text, 
  TouchableOpacity, 
  Alert, 
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  Modal,
  ActionSheetIOS,
  Platform,
  TextInput,
  KeyboardAvoidingView,
  Switch,
  StatusBar
} from 'react-native';
// Import correto para a pasta example/
import * as ExpoAnalytics from 'expo-analytics';

interface TestResult {
  success: boolean;
  message?: string;
  error?: string;
  testType?: string;
  timestamp?: number;
}

export default function ComprehensiveUITestExample() {
  const [loading, setLoading] = useState(false);
  const [lastResult, setLastResult] = useState<TestResult | null>(null);
  
  // Estados para diferentes componentes de UI
  const [showModal, setShowModal] = useState(false);
  const [showSecondModal, setShowSecondModal] = useState(false);
  const [textInput, setTextInput] = useState('');
  const [textInputFocused, setTextInputFocused] = useState(false);
  const [selectedOption, setSelectedOption] = useState('Opção 1');
  const [switchValue, setSwitchValue] = useState(false);

  // Função para capturar screenshot e testar
  const captureTestScreenshot = async (testType: string, delay: number = 500) => {
    setLoading(true);
    
    try {
      // Aguardar um pouco para o UI se estabilizar
      await new Promise(resolve => setTimeout(resolve, delay));
      
      console.log(`📸 Testando captura de screenshot: ${testType}`);
      
      const result = await (ExpoAnalytics as any).takeScreenshot(480, 960, 0.8);
      
      const enhancedResult = {
        ...result,
        testType,
        timestamp: Date.now()
      };
      
      setLastResult(enhancedResult);
      console.log(`✅ Teste ${testType} concluído:`, enhancedResult);
      
    } catch (error) {
      console.error(`❌ Erro no teste ${testType}:`, error);
      setLastResult({
        success: false,
        error: error instanceof Error ? error.message : 'Erro desconhecido',
        testType
      });
    } finally {
      setLoading(false);
    }
  };

  // Teste com Modal simples
  const testSimpleModal = () => {
    setShowModal(true);
    captureTestScreenshot('Modal Simples', 800);
  };

  // Teste com Modal aninhado (modal sobre modal)
  const testNestedModal = () => {
    setShowModal(true);
    setTimeout(() => {
      setShowSecondModal(true);
      captureTestScreenshot('Modal Aninhado', 800);
    }, 500);
  };

  // Teste com ActionSheet (iOS)
  const testActionSheet = () => {
    if (Platform.OS === 'ios') {
      ActionSheetIOS.showActionSheetWithOptions(
        {
          options: ['Cancelar', 'Opção 1', 'Opção 2', 'Opção Destrutiva'],
          destructiveButtonIndex: 3,
          cancelButtonIndex: 0,
          title: 'Escolha uma opção',
          message: 'Este ActionSheet deve aparecer no screenshot'
        },
        (buttonIndex) => {
          console.log('ActionSheet selecionado:', buttonIndex);
        }
      );
      
      // Capturar screenshot após ActionSheet aparecer
      captureTestScreenshot('ActionSheet iOS', 1000);
    } else {
      // Para Android, usar Alert com múltiplas opções
      Alert.alert(
        'ActionSheet Android',
        'Escolha uma das opções (simulando ActionSheet)',
        [
          { text: 'Cancelar', style: 'cancel' },
          { text: 'Opção 1' },
          { text: 'Opção 2' },
          { text: 'Opção Destrutiva', style: 'destructive' }
        ]
      );
      captureTestScreenshot('ActionSheet Android', 1000);
    }
  };

  // Teste com Teclado
  const testKeyboard = () => {
    setTextInputFocused(true);
    // O teclado aparecerá automaticamente quando o TextInput receber foco
    captureTestScreenshot('Teclado Visível', 1200);
  };

  // Teste com Alert de confirmação
  const testConfirmAlert = () => {
    Alert.alert(
      'Confirmação',
      'Você tem certeza que deseja prosseguir com esta ação? Esta é uma mensagem longa para testar como alerts com muito texto aparecem no screenshot.',
      [
        { 
          text: 'Cancelar', 
          style: 'cancel',
          onPress: () => console.log('Cancelado')
        },
        { 
          text: 'Sim, prosseguir', 
          style: 'destructive',
          onPress: () => console.log('Confirmado')
        }
      ]
    );
    captureTestScreenshot('Alert de Confirmação', 1000);
  };

  // Teste com múltiplos elementos sobrepostos
  const testMultipleOverlays = () => {
    // Primeiro modal
    setShowModal(true);
    
    setTimeout(() => {
      // Segundo modal
      setShowSecondModal(true);
      
      setTimeout(() => {
        // Alert por cima dos modais
        Alert.alert(
          'Alert sobre Modais',
          'Este alert aparece por cima de dois modais!',
          [{ text: 'OK' }]
        );
        captureTestScreenshot('Múltiplos Overlays', 1200);
      }, 500);
    }, 500);
  };

  // Fechar todos os overlays
  const closeAllOverlays = () => {
    setShowModal(false);
    setShowSecondModal(false);
    setTextInputFocused(false);
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
            Teste: {lastResult.testType || 'Desconhecido'}
          </Text>
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
          
          {lastResult.timestamp && (
            <Text style={styles.resultText}>
              Horário: {new Date(lastResult.timestamp).toLocaleTimeString()}
            </Text>
          )}
        </View>
      </View>
    );
  };

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <StatusBar barStyle="dark-content" />
      
      <ScrollView style={styles.scrollView} keyboardShouldPersistTaps="handled">
        <Text style={styles.title}>🧪 Teste Completo de UI</Text>
        
        <Text style={styles.description}>
          Este exemplo testa a captura de screenshots com diferentes tipos de overlays e elementos de UI.
        </Text>

        {/* Seção de Controles */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>🎛️ Controles de Teste</Text>
          
          <TouchableOpacity
            style={[styles.button, styles.primaryButton]}
            onPress={closeAllOverlays}
          >
            <Text style={styles.buttonText}>🧹 Fechar Todos os Overlays</Text>
          </TouchableOpacity>
        </View>

        {/* Testes de Modal */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>🗂️ Testes de Modal</Text>
          
          <TouchableOpacity
            style={[styles.button, styles.secondaryButton]}
            onPress={testSimpleModal}
            disabled={loading}
          >
            <Text style={styles.buttonText}>📱 Modal Simples</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.secondaryButton]}
            onPress={testNestedModal}
            disabled={loading}
          >
            <Text style={styles.buttonText}>📱📱 Modais Aninhados</Text>
          </TouchableOpacity>
        </View>

        {/* Testes de ActionSheet e Alerts */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>⚠️ Testes de ActionSheet e Alerts</Text>
          
          <TouchableOpacity
            style={[styles.button, styles.tertiaryButton]}
            onPress={testActionSheet}
            disabled={loading}
          >
            <Text style={styles.buttonText}>
              📋 ActionSheet {Platform.OS === 'ios' ? '(iOS)' : '(Android)'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.tertiaryButton]}
            onPress={testConfirmAlert}
            disabled={loading}
          >
            <Text style={styles.buttonText}>❓ Alert de Confirmação</Text>
          </TouchableOpacity>
        </View>

        {/* Teste de Teclado */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>⌨️ Teste de Teclado</Text>
          
          <TextInput
            style={styles.textInput}
            placeholder="Digite algo para mostrar o teclado..."
            value={textInput}
            onChangeText={setTextInput}
            onFocus={() => setTextInputFocused(true)}
            onBlur={() => setTextInputFocused(false)}
            multiline
          />
          
          <TouchableOpacity
            style={[styles.button, styles.keyboardButton]}
            onPress={testKeyboard}
            disabled={loading}
          >
            <Text style={styles.buttonText}>⌨️ Testar com Teclado</Text>
          </TouchableOpacity>
        </View>

        {/* Outros Componentes */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>🎚️ Outros Componentes</Text>
          
          <View style={styles.row}>
            <Text>Selected Option: </Text>
            <Text>{selectedOption}</Text>
          </View>
          
          <TouchableOpacity
            style={[styles.button, styles.secondaryButton]}
            onPress={() => {
              const options = ['Opção 1', 'Opção 2', 'Opção 3'];
              const currentIndex = options.indexOf(selectedOption);
              const nextIndex = (currentIndex + 1) % options.length;
              setSelectedOption(options[nextIndex]);
            }}
          >
            <Text style={styles.buttonText}>Trocar Opção</Text>
          </TouchableOpacity>
          
          <View style={styles.row}>
            <Text>Switch: </Text>
            <Switch
              value={switchValue}
              onValueChange={setSwitchValue}
            />
          </View>
        </View>

        {/* Teste Avançado */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>🚀 Teste Avançado</Text>
          
          <TouchableOpacity
            style={[styles.button, styles.advancedButton]}
            onPress={testMultipleOverlays}
            disabled={loading}
          >
            <Text style={styles.buttonText}>🎭 Múltiplos Overlays</Text>
          </TouchableOpacity>
        </View>

        {loading && (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color="#007AFF" />
            <Text style={styles.loadingText}>
              Capturando screenshot...
            </Text>
          </View>
        )}

        {renderResult()}

        <View style={styles.infoBox}>
          <Text style={styles.infoTitle}>🔍 O que está sendo testado:</Text>
          <Text style={styles.infoText}>
            • Modais simples e aninhados{'\n'}
            • ActionSheets (iOS/Android){'\n'}
            • Alertas de confirmação{'\n'}
            • Teclado virtual{'\n'}
            • Outros Componentes{'\n'}
            • Múltiplos overlays sobrepostos{'\n'}
            • Diferentes windowLevels
          </Text>
        </View>
      </ScrollView>

      {/* Modal Simples */}
      <Modal
        visible={showModal}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setShowModal(false)}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>📱 Modal Simples</Text>
            <Text style={styles.modalText}>
              Este é um modal simples que deve aparecer no screenshot.
              Você pode testar diferentes tipos de modais aqui.
            </Text>
            
            <TouchableOpacity
              style={[styles.button, styles.secondaryButton]}
              onPress={() => setShowSecondModal(true)}
            >
              <Text style={styles.buttonText}>Abrir Segundo Modal</Text>
            </TouchableOpacity>
            
            <TouchableOpacity
              style={[styles.button, styles.primaryButton]}
              onPress={() => setShowModal(false)}
            >
              <Text style={styles.buttonText}>Fechar Modal</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* Modal Aninhado */}
      <Modal
        visible={showSecondModal}
        animationType="fade"
        transparent={true}
        onRequestClose={() => setShowSecondModal(false)}
      >
        <View style={styles.overlayModal}>
          <View style={styles.overlayContent}>
            <Text style={styles.modalTitle}>📱📱 Modal Aninhado</Text>
            <Text style={styles.modalText}>
              Este é um segundo modal por cima do primeiro!
              Ambos devem aparecer no screenshot.
            </Text>
            
            <TouchableOpacity
              style={[styles.button, styles.tertiaryButton]}
              onPress={() => Alert.alert('Alert sobre Modal', 'Este alert aparece sobre o modal aninhado!')}
            >
              <Text style={styles.buttonText}>Mostrar Alert</Text>
            </TouchableOpacity>
            
            <TouchableOpacity
              style={[styles.button, styles.primaryButton]}
              onPress={() => setShowSecondModal(false)}
            >
              <Text style={styles.buttonText}>Fechar</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  scrollView: {
    flex: 1,
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
  section: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 12,
    color: '#333',
  },
  button: {
    padding: 16,
    borderRadius: 8,
    marginBottom: 8,
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
  keyboardButton: {
    backgroundColor: '#AF52DE',
  },
  advancedButton: {
    backgroundColor: '#FF3B30',
  },
  buttonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
  textInput: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    padding: 12,
    backgroundColor: 'white',
    marginBottom: 8,
    minHeight: 60,
    textAlignVertical: 'top',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
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
    marginBottom: 20,
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
  modalContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f5f5f5',
  },
  modalContent: {
    margin: 20,
    backgroundColor: 'white',
    borderRadius: 20,
    padding: 35,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.25,
    shadowRadius: 4,
    elevation: 5,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 15,
    textAlign: 'center',
  },
  modalText: {
    fontSize: 16,
    marginBottom: 20,
    textAlign: 'center',
    color: '#666',
    lineHeight: 22,
  },
  overlayModal: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
  },
  overlayContent: {
    margin: 20,
    backgroundColor: 'white',
    borderRadius: 20,
    padding: 30,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.25,
    shadowRadius: 4,
    elevation: 5,
  },
}); 