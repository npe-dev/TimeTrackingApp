<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
      <h1 class="text-3xl font-bold text-center mb-2 bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent">
        Time Tracking
      </h1>
      <p class="text-center text-gray-500 mb-8">Create your account</p>

      <div v-if="error" class="bg-red-50 text-red-600 rounded-xl px-4 py-3 mb-6 text-sm">
        {{ error }}
      </div>

      <form @submit.prevent="handleRegister" class="space-y-5">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Name</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-0 outline-none transition-colors"
            placeholder="Your name"
          />
        </div>

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
            placeholder="Min 8 characters"
          />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm Password</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-0 outline-none transition-colors"
            placeholder="Repeat password"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
        >
          {{ loading ? 'Creating account...' : 'Create Account' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Already have an account?
        <router-link to="/login" class="text-indigo-600 font-semibold hover:underline">Sign in</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { register } = useAuth();

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const error = ref('');
const loading = ref(false);

async function handleRegister() {
  error.value = '';
  loading.value = true;
  try {
    await register(form);
  } catch (e) {
    const errors = e.response?.data?.errors;
    if (errors) {
      error.value = Object.values(errors).flat().join(' ');
    } else {
      error.value = e.response?.data?.message || 'Registration failed';
    }
  }
  loading.value = false;
}
</script>
