import { ref } from 'vue';
import axios from 'axios';

const backgroundUrl = ref(null);
const loaded = ref(false);
const preloaded = ref(false);

export function useBackground() {
    async function loadBackground() {
        if (loaded.value) return;
        loaded.value = true;
        try {
            const { data } = await axios.get('/settings/background/status');
            if (data.exists) {
                await preloadImage(data.url);
                backgroundUrl.value = data.url;
            }
        } catch {
            // ignore
        }
    }

    function setBackground(url) {
        if (url) {
            preloadImage(url).then(() => {
                backgroundUrl.value = url;
            });
        } else {
            backgroundUrl.value = null;
        }
    }

    function clearCache() {
        loaded.value = false;
        preloaded.value = false;
    }

    function preloadImage(url) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = resolve;
            img.onerror = resolve;
            img.src = url;
        });
    }

    return { backgroundUrl, loadBackground, setBackground, clearCache };
}
