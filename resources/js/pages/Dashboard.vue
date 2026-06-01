<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto">
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-8 text-center">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome, {{ user?.name }}!</h2>
        <p class="text-gray-500">Your time tracking dashboard will be here.</p>
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
