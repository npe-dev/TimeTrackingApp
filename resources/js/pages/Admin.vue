<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-6">

      <!-- Invite a user -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-1">Invite a user</h3>
        <p class="text-sm text-gray-500 mb-4">
          Creates a member account with a temporary password. The credentials are
          emailed to them and shown here once so you can share them directly.
        </p>

        <form @submit.prevent="invite" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <input
            v-model="form.name"
            type="text"
            placeholder="Name"
            class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none"
          />
          <input
            v-model="form.email"
            type="email"
            placeholder="Email"
            class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none"
          />
          <div class="sm:col-span-2 flex items-center gap-2">
            <input
              v-model="form.password"
              type="text"
              placeholder="Temporary password (leave blank to auto-generate)"
              class="flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none"
            />
            <button
              type="button"
              @click="generatePassword"
              class="px-3 py-2.5 text-xs font-medium bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition shrink-0"
            >
              Generate
            </button>
          </div>
          <div class="sm:col-span-2 flex items-center gap-3">
            <button
              type="submit"
              :disabled="!form.name.trim() || !form.email.trim() || inviting"
              class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold shadow hover:shadow-lg transition disabled:opacity-50"
            >
              {{ inviting ? 'Inviting…' : 'Send invite' }}
            </button>
            <span v-if="inviteError" class="text-sm text-red-500">{{ inviteError }}</span>
          </div>
        </form>

        <!-- Freshly created credentials (shown once) -->
        <div v-if="created" class="mt-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50">
          <p class="text-xs font-medium text-emerald-700 mb-2">
            Account created for {{ created.user.name }}.
            {{ created.email_sent ? 'An invite email was sent.' : 'Email could not be sent — share these credentials manually.' }}
          </p>
          <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2">
              <span class="w-20 text-gray-500 shrink-0">Email</span>
              <code class="flex-1 text-xs bg-white rounded-lg border border-emerald-200 px-3 py-2 break-all">{{ created.user.email }}</code>
              <button @click="copy(created.user.email)" class="px-3 py-2 text-xs font-medium bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition shrink-0">
                {{ copiedText === created.user.email ? 'Copied' : 'Copy' }}
              </button>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-20 text-gray-500 shrink-0">Password</span>
              <code class="flex-1 text-xs bg-white rounded-lg border border-emerald-200 px-3 py-2 break-all">{{ created.temporary_password }}</code>
              <button @click="copy(created.temporary_password)" class="px-3 py-2 text-xs font-medium bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition shrink-0">
                {{ copiedText === created.temporary_password ? 'Copied' : 'Copy' }}
              </button>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-20 text-gray-500 shrink-0">Login URL</span>
              <code class="flex-1 text-xs bg-white rounded-lg border border-emerald-200 px-3 py-2 break-all">{{ created.login_url }}</code>
              <button @click="copy(created.login_url)" class="px-3 py-2 text-xs font-medium bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition shrink-0">
                {{ copiedText === created.login_url ? 'Copied' : 'Copy' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-xl font-bold text-gray-800">Users</h2>
            <p class="text-sm text-gray-500">All accounts in the app and when they last signed in.</p>
          </div>
          <span class="text-sm text-gray-400">{{ users.length }} user{{ users.length === 1 ? '' : 's' }}</span>
        </div>

        <div v-if="loading" class="py-10 text-center text-sm text-gray-400">Loading…</div>
        <div v-else-if="error" class="py-10 text-center text-sm text-red-500">{{ error }}</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-100">
                <th class="py-2 pr-4 font-medium">Name</th>
                <th class="py-2 pr-4 font-medium">Email</th>
                <th class="py-2 pr-4 font-medium">Role</th>
                <th class="py-2 pr-4 font-medium">Last login</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="u in users"
                :key="u.id"
                class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors"
              >
                <td class="py-2.5 pr-4 font-medium text-gray-700">{{ u.name }}</td>
                <td class="py-2.5 pr-4 text-gray-500">{{ u.email }}</td>
                <td class="py-2.5 pr-4">
                  <span
                    v-if="u.is_admin"
                    class="inline-block rounded-full bg-indigo-100 text-indigo-700 px-2 py-0.5 text-xs font-medium"
                  >Admin</span>
                  <span v-else class="text-gray-400 text-xs">Member</span>
                </td>
                <td class="py-2.5 pr-4 text-gray-500">
                  <span :title="u.last_login_at ? formatFull(u.last_login_at) : ''">
                    {{ u.last_login_at ? formatRelative(u.last_login_at) : 'Never' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useApi } from '@/composables/useApi';

const api = useApi();

const users = ref([]);
const loading = ref(true);
const error = ref('');

const form = reactive({ name: '', email: '', password: '' });
const inviting = ref(false);
const inviteError = ref('');
const created = ref(null);
const copiedText = ref('');

function generatePassword() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
  let out = '';
  const rnd = new Uint32Array(14);
  crypto.getRandomValues(rnd);
  for (let i = 0; i < 14; i++) out += chars[rnd[i] % chars.length];
  form.password = out;
}

async function invite() {
  if (!form.name.trim() || !form.email.trim()) return;
  inviting.value = true;
  inviteError.value = '';
  try {
    created.value = await api.post('/admin/users', {
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password.trim() || undefined,
    });
    form.name = '';
    form.email = '';
    form.password = '';
    await loadUsers();
  } catch (e) {
    const errors = e?.response?.data?.errors;
    inviteError.value = errors
      ? Object.values(errors).flat().join(' ')
      : (e?.response?.data?.message || 'Failed to invite user.');
  } finally {
    inviting.value = false;
  }
}

async function copy(text) {
  try {
    await navigator.clipboard.writeText(text);
    copiedText.value = text;
    setTimeout(() => { if (copiedText.value === text) copiedText.value = ''; }, 1500);
  } catch (e) {
    console.error('Copy failed', e);
  }
}

async function loadUsers() {
  loading.value = true;
  error.value = '';
  try {
    users.value = await api.get('/admin/users');
  } catch (e) {
    error.value = e?.response?.status === 403
      ? 'You do not have access to this page.'
      : 'Failed to load users.';
  } finally {
    loading.value = false;
  }
}

function formatFull(dt) {
  return new Date(dt).toLocaleString([], {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function formatRelative(dt) {
  const then = new Date(dt).getTime();
  const diffSec = Math.round((Date.now() - then) / 1000);
  if (diffSec < 60) return 'Just now';
  const diffMin = Math.round(diffSec / 60);
  if (diffMin < 60) return `${diffMin}m ago`;
  const diffHr = Math.round(diffMin / 60);
  if (diffHr < 24) return `${diffHr}h ago`;
  const diffDay = Math.round(diffHr / 24);
  if (diffDay < 30) return `${diffDay}d ago`;
  return formatFull(dt);
}

onMounted(loadUsers);
</script>
