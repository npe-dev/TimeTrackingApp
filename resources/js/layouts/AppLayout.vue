<template>
  <div class="h-screen overflow-auto bg-gradient-to-br from-indigo-500 to-purple-600" :style="backgroundStyle">
    <!-- Navigation -->
    <header class="relative z-40 bg-white/95 backdrop-blur-sm shadow-lg px-6 py-4">
      <div class="relative flex items-center">
        <!-- Board picker (top-level board switching) -->
        <div class="flex items-center gap-2">
          <div v-if="boards.length" class="relative">
            <button
              @click="boardMenuOpen = !boardMenuOpen"
              class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-colors"
            >
              <span class="max-w-[12rem] truncate">{{ activeBoardName }}</span>
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Backdrop closes the menu on outside click -->
            <div v-if="boardMenuOpen" class="fixed inset-0 z-40" @click="boardMenuOpen = false"></div>

            <!-- Menu (always opens downward) -->
            <div
              v-if="boardMenuOpen"
              class="absolute left-0 top-full mt-1 z-50 min-w-[14rem] max-h-72 overflow-y-auto rounded-xl bg-white shadow-xl border border-gray-100 py-1"
            >
              <button
                v-for="b in boards"
                :key="b.id"
                @click="selectBoard(b.id)"
                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-left hover:bg-indigo-50 transition-colors"
                :class="b.id === activeBoardId ? 'text-indigo-600 font-medium' : 'text-gray-700'"
              >
                <span class="truncate">{{ b.name }}</span>
                <svg v-if="b.id === activeBoardId" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>
          </div>
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
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { useBackground } from '@/composables/useBackground';
import { useBoard } from '@/composables/useBoard';

const { user, logout } = useAuth();
const { backgroundUrl } = useBackground();
const { boards, activeBoardId, loadBoards, setActiveBoard, createBoard } = useBoard();

const boardMenuOpen = ref(false);

const activeBoardName = computed(
  () => boards.value.find(b => b.id === activeBoardId.value)?.name || 'Select board'
);

function selectBoard(id) {
  setActiveBoard(id);
  boardMenuOpen.value = false;
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
