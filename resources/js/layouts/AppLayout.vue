<template>
  <div class="h-screen overflow-auto bg-gradient-to-br from-indigo-500 to-purple-600" :style="backgroundStyle">
    <!-- Navigation -->
    <header class="bg-white/95 backdrop-blur-sm shadow-lg px-6 py-4">
      <div class="relative flex items-center">
        <!-- Board picker (top-level board switching) -->
        <div class="flex items-center gap-2">
          <select
            v-if="boards.length"
            :value="activeBoardId"
            @change="onBoardSelect($event)"
            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400"
          >
            <option v-for="b in boards" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
          <button
            @click="startCreateBoard"
            class="text-sm text-indigo-500 hover:text-indigo-700 font-medium px-2 py-1 rounded-lg hover:bg-indigo-50 transition-colors"
            title="New board"
          >
            + New
          </button>
        </div>
        <nav class="absolute left-1/2 -translate-x-1/2 flex items-center gap-2">
          <router-link
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
            :class="$route.path === link.to
              ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md'
              : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-600'"
          >
            {{ link.label }}
          </router-link>
        </nav>
        <div class="ml-auto flex items-center gap-2">
          <router-link
            to="/profile"
            class="text-sm font-medium transition-colors"
            :class="$route.path === '/profile' ? 'text-indigo-600' : 'text-gray-500 hover:text-indigo-600'"
          >
            {{ user?.name }}
          </router-link>
          <button
            @click="logout"
            class="text-sm text-gray-400 hover:text-red-500 transition-colors px-2 py-1"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <!-- Page content -->
    <main class="px-4 pb-4 pt-2">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { useBackground } from '@/composables/useBackground';
import { useBoard } from '@/composables/useBoard';

const { user, logout } = useAuth();
const { backgroundUrl } = useBackground();
const { boards, activeBoardId, loadBoards, setActiveBoard, createBoard } = useBoard();

function onBoardSelect(event) {
  setActiveBoard(Number(event.target.value));
}

async function startCreateBoard() {
  const name = window.prompt('New board name:');
  if (!name || !name.trim()) return;
  await createBoard({ name: name.trim() });
}

onMounted(() => {
  loadBoards();
});

const navLinks = [
  { to: '/', label: 'Timer' },
  { to: '/tasks', label: 'Tasks' },
  { to: '/reports', label: 'Reports' },
  { to: '/settings', label: 'Settings' },
  { to: '/automations', label: 'Automation' },
];

const backgroundStyle = computed(() => {
  if (!backgroundUrl.value) return {};
  return {
    backgroundImage: `url(${backgroundUrl.value})`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    backgroundAttachment: 'fixed',
  };
});


</script>
