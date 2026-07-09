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
            <!-- Edit mode -->
            <template v-if="editingProjectId === p.id">
              <div class="flex items-center gap-3 flex-1 min-w-0">
                <input
                  v-model="editProject.color"
                  type="color"
                  class="w-8 h-8 rounded-lg border-0 cursor-pointer p-0.5 shrink-0"
                />
                <input
                  v-model="editProject.name"
                  type="text"
                  class="flex-1 min-w-0 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none"
                  @keyup.enter="saveProject"
                  @keyup.esc="cancelEditProject"
                />
              </div>
              <div class="flex items-center gap-1 shrink-0 ml-2">
                <button
                  @click="saveProject"
                  :disabled="!editProject.name.trim()"
                  class="px-3 py-1.5 text-xs font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:shadow-md transition disabled:opacity-50"
                >
                  Save
                </button>
                <button
                  @click="cancelEditProject"
                  class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 transition"
                >
                  Cancel
                </button>
              </div>
            </template>

            <!-- Display mode -->
            <template v-else>
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-4 h-4 rounded-full shrink-0" :style="{ backgroundColor: p.color }"></span>
                <span class="text-sm font-medium text-gray-700 truncate">{{ p.name }}</span>
              </div>
              <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition shrink-0">
                <button
                  @click="startEditProject(p)"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 transition"
                  title="Edit project"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
                <button
                  @click="removeProject(p.id)"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition"
                  title="Delete project"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </template>
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

      <!-- Email Reports (global) -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-1">
          <h3 class="text-lg font-semibold text-gray-800">Email reports</h3>
          <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="reportSettings.enabled" class="sr-only peer" />
            <div class="relative w-11 h-6 bg-gray-200 peer-checked:bg-indigo-500 rounded-full transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
          </label>
        </div>
        <p class="text-sm text-gray-400 mb-4">Get a scheduled email summary for each enabled board.</p>

        <div class="space-y-5" :class="{ 'opacity-50 pointer-events-none': !reportSettings.enabled }">
          <!-- Schedule -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Frequency</label>
              <select v-model="reportSettings.frequency" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none">
                <option value="daily">Every day</option>
                <option value="weekdays">Weekdays only</option>
                <option value="weekly">Weekly</option>
              </select>
            </div>
            <div v-if="reportSettings.frequency === 'weekly'">
              <label class="block text-sm font-medium text-gray-600 mb-1">Day</label>
              <select v-model.number="reportSettings.day_of_week" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none">
                <option v-for="d in weekdayOptions" :key="d.value" :value="d.value">{{ d.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Time</label>
              <input v-model="reportSettings.time" type="time" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none" />
            </div>
          </div>

          <!-- Recipient -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Send to</label>
            <input v-model="reportSettings.recipient_email" type="email" :placeholder="accountEmail || 'you@example.com'" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none" />
            <p class="text-[11px] text-gray-400 mt-1">Leave blank to use your account email ({{ accountEmail || '—' }}).</p>
          </div>

          <!-- Sections -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Include in the report</label>
            <div class="space-y-2">
              <label v-for="s in availableSections" :key="s.key" class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" :checked="reportSettings.sections.includes(s.key)" @change="toggleSection(s.key)" class="rounded border-gray-300 text-indigo-500 focus:ring-indigo-400" />
                {{ s.label }}
              </label>
            </div>
          </div>

          <!-- Per-board opt-in -->
          <div v-if="reportBoards.length">
            <label class="block text-sm font-medium text-gray-600 mb-2">Boards</label>
            <div class="space-y-2">
              <div v-for="b in reportBoards" :key="b.id" class="flex items-center justify-between px-4 py-2.5 rounded-xl bg-gray-50">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer min-w-0">
                  <input type="checkbox" :checked="b.report_enabled" @change="toggleReportBoard(b)" class="rounded border-gray-300 text-indigo-500 focus:ring-indigo-400" />
                  <span class="truncate font-medium">{{ b.name }}</span>
                  <span v-if="b.last_run" class="text-[11px] shrink-0" :class="b.last_run.status === 'sent' ? 'text-green-500' : b.last_run.status === 'failed' ? 'text-red-500' : 'text-gray-400'">
                    · {{ b.last_run.status }}
                  </span>
                </label>
                <button
                  @click="sendReportTest(b)"
                  :disabled="testingBoardId === b.id"
                  class="px-3 py-1.5 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition disabled:opacity-50 shrink-0 ml-2"
                >
                  {{ testingBoardId === b.id ? 'Sending…' : 'Send test' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 mt-5">
          <button
            @click="saveReportSettings"
            class="px-4 py-2 text-sm bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:shadow-md transition-all"
          >
            Save
          </button>
          <span v-if="reportSaved" class="text-xs text-green-600">Saved</span>
          <span v-if="testMessage" class="text-xs" :class="testOk ? 'text-green-600' : 'text-red-500'">{{ testMessage }}</span>
        </div>
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
const { projects, loadProjects, createProject, updateProject, deleteProject } = useProjects();

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

// Inline edit of an existing project's name + color.
const editingProjectId = ref(null);
const editProject = reactive({ name: '', color: '#6366f1' });

function startEditProject(p) {
  editingProjectId.value = p.id;
  editProject.name = p.name;
  editProject.color = p.color;
}

function cancelEditProject() {
  editingProjectId.value = null;
}

async function saveProject() {
  if (!editProject.name.trim() || !editingProjectId.value) return;
  await updateProject(editingProjectId.value, {
    name: editProject.name.trim(),
    color: editProject.color,
  });
  editingProjectId.value = null;
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

// ─── Email reports (global) ────────────────────────────────────────
const reportSettings = reactive({
  enabled: false,
  frequency: 'daily',
  day_of_week: 1,
  time: '09:00',
  sections: ['critical', 'columns', 'time', 'completed'],
  recipient_email: '',
});
const reportBoards = ref([]);
const availableSections = ref([]);
const accountEmail = ref('');
const reportSaved = ref(false);
const testingBoardId = ref(null);
const testMessage = ref('');
const testOk = ref(false);
const weekdayOptions = [
  { value: 1, label: 'Monday' },
  { value: 2, label: 'Tuesday' },
  { value: 3, label: 'Wednesday' },
  { value: 4, label: 'Thursday' },
  { value: 5, label: 'Friday' },
  { value: 6, label: 'Saturday' },
  { value: 7, label: 'Sunday' },
];

async function loadReportSettings() {
  try {
    const data = await api.get('/reports/settings');
    Object.assign(reportSettings, {
      enabled: data.settings.enabled,
      frequency: data.settings.frequency,
      day_of_week: data.settings.day_of_week || 1,
      time: (data.settings.time || '09:00').slice(0, 5),
      sections: Array.isArray(data.settings.sections) ? data.settings.sections : [],
      recipient_email: data.settings.recipient_email || '',
    });
    reportBoards.value = data.boards || [];
    availableSections.value = data.available_sections || [];
    accountEmail.value = data.account_email || '';
  } catch (e) {
    console.error('Failed to load report settings', e);
  }
}

function toggleSection(key) {
  const i = reportSettings.sections.indexOf(key);
  if (i === -1) reportSettings.sections.push(key);
  else reportSettings.sections.splice(i, 1);
}

async function saveReportSettings() {
  try {
    await api.put('/reports/settings', {
      enabled: reportSettings.enabled,
      frequency: reportSettings.frequency,
      day_of_week: reportSettings.frequency === 'weekly' ? reportSettings.day_of_week : null,
      time: reportSettings.time,
      sections: reportSettings.sections,
      recipient_email: reportSettings.recipient_email.trim() || null,
    });
    reportSaved.value = true;
    setTimeout(() => { reportSaved.value = false; }, 1500);
  } catch (e) {
    console.error('Failed to save report settings', e);
  }
}

async function toggleReportBoard(board) {
  try {
    const data = await api.patch(`/boards/${board.id}/report-toggle`);
    board.report_enabled = data.report_enabled;
  } catch (e) {
    console.error('Failed to toggle board report', e);
  }
}

async function sendReportTest(board) {
  testingBoardId.value = board.id;
  testMessage.value = '';
  try {
    const data = await api.post(`/reports/send-now/${board.id}`);
    testOk.value = data.success;
    testMessage.value = `${board.name}: ${data.message}`;
  } catch (e) {
    testOk.value = false;
    testMessage.value = `${board.name}: failed to send.`;
    console.error('Failed to send test report', e);
  } finally {
    testingBoardId.value = null;
    setTimeout(() => { testMessage.value = ''; }, 5000);
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

onMounted(() => {
  refreshForBoard();
  loadReportSettings();
});
</script>
