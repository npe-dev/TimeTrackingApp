<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
      <h1 class="text-3xl font-bold text-center mb-2 bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent">
        Time Tracking
      </h1>
      <p class="text-center text-gray-500 mb-8">Sign in to your account</p>

      <div v-if="error" class="bg-red-50 text-red-600 rounded-xl px-4 py-3 mb-6 text-sm">
        {{ error }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-0 outline-none transition-colors"
            placeholder="you@example.com"
          />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-0 outline-none transition-colors"
            placeholder="Your password"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
        >
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Don't have an account?
        <router-link to="/register" class="text-indigo-600 font-semibold hover:underline">Register</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { login } = useAuth();

const form = reactive({ email: '', password: '' });
const error = ref('');
const loading = ref(false);

async function handleLogin() {
  error.value = '';
  loading.value = true;
  try {
    await login(form);
  } catch (e) {
    error.value = e.response?.data?.message || 'Invalid credentials';
  }
  loading.value = false;
}
</script>
