<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto">
      <div class="bg-neutral-900/55 backdrop-blur-md border border-white/10 rounded-2xl shadow-lg p-8 text-center">
        <h2 class="text-2xl font-bold text-gray-100 mb-2">Welcome, {{ user?.name }}!</h2>
        <p class="text-gray-400">Your time tracking dashboard will be here.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuth } from '@/composables/useAuth';
import { onMounted } from 'vue';

const { user, fetchUser } = useAuth();

onMounted(() => {
  fetchUser();
});
</script>
