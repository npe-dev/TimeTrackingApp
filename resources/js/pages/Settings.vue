<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <!-- Background Image -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Background Image</h2>
        <div class="flex items-start gap-6">
          <div class="w-48 h-28 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 shrink-0 flex items-center justify-center">
            <img
              v-if="backgroundUrl"
              :src="backgroundUrl"
              class="w-full h-full object-cover"
              alt="Background preview"
            />
            <span v-else class="text-gray-400 text-sm">No background</span>
          </div>
          <div class="space-y-3">
            <div>
              <label
                class="inline-block px-4 py-2 text-sm font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg transition-all cursor-pointer"
              >
                Upload Image
                <input
                  type="file"
                  accept="image/*"
                  class="hidden"
                  @change="uploadBackground"
                />
              </label>
            </div>
            <p class="text-xs text-gray-400">Accepted: JPG, PNG, WebP. Max 10MB.</p>
            <button
              v-if="backgroundUrl"
              @click="removeBackground"
              class="px-4 py-2 text-sm font-medium text-red-500 border border-red-200 rounded-lg hover:bg-red-50 transition-all"
            >
              Remove Background
            </button>
          </div>
        </div>
      </div>

      <!-- Labels Management -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold text-gray-800">Labels</h2>
          <button
            @click="openLabelModal()"
            class="px-4 py-2 text-sm font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg transition-all"
          >
            + Add Label
          </button>
        </div>
        <div v-if="labels.length" class="space-y-2">
          <div
            v-for="(label, index) in labels"
            :key="label.id"
            class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors"
          >
            <span class="text-xs text-gray-400 font-mono w-5 shrink-0">{{ index + 1 }}</span>
            <span
              class="w-4 h-4 rounded-full shrink-0"
              :style="{ backgroundColor: label.color }"
            ></span>
            <span class="text-sm font-medium text-gray-700 flex-1">{{ label.name }}</span>
            <div class="flex items-center gap-1">
              <button
                @click="reorderLabel(index, -1)"
                :disabled="index === 0"
                class="p-1 text-gray-400 hover:text-indigo-500 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                title="Move up"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
              </button>
              <button
                @click="reorderLabel(index, 1)"
                :disabled="index === labels.length - 1"
                class="p-1 text-gray-400 hover:text-indigo-500 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                title="Move down"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <button
                @click="openLabelModal(label)"
                class="p-1 text-gray-400 hover:text-indigo-500 transition-colors"
                title="Edit"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              </button>
              <button
                @click="deleteLabel(label)"
                class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                title="Delete"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-gray-400 text-sm">No labels yet. Add one to get started.</p>
      </div>

      <!-- Label Modal -->
      <div
        v-if="showLabelModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
        @click.self="showLabelModal = false"
      >
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
          <h3 class="text-lg font-bold text-gray-800 mb-4">
            {{ editingLabel ? 'Edit Label' : 'New Label' }}
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Name</label>
              <input
                v-model="labelForm.name"
                type="text"
                placeholder="Label name"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                @keydown.enter="saveLabel"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Color</label>
              <div class="grid grid-cols-9 gap-2">
                <button
                  v-for="color in colorOptions"
                  :key="color"
                  @click="labelForm.color = color"
                  class="w-8 h-8 rounded-lg transition-all"
                  :class="labelForm.color === color ? 'ring-2 ring-offset-2 ring-indigo-400 scale-110' : 'hover:scale-105'"
                  :style="{ backgroundColor: color }"
                ></button>
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-6">
            <button
              @click="showLabelModal = false"
              class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all"
            >
              Cancel
            </button>
            <button
              @click="saveLabel"
              :disabled="!labelForm.name.trim()"
              class="px-4 py-2 text-sm font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ editingLabel ? 'Update' : 'Create' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuth } from '@/composables/useAuth';
import { useApi } from '@/composables/useApi';
import { useBackground } from '@/composables/useBackground';

const { fetchUser } = useAuth();
const { get, post, put, del, patch } = useApi();
const { backgroundUrl, setBackground } = useBackground();
const labels = ref([]);
const showLabelModal = ref(false);
const editingLabel = ref(null);
const labelForm = ref({ name: '', color: '#6366F1' });

const colorOptions = [
  '#EF4444', '#F97316', '#EAB308',
  '#22C55E', '#14B8A6', '#3B82F6',
  '#6366F1', '#8B5CF6', '#EC4899',
];

async function uploadBackground(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (file.size > 10 * 1024 * 1024) {
    alert('File size must be under 10MB.');
    return;
  }
  const formData = new FormData();
  formData.append('background', file);
  try {
    const data = await post('/settings/background', formData);
    setBackground(data.url || null);
  } catch (e) {
    console.error('Failed to upload background', e);
  }
  event.target.value = '';
}

async function removeBackground() {
  try {
    await del('/settings/background');
    setBackground(null);
  } catch (e) {
    console.error('Failed to remove background', e);
  }
}

async function loadLabels() {
  try {
    labels.value = await get('/global-labels');
  } catch (e) {
    console.error('Failed to load labels', e);
  }
}

function openLabelModal(label = null) {
  editingLabel.value = label;
  labelForm.value = label
    ? { name: label.name, color: label.color }
    : { name: '', color: '#6366F1' };
  showLabelModal.value = true;
}

async function saveLabel() {
  const name = labelForm.value.name.trim();
  if (!name) return;
  try {
    if (editingLabel.value) {
      await put(`/global-labels/${editingLabel.value.id}`, {
        name,
        color: labelForm.value.color,
      });
    } else {
      await post('/global-labels', {
        name,
        color: labelForm.value.color,
      });
    }
    showLabelModal.value = false;
    await loadLabels();
  } catch (e) {
    console.error('Failed to save label', e);
  }
}

async function deleteLabel(label) {
  if (!confirm(`Delete label "${label.name}"?`)) return;
  try {
    await del(`/global-labels/${label.id}`);
    await loadLabels();
  } catch (e) {
    console.error('Failed to delete label', e);
  }
}

async function reorderLabel(index, direction) {
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= labels.value.length) return;
  const reordered = [...labels.value];
  const [moved] = reordered.splice(index, 1);
  reordered.splice(newIndex, 0, moved);
  labels.value = reordered;
  try {
    await patch('/global-labels/reorder', {
      labelIds: reordered.map(l => l.id),
    });
  } catch (e) {
    console.error('Failed to reorder labels', e);
    await loadLabels();
  }
}

onMounted(async () => {
  await fetchUser();
  loadLabels();
});
</script>
