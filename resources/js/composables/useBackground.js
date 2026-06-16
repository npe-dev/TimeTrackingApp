import { ref } from 'vue';
import axios from 'axios';

const CACHE_KEY = 'board_bg_';

// Seed synchronously from localStorage so AppLayout renders with the correct
// background on the very first paint — no async round-trip, no flicker.
const cachedBoardId = sessionStorage.getItem('selectedBoardId');
const cachedUrl = cachedBoardId ? localStorage.getItem(CACHE_KEY + cachedBoardId) : null;
const backgroundUrl = ref(cachedUrl);
let loadedBoardId = null;

export function useBackground() {
    async function loadBoardBackground(boardId) {
        if (!boardId) {
            backgroundUrl.value = null;
            loadedBoardId = null;
            return;
        }
        if (loadedBoardId === boardId) return;
        loadedBoardId = boardId;

        // Show cached value immediately while API confirms
        const cached = localStorage.getItem(CACHE_KEY + boardId);
        if (cached && backgroundUrl.value !== cached) {
            backgroundUrl.value = cached;
        }

        try {
            const { data } = await axios.get(`/boards/${boardId}/background/status`);
            if (data.exists) {
                // Strip timestamp — store a stable URL; browser cache handles the image
                const stableUrl = data.url.split('?')[0];
                localStorage.setItem(CACHE_KEY + boardId, stableUrl);
                backgroundUrl.value = stableUrl;
            } else {
                backgroundUrl.value = null;
                localStorage.removeItem(CACHE_KEY + boardId);
            }
        } catch {
            // keep whatever is already showing
        }
    }

    function setBackground(url) {
        if (url) {
            const stableUrl = url.split('?')[0];
            backgroundUrl.value = stableUrl;
            if (loadedBoardId) localStorage.setItem(CACHE_KEY + loadedBoardId, stableUrl);
        } else {
            backgroundUrl.value = null;
            if (loadedBoardId) localStorage.removeItem(CACHE_KEY + loadedBoardId);
        }
    }

    function clearBoardCache(boardId) {
        if (loadedBoardId === boardId) loadedBoardId = null;
        backgroundUrl.value = null;
        localStorage.removeItem(CACHE_KEY + boardId);
    }

    return { backgroundUrl, loadBoardBackground, setBackground, clearBoardCache };
}
