import React from 'react';
import { StyleSheet, Text, View, TouchableOpacity, Alert, ScrollView, Switch } from 'react-native';
import { useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as ExpoAnalytics from 'expo-analytics';

export default function App() {
    const [isRecording, setIsRecording] = useState(false);
    const [apiHost, setApiHost] = useState('http://localhost:8080');
    const [appConfig, setAppConfig] = useState<any>(null);
    const [configLoading, setConfigLoading] = useState(false);
    const [currentUserId, setCurrentUserId] = useState<string>('');

    useEffect(() => {
        // Inicializar userId persistente e buscar configurações
        initializeUser();
        fetchAppConfiguration();
    }, []);

    const initializeUser = async () => {
        try {
            let userId = await AsyncStorage.getItem('expo_analytics_user_id');
            
            if (!userId) {
                // Gerar userId único apenas na primeira vez
                userId = 'user-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                await AsyncStorage.setItem('expo_analytics_user_id', userId);
                console.log('✅ Novo usuário criado:', userId);
            } else {
                console.log('✅ Usuário existente recuperado:', userId);
            }
            
            setCurrentUserId(userId);
        } catch (error) {
            console.error('❌ Erro ao inicializar usuário:', error);
            // Fallback para userId temporário se AsyncStorage falhar
            const fallbackUserId = 'temp-user-' + Date.now();
            setCurrentUserId(fallbackUserId);
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
                apiHost: apiHost,
                userId: currentUserId, // Usar o userId persistente
                userData: {
                    appVersion: '1.0.0',
                    platform: 'iOS',
                    device: 'iPhone',
                    environment: 'development',
                    sessionStartTime: new Date().toISOString()
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
            </ScrollView>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f8fafc',
    },
    scrollContent: {
        flexGrow: 1,
        padding: 20,
        paddingTop: 60,
    },
    title: {
        fontSize: 28,
        fontWeight: 'bold',
        textAlign: 'center',
        marginBottom: 8,
        color: '#1e293b',
    },
    subtitle: {
        fontSize: 16,
        textAlign: 'center',
        marginBottom: 30,
        color: '#64748b',
    },
    sectionTitle: {
        fontSize: 18,
        fontWeight: '600',
        marginBottom: 15,
        color: '#374151',
    },
    configSection: {
        marginBottom: 25,
    },
    configCard: {
        backgroundColor: '#ffffff',
        borderRadius: 12,
        padding: 16,
        marginBottom: 12,
        borderWidth: 1,
        borderColor: '#e5e7eb',
        shadowColor: '#000',
        shadowOffset: {
            width: 0,
            height: 2,
        },
        shadowOpacity: 0.1,
        shadowRadius: 3.84,
        elevation: 5,
    },
    configItem: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingVertical: 8,
        borderBottomWidth: 1,
        borderBottomColor: '#f3f4f6',
    },
    configLabel: {
        fontSize: 14,
        fontWeight: '500',
        color: '#6b7280',
    },
    configValue: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    configText: {
        fontSize: 14,
        fontWeight: '600',
        color: '#374151',
    },
    loadingText: {
        textAlign: 'center',
        color: '#6b7280',
        fontStyle: 'italic',
    },
    errorText: {
        textAlign: 'center',
        color: '#ef4444',
    },
    refreshButton: {
        backgroundColor: '#3b82f6',
        paddingHorizontal: 16,
        paddingVertical: 10,
        borderRadius: 8,
        alignItems: 'center',
    },
    refreshButtonText: {
        color: '#ffffff',
        fontWeight: '600',
        fontSize: 14,
    },
    statusSection: {
        marginBottom: 25,
    },
    statusCard: {
        backgroundColor: '#ffffff',
        borderRadius: 8,
        padding: 12,
        marginBottom: 8,
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        borderWidth: 1,
        borderColor: '#e5e7eb',
    },
    statusLabel: {
        fontSize: 14,
        color: '#6b7280',
    },
    statusText: {
        fontSize: 14,
        fontWeight: '600',
    },
    warningCard: {
        backgroundColor: '#fef3c7',
        borderRadius: 8,
        padding: 12,
        marginTop: 8,
        borderWidth: 1,
        borderColor: '#f59e0b',
    },
    warningText: {
        fontSize: 13,
        color: '#92400e',
        textAlign: 'center',
    },
    controlsSection: {
        marginBottom: 25,
    },
    button: {
        paddingVertical: 14,
        paddingHorizontal: 20,
        borderRadius: 10,
        alignItems: 'center',
        marginBottom: 12,
        shadowColor: '#000',
        shadowOffset: {
            width: 0,
            height: 2,
        },
        shadowOpacity: 0.1,
        shadowRadius: 3.84,
        elevation: 5,
    },
    startButton: {
        backgroundColor: '#10b981',
    },
    stopButton: {
        backgroundColor: '#ef4444',
    },
    eventButton: {
        backgroundColor: '#3b82f6',
    },
    userButton: {
        backgroundColor: '#8b5cf6',
    },
    disabledButton: {
        backgroundColor: '#d1d5db',
    },
    buttonText: {
        color: '#ffffff',
        fontWeight: '600',
        fontSize: 16,
    },
    disabledButtonText: {
        color: '#9ca3af',
    },
    infoSection: {
        marginBottom: 20,
    },
    infoText: {
        fontSize: 14,
        color: '#6b7280',
        lineHeight: 20,
        backgroundColor: '#ffffff',
        padding: 16,
        borderRadius: 8,
        borderWidth: 1,
        borderColor: '#e5e7eb',
    },
});