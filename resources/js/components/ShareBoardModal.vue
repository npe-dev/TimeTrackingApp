<template>
  <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>

    <div class="relative bg-neutral-900/90 backdrop-blur-xl text-gray-100 [color-scheme:dark] border border-white/10 rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
        <h2 class="text-lg font-semibold text-gray-100">
          Share &ldquo;{{ boardName }}&rdquo;
        </h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-200 p-1 rounded-lg">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="px-6 py-5">
        <!-- Invite form -->
        <form @submit.prevent="invite" class="flex items-start gap-2">
          <div class="flex-1">
            <input
              v-model="email"
              type="email"
              required
              placeholder="teammate@example.com"
              class="w-full px-4 py-2.5 border-2 border-white/10 rounded-xl focus:border-indigo-500 focus:ring-0 outline-none transition-colors text-sm"
            />
          </div>
          <button
            type="submit"
            :disabled="inviting"
            class="shrink-0 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-xl shadow hover:shadow-md transition-all disabled:opacity-50 text-sm"
          >
            {{ inviting ? 'Inviting…' : 'Invite' }}
          </button>
        </form>

        <p v-if="formError" class="mt-2 text-sm text-red-300">{{ formError }}</p>

        <!-- Result banner (temp password / manual link when mail didn't send) -->
        <div v-if="lastInvite" class="mt-3 rounded-xl bg-indigo-500/15 border border-indigo-400/30 px-4 py-3 text-sm text-gray-200">
          <p v-if="lastInvite.email_sent" class="text-green-300">
            Invitation email sent to {{ lastInvite.email }}.
          </p>
          <p v-else class="text-amber-300 font-medium">
            Email could not be sent — share this link manually:
          </p>
          <p class="mt-1 break-all font-mono text-xs text-gray-300">{{ lastInvite.accept_url }}</p>
          <p v-if="lastInvite.temporary_password" class="mt-2">
            New account created. Temporary password:
            <span class="font-mono font-semibold">{{ lastInvite.temporary_password }}</span>
          </p>
        </div>

        <!-- Members -->
        <div class="mt-6">
          <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">People with access</h3>
          <div class="space-y-1.5">
            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-white/5">
              <div class="min-w-0">
                <div class="text-sm font-medium text-gray-100 truncate">{{ owner.name || owner.email }}</div>
                <div class="text-xs text-gray-400 truncate">{{ owner.email }}</div>
              </div>
              <span class="shrink-0 text-xs font-medium text-indigo-300">Owner</span>
            </div>

            <div
              v-for="m in members"
              :key="m.user_id"
              class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-white/10"
            >
              <div class="min-w-0">
                <div class="text-sm font-medium text-gray-100 truncate">{{ m.name || m.email }}</div>
                <div class="text-xs text-gray-400 truncate">{{ m.email }}</div>
              </div>
              <button
                @click="removeMember(m)"
                class="shrink-0 text-xs text-gray-400 hover:text-red-400 transition-colors"
              >
                Remove
              </button>
            </div>
          </div>
        </div>

        <!-- Pending invitations -->
        <div v-if="invitations.length" class="mt-5">
          <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Pending invitations</h3>
          <div class="space-y-1.5">
            <div
              v-for="inv in invitations"
              :key="inv.id"
              class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-white/10"
            >
              <div class="text-sm text-gray-300 truncate">{{ inv.email }}</div>
              <button
                @click="cancelInvite(inv)"
                class="shrink-0 text-xs text-gray-400 hover:text-red-400 transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useApi } from '@/composables/useApi';

const props = defineProps({
  boardId: { type: Number, required: true },
  boardName: { type: String, default: '' },
});
defineEmits(['close']);

const api = useApi();

const email = ref('');
const inviting = ref(false);
const formError = ref('');
const lastInvite = ref(null);

const owner = reactive({ name: '', email: '' });
const members = ref([]);
const invitations = ref([]);

async function loadMembers() {
  const data = await api.get(`/boards/${props.boardId}/members`);
  owner.name = data.owner?.name || '';
  owner.email = data.owner?.email || '';
  members.value = data.members || [];
  invitations.value = data.invitations || [];
}

async function invite() {
  formError.value = '';
  lastInvite.value = null;
  inviting.value = true;
  try {
    const res = await api.post(`/boards/${props.boardId}/invitations`, { email: email.value });
    lastInvite.value = { ...res, email: email.value };
    email.value = '';
    await loadMembers();
  } catch (e) {
    formError.value = e.response?.data?.message
      || e.response?.data?.errors?.email?.[0]
      || 'Could not send the invitation.';
  }
  inviting.value = false;
}

async function removeMember(m) {
  if (!window.confirm(`Remove ${m.name || m.email} from this board?`)) return;
  await api.del(`/boards/${props.boardId}/members/${m.user_id}`);
  await loadMembers();
}

async function cancelInvite(inv) {
  await api.del(`/boards/${props.boardId}/invitations/${inv.id}`);
  await loadMembers();
}

onMounted(loadMembers);
</script>
