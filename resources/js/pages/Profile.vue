<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6">

      <!-- Account -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Profile</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <div class="text-gray-400 mb-0.5">Name</div>
            <div class="font-medium text-gray-700">{{ user?.name || '—' }}</div>
          </div>
          <div>
            <div class="text-gray-400 mb-0.5">Email</div>
            <div class="font-medium text-gray-700">{{ user?.email || '—' }}</div>
          </div>
        </div>
      </div>

      <!-- MCP Access -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-1">MCP Access</h3>
        <p class="text-sm text-gray-500 mb-4">
          Generate a bearer token to connect an AI agent (e.g. Claude Code) to this app as your account.
        </p>

        <!-- Generate -->
        <div class="flex items-center gap-3 mb-4">
          <input
            v-model="newTokenName"
            type="text"
            placeholder="Token name (e.g. Claude Code)"
            class="flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none"
            @keyup.enter="generateToken"
          />
          <button
            @click="generateToken"
            :disabled="!newTokenName.trim() || generating"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold shadow hover:shadow-lg transition disabled:opacity-50"
          >
            Generate token
          </button>
        </div>

        <!-- Freshly created secret (shown once) -->
        <div v-if="freshToken" class="mb-4 p-4 rounded-xl border border-amber-200 bg-amber-50">
          <p class="text-xs font-medium text-amber-700 mb-2">
            Copy this token now — it won't be shown again.
          </p>
          <div class="flex items-center gap-2">
            <code class="flex-1 text-xs bg-white rounded-lg border border-amber-200 px-3 py-2 break-all">{{ freshToken }}</code>
            <button
              @click="copy(freshToken)"
              class="px-3 py-2 text-xs font-medium bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition shrink-0"
            >
              {{ copiedText === freshToken ? 'Copied' : 'Copy' }}
            </button>
          </div>
        </div>

        <!-- Token list -->
        <div v-if="tokens.length" class="space-y-2">
          <div
            v-for="t in tokens"
            :key="t.id"
            class="flex items-center justify-between px-4 py-2.5 rounded-xl bg-gray-50"
          >
            <div class="min-w-0">
              <div class="text-sm font-medium text-gray-700 truncate">{{ t.name }}</div>
              <div class="text-xs text-gray-400">
                Last used: {{ t.last_used_at ? formatDate(t.last_used_at) : 'never' }}
                · Created: {{ formatDate(t.created_at) }}
              </div>
            </div>
            <button
              @click="revokeToken(t)"
              class="text-xs font-medium text-red-500 border border-red-200 rounded-lg px-3 py-1.5 hover:bg-red-50 transition shrink-0"
            >
              Revoke
            </button>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">No tokens yet.</p>
      </div>

      <!-- Connection info -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Connect a client</h3>
        <div class="space-y-3 text-sm">
          <div>
            <div class="text-gray-400 mb-1">MCP server URL</div>
            <div class="flex items-center gap-2">
              <code class="flex-1 text-xs bg-gray-50 rounded-lg border border-gray-200 px-3 py-2 break-all">{{ serverUrl }}</code>
              <button @click="copy(serverUrl)" class="px-3 py-2 text-xs font-medium bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition shrink-0">
                {{ copiedText === serverUrl ? 'Copied' : 'Copy' }}
              </button>
            </div>
          </div>
          <div>
            <div class="text-gray-400 mb-1">Claude Code</div>
            <div class="flex items-center gap-2">
              <code class="flex-1 text-xs bg-gray-50 rounded-lg border border-gray-200 px-3 py-2 break-all">{{ claudeSnippet }}</code>
              <button @click="copy(claudeSnippet)" class="px-3 py-2 text-xs font-medium bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition shrink-0">
                {{ copiedText === claudeSnippet ? 'Copied' : 'Copy' }}
              </button>
            </div>
            <p class="text-[11px] text-gray-400 mt-1">Replace &lt;token&gt; with a generated token above.</p>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuth } from '@/composables/useAuth';
import { useApi } from '@/composables/useApi';

const { user, fetchUser } = useAuth();
const api = useApi();

const tokens = ref([]);
const newTokenName = ref('');
const freshToken = ref('');
const generating = ref(false);
const copiedText = ref('');

const serverUrl = computed(() => `${window.location.origin}/api/mcp`);
const claudeSnippet = computed(
  () => `claude mcp add --transport http timetracking ${serverUrl.value} --header "Authorization: Bearer <token>"`
);

async function loadTokens() {
  try {
    tokens.value = await api.get('/tokens');
  } catch (e) {
    console.error('Failed to load tokens', e);
  }
}

async function generateToken() {
  if (!newTokenName.value.trim()) return;
  generating.value = true;
  try {
    const data = await api.post('/tokens', { name: newTokenName.value.trim() });
    freshToken.value = data.plain_text_token;
    newTokenName.value = '';
    await loadTokens();
  } catch (e) {
    console.error('Failed to generate token', e);
  } finally {
    generating.value = false;
  }
}

async function revokeToken(token) {
  if (!confirm(`Revoke token "${token.name}"? Any client using it will lose access.`)) return;
  try {
    await api.del(`/tokens/${token.id}`);
    if (freshToken.value && token.id) freshToken.value = '';
    await loadTokens();
  } catch (e) {
    console.error('Failed to revoke token', e);
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

function formatDate(dt) {
  if (!dt) return '';
  return new Date(dt).toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric' });
}

onMounted(async () => {
  if (!user.value) await fetchUser();
  await loadTokens();
});
</script>
