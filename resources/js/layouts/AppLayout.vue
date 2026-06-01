<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-500 to-purple-600" :style="backgroundStyle">
    <!-- Navigation -->
    <header class="bg-white/95 backdrop-blur-sm mx-4 mt-4 rounded-2xl shadow-lg px-6 py-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent">
          Time Tracking
        </h1>
        <nav class="flex items-center gap-2">
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
          <span class="ml-4 text-sm text-gray-500 font-medium">{{ user?.name }}</span>
          <button
            @click="logout"
            class="text-sm text-gray-400 hover:text-red-500 transition-colors px-2 py-1"
          >
            Logout
          </button>
        </nav>
      </div>
    </header>

    <!-- Page content -->
    <main class="p-4">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuth } from '@/composables/useAuth';
import axios from 'axios';

const { user, logout } = useAuth();

const backgroundUrl = ref(null);

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

onMounted(async () => {
  try {
    const { data } = await axios.get('/settings/background/status');
    if (data.exists) {
      backgroundUrl.value = data.url;
    }
  } catch {
    // ignore
  }
});
</script>
