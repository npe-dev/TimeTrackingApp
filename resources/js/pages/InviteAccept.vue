<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md text-center">
      <h1 class="text-2xl font-bold mb-2 bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent">
        Board invitation
      </h1>

      <!-- Loading -->
      <div v-if="state === 'loading'" class="text-gray-500 mt-6">
        Accepting your invitation…
      </div>

      <!-- Success -->
      <div v-else-if="state === 'success'" class="mt-6">
        <p class="text-gray-700 mb-6">
          You now have access to <strong>{{ details.board_name }}</strong>.
        </p>
        <button
          @click="goToBoard"
          class="w-full py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all"
        >
          Open board
        </button>
      </div>

      <!-- Error -->
      <div v-else class="mt-6">
        <div class="bg-red-50 text-red-600 rounded-xl px-4 py-3 mb-6 text-sm">
          {{ error }}
        </div>
        <router-link
          to="/tasks"
          class="inline-block w-full py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all"
        >
          Go to my boards
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useApi } from '@/composables/useApi';
import { useBoard } from '@/composables/useBoard';

const route = useRoute();
const router = useRouter();
const api = useApi();
const { loadBoards, setActiveBoard } = useBoard();

const state = ref('loading'); // loading | success | error
const error = ref('');
const details = reactive({ board_name: '', board_id: null });

const token = route.params.token;

onMounted(async () => {
  try {
    // Fetch details first so we can show the board name and give a precise
    // message if the invitation can't be accepted.
    const info = await api.get(`/invitations/${token}`);
    details.board_name = info.board_name;

    if (info.expired) {
      state.value = 'error';
      error.value = 'This invitation has expired. Ask the board owner to send a new one.';
      return;
    }

    const board = await api.post(`/invitations/${token}/accept`);
    details.board_id = board.id;
    details.board_name = board.name;

    // The shared board is now accessible — refresh the list and switch to it.
    await loadBoards();
    setActiveBoard(board.id);
    state.value = 'success';
  } catch (e) {
    state.value = 'error';
    const status = e.response?.status;
    if (status === 403) {
      error.value = e.response?.data?.message
        || 'This invitation was sent to a different email address. Sign in with that account to accept it.';
    } else if (status === 404) {
      error.value = 'This invitation link is not valid.';
    } else {
      error.value = e.response?.data?.message || 'Could not accept the invitation.';
    }
  }
});

function goToBoard() {
  if (details.board_id) setActiveBoard(details.board_id);
  router.push({ name: 'tasks' });
}
</script>
