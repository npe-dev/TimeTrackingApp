<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-6">

      <!-- Timer Section -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-8 text-center">
        <!-- Timer Display -->
        <div class="text-6xl font-mono font-bold text-gray-800 mb-6 tracking-wider">
          {{ formattedTimer }}
        </div>

        <!-- Running Info -->
        <div v-if="runningEntry" class="mb-4">
          <span
            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-medium bg-green-50 text-green-700"
          >
            <span
              class="w-2.5 h-2.5 rounded-full"
              :style="{ backgroundColor: runningProject?.color || '#6366f1' }"
            ></span>
            {{ runningProject?.name || 'No project' }}
            <span v-if="runningEntry.task_title" class="text-green-600">
              &mdash; {{ runningEntry.task_title }}
            </span>
            <span v-else-if="runningEntry.description" class="text-green-500">
              &mdash; {{ runningEntry.description }}
            </span>
          </span>
        </div>

        <!-- Controls (when stopped) -->
        <div v-if="!runningEntry" class="space-y-4 max-w-md mx-auto">
          <select
            v-model="timerForm.project_id"
            class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition"
          >
            <option :value="null">Select project...</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <input
            v-model="timerForm.description"
            type="text"
            placeholder="What are you working on?"
            class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition"
            @keyup.enter="startTimer"
          />
        </div>

        <!-- Start / Stop Button -->
        <button
          @click="runningEntry ? stopTimer() : startTimer()"
          class="mt-6 px-10 py-3.5 rounded-xl text-white font-semibold text-lg shadow-lg transition-all duration-200 hover:scale-105 active:scale-95"
          :class="runningEntry
            ? 'bg-gradient-to-r from-red-500 to-rose-600 hover:shadow-red-200'
            : 'bg-gradient-to-r from-green-500 to-emerald-600 hover:shadow-green-200'"
        >
          {{ runningEntry ? 'Stop' : 'Start' }}
        </button>
      </div>

      <!-- Action Bar -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button
            @click="showManualModal = true; resetManualForm()"
            class="px-4 py-2 rounded-xl bg-white/95 backdrop-blur-sm shadow text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition"
          >
            + Manual Entry
          </button>
          <button
            @click="showProjectsModal = true"
            class="px-4 py-2 rounded-xl bg-white/95 backdrop-blur-sm shadow text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition"
          >
            Manage Projects
          </button>
        </div>
        <div class="flex items-center gap-3">
          <button
            v-if="selectedIds.size > 0"
            @click="bulkDelete"
            class="px-4 py-2 rounded-xl bg-red-500 text-white text-sm font-medium shadow hover:bg-red-600 transition"
          >
            Delete Selected ({{ selectedIds.size }})
          </button>
          <button
            @click="exportCsv"
            class="px-4 py-2 rounded-xl bg-white/95 backdrop-blur-sm shadow text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition"
          >
            Export CSV
          </button>
        </div>
      </div>

      <!-- Entries List -->
      <div v-for="group in groupedEntries" :key="group.label" class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden">
        <!-- Day Header -->
        <div class="flex items-center justify-between px-6 py-3 bg-gray-50/80 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <input
              type="checkbox"
              :checked="isDaySelected(group)"
              @change="toggleDay(group)"
              class="rounded border-gray-300 text-indigo-500 focus:ring-indigo-400"
            />
            <span class="font-semibold text-gray-700 text-sm">{{ group.label }}</span>
          </div>
          <span class="text-sm font-medium text-indigo-500">{{ formatDuration(group.total) }}</span>
        </div>

        <!-- Entries -->
        <div
          v-for="entry in group.entries"
          :key="entry.id"
          class="flex items-center gap-4 px-6 py-3 border-b border-gray-50 last:border-0 hover:bg-indigo-50/30 transition group"
        >
          <input
            type="checkbox"
            :checked="selectedIds.has(entry.id)"
            @change="toggleSelect(entry.id)"
            class="rounded border-gray-300 text-indigo-500 focus:ring-indigo-400"
          />
          <span
            class="w-3 h-3 rounded-full flex-shrink-0"
            :style="{ backgroundColor: getProjectById(entry.project_id)?.color || '#d1d5db' }"
          ></span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800 truncate">
              {{ entry.description || entry.task_title || 'No description' }}
            </p>
            <p class="text-xs text-gray-400">
              {{ getProjectById(entry.project_id)?.name || 'No project' }}
            </p>
          </div>
          <span class="text-xs text-gray-400 whitespace-nowrap">
            {{ formatTime(entry.start_time) }} &ndash; {{ formatTime(entry.end_time) }}
          </span>
          <span class="text-sm font-mono font-medium text-gray-700 w-20 text-right">
            {{ formatDuration(entryDuration(entry)) }}
          </span>
          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
            <button
              @click="editEntry(entry)"
              class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 transition"
              title="Edit"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="confirmDeleteEntry(entry)"
              class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition"
              title="Delete"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div v-if="groupedEntries.length === 0" class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-12 text-center">
        <p class="text-gray-400">No time entries yet. Start a timer or add a manual entry.</p>
      </div>

      <!-- Manual Entry Modal -->
      <Teleport to="body">
        <div v-if="showManualModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showManualModal = false"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">
              {{ manualForm.id ? 'Edit Entry' : 'New Manual Entry' }}
            </h3>
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Project</label>
              <select
                v-model="manualForm.project_id"
                class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent"
              >
                <option :value="null">No project</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Description</label>
              <input
                v-model="manualForm.description"
                type="text"
                class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent"
              />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Start</label>
                <input
                  v-model="manualForm.start_time"
                  type="datetime-local"
                  class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">End</label>
                <input
                  v-model="manualForm.end_time"
                  type="datetime-local"
                  class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent"
                />
              </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
              <button
                @click="showManualModal = false"
                class="px-5 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition"
              >
                Cancel
              </button>
              <button
                @click="saveManualEntry"
                class="px-5 py-2 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold shadow hover:shadow-lg transition"
              >
                Save
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDeleteModal = false"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Delete Entry?</h3>
            <p class="text-sm text-gray-500">This action cannot be undone.</p>
            <div class="flex justify-center gap-3 pt-2">
              <button
                @click="showDeleteModal = false"
                class="px-5 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition"
              >
                Cancel
              </button>
              <button
                @click="deleteEntry"
                class="px-5 py-2 rounded-xl bg-red-500 text-white text-sm font-semibold shadow hover:bg-red-600 transition"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Projects Modal -->
      <Teleport to="body">
        <div v-if="showProjectsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showProjectsModal = false"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Manage Projects</h3>

            <!-- Add Project -->
            <div class="flex items-center gap-3">
              <input
                v-model="newProject.color"
                type="color"
                class="w-10 h-10 rounded-lg border-0 cursor-pointer p-0.5"
              />
              <input
                v-model="newProject.name"
                type="text"
                placeholder="New project name"
                class="flex-1 rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent"
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
            <div class="space-y-2 max-h-64 overflow-y-auto">
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
              <p v-if="projects.length === 0" class="text-sm text-gray-400 text-center py-4">
                No projects yet.
              </p>
            </div>

            <div class="flex justify-end pt-2">
              <button
                @click="showProjectsModal = false"
                class="px-5 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Idle Detection Modal -->
      <Teleport to="body">
        <div v-if="showIdleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center space-y-4">
            <div class="w-14 h-14 mx-auto rounded-full bg-amber-100 flex items-center justify-center">
              <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Are you still working?</h3>
            <p class="text-sm text-gray-500">
              No activity detected for 5 minutes. Timer will auto-stop in
              <span class="font-semibold text-amber-600">{{ idleCountdown }}s</span>.
            </p>
            <!-- Progress bar -->
            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-amber-400 to-red-500 transition-all duration-1000 ease-linear rounded-full"
                :style="{ width: (idleCountdown / 120) * 100 + '%' }"
              ></div>
            </div>
            <div class="flex justify-center gap-3 pt-2">
              <button
                @click="dismissIdle"
                class="px-5 py-2 rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 text-white text-sm font-semibold shadow hover:shadow-lg transition"
              >
                I'm still working
              </button>
              <button
                @click="stopTimerAtIdleStart"
                class="px-5 py-2 rounded-xl bg-red-500 text-white text-sm font-semibold shadow hover:bg-red-600 transition"
              >
                Stop timer
              </button>
            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuth } from '@/composables/useAuth';
import { useApi } from '@/composables/useApi';
import { useTimer } from '@/composables/useTimer';
import { useProjects } from '@/composables/useProjects';
import { useBoard } from '@/composables/useBoard';

const { user } = useAuth();
const api = useApi();
const { runningEntry, checkRunning, start, stop, stopAt, startHeartbeat } = useTimer();
const { projects, loadProjects, createProject, deleteProject, getProjectById } = useProjects();
const { activeBoardId } = useBoard();

// ─── Timer Display ──────────────────────────────────────────────
const elapsedSeconds = ref(0);
let timerInterval = null;
const originalTitle = document.title;

const formattedTimer = computed(() => {
  const s = elapsedSeconds.value;
  const h = String(Math.floor(s / 3600)).padStart(2, '0');
  const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
  const sec = String(s % 60).padStart(2, '0');
  return `${h}:${m}:${sec}`;
});

function startTimerDisplay() {
  stopTimerDisplay();
  updateElapsed();
  timerInterval = setInterval(updateElapsed, 1000);
}

function stopTimerDisplay() {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
  elapsedSeconds.value = 0;
  document.title = originalTitle;
}

function updateElapsed() {
  if (!runningEntry.value?.start_time) return;
  const start = new Date(runningEntry.value.start_time).getTime();
  elapsedSeconds.value = Math.max(0, Math.floor((Date.now() - start) / 1000));
  // While the idle alert is flashing the title, don't clobber it with the clock.
  if (!idleAlerting) {
    document.title = `${formattedTimer.value} - Time Tracking`;
  }
}

watch(runningEntry, (val) => {
  if (val) {
    startTimerDisplay();
    startHeartbeat();
  } else {
    stopTimerDisplay();
  }
});

const runningProject = computed(() => {
  if (!runningEntry.value?.project_id) return null;
  return getProjectById(runningEntry.value.project_id);
});

// ─── Timer Controls ─────────────────────────────────────────────
const timerForm = reactive({ project_id: null, description: '' });

async function startTimer() {
  if (!timerForm.project_id) {
    alert('Please select a project before starting the timer.');
    return;
  }
  await start({
    project_id: timerForm.project_id,
    description: timerForm.description,
  });
  timerForm.description = '';
  resetIdleDetection();
}

async function stopTimer() {
  await stop();
  await loadEntries();
}

// ─── Entries ────────────────────────────────────────────────────
const entries = ref([]);

async function loadEntries() {
  const now = new Date();
  const start = new Date(now);
  start.setDate(start.getDate() - 30);
  entries.value = await api.get('/entries', {
    start_date: start.toISOString().slice(0, 10),
    end_date: now.toISOString().slice(0, 10),
    board_id: activeBoardId.value,
  });
}

function entryDuration(entry) {
  if (!entry.start_time || !entry.end_time) return 0;
  return Math.floor((new Date(entry.end_time) - new Date(entry.start_time)) / 1000);
}

function formatDuration(totalSeconds) {
  const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
  const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
  const s = String(totalSeconds % 60).padStart(2, '0');
  return `${h}:${m}:${s}`;
}

function formatTime(datetime) {
  if (!datetime) return '--:--';
  const d = new Date(datetime);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function dateKey(datetime) {
  const d = new Date(datetime);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function dayLabel(key) {
  const today = dateKey(new Date());
  const yesterday = dateKey(new Date(Date.now() - 86400000));
  if (key === today) return 'Today';
  if (key === yesterday) return 'Yesterday';
  const [y, m, d] = key.split('-');
  return `${d}.${m}.${y}`;
}

const groupedEntries = computed(() => {
  const groups = {};
  for (const entry of entries.value) {
    const key = dateKey(entry.start_time);
    if (!groups[key]) groups[key] = { key, entries: [], total: 0 };
    groups[key].entries.push(entry);
    groups[key].total += entryDuration(entry);
  }
  return Object.values(groups)
    .sort((a, b) => b.key.localeCompare(a.key))
    .map(g => ({
      ...g,
      label: dayLabel(g.key),
      entries: g.entries.sort((a, b) => new Date(b.start_time) - new Date(a.start_time)),
    }));
});

// ─── Selection ──────────────────────────────────────────────────
const selectedIds = ref(new Set());

function toggleSelect(id) {
  const next = new Set(selectedIds.value);
  if (next.has(id)) next.delete(id);
  else next.add(id);
  selectedIds.value = next;
}

function isDaySelected(group) {
  return group.entries.length > 0 && group.entries.every(e => selectedIds.value.has(e.id));
}

function toggleDay(group) {
  const next = new Set(selectedIds.value);
  const allSelected = isDaySelected(group);
  for (const e of group.entries) {
    if (allSelected) next.delete(e.id);
    else next.add(e.id);
  }
  selectedIds.value = next;
}

async function bulkDelete() {
  if (!confirm(`Delete ${selectedIds.value.size} entries?`)) return;
  const ids = [...selectedIds.value];
  await Promise.all(ids.map(id => api.del(`/entries/${id}`)));
  selectedIds.value = new Set();
  await loadEntries();
}

// ─── Manual Entry Modal ─────────────────────────────────────────
const showManualModal = ref(false);
const manualForm = reactive({
  id: null,
  project_id: null,
  description: '',
  start_time: '',
  end_time: '',
});

function resetManualForm() {
  manualForm.id = null;
  manualForm.project_id = null;
  manualForm.description = '';
  const now = new Date();
  const oneHourAgo = new Date(now.getTime() - 3600000);
  manualForm.start_time = toLocalDatetime(oneHourAgo);
  manualForm.end_time = toLocalDatetime(now);
}

function toLocalDatetime(d) {
  const offset = d.getTimezoneOffset();
  const local = new Date(d.getTime() - offset * 60000);
  return local.toISOString().slice(0, 16);
}

function editEntry(entry) {
  manualForm.id = entry.id;
  manualForm.project_id = entry.project_id;
  manualForm.description = entry.description || '';
  manualForm.start_time = toLocalDatetime(new Date(entry.start_time));
  manualForm.end_time = entry.end_time ? toLocalDatetime(new Date(entry.end_time)) : '';
  showManualModal.value = true;
}

async function saveManualEntry() {
  const payload = {
    project_id: manualForm.project_id,
    description: manualForm.description,
    start_time: new Date(manualForm.start_time).toISOString(),
    end_time: new Date(manualForm.end_time).toISOString(),
  };
  if (manualForm.id) {
    await api.put(`/entries/${manualForm.id}`, payload);
  } else {
    await api.post('/entries', payload);
  }
  showManualModal.value = false;
  await loadEntries();
}

// ─── Delete Confirmation ────────────────────────────────────────
const showDeleteModal = ref(false);
const entryToDelete = ref(null);

function confirmDeleteEntry(entry) {
  entryToDelete.value = entry;
  showDeleteModal.value = true;
}

async function deleteEntry() {
  if (entryToDelete.value) {
    await api.del(`/entries/${entryToDelete.value.id}`);
    showDeleteModal.value = false;
    entryToDelete.value = null;
    await loadEntries();
  }
}

// ─── Projects Modal ─────────────────────────────────────────────
const showProjectsModal = ref(false);
const newProject = reactive({ name: '', color: '#6366f1' });

async function addProject() {
  if (!newProject.name.trim() || !activeBoardId.value) return;
  await createProject({ name: newProject.name.trim(), color: newProject.color, board_id: activeBoardId.value });
  newProject.name = '';
  newProject.color = '#6366f1';
}

async function removeProject(id) {
  if (!confirm('Delete this project? Entries will not be deleted.')) return;
  await deleteProject(id);
}

// ─── CSV Export ─────────────────────────────────────────────────
function exportCsv() {
  const now = new Date();
  const start = new Date(now);
  start.setDate(start.getDate() - 30);
  const boardParam = activeBoardId.value ? `&board_id=${activeBoardId.value}` : '';
  const url = `/api/entries/export/csv?start_date=${start.toISOString().slice(0, 10)}&end_date=${now.toISOString().slice(0, 10)}${boardParam}`;
  window.open(url, '_blank');
}

// ─── Idle Detection ─────────────────────────────────────────────
const showIdleModal = ref(false);
const idleCountdown = ref(120);
let lastActivity = Date.now();
let idleCheckInterval = null;
let idleCountdownInterval = null;
let idleStartTime = null;

// ─── Background-tab attention grabbers ──────────────────────────
// A browser can't force-focus a background tab, so instead we (1) post a
// desktop notification, (2) flash the tab title, and (3) beep.
let idleAlerting = false;
let titleFlashInterval = null;
let idleNotification = null;

function beep() {
  try {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return;
    const ctx = new Ctx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.frequency.value = 880;
    gain.gain.setValueAtTime(0.1, ctx.currentTime);
    osc.start();
    osc.stop(ctx.currentTime + 0.2);
    osc.onended = () => ctx.close();
  } catch (e) { /* audio is best-effort */ }
}

function startIdleAlert() {
  idleAlerting = true;
  beep();

  // Flash the tab title so a background tab visibly signals.
  let on = true;
  document.title = '⏰ Still working?';
  titleFlashInterval = setInterval(() => {
    document.title = on ? '⏰ Still working?' : `${formattedTimer.value} - Time Tracking`;
    on = !on;
  }, 1000);

  // Desktop notification works even when the tab/window is hidden.
  if ('Notification' in window && Notification.permission === 'granted') {
    idleNotification = new Notification('Are you still working?', {
      body: 'Your timer will stop soon unless you confirm.',
      requireInteraction: true,
      tag: 'idle-timer',
    });
    idleNotification.onclick = () => { window.focus(); idleNotification?.close(); };
  }
}

function stopIdleAlert() {
  idleAlerting = false;
  if (titleFlashInterval) { clearInterval(titleFlashInterval); titleFlashInterval = null; }
  if (idleNotification) { idleNotification.close(); idleNotification = null; }
}

function onActivity() {
  lastActivity = Date.now();
}

function startIdleDetection() {
  stopIdleDetection();
  window.addEventListener('mousemove', onActivity);
  window.addEventListener('keydown', onActivity);
  window.addEventListener('click', onActivity);
  idleCheckInterval = setInterval(checkIdle, 60000);
}

function stopIdleDetection() {
  window.removeEventListener('mousemove', onActivity);
  window.removeEventListener('keydown', onActivity);
  window.removeEventListener('click', onActivity);
  if (idleCheckInterval) { clearInterval(idleCheckInterval); idleCheckInterval = null; }
  clearIdleCountdown();
  stopIdleAlert();
}

function checkIdle() {
  if (!runningEntry.value) return;
  const idleMs = Date.now() - lastActivity;
  if (idleMs >= 30 * 60 * 1000 && !showIdleModal.value) {
    idleStartTime = new Date(lastActivity);
    showIdleModal.value = true;
    idleCountdown.value = 120;
    startIdleAlert();
    idleCountdownInterval = setInterval(() => {
      idleCountdown.value--;
      if (idleCountdown.value <= 0) {
        clearIdleCountdown();
        stopTimerAtIdleStart();
      }
    }, 1000);
  }
}

function clearIdleCountdown() {
  if (idleCountdownInterval) { clearInterval(idleCountdownInterval); idleCountdownInterval = null; }
}

function dismissIdle() {
  showIdleModal.value = false;
  clearIdleCountdown();
  stopIdleAlert();
  lastActivity = Date.now();
}

async function stopTimerAtIdleStart() {
  showIdleModal.value = false;
  clearIdleCountdown();
  stopIdleAlert();
  if (idleStartTime) {
    await stopAt(idleStartTime.toISOString());
  } else {
    await stop();
  }
  await loadEntries();
}

function resetIdleDetection() {
  lastActivity = Date.now();
  if (runningEntry.value) startIdleDetection();
}

watch(runningEntry, (val) => {
  if (val) startIdleDetection();
  else stopIdleDetection();
});

// Reload board-scoped projects and entries when the active board changes.
watch(activeBoardId, async () => {
  timerForm.project_id = null;
  await Promise.all([loadProjects(activeBoardId.value), loadEntries()]);
});

// ─── Lifecycle ──────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([loadProjects(activeBoardId.value), checkRunning(), loadEntries()]);
  // Ask for desktop-notification permission so we can alert from a background tab.
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }
});

onUnmounted(() => {
  stopTimerDisplay();
  stopIdleDetection();
  document.title = originalTitle;
});
</script>
