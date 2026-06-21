<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6">

      <!-- Board -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-1">Board settings</h2>
        <p class="text-sm text-gray-400 mb-4">Settings apply to the active board: <strong>{{ activeBoard()?.name || '—' }}</strong></p>

        <div v-if="activeBoardId" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Name</label>
            <input
              v-model="boardForm.name"
              class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Description</label>
            <input
              v-model="boardForm.description"
              placeholder="Optional"
              class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
            />
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="saveBoard"
              :disabled="!boardForm.name.trim()"
              class="px-4 py-2 text-sm bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:shadow-md transition-all disabled:opacity-50"
            >
              Save
            </button>
            <span v-if="boardSaved" class="text-xs text-green-600">Saved</span>
            <div class="flex-1"></div>
            <button
              @click="confirmDeleteBoard"
              class="px-4 py-2 text-sm text-red-500 border border-red-200 rounded-lg hover:bg-red-50 transition-all"
            >
              Delete board
            </button>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">No board selected. Create one from the top navigation.</p>
      </div>

      <!-- Background -->
      <div v-if="activeBoardId" class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Background image</h3>
        <div class="flex items-center gap-4">
          <div class="w-32 h-20 rounded-lg overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center shrink-0">
            <img v-if="boardBackgroundUrl" :src="boardBackgroundUrl" class="w-full h-full object-cover" alt="Background" />
            <span v-else class="text-xs text-gray-400">None</span>
          </div>
          <div class="space-y-2">
            <label class="inline-block px-3 py-1.5 text-xs font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg cursor-pointer hover:shadow-md transition-all">
              Upload
              <input type="file" accept="image/*" class="hidden" @change="uploadBoardBackground" />
            </label>
            <button
              v-if="boardBackgroundUrl"
              @click="removeBoardBackground"
              class="block px-3 py-1.5 text-xs font-medium text-red-500 border border-red-200 rounded-lg hover:bg-red-50 transition-all"
            >
              Remove
            </button>
            <p class="text-[11px] text-gray-400">Max 40MB.</p>
          </div>
        </div>
      </div>

      <!-- Projects -->
      <div v-if="activeBoardId" class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Projects</h3>

        <!-- Add Project -->
        <div class="flex items-center gap-3 mb-4">
          <input
            v-model="newProject.color"
            type="color"
            class="w-10 h-10 rounded-lg border-0 cursor-pointer p-0.5"
          />
          <input
            v-model="newProject.name"
            type="text"
            placeholder="New project name"
            class="flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none"
            @keyup.enter="addProject"
          />
          <button
            @click="addProject"
            :disabled="!newProject.name.trim()"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold shadow hover:shadow-lg transition disabled:opacity-50"
          >
            Add
          </button>
        </div>

        <!-- Project List -->
        <div v-if="projects.length" class="space-y-2">
          <div
            v-for="p in projects"
            :key="p.id"
            class="flex items-center justify-between px-4 py-2.5 rounded-xl bg-gray-50 group"
          >
            <div class="flex items-center gap-3">
              <span class="w-4 h-4 rounded-full" :style="{ backgroundColor: p.color }"></span>
              <span class="text-sm font-medium text-gray-700">{{ p.name }}</span>
            </div>
            <button
              @click="removeProject(p.id)"
              class="text-gray-300 hover:text-red-500 transition opacity-0 group-hover:opacity-100"
              title="Delete project"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">No projects yet. Add one to track time against it.</p>
      </div>

      <!-- Labels -->
      <div v-if="activeBoardId" class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Labels</h3>
          <button
            @click="openLabelModal()"
            class="px-3 py-1.5 text-xs font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:shadow-md transition-all"
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
            <span class="w-4 h-4 rounded-full shrink-0" :style="{ backgroundColor: label.color }"></span>
            <span class="text-sm font-medium text-gray-700 flex-1">{{ label.name }}</span>
            <div class="flex items-center gap-1">
              <button @click="reorderLabel(index, -1)" :disabled="index === 0" class="p-1 text-gray-400 hover:text-indigo-500 disabled:opacity-30 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
              </button>
              <button @click="reorderLabel(index, 1)" :disabled="index === labels.length - 1" class="p-1 text-gray-400 hover:text-indigo-500 disabled:opacity-30 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <button @click="openLabelModal(label)" class="p-1 text-gray-400 hover:text-indigo-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              </button>
              <button @click="deleteLabel(label)" class="p-1 text-gray-400 hover:text-red-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">No labels yet. Add one to get started.</p>
      </div>

      <!-- Label Edit Modal -->
      <Teleport to="body">
        <div v-if="showLabelModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showLabelModal = false"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm mx-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ editingLabel ? 'Edit Label' : 'New Label' }}</h3>
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
                    v-for="color in labelColorOptions"
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
              <button @click="showLabelModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
              <button
                @click="saveLabel"
                :disabled="!labelForm.name.trim()"
                class="px-4 py-2 text-sm font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ editingLabel ? 'Update' : 'Create' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useApi } from '@/composables/useApi';
import { useBoard } from '@/composables/useBoard';
import { useBackground } from '@/composables/useBackground';
import { useProjects } from '@/composables/useProjects';

const api = useApi();
const { activeBoardId, activeBoard, updateBoard, deleteBoard, boards } = useBoard();
const { loadBoardBackground, setBackground, clearBoardCache } = useBackground();
const { projects, loadProjects, createProject, deleteProject } = useProjects();

// ─── Board ─────────────────────────────────────────────────────────
const boardForm = reactive({ name: '', description: '' });
const boardSaved = ref(false);

function seedBoardForm() {
  const b = activeBoard();
  boardForm.name = b?.name || '';
  boardForm.description = b?.description || '';
}

async function saveBoard() {
  if (!boardForm.name.trim() || !activeBoardId.value) return;
  await updateBoard(activeBoardId.value, {
    name: boardForm.name.trim(),
    description: boardForm.description.trim(),
  });
  boardSaved.value = true;
  setTimeout(() => { boardSaved.value = false; }, 1500);
}

async function confirmDeleteBoard() {
  if (boards.value.length <= 1) {
    alert('You cannot delete your only board.');
    return;
  }
  const b = activeBoard();
  if (!confirm(`Delete board "${b?.name}"? All its columns, tasks, labels and projects will be permanently deleted.`)) return;
  await deleteBoard(activeBoardId.value);
  // deleteBoard switches to another board; reseed below via the watcher.
}

// ─── Background ────────────────────────────────────────────────────
const boardBackgroundUrl = ref(null);

async function loadBackgroundStatus() {
  if (!activeBoardId.value) { boardBackgroundUrl.value = null; return; }
  try {
    const data = await api.get(`/boards/${activeBoardId.value}/background/status`);
    boardBackgroundUrl.value = data.exists ? data.url : null;
  } catch {
    boardBackgroundUrl.value = null;
  }
}

async function uploadBoardBackground(event) {
  const file = event.target.files?.[0];
  if (!file || !activeBoardId.value) return;
  if (file.size > 40 * 1024 * 1024) { alert('File size must be under 40MB.'); return; }
  const formData = new FormData();
  formData.append('background', file);
  try {
    const data = await api.post(`/boards/${activeBoardId.value}/background`, formData);
    boardBackgroundUrl.value = data.url || null;
    clearBoardCache(activeBoardId.value);
    setBackground(data.url || null);
  } catch (e) {
    console.error('Failed to upload background', e);
  }
  event.target.value = '';
}

async function removeBoardBackground() {
  if (!activeBoardId.value) return;
  try {
    await api.del(`/boards/${activeBoardId.value}/background`);
    boardBackgroundUrl.value = null;
    clearBoardCache(activeBoardId.value);
    setBackground(null);
  } catch (e) {
    console.error('Failed to remove background', e);
  }
}

// ─── Projects ──────────────────────────────────────────────────────
const newProject = reactive({ name: '', color: '#6366f1' });

async function addProject() {
  if (!newProject.name.trim() || !activeBoardId.value) return;
  await createProject({ name: newProject.name.trim(), color: newProject.color, board_id: activeBoardId.value });
  newProject.name = '';
  newProject.color = '#6366f1';
}

async function removeProject(id) {
  if (!confirm('Delete this project? Existing time entries will keep their data but lose the project link.')) return;
  await deleteProject(id);
}

// ─── Labels ────────────────────────────────────────────────────────
const labels = ref([]);
const showLabelModal = ref(false);
const editingLabel = ref(null);
const labelForm = reactive({ name: '', color: '#6366F1' });
const labelColorOptions = [
  '#EF4444', '#F97316', '#EAB308',
  '#22C55E', '#14B8A6', '#3B82F6',
  '#6366F1', '#8B5CF6', '#EC4899',
];

async function loadLabels() {
  if (!activeBoardId.value) { labels.value = []; return; }
  try {
    labels.value = await api.get(`/boards/${activeBoardId.value}/labels`);
  } catch {
    labels.value = [];
  }
}

function openLabelModal(label = null) {
  editingLabel.value = label;
  labelForm.name = label ? label.name : '';
  labelForm.color = label ? label.color : '#6366F1';
  showLabelModal.value = true;
}

async function saveLabel() {
  const name = labelForm.name.trim();
  if (!name || !activeBoardId.value) return;
  try {
    if (editingLabel.value) {
      await api.put(`/boards/${activeBoardId.value}/labels/${editingLabel.value.id}`, { name, color: labelForm.color });
    } else {
      await api.post(`/boards/${activeBoardId.value}/labels`, { name, color: labelForm.color });
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
    await api.del(`/boards/${activeBoardId.value}/labels/${label.id}`);
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
    await api.patch(`/boards/${activeBoardId.value}/labels/reorder`, {
      labelIds: reordered.map(l => l.id),
    });
  } catch (e) {
    console.error('Failed to reorder labels', e);
    await loadLabels();
  }
}

// ─── Lifecycle ─────────────────────────────────────────────────────
function refreshForBoard() {
  seedBoardForm();
  loadBackgroundStatus();
  loadLabels();
  loadProjects(activeBoardId.value);
}

watch(activeBoardId, refreshForBoard);
// The boards list is loaded by AppLayout (parent), which may resolve after this
// page mounts — reseed the form once it arrives.
watch(boards, seedBoardForm, { deep: true });

onMounted(refreshForBoard);
</script>
