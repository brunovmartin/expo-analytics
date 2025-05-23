import ExpoAnalytics, { StartOptions } from 'expo-analytics';
import { useEffect, useState } from 'react';
import { Text, View, Button, StyleSheet, Alert } from 'react-native';

export default function App() {
    const [isRecording, setIsRecording] = useState(false);

    const startAnalytics = async () => {
        try {
            const options: StartOptions = {
                apiHost: 'http://localhost:8080',
                userId: 'user123',
                framerate: 30,
                userData: {
                    appVersion: '1.0.0',
                    userType: 'premium'
                }
            };

            await ExpoAnalytics.start(options);
            setIsRecording(true);
            Alert.alert('Sucesso', 'Analytics iniciado com sucesso!');
        } catch (error) {
            Alert.alert('Erro', 'Falha ao iniciar analytics: ' + error);
        }
    };

    const stopAnalytics = async () => {
        try {
            await ExpoAnalytics.stop();
            setIsRecording(false);
            Alert.alert('Sucesso', 'Analytics parado com sucesso!');
        } catch (error) {
            Alert.alert('Erro', 'Falha ao parar analytics: ' + error);
        }
    };

    const trackCustomEvent = async () => {
        try {
            await ExpoAnalytics.trackEvent('button_pressed', 'track_event_button');
            Alert.alert('Sucesso', 'Evento rastreado com sucesso!');
        } catch (error) {
            Alert.alert('Erro', 'Falha ao rastrear evento: ' + error);
        }
    };

    const updateUserInfo = async () => {
        try {
            await ExpoAnalytics.updateUserInfo({
                lastActivity: new Date().toISOString(),
                sessionCount: 1
            });
            Alert.alert('Sucesso', 'Informações do usuário atualizadas!');
        } catch (error) {
            Alert.alert('Erro', 'Falha ao atualizar informações: ' + error);
        }
    };

    return (
        <View style={styles.container}>
            <Text style={styles.title}>Expo Analytics Demo</Text>

            <Text style={styles.status}>
                Status: {isRecording ? 'Gravando' : 'Parado'}
            </Text>

            <View style={styles.buttonContainer}>
                <Button
                    title={isRecording ? "Parar Analytics" : "Iniciar Analytics"}
                    onPress={isRecording ? stopAnalytics : startAnalytics}
                    color={isRecording ? '#ff4444' : '#4CAF50'}
                />
            </View>

            <View style={styles.buttonContainer}>
                <Button
                    title="Rastrear Evento"
                    onPress={trackCustomEvent}
                    disabled={!isRecording}
                />
            </View>

            <View style={styles.buttonContainer}>
                <Button
                    title="Atualizar User Info"
                    onPress={updateUserInfo}
                    disabled={!isRecording}
                />
            </View>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        alignItems: 'center',
        justifyContent: 'center',
        padding: 20,
        backgroundColor: '#f5f5f5',
    },
    title: {
        fontSize: 24,
        fontWeight: 'bold',
        marginBottom: 20,
        color: '#333',
    },
    status: {
        fontSize: 18,
        marginBottom: 30,
        color: '#666',
    },
    buttonContainer: {
        marginVertical: 10,
        minWidth: 200,
    },
});