import { ref } from 'vue';
import axios from 'axios';

const CACHE_KEY = 'board_bg_';

// Seed synchronously from localStorage so AppLayout renders with the correct
// background on the very first paint — no async round-trip, no flicker.
// The active board id is persisted by useBoard under 'activeBoardId'.
const cachedBoardId = localStorage.getItem('activeBoardId');
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
                // Keep the ?v=<mtime> token: it stays the same while the image is
                // unchanged (browser cache hit) and changes on re-upload (busts cache).
                localStorage.setItem(CACHE_KEY + boardId, data.url);
                backgroundUrl.value = data.url;
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
            backgroundUrl.value = url;
            if (loadedBoardId) localStorage.setItem(CACHE_KEY + loadedBoardId, url);
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
