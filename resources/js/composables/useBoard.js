import { ref } from 'vue';
import { useApi } from './useApi';
import { useBackground } from './useBackground';

// Shared, app-wide "active board" state. Boards are the top-level concept: every
// page (Tasks, Timer, Reports, Automations, Settings) reads activeBoardId and loads
// only that board's data. Seeded synchronously from localStorage so the first render
// already knows the board.
const STORAGE_KEY = 'activeBoardId';

const boards = ref([]);
const activeBoardId = ref(
    localStorage.getItem(STORAGE_KEY) ? Number(localStorage.getItem(STORAGE_KEY)) : null
);

export function useBoard() {
    const api = useApi();
    const { loadBoardBackground } = useBackground();

    async function loadBoards() {
        boards.value = await api.get('/boards');
        // Ensure a valid active board: keep the stored one if it still exists,
        // otherwise fall back to the first board.
        const exists = boards.value.some(b => b.id === activeBoardId.value);
        if (!exists) {
            setActiveBoard(boards.value.length ? boards.value[0].id : null);
        } else {
            loadBoardBackground(activeBoardId.value);
        }
        return boards.value;
    }

    function setActiveBoard(id) {
        activeBoardId.value = id;
        if (id) localStorage.setItem(STORAGE_KEY, id);
        else localStorage.removeItem(STORAGE_KEY);
        loadBoardBackground(id);
    }

    async function createBoard(data) {
        const board = await api.post('/boards', data);
        boards.value.push(board);
        setActiveBoard(board.id);
        return board;
    }

    async function updateBoard(id, data) {
        const updated = await api.put(`/boards/${id}`, data);
        const idx = boards.value.findIndex(b => b.id === id);
        if (idx >= 0) boards.value[idx] = { ...boards.value[idx], ...updated };
        return updated;
    }

    async function deleteBoard(id) {
        await api.del(`/boards/${id}`);
        boards.value = boards.value.filter(b => b.id !== id);
        if (activeBoardId.value === id) {
            setActiveBoard(boards.value.length ? boards.value[0].id : null);
        }
    }

    function activeBoard() {
        return boards.value.find(b => b.id === activeBoardId.value) || null;
    }

    return {
        boards,
        activeBoardId,
        loadBoards,
        setActiveBoard,
        createBoard,
        updateBoard,
        deleteBoard,
        activeBoard,
    };
}
