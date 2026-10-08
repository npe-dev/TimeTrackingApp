<template>
  <div class="h-screen overflow-auto bg-gradient-to-br from-indigo-500 to-purple-600 text-gray-100 [color-scheme:dark]" :style="backgroundStyle">
    <!-- Navigation -->
    <header class="relative z-40 bg-neutral-900/55 backdrop-blur-md border-b border-white/10 shadow-lg px-4 sm:px-6 py-3 sm:py-4">
      <div class="relative flex items-center gap-2">
        <!-- Board picker (top-level board switching) -->
        <div class="flex items-center gap-1 sm:gap-2 min-w-0">
          <div v-if="boards.length" class="relative min-w-0">
            <button
              @click="boardMenuOpen = !boardMenuOpen"
              class="flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/10 px-2.5 sm:px-3 py-2 text-sm font-medium text-gray-100 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-colors max-w-full"
            >
              <span class="max-w-[8rem] sm:max-w-[12rem] truncate">{{ activeBoardName }}</span>
              <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Backdrop closes the menu on outside click -->
            <div v-if="boardMenuOpen" class="fixed inset-0 z-40" @click="boardMenuOpen = false"></div>

            <!-- Menu (always opens downward) -->
            <div
              v-if="boardMenuOpen"
              class="absolute left-0 top-full mt-1 z-50 min-w-[12rem] sm:min-w-[14rem] max-w-[calc(100vw-2rem)] max-h-72 overflow-y-auto rounded-xl bg-neutral-800/95 backdrop-blur-md shadow-xl border border-white/10 py-1"
            >
              <button
                v-for="b in boards"
                :key="b.id"
                @click="selectBoard(b.id)"
                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-left hover:bg-white/10 transition-colors"
                :class="b.id === activeBoardId ? 'text-indigo-300 font-medium' : 'text-gray-200'"
              >
                <span class="truncate flex items-center gap-1.5">
                  {{ b.name }}
                  <span v-if="!isOwned(b)" class="shrink-0 text-[10px] uppercase tracking-wide text-purple-200 bg-purple-500/25 rounded px-1 py-0.5">Shared</span>
                </span>
                <svg v-if="b.id === activeBoardId" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>
          </div>
          <button
            @click="startCreateBoard"
            class="shrink-0 text-sm text-gray-200 hover:text-white font-medium px-2 py-1 rounded-lg hover:bg-white/10 transition-colors"
            title="New board"
          >
            + New
          </button>
          <button
            v-if="isOwnerActive"
            @click="shareModalOpen = true"
            class="shrink-0 text-sm text-gray-200 hover:text-white font-medium px-2 py-1 rounded-lg hover:bg-white/10 transition-colors"
            title="Share board"
          >
            Share
          </button>
        </div>

        <!-- Desktop nav (centered) -->
        <nav class="hidden md:flex absolute left-1/2 -translate-x-1/2 items-center gap-2">
          <router-link
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
            :class="$route.path === link.to
              ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md'
              : 'text-gray-300 hover:bg-white/10 hover:text-white'"
          >
            {{ link.label }}
          </router-link>
        </nav>

        <!-- Page-specific header actions (e.g. board filters on Tasks) -->
        <div class="ml-auto flex items-center gap-2 shrink-0">
          <slot name="header-actions" />
        </div>

        <!-- Desktop user actions -->
        <div class="hidden md:flex items-center gap-2">
          <div class="relative">
            <button
              @click="userMenuOpen = !userMenuOpen"
              class="flex items-center gap-1 text-sm font-medium px-2 py-1 rounded-lg transition-colors"
              :class="userMenuOpen || isUserMenuRoute ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'"
              :aria-expanded="userMenuOpen"
            >
              {{ user?.name }}
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Backdrop closes the menu on outside click -->
            <div v-if="userMenuOpen" class="fixed inset-0 z-40" @click="userMenuOpen = false"></div>

            <div
              v-if="userMenuOpen"
              class="absolute right-0 top-full mt-1 z-50 min-w-[10rem] rounded-xl bg-neutral-800/95 backdrop-blur-md shadow-xl border border-white/10 py-1"
            >
              <router-link
                v-for="link in userMenuLinks"
                :key="link.to"
                :to="link.to"
                @click="userMenuOpen = false"
                class="block px-3 py-2 text-sm hover:bg-white/10 transition-colors"
                :class="$route.path === link.to ? 'text-indigo-300 font-medium' : 'text-gray-200'"
              >
                {{ link.label }}
              </router-link>
            </div>
          </div>
          <button
            @click="logout"
            class="text-sm text-gray-400 hover:text-red-400 transition-colors px-2 py-1"
          >
            Logout
          </button>
        </div>

        <!-- Mobile hamburger -->
        <button
          @click="mobileMenuOpen = !mobileMenuOpen"
          class="md:hidden shrink-0 p-2 -mr-1 rounded-lg text-gray-300 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-colors"
          :aria-expanded="mobileMenuOpen"
          aria-label="Toggle navigation menu"
        >
          <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Mobile menu panel -->
      <div v-if="mobileMenuOpen" class="md:hidden fixed inset-0 top-0 z-30" @click="mobileMenuOpen = false"></div>
      <nav
        v-if="mobileMenuOpen"
        class="md:hidden relative z-40 mt-3 pt-3 border-t border-white/10 flex flex-col gap-1"
      >
        <router-link
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          @click="mobileMenuOpen = false"
          class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all"
          :class="$route.path === link.to
            ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md'
            : 'text-gray-300 hover:bg-white/10 hover:text-white'"
        >
          {{ link.label }}
        </router-link>
        <div class="mt-1 pt-2 border-t border-white/10 flex flex-col gap-1">
          <div class="px-4 pt-1 pb-0.5 text-xs uppercase tracking-wide text-gray-400">{{ user?.name }}</div>
          <router-link
            v-for="link in userMenuLinks"
            :key="link.to"
            :to="link.to"
            @click="mobileMenuOpen = false"
            class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all"
            :class="$route.path === link.to
              ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md'
              : 'text-gray-300 hover:bg-white/10 hover:text-white'"
          >
            {{ link.label }}
          </router-link>
          <button
            @click="logout"
            class="text-left text-sm text-gray-400 hover:text-red-400 transition-colors px-4 py-2.5"
          >
            Logout
          </button>
        </div>
      </nav>
    </header>

    <!-- Page content -->
    <main class="px-4 pb-4 pt-2">
      <slot />
    </main>

    <ShareBoardModal
      v-if="shareModalOpen && activeBoardId"
      :board-id="activeBoardId"
      :board-name="activeBoardName"
      @close="shareModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useBackground } from '@/composables/useBackground';
import { useBoard } from '@/composables/useBoard';
import ShareBoardModal from '@/components/ShareBoardModal.vue';

const { user, fetchUser, logout } = useAuth();
const { backgroundUrl } = useBackground();
const { boards, activeBoardId, loadBoards, setActiveBoard, createBoard } = useBoard();

const route = useRoute();

const boardMenuOpen = ref(false);
const mobileMenuOpen = ref(false);
const userMenuOpen = ref(false);
const shareModalOpen = ref(false);

const activeBoardName = computed(
  () => boards.value.find(b => b.id === activeBoardId.value)?.name || 'Select board'
);

// A board the current user owns (vs one shared with them as a member).
function isOwned(board) {
  return !!board && !!user.value && board.user_id === user.value.id;
}

const isOwnerActive = computed(
  () => isOwned(boards.value.find(b => b.id === activeBoardId.value))
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
  // Ensure the authenticated user is loaded so the header (name) and the
  // admin-only nav link render on a fresh page load, not just after a page
  // that fetches the user itself.
  if (!user.value) fetchUser().catch(() => {});

  // A transient network blip (server restart, or an in-flight request aborted
  // by a reload/navigation) must not surface as an unhandled rejection — it
  // retries on the next navigation anyway. The header just shows "Select board".
  loadBoards().catch(() => {});
});

const navLinks = computed(() => {
  const links = [
    { to: '/tasks', label: 'Tasks' },
    { to: '/timer', label: 'Timer' },
    { to: '/reports', label: 'Reports' },
  ];
  // Automations is owner-only; hide it when the active board is one shared
  // with the user (it's gated server-side too).
  if (isOwnerActive.value) {
    links.push({ to: '/automations', label: 'Automation' });
  }
  return links;
});

// Items in the user-name dropdown (top right).
const userMenuLinks = computed(() => {
  const links = [{ to: '/profile', label: 'Profile' }];
  // Settings is owner-only, same as Automations.
  if (isOwnerActive.value) {
    links.push({ to: '/settings', label: 'Settings' });
  }
  if (user.value?.is_admin) {
    links.push({ to: '/admin', label: 'Admin' });
  }
  return links;
});

const isUserMenuRoute = computed(() => userMenuLinks.value.some(l => l.to === route.path));

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
