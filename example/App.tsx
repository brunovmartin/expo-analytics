import React from 'react';
import { StyleSheet, Text, View, TouchableOpacity, Alert, ScrollView, Switch, Button } from 'react-native';
import { useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as ExpoAnalytics from 'expo-analytics';

export default function App() {
    const [isRecording, setIsRecording] = useState(false);
    const [apiHost, setApiHost] = useState('http://localhost:8080');
    const [appConfig, setAppConfig] = useState<any>(null);
    const [configLoading, setConfigLoading] = useState(false);
    const [currentUserId, setCurrentUserId] = useState<string>('');
    const [isInitialized, setIsInitialized] = useState(false);

    useEffect(() => {
        // Inicializar userId persistente e buscar configurações
        initializeUser();
        fetchAppConfiguration();
    }, []);

    const initializeUser = async () => {
        try {
            let storedUserId = await AsyncStorage.getItem('analytics_user_id');
            
            if (!storedUserId) {
                // Gerar ID único: user-timestamp-random
                const timestamp = Date.now();
                const random = Math.random().toString(36).substring(2, 8);
                storedUserId = `user-${timestamp}-${random}`;
                
                await AsyncStorage.setItem('analytics_user_id', storedUserId);
                console.log('💾 Novo usuário criado:', storedUserId);
            } else {
                console.log('👤 Usuário existente:', storedUserId);
            }
            
            setCurrentUserId(storedUserId);
            setIsInitialized(true);
            
            // INICIALIZAR SISTEMA E CADASTRAR USUÁRIO AUTOMATICAMENTE
            console.log('🚀 Inicializando sistema Expo Analytics...');
            await ExpoAnalytics.init({
                userId: storedUserId,
                apiHost: apiHost,
                userData: {
                    initializeMethod: 'automatic',
                    initializedAt: new Date().toISOString()
                }
            });
            console.log('✅ Sistema inicializado e usuário cadastrado!');
            
            return storedUserId;
        } catch (error) {
            console.error('❌ Erro ao inicializar usuário:', error);
            return 'user-fallback-' + Date.now();
        }
    };

    const fetchAppConfiguration = async () => {
        setConfigLoading(true);
        try {
            const config = await ExpoAnalytics.fetchAppConfig(apiHost);
            setAppConfig(config);
            console.log('✅ Configurações recebidas:', config);
        } catch (error) {
            console.log('❌ Erro ao buscar configurações:', error);
            Alert.alert('Erro', 'Não foi possível buscar configurações do servidor');
        } finally {
            setConfigLoading(false);
        }
    };

    const handleStart = async () => {
        if (!currentUserId) {
            Alert.alert('Erro', 'ID do usuário não foi inicializado');
            return;
        }

        try {
            await ExpoAnalytics.start({
                userData: {
                    customData: 'example-value'
                }
            });
            
            setIsRecording(true);
            console.log('✅ ExpoAnalytics iniciado com usuário:', currentUserId);
            Alert.alert('Sucesso', `Analytics iniciado com usuário: ${currentUserId}`);
        } catch (error) {
            console.error('❌ Erro ao iniciar ExpoAnalytics:', error);
            Alert.alert('Erro', 'Não foi possível iniciar o analytics');
        }
    };

    const handleStop = async () => {
        try {
            await ExpoAnalytics.stop();
            setIsRecording(false);
            console.log('⏹️ ExpoAnalytics parado');
            Alert.alert('Parado', 'Analytics parado com sucesso!');
        } catch (error) {
            console.error('❌ Erro ao parar ExpoAnalytics:', error);
        }
    };

    const handleTrackEvent = async () => {
        try {
            await ExpoAnalytics.trackEvent('button_click', 'test_event_' + Date.now());
            console.log('📊 Evento rastreado');
            Alert.alert('Evento', 'Evento rastreado com sucesso!');
        } catch (error) {
            console.error('❌ Erro ao rastrear evento:', error);
        }
    };

    const handleUpdateUserInfo = async () => {
        try {
            await ExpoAnalytics.updateUserInfo({
                lastAction: 'user_info_update',
                timestamp: new Date().toISOString(),
                sessionTime: Date.now()
            });
            console.log('👤 Informações do usuário atualizadas');
            Alert.alert('Atualizado', 'Informações do usuário atualizadas!');
        } catch (error) {
            console.error('❌ Erro ao atualizar usuário:', error);
        }
    };

    const trackButtonPress = () => {
        if (!isInitialized) {
            Alert.alert('Aviso', 'Analytics ainda não foi inicializado');
            return;
        }

        console.log('📊 Rastreando evento: button_press');
        ExpoAnalytics.trackEvent('button_press', 'main_action_button');
        
        Alert.alert('Evento Rastreado', 'Evento "button_press" enviado com screenshot!');
    };

    const trackCustomEvent = () => {
        if (!isInitialized) {
            Alert.alert('Aviso', 'Analytics ainda não foi inicializado');
            return;
        }

        console.log('📊 Rastreando evento: custom_interaction');
        ExpoAnalytics.trackEvent('custom_interaction', 'user_engagement');
        
        Alert.alert('Evento Personalizado', 'Evento "custom_interaction" enviado com screenshot!');
    };

    const updateUserInfo = () => {
        if (!isInitialized) {
            Alert.alert('Aviso', 'Analytics ainda não foi inicializado');
            return;
        }

        console.log('👤 Atualizando informações do usuário');
        ExpoAnalytics.updateUserInfo({
            lastAction: new Date().toISOString(),
            batatinha: "asdasdasd"
        });
        
        Alert.alert('Info Atualizada', 'Informações do usuário atualizadas!');
    };

    const initializeAnalytics = async () => {
        try {
            console.log('🚀 Inicializando Analytics...');
            
            await ExpoAnalytics.init({
                userId: `user-${Date.now()}-${Math.random().toString(36).substr(2, 6)}`,
                apiHost: 'http://localhost:8080',
                userData: {
                    appVersion: '1.0.0',
                    userType: 'premium',
                    onboardingCompleted: true
                }
            });
            
            console.log('✅ Analytics inicializado com sucesso');
            Alert.alert('Sucesso', 'Analytics inicializado com sucesso!');
        } catch (error) {
            console.error('❌ Erro ao inicializar Analytics:', error);
            Alert.alert('Erro', 'Falha ao inicializar Analytics');
        }
    };

    const startTracking = async () => {
        try {
            console.log('🎬 Iniciando tracking...');
            
            await ExpoAnalytics.start({
                framerate: 10,
                screenSize: 480
            });
            
            console.log('✅ Tracking iniciado');
            Alert.alert('Sucesso', 'Tracking iniciado!');
        } catch (error) {
            console.error('❌ Erro ao iniciar tracking:', error);
            Alert.alert('Erro', 'Falha ao iniciar tracking');
        }
    };

    const stopTracking = async () => {
        try {
            console.log('⏹️ Parando tracking...');
            
            await ExpoAnalytics.stop();
            
            console.log('✅ Tracking parado');
            Alert.alert('Sucesso', 'Tracking parado!');
        } catch (error) {
            console.error('❌ Erro ao parar tracking:', error);
            Alert.alert('Erro', 'Falha ao parar tracking');
        }
    };

    const trackButtonClick = async () => {
        try {
            console.log('📝 Registrando evento...');
            
            await ExpoAnalytics.trackEvent('button_click', 'Test button clicked');
            
            console.log('✅ Evento registrado');
            Alert.alert('Sucesso', 'Evento registrado!');
        } catch (error) {
            console.error('❌ Erro ao registrar evento:', error);
            Alert.alert('Erro', 'Falha ao registrar evento');
        }
    };

    const trackCustomInteraction = async () => {
        try {
            console.log('📝 Registrando interação customizada...');
            
            await ExpoAnalytics.trackEvent('custom_interaction', 'User performed special action');
            
            console.log('✅ Interação registrada');
            Alert.alert('Sucesso', 'Interação customizada registrada!');
        } catch (error) {
            console.error('❌ Erro ao registrar interação:', error);
            Alert.alert('Erro', 'Falha ao registrar interação');
        }
    };

    const takeManualScreenshot = async () => {
        try {
            console.log('📸 Capturando screenshot manual...');
            
            const result = await ExpoAnalytics.takeScreenshot(640, 1280, 0.9);
            
            if (result.success) {
                console.log('✅ Screenshot capturado:', result);
                const size = result.size ?? 0;
                Alert.alert('Sucesso', `Screenshot enviado para o dashboard!\nTamanho: ${result.width}x${result.height}\nArquivo: ${(size / 1024).toFixed(1)}KB\n\n${result.message || 'Screenshot salvo com sucesso'}`);
            } else {
                console.error('❌ Erro no screenshot:', result.error);
                Alert.alert('Erro', `Falha ao capturar screenshot: ${result.error}`);
            }
        } catch (error) {
            console.error('❌ Erro ao capturar screenshot:', error);
            Alert.alert('Erro', 'Falha ao capturar screenshot');
        }
    };

    const takeCompactScreenshot = async () => {
        try {
            console.log('📸 Capturando screenshot compacto...');
            
            const result = await ExpoAnalytics.takeScreenshot(320, 640, 0.6);
            
            if (result.success) {
                console.log('✅ Screenshot compacto capturado:', result);
                const size = result.size ?? 0;
                Alert.alert('Sucesso', `Screenshot compacto enviado para o dashboard!\nTamanho: ${result.width}x${result.height}\nArquivo: ${(size / 1024).toFixed(1)}KB\n\n${result.message || 'Screenshot salvo com sucesso'}`);
            } else {
                console.error('❌ Erro no screenshot compacto:', result.error);
                Alert.alert('Erro', `Falha ao capturar screenshot: ${result.error}`);
            }
        } catch (error) {
            console.error('❌ Erro ao capturar screenshot compacto:', error);
            Alert.alert('Erro', 'Falha ao capturar screenshot compacto');
        }
    };

    return (
        <View style={styles.container}>
            <ScrollView contentContainerStyle={styles.scrollContent}>
                <Text style={styles.title}>📊 Expo Analytics</Text>
                <Text style={styles.subtitle}>Demo com Configurações do Servidor</Text>

                {/* Configurações do Servidor */}
                <View style={styles.configSection}>
                    <Text style={styles.sectionTitle}>🔧 Configurações do Servidor</Text>
                    
                    {configLoading ? (
                        <Text style={styles.loadingText}>Carregando configurações...</Text>
                    ) : appConfig ? (
                        <View style={styles.configCard}>
                            <View style={styles.configItem}>
                                <Text style={styles.configLabel}>Record Screen:</Text>
                                <View style={styles.configValue}>
                                    <Switch 
                                        value={appConfig.recordScreen} 
                                        disabled={true}
                                        trackColor={{ false: "#767577", true: "#81b0ff" }}
                                        thumbColor={appConfig.recordScreen ? "#f5dd4b" : "#f4f3f4"}
                                    />
                                    <Text style={[styles.configText, { color: appConfig.recordScreen ? '#4ade80' : '#6b7280' }]}>
                                        {appConfig.recordScreen ? 'Ativo' : 'Inativo'}
                                    </Text>
                                </View>
                            </View>
                            
                            {appConfig.recordScreen && (
                                <>
                                    <View style={styles.configItem}>
                                        <Text style={styles.configLabel}>Framerate:</Text>
                                        <Text style={styles.configText}>{appConfig.framerate} fps</Text>
                                    </View>
                                    
                                    <View style={styles.configItem}>
                                        <Text style={styles.configLabel}>Screen Size:</Text>
                                        <Text style={styles.configText}>{appConfig.screenSize}px</Text>
                                    </View>
                                </>
                            )}
                        </View>
                    ) : (
                        <Text style={styles.errorText}>Configurações não carregadas</Text>
                    )}

                    <TouchableOpacity style={styles.refreshButton} onPress={fetchAppConfiguration}>
                        <Text style={styles.refreshButtonText}>🔄 Atualizar Configurações</Text>
                    </TouchableOpacity>
                </View>

                {/* Status */}
                <View style={styles.statusSection}>
                    <Text style={styles.sectionTitle}>📱 Status</Text>
                    <View style={styles.statusCard}>
                        <Text style={styles.statusLabel}>Estado da Gravação:</Text>
                        <Text style={[styles.statusText, { color: isRecording ? '#4ade80' : '#ef4444' }]}>
                            {isRecording ? '🔴 Gravando' : '⚫ Parado'}
                        </Text>
                    </View>
                    
                    <View style={styles.statusCard}>
                        <Text style={styles.statusLabel}>API Host:</Text>
                        <Text style={styles.statusText}>{apiHost}</Text>
                    </View>
                    
                    {appConfig && !appConfig.recordScreen && (
                        <View style={styles.warningCard}>
                            <Text style={styles.warningText}>
                                ⚠️ Record Screen está desabilitado no servidor. A gravação não será iniciada.
                            </Text>
                        </View>
                    )}
                </View>

                {/* Controles */}
                <View style={styles.controlsSection}>
                    <Text style={styles.sectionTitle}>🎮 Controles</Text>
                    
                    <TouchableOpacity 
                        style={[styles.button, isRecording ? styles.stopButton : styles.startButton]}
                        onPress={isRecording ? handleStop : handleStart}
                    >
                        <Text style={styles.buttonText}>
                            {isRecording ? '⏹️ Parar Analytics' : '▶️ Iniciar Analytics'}
                        </Text>
                    </TouchableOpacity>

                    <TouchableOpacity 
                        style={[styles.button, styles.eventButton, !isRecording && styles.disabledButton]}
                        onPress={handleTrackEvent}
                        disabled={!isRecording}
                    >
                        <Text style={[styles.buttonText, !isRecording && styles.disabledButtonText]}>
                            📊 Rastrear Evento
                        </Text>
                    </TouchableOpacity>

                    <TouchableOpacity 
                        style={[styles.button, styles.userButton, !isRecording && styles.disabledButton]}
                        onPress={handleUpdateUserInfo}
                        disabled={!isRecording}
                    >
                        <Text style={[styles.buttonText, !isRecording && styles.disabledButtonText]}>
                            👤 Atualizar Usuário
                        </Text>
                    </TouchableOpacity>
                </View>

                {/* Informações */}
                <View style={styles.infoSection}>
                    <Text style={styles.sectionTitle}>ℹ️ Como Funciona</Text>
                    <Text style={styles.infoText}>
                        1. O app busca automaticamente as configurações do servidor pelo Bundle ID
                        {'\n'}2. Se "Record Screen" estiver ativo, a gravação inicia automaticamente
                        {'\n'}3. O framerate e tamanho da tela são aplicados conforme configurado
                        {'\n'}4. Eventos e dados do usuário são sempre rastreados independente da gravação
                    </Text>
                </View>

                <View style={styles.infoContainer}>
                    <Text style={styles.infoText}>
                        Status: {isInitialized ? '✅ Inicializado' : '⏳ Carregando...'}
                    </Text>
                    {currentUserId && (
                        <Text style={styles.userText}>
                            Usuário: {currentUserId}
                        </Text>
                    )}
                </View>

                <View style={styles.buttonsContainer}>
                    <TouchableOpacity 
                        style={[styles.button, styles.primaryButton]} 
                        onPress={trackButtonPress}
                        disabled={!isInitialized}
                    >
                        <Text style={styles.buttonText}>📊 Track Button Press</Text>
                    </TouchableOpacity>

                    <TouchableOpacity 
                        style={[styles.button, styles.secondaryButton]} 
                        onPress={trackCustomEvent}
                        disabled={!isInitialized}
                    >
                        <Text style={styles.buttonText}>🎯 Track Custom Event</Text>
                    </TouchableOpacity>

                    <TouchableOpacity 
                        style={[styles.button, styles.tertiaryButton]} 
                        onPress={updateUserInfo}
                        disabled={!isInitialized}
                    >
                        <Text style={styles.buttonText}>👤 Update User Info</Text>
                    </TouchableOpacity>
                </View>

                <Text style={styles.footer}>
                    Todos os eventos são enviados com screenshot automático! 📸
                </Text>

                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>🚀 Inicialização</Text>
                    <Button title="Inicializar Analytics" onPress={initializeAnalytics} />
                </View>

                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>🎬 Controle de Tracking</Text>
                    <View style={styles.buttonGroup}>
                        <Button title="Iniciar Tracking" onPress={startTracking} />
                        <Button title="Parar Tracking" onPress={stopTracking} color="#f44336" />
                    </View>
                </View>

                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>📝 Eventos</Text>
                    <View style={styles.buttonGroup}>
                        <Button title="Click do Botão" onPress={trackButtonClick} />
                        <Button title="Interação Customizada" onPress={trackCustomInteraction} color="#4CAF50" />
                    </View>
                </View>

                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>📸 Screenshots Manuais</Text>
                    <View style={styles.buttonGroup}>
                        <Button title="Screenshot HD (640x1280)" onPress={takeManualScreenshot} color="#9C27B0" />
                        <Button title="Screenshot Compacto (320x640)" onPress={takeCompactScreenshot} color="#FF9800" />
                    </View>
                    <Text style={styles.note}>
                        Os screenshots manuais são enviados para o servidor com os parâmetros especificados
                    </Text>
                </View>

                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>👤 Dados do Usuário</Text>
                    <Button title="Atualizar Informações" onPress={updateUserInfo} color="#2196F3" />
                </View>

                <View style={styles.info}>
                    <Text style={styles.infoTitle}>ℹ️ Informações</Text>
                    <Text style={styles.infoText}>
                        • O sistema coleta automaticamente informações do dispositivo{'\n'}
                        • Screenshots são capturados durante eventos{'\n'}
                        • Sessões são gravadas em vídeo quando habilitado{'\n'}
                        • Dados geográficos são obtidos pelo IP{'\n'}
                        • Dashboard disponível em: http://localhost:8080/dashboard
                    </Text>
                </View>
            </ScrollView>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f5f7fa',
    },
    scrollContent: {
        flexGrow: 1,
        padding: 20,
        paddingTop: 40,
    },
    title: {
        fontSize: 28,
        fontWeight: 'bold',
        textAlign: 'center',
        marginBottom: 8,
        color: '#1f2937',
    },
    subtitle: {
        fontSize: 16,
        textAlign: 'center',
        marginBottom: 30,
        color: '#6b7280',
    },
    section: {
        backgroundColor: 'white',
        padding: 20,
        marginBottom: 15,
        borderRadius: 10,
        shadowColor: '#000',
        shadowOffset: {
            width: 0,
            height: 2,
        },
        shadowOpacity: 0.1,
        shadowRadius: 3.84,
        elevation: 5,
    },
    sectionTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        marginBottom: 15,
        color: '#333',
    },
    buttonGroup: {
        gap: 10,
    },
    configSection: {
        backgroundColor: 'white',
        padding: 20,
        marginBottom: 20,
        borderRadius: 12,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
        elevation: 3,
    },
    configCard: {
        backgroundColor: '#f8fafc',
        padding: 15,
        borderRadius: 8,
        marginBottom: 15,
    },
    configItem: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingVertical: 8,
    },
    configLabel: {
        fontSize: 16,
        fontWeight: '500',
        color: '#374151',
    },
    configValue: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    configText: {
        fontSize: 16,
        fontWeight: '600',
    },
    loadingText: {
        fontSize: 16,
        color: '#6b7280',
        textAlign: 'center',
        fontStyle: 'italic',
    },
    errorText: {
        fontSize: 16,
        color: '#ef4444',
        textAlign: 'center',
        fontWeight: '500',
    },
    refreshButton: {
        backgroundColor: '#3b82f6',
        paddingVertical: 12,
        paddingHorizontal: 20,
        borderRadius: 8,
        alignItems: 'center',
    },
    refreshButtonText: {
        color: 'white',
        fontSize: 16,
        fontWeight: '600',
    },
    statusSection: {
        backgroundColor: 'white',
        padding: 20,
        marginBottom: 20,
        borderRadius: 12,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
        elevation: 3,
    },
    statusCard: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingVertical: 8,
        borderBottomWidth: 1,
        borderBottomColor: '#f3f4f6',
    },
    statusLabel: {
        fontSize: 16,
        color: '#6b7280',
    },
    statusText: {
        fontSize: 16,
        fontWeight: '600',
    },
    warningCard: {
        backgroundColor: '#fef3c7',
        padding: 12,
        borderRadius: 8,
        borderLeftWidth: 4,
        borderLeftColor: '#f59e0b',
        marginTop: 10,
    },
    warningText: {
        color: '#92400e',
        fontSize: 14,
        fontWeight: '500',
    },
    controlsSection: {
        backgroundColor: 'white',
        padding: 20,
        marginBottom: 20,
        borderRadius: 12,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
        elevation: 3,
    },
    button: {
        paddingVertical: 16,
        paddingHorizontal: 24,
        borderRadius: 10,
        alignItems: 'center',
        marginBottom: 12,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
        elevation: 2,
    },
    startButton: {
        backgroundColor: '#10b981',
    },
    stopButton: {
        backgroundColor: '#ef4444',
    },
    eventButton: {
        backgroundColor: '#8b5cf6',
    },
    userButton: {
        backgroundColor: '#06b6d4',
    },
    primaryButton: {
        backgroundColor: '#3b82f6',
    },
    secondaryButton: {
        backgroundColor: '#6366f1',
    },
    tertiaryButton: {
        backgroundColor: '#8b5cf6',
    },
    disabledButton: {
        backgroundColor: '#d1d5db',
    },
    buttonText: {
        color: 'white',
        fontSize: 16,
        fontWeight: '600',
    },
    disabledButtonText: {
        color: '#9ca3af',
    },
    infoSection: {
        backgroundColor: '#eff6ff',
        padding: 20,
        marginBottom: 20,
        borderRadius: 12,
        borderWidth: 1,
        borderColor: '#dbeafe',
    },
    infoContainer: {
        backgroundColor: '#f0f9ff',
        padding: 16,
        marginBottom: 20,
        borderRadius: 8,
        borderWidth: 1,
        borderColor: '#7dd3fc',
    },
    buttonsContainer: {
        gap: 12,
        marginBottom: 20,
    },
    userText: {
        fontSize: 14,
        color: '#374151',
        marginTop: 4,
        fontFamily: 'monospace',
    },
    footer: {
        fontSize: 14,
        textAlign: 'center',
        color: '#6b7280',
        marginTop: 20,
        fontStyle: 'italic',
    },
    note: {
        fontSize: 12,
        color: '#666',
        fontStyle: 'italic',
        marginTop: 8,
        textAlign: 'center',
    },
    info: {
        backgroundColor: '#e3f2fd',
        padding: 20,
        marginBottom: 20,
        borderRadius: 10,
        borderColor: '#2196F3',
        borderWidth: 1,
    },
    infoTitle: {
        fontSize: 16,
        fontWeight: 'bold',
        marginBottom: 10,
        color: '#1976D2',
    },
    infoText: {
        fontSize: 14,
        lineHeight: 20,
        color: '#333',
    },
});