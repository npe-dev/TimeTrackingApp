<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- Date Range Filter -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Time Reports</h2>
        <div class="flex flex-wrap items-end gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Start Date</label>
            <input
              v-model="startDate"
              type="date"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">End Date</label>
            <input
              v-model="endDate"
              type="date"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
            />
          </div>
          <div class="flex gap-2">
            <button
              @click="setThisWeek"
              class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
              :class="activePreset === 'week'
                ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md'
                : 'bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-600'"
            >
              This Week
            </button>
            <button
              @click="setThisMonth"
              class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
              :class="activePreset === 'month'
                ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md'
                : 'bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-600'"
            >
              This Month
            </button>
          </div>
          <button
            @click="loadReport"
            class="px-5 py-2 text-sm font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg transition-all"
          >
            Apply
          </button>
          <button
            @click="exportCsv"
            class="px-4 py-2 text-sm font-medium bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-all"
          >
            Export CSV
          </button>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 text-center">
          <p class="text-sm text-gray-500 mb-1">Total Time</p>
          <p class="text-2xl font-bold text-gray-800">{{ formatMinutes(summary.totalMinutes) }}</p>
        </div>
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 text-center">
          <p class="text-sm text-gray-500 mb-1">Total Entries</p>
          <p class="text-2xl font-bold text-gray-800">{{ summary.totalEntries }}</p>
        </div>
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 text-center">
          <p class="text-sm text-gray-500 mb-1">Average per Day</p>
          <p class="text-2xl font-bold text-gray-800">{{ formatMinutes(summary.averagePerDay) }}</p>
        </div>
      </div>

      <!-- Time by Project -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Time by Project</h3>
        <div v-if="summary.byProject && summary.byProject.length" class="space-y-3">
          <div v-for="project in summary.byProject" :key="project.id" class="flex items-center gap-3">
            <span class="text-sm text-gray-600 w-32 truncate shrink-0">{{ project.name }}</span>
            <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
              <div
                class="h-full rounded-full flex items-center px-2 transition-all duration-500"
                :style="{
                  width: maxProjectMinutes ? (project.minutes / maxProjectMinutes * 100) + '%' : '0%',
                  backgroundColor: project.color || '#6366F1',
                  minWidth: '2rem'
                }"
              >
                <span class="text-xs text-white font-medium whitespace-nowrap">
                  {{ formatMinutes(project.minutes) }}
                </span>
              </div>
            </div>
            <span class="text-xs text-gray-400 w-12 text-right shrink-0">
              {{ summary.totalMinutes ? Math.round(project.minutes / summary.totalMinutes * 100) : 0 }}%
            </span>
          </div>
        </div>
        <p v-else class="text-gray-400 text-sm">No project data for this period.</p>
      </div>

      <!-- Time by Day -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Time by Day</h3>
        <div v-if="summary.byDay && summary.byDay.length" class="space-y-2">
          <div v-for="day in summary.byDay" :key="day.date" class="flex items-center gap-3">
            <span class="text-sm text-gray-600 w-28 shrink-0">{{ day.date }}</span>
            <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
              <div
                class="h-full bg-indigo-500 rounded-full flex items-center px-2 transition-all duration-500"
                :style="{
                  width: maxDayMinutes ? (day.minutes / maxDayMinutes * 100) + '%' : '0%',
                  minWidth: day.minutes > 0 ? '2rem' : '0'
                }"
              >
                <span v-if="day.minutes > 0" class="text-xs text-white font-medium whitespace-nowrap">
                  {{ formatMinutes(day.minutes) }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-gray-400 text-sm">No daily data for this period.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuth } from '@/composables/useAuth';
import { useApi } from '@/composables/useApi';
import { useBoard } from '@/composables/useBoard';

const { fetchUser } = useAuth();
const { get } = useApi();
const { activeBoardId } = useBoard();

const startDate = ref('');
const endDate = ref('');
const activePreset = ref('week');
const summary = ref({
  totalMinutes: 0,
  totalEntries: 0,
  averagePerDay: 0,
  byProject: [],
  byDay: [],
});

const maxProjectMinutes = computed(() => {
  if (!summary.value.byProject || !summary.value.byProject.length) return 0;
  return Math.max(...summary.value.byProject.map(p => p.minutes));
});

const maxDayMinutes = computed(() => {
  if (!summary.value.byDay || !summary.value.byDay.length) return 0;
  return Math.max(...summary.value.byDay.map(d => d.minutes));
});

function formatMinutes(mins) {
  if (!mins || mins <= 0) return '0m';
  const days = Math.floor(mins / (60 * 24));
  const hours = Math.floor((mins % (60 * 24)) / 60);
  const minutes = Math.round(mins % 60);
  if (days > 0) return `${days}d ${hours}h ${minutes}m`;
  if (hours > 0) return `${hours}h ${minutes}m`;
  return `${minutes}m`;
}

function toDateString(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function setThisWeek() {
  const now = new Date();
  const day = now.getDay();
  const mondayOffset = day === 0 ? -6 : 1 - day;
  const monday = new Date(now);
  monday.setDate(now.getDate() + mondayOffset);
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);
  startDate.value = toDateString(monday);
  endDate.value = toDateString(sunday);
  activePreset.value = 'week';
  loadReport();
}

function setThisMonth() {
  const now = new Date();
  const first = new Date(now.getFullYear(), now.getMonth(), 1);
  const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  startDate.value = toDateString(first);
  endDate.value = toDateString(last);
  activePreset.value = 'month';
  loadReport();
}

async function loadReport() {
  try {
    const data = await get('/reports/summary', {
      start_date: startDate.value,
      end_date: endDate.value,
      board_id: activeBoardId.value,
    });
    summary.value = {
      totalMinutes: data.total_minutes ?? 0,
      totalEntries: data.total_entries ?? 0,
      averagePerDay: data.average_per_day ?? 0,
      byProject: data.by_project ?? [],
      byDay: data.by_day ?? [],
    };
  } catch (e) {
    console.error('Failed to load report', e);
  }
}

function exportCsv() {
  const params = new URLSearchParams({
    start_date: startDate.value,
    end_date: endDate.value,
  });
  if (activeBoardId.value) params.set('board_id', activeBoardId.value);
  window.open(`/api/entries/export/csv?${params.toString()}`, '_blank');
}

// Reload the report when the active board changes.
watch(activeBoardId, () => loadReport());

onMounted(async () => {
  await fetchUser();
  setThisWeek();
});
</script>
