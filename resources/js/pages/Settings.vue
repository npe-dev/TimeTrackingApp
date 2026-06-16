<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-3">Settings</h2>
        <p class="text-sm text-gray-500">
          Labels and background images are now configured per board.
          Go to the <router-link to="/tasks" class="text-indigo-500 hover:underline font-medium">Tasks</router-link>
          page, then use the <strong>Labels</strong> button or the <strong>Edit board</strong> dialog to manage them.
        </p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
</script>
