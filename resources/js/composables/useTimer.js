import { ref, onUnmounted } from 'vue';
import { useApi } from './useApi';

const runningEntry = ref(null);

export function useTimer() {
    const api = useApi();
    let heartbeatInterval = null;

    async function checkRunning() {
        runningEntry.value = await api.get('/entries/running');
    }

    async function start(data = {}) {
        runningEntry.value = await api.post('/entries/start', data);
        startHeartbeat();
        return runningEntry.value;
    }

    async function stop() {
        await api.post('/entries/stop');
        runningEntry.value = null;
        stopHeartbeat();
    }

    async function stopAt(endTime) {
        await api.post('/entries/stop-at', { end_time: endTime });
        runningEntry.value = null;
        stopHeartbeat();
    }

    async function heartbeat() {
        await api.post('/entries/heartbeat');
    }

    function startHeartbeat() {
        stopHeartbeat();
        heartbeatInterval = setInterval(heartbeat, 60000);
    }

    function stopHeartbeat() {
        if (heartbeatInterval) {
            clearInterval(heartbeatInterval);
            heartbeatInterval = null;
        }
    }

    onUnmounted(() => stopHeartbeat());

    return { runningEntry, checkRunning, start, stop, stopAt, startHeartbeat, stopHeartbeat };
}
