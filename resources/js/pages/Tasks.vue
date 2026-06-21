<template>
  <AppLayout>
    <div class="max-w-full mx-auto px-2">

      <!-- Board Selector -->
      <div class="relative z-30 bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg px-6 py-4 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <h2 class="text-lg font-semibold text-gray-800">{{ board?.name || 'No board' }}</h2>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1 relative filter-panel-container">
            <button
              @click="toggleFilterPanel"
              class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg transition-colors"
              :class="hasActiveFilters ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:bg-gray-100'"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
              </svg>
              Filter
              <span v-if="hasActiveFilters" class="bg-indigo-500 text-white rounded-full w-4 h-4 text-[10px] flex items-center justify-center">{{ activeFilterCount }}</span>
            </button>
            <button
              v-if="hasActiveFilters"
              @click.stop="clearFilters"
              class="p-1 text-gray-400 hover:text-red-500 transition-colors"
              title="Clear all filters"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Filter Panel -->
            <div
              v-if="showFilterPanel"
              class="absolute right-0 top-10 z-50 bg-white rounded-xl shadow-2xl border border-gray-100 w-72 max-h-[70vh] overflow-y-auto"
            >
              <div class="p-3 space-y-3">
                <!-- Search -->
                <div>
                  <input
                    ref="filterSearchInput"
                    v-model="filterSearch"
                    @keydown="onFilterKeydown"
                    placeholder="Search cards... (press / to focus)"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
                  />
                </div>

                <!-- Matched labels from search -->
                <div v-if="filterSearch.trim() && matchedLabels.length" class="space-y-1">
                  <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Matching labels</div>
                  <button
                    v-for="ml in matchedLabels"
                    :key="ml.id"
                    @click="toggleFilterLabel(ml.id)"
                    class="w-full flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-gray-50 text-left text-xs"
                  >
                    <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: ml.color }"></span>
                    <span class="flex-1">{{ ml.name }}</span>
                    <span v-if="filterLabelIds.includes(ml.id)" class="text-indigo-500">✓</span>
                  </button>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Due Date Filters -->
                <div>
                  <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wide mb-1">Due date</div>
                  <div class="space-y-0.5">
                    <button
                      v-for="opt in dueDateOptions"
                      :key="opt.value"
                      @click="toggleDueDateFilter(opt.value)"
                      class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-left text-xs transition-colors"
                      :class="filterDueDate === opt.value ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50 text-gray-600'"
                    >
                      <span class="flex-1">{{ opt.label }}</span>
                      <span v-if="filterDueDate === opt.value" class="text-indigo-500">✓</span>
                    </button>
                  </div>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Labels -->
                <div>
                  <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wide mb-1">Labels</div>
                  <div class="space-y-0.5">
                    <button
                      v-for="gl in globalLabels"
                      :key="gl.id"
                      @click="toggleFilterLabel(gl.id)"
                      class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-left text-xs transition-colors"
                      :class="filterLabelIds.includes(gl.id) ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50 text-gray-600'"
                    >
                      <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: gl.color }"></span>
                      <span class="flex-1">{{ gl.name }}</span>
                      <span v-if="filterLabelIds.includes(gl.id)" class="text-indigo-500">✓</span>
                    </button>
                    <div v-if="!globalLabels.length" class="text-xs text-gray-400 px-2 py-1">No labels</div>
                  </div>
                </div>

                <!-- Clear Filters -->
                <div v-if="hasActiveFilters" class="border-t border-gray-100 pt-2">
                  <button
                    @click="clearFilters"
                    class="w-full text-center text-xs text-red-500 hover:text-red-600 font-medium py-1"
                  >
                    Clear all filters
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="text-sm text-gray-400">
            {{ board?.columns?.length || 0 }} columns &middot;
            {{ totalTaskCount }} tasks
          </div>
        </div>
      </div>

      <!-- Kanban Board -->
      <div
        ref="boardContainer"
        class="board-container flex gap-4 pb-4 items-start"
        style="min-height: 70vh;"
        @mousedown="onBoardMouseDown"
      >

        <!-- Columns -->
        <div
          v-for="column in filteredColumns"
          :key="column.id"
          class="flex-shrink-0 w-80 column-wrapper"
          :class="{
            'column-being-dragged': draggedColumn?.id === column.id,
            'column-drop-before': columnDropTarget?.columnId === column.id && columnDropTarget?.before,
            'column-drop-after': columnDropTarget?.columnId === column.id && !columnDropTarget?.before,
          }"
          @dragover="onColumnDragOver($event, column)"
          @drop="onColumnDrop($event, column)"
        >
          <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg flex flex-col max-h-[80vh]">
            <!-- Column Header -->
            <div
              class="column-header px-4 py-3 flex items-center justify-between border-b border-gray-100 cursor-grab active:cursor-grabbing"
              :draggable="true"
              @dragstart="onColumnDragStart($event, column)"
              @dragend="onColumnDragEnd"
            >
              <div class="flex items-center gap-2">
                <h3 class="font-semibold text-gray-700 text-sm">{{ column.name }}</h3>
                <span class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5">
                  {{ (column.tasks || []).length }}
                </span>
              </div>
              <div class="relative">
                <button
                  @click="toggleColumnMenu(column.id)"
                  class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <circle cx="10" cy="4" r="1.5" /><circle cx="10" cy="10" r="1.5" /><circle cx="10" cy="16" r="1.5" />
                  </svg>
                </button>
                <!-- Column Menu -->
                <div
                  v-if="openColumnMenu === column.id"
                  class="absolute right-0 top-8 z-50 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-1 text-sm"
                >
                  <button
                    @click="startRenameColumn(column)"
                    class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-gray-700"
                  >
                    Rename
                  </button>
                  <div class="border-t border-gray-100 my-1"></div>
                  <div class="px-4 py-1 text-xs text-gray-400 font-medium uppercase tracking-wide">Sort by</div>
                  <button @click="sortColumn(column.id, 'due-asc')" class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-gray-700">Due date (oldest first)</button>
                  <button @click="sortColumn(column.id, 'due-desc')" class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-gray-700">Due date (newest first)</button>
                  <button @click="sortColumn(column.id, 'created-asc')" class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-gray-700">Created (oldest first)</button>
                  <button @click="sortColumn(column.id, 'created-desc')" class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-gray-700">Created (newest first)</button>
                  <button @click="sortColumn(column.id, 'priority-desc')" class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-gray-700">Priority (high &rarr; low)</button>
                  <button @click="sortColumn(column.id, 'priority-asc')" class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-gray-700">Priority (low &rarr; high)</button>
                  <button @click="sortColumn(column.id, 'name-asc')" class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-gray-700">Name (A &rarr; Z)</button>
                  <div class="border-t border-gray-100 my-1"></div>
                  <button
                    @click="confirmDeleteColumn(column)"
                    class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600"
                  >
                    Delete column
                  </button>
                </div>
              </div>
            </div>

            <!-- Tasks Container -->
            <div
              class="flex-1 overflow-y-auto p-2 pt-1 tasks-list"
              :class="{ 'column-drag-over': dropTarget?.columnId === column.id }"
              @dragover.prevent="onDragOver($event, column.id)"
              @dragleave="onDragLeave($event, column.id)"
              @drop="onDrop($event, column.id)"
              :data-column-id="column.id"
            >
              <template v-for="(task, idx) in (column.tasks || [])" :key="task.id">
                <!-- Drop placeholder before card -->
                <div
                  v-if="dropTarget?.columnId === column.id && dropTarget?.position === idx && draggedTask?.id !== task.id"
                  class="drop-placeholder"
                  :style="{ height: draggedCardHeight + 'px' }"
                >
                  <span class="text-xs text-indigo-400 font-medium">Drop here</span>
                </div>

                <!-- Task Card (Parent) -->
                <div
                  :draggable="true"
                  @dragstart="onDragStart($event, task, column.id)"
                  @dragend="onDragEnd"
                  class="task-card bg-white rounded-lg border border-gray-200 shadow-sm p-3 cursor-move group"
                  :class="{ 'task-card-done': isTaskDone(task) }"
                  @click="openTaskModal(task)"
                >
                  <!-- Card Top Row -->
                  <div class="flex items-start gap-2 mb-1 -mx-3 -mt-3 px-3 pt-3 pb-1 rounded-t-lg"
                    :style="task.labels && task.labels.length ? { backgroundColor: task.labels[0].color + '18' } : {}"
                  >
                    <input
                      type="checkbox"
                      class="task-done-checkbox"
                      :checked="isTaskDone(task)"
                      @click.stop.prevent="markTaskDone(task)"
                    />
                    <h4 class="text-sm font-medium flex-1 leading-snug"
                      :class="isTaskDone(task) ? 'line-through text-gray-400' : 'text-gray-800'"
                    >{{ task.title }}</h4>
                    <span v-if="priorityEmoji(task.priority)" class="text-sm flex-shrink-0" :title="priorityLabel(task.priority)">
                      {{ priorityEmoji(task.priority) }}
                    </span>
                  </div>

                  <!-- Card Bottom Row -->
                  <div class="flex items-center justify-between mt-1">
                    <div class="flex items-center gap-2 text-xs text-gray-400">
                      <!-- Due date -->
                      <span
                        v-if="task.due_date"
                        class="flex items-center gap-1"
                        :class="{ 'text-red-500 font-medium': isOverdue(task.due_date) }"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ formatDate(task.due_date) }}
                      </span>
                      <!-- Subtask count -->
                      <span v-if="task.subtasks && task.subtasks.length" class="flex items-center gap-1"
                        :class="subtaskDoneCount(task) === task.subtasks.length ? 'text-green-500' : ''"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/>
                        </svg>
                        {{ subtaskDoneCount(task) }}/{{ task.subtasks.length }}
                      </span>
                      <!-- Project -->
                      <span v-if="task.project" class="truncate max-w-[100px]" :title="task.project.name">
                        {{ task.project.name }}
                      </span>
                    </div>
                    <div class="flex items-center gap-1 transition-opacity"
                      :class="isTimerRunningForTask(task.id) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                    >
                      <button
                        @click.stop="toggleTaskTimer(task)"
                        class="p-1 rounded-lg transition-colors"
                        :class="isTimerRunningForTask(task.id)
                          ? 'text-red-500 hover:bg-red-50'
                          : 'text-gray-400 hover:bg-indigo-50 hover:text-indigo-500'"
                        :title="isTimerRunningForTask(task.id) ? 'Stop timer' : 'Start timer'"
                      >
                        <svg v-if="!isTimerRunningForTask(task.id)" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Subtask Cards -->
                <template v-for="subtask in visibleSubtasks(task)" :key="'sub-' + subtask.id">
                  <div
                    class="subtask-card bg-gray-50 rounded-lg border border-gray-200 shadow-sm p-2.5 ml-6 group"
                  >
                    <div class="flex items-center gap-2">
                      <input
                        type="checkbox"
                        class="subtask-done-checkbox"
                        :checked="isSubtaskDone(subtask)"
                        @click.stop="markSubtaskDone(subtask, task)"
                      />
                      <h4 class="text-xs font-medium text-gray-700 flex-1 leading-snug truncate">{{ subtask.title }}</h4>
                      <div class="flex items-center gap-1 transition-opacity"
                        :class="isTimerRunningForTask(subtask.id) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                      >
                        <button
                          @click.stop="toggleTaskTimer(subtask)"
                          class="p-0.5 rounded transition-colors"
                          :class="isTimerRunningForTask(subtask.id)
                            ? 'text-red-500 hover:bg-red-50'
                            : 'text-gray-400 hover:bg-indigo-50 hover:text-indigo-500'"
                          :title="isTimerRunningForTask(subtask.id) ? 'Stop timer' : 'Start timer'"
                        >
                          <svg v-if="!isTimerRunningForTask(subtask.id)" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                          </svg>
                          <svg v-else class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </template>

              <!-- Drop placeholder at bottom -->
              <div
                v-if="dropTarget?.columnId === column.id && dropTarget?.position >= (column.tasks || []).length"
                class="drop-placeholder"
                :style="{ height: draggedCardHeight + 'px' }"
              >
                <span class="text-xs text-indigo-400 font-medium">Drop here</span>
              </div>

              <!-- Empty column hint -->
              <div
                v-if="!(column.tasks || []).length && !(dropTarget?.columnId === column.id)"
                class="text-center text-xs text-gray-300 py-8"
              >
                Drop tasks here
              </div>
            </div>

            <!-- Add Task -->
            <div class="p-2 border-t border-gray-100">
              <div v-if="addingTaskColumnId === column.id" class="space-y-2">
                <input
                  ref="newTaskInput"
                  v-model="newTaskTitle"
                  @keydown.enter="createTask(column.id)"
                  @keydown.esc="cancelAddTask"
                  placeholder="Task title..."
                  class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
                />
                <div class="flex gap-2">
                  <button
                    @click="createTask(column.id)"
                    class="flex-1 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-medium rounded-lg px-3 py-1.5 hover:shadow-md transition-all"
                  >
                    Add
                  </button>
                  <button
                    @click="cancelAddTask"
                    class="text-xs text-gray-400 hover:text-gray-600 px-2"
                  >
                    Cancel
                  </button>
                </div>
              </div>
              <button
                v-else
                @click="startAddTask(column.id)"
                class="w-full text-left text-sm text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-lg px-3 py-2 transition-colors"
              >
                + Add task
              </button>
            </div>
          </div>
        </div>

        <!-- Add Column Button -->
        <div
          v-if="columns.length < 5"
          class="flex-shrink-0 w-80"
        >
          <div v-if="addingColumn" class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-4 space-y-2">
            <input
              ref="newColumnInput"
              v-model="newColumnName"
              @keydown.enter="createColumn"
              @keydown.esc="addingColumn = false"
              placeholder="Column name..."
              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
            />
            <div class="flex gap-2">
              <button
                @click="createColumn"
                class="flex-1 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-medium rounded-lg px-3 py-1.5 hover:shadow-md transition-all"
              >
                Add column
              </button>
              <button
                @click="addingColumn = false"
                class="text-xs text-gray-400 hover:text-gray-600 px-2"
              >
                Cancel
              </button>
            </div>
          </div>
          <button
            v-else
            @click="startAddColumn"
            class="w-full bg-white/60 backdrop-blur-sm rounded-2xl shadow border-2 border-dashed border-gray-200 hover:border-indigo-300 hover:bg-white/80 text-gray-400 hover:text-indigo-500 text-sm font-medium py-10 transition-all"
          >
            + Add column
          </button>
        </div>
      </div>

      <!-- Rename Column Modal -->
      <Teleport to="body">
        <div v-if="renamingColumn" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="renamingColumn = null"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl p-6 w-96">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Rename column</h3>
            <input
              v-model="renameColumnName"
              @keydown.enter="doRenameColumn"
              class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none mb-4"
            />
            <div class="flex justify-end gap-2">
              <button @click="renamingColumn = null" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Cancel</button>
              <button
                @click="doRenameColumn"
                class="px-4 py-2 text-sm bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:shadow-md transition-all"
              >
                Save
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Delete Column Confirmation -->
      <Teleport to="body">
        <div v-if="deletingColumn" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="deletingColumn = null"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl p-6 w-96">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Delete column</h3>
            <p class="text-sm text-gray-500 mb-4">
              Are you sure you want to delete <strong>{{ deletingColumn.name }}</strong>?
              All tasks in this column will be deleted.
            </p>
            <div class="flex justify-end gap-2">
              <button @click="deletingColumn = null" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Cancel</button>
              <button
                @click="doDeleteColumn"
                class="px-4 py-2 text-sm bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Task Modal -->
      <Teleport to="body">
        <div v-if="modalTask" class="fixed inset-0 z-50 flex items-center justify-center" tabindex="0" ref="modalOverlay" @keydown="onModalKeydown">
          <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeTaskModal"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4">
            <div class="p-6 space-y-5">

              <!-- Title -->
              <input
                v-model="modalTask.title"
                @input="debouncedSave"
                placeholder="Task title..."
                class="w-full text-xl font-bold text-gray-800 border-none outline-none bg-transparent placeholder-gray-300"
              />

              <!-- Top Controls Row -->
              <div class="flex flex-wrap items-center gap-3">
                <!-- Priority -->
                <button
                  @click="cyclePriority"
                  class="flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors"
                >
                  <span v-if="priorityEmoji(modalTask.priority)">{{ priorityEmoji(modalTask.priority) }}</span>
                  <span class="text-gray-600 capitalize">{{ modalTask.priority || 'none' }}</span>
                </button>

                <!-- Due Date -->
                <div class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <input
                    v-model="modalTask.due_date"
                    @change="debouncedSave"
                    type="date"
                    class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
                  />
                </div>

                <!-- Project -->
                <select
                  v-model="modalTask.project_id"
                  @change="debouncedSave"
                  class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
                >
                  <option :value="null">No project</option>
                  <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>

              <!-- Description -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <h4 class="text-sm font-semibold text-gray-600">Description</h4>
                  <button
                    @click="editingDescription ? exitEditDescription() : startEditDescription()"
                    class="text-xs font-medium px-2.5 py-1 rounded-lg transition-colors"
                    :class="editingDescription
                      ? 'bg-indigo-100 text-indigo-700'
                      : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
                  >
                    {{ editingDescription ? '✏️ Editing' : '👁️ Viewing' }}
                  </button>
                </div>
                <textarea
                  v-if="editingDescription"
                  ref="descriptionTextarea"
                  v-model="modalTask.description"
                  @input="debouncedSave"
                  placeholder="Add a description... (supports **bold**, *italic*, `code`, [links](url), # headings)"
                  rows="6"
                  class="w-full rounded-lg border border-indigo-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none resize-y"
                ></textarea>
                <div
                  v-else
                  @dblclick="startEditDescription"
                  class="prose prose-sm max-w-none p-3 bg-gray-50 rounded-lg min-h-[80px] cursor-text hover:bg-gray-100 transition-colors"
                  :class="{ 'text-gray-400 italic': !modalTask.description }"
                  v-html="modalTask.description ? renderMarkdown(modalTask.description) : 'Double-click to add a description...'"
                ></div>
              </div>

              <!-- Labels -->
              <div>
                <h4 class="text-sm font-semibold text-gray-600 mb-2">Labels</h4>
                <div class="flex flex-wrap gap-1.5 mb-2">
                  <span
                    v-for="label in (modalTask.labels || [])"
                    :key="label.id"
                    class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium"
                    :style="{ backgroundColor: label.color + '20', color: label.color }"
                  >
                    {{ label.label || label.global_label?.name }}
                    <button
                      @click="removeLabel(label.id)"
                      class="hover:opacity-70 ml-0.5"
                    >&times;</button>
                  </span>
                  <span v-if="!(modalTask.labels || []).length" class="text-xs text-gray-400">No labels</span>
                </div>
                <!-- Label Picker -->
                <div class="relative">
                  <button
                    @click="showLabelPicker = !showLabelPicker"
                    class="text-xs text-indigo-500 hover:text-indigo-700 font-medium"
                  >
                    + Add label
                  </button>
                  <div
                    v-if="showLabelPicker"
                    class="absolute left-0 top-6 z-10 bg-white rounded-xl shadow-xl border border-gray-100 p-3 w-64 max-h-48 overflow-y-auto"
                  >
                    <div v-if="!globalLabels.length" class="text-xs text-gray-400">No global labels available</div>
                    <button
                      v-for="(gl, glIdx) in globalLabels"
                      :key="gl.id"
                      @click="toggleLabelByIndex(glIdx)"
                      class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 text-left text-sm"
                      :class="isLabelApplied(gl) ? 'bg-indigo-50' : ''"
                    >
                      <span v-if="glIdx < 9" class="text-[10px] text-gray-400 font-mono w-3 text-center shrink-0">{{ glIdx + 1 }}</span>
                      <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: gl.color }"></span>
                      <span class="flex-1">{{ gl.name }}</span>
                      <span v-if="isLabelApplied(gl)" class="text-indigo-500 text-xs">✓</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Subtasks -->
              <div v-if="!modalTask.parent_task_id">
                <h4 class="text-sm font-semibold text-gray-600 mb-2">Subtasks</h4>
                <div class="space-y-1 mb-2">
                  <div
                    v-for="(st, stIndex) in (modalTask.subtasks || [])"
                    :key="st.id"
                    class="flex items-center gap-2 px-2 py-1.5 bg-gray-50 rounded-lg group/st"
                  >
                    <div class="flex flex-col -my-1">
                      <button
                        @click="moveSubtask(stIndex, -1)"
                        :disabled="stIndex === 0"
                        class="p-0 text-gray-300 hover:text-indigo-500 disabled:opacity-20 disabled:cursor-default leading-none"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                      </button>
                      <button
                        @click="moveSubtask(stIndex, 1)"
                        :disabled="stIndex === (modalTask.subtasks || []).length - 1"
                        class="p-0 text-gray-300 hover:text-indigo-500 disabled:opacity-20 disabled:cursor-default leading-none"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                      </button>
                    </div>
                    <input
                      type="checkbox"
                      class="subtask-done-checkbox"
                      :checked="isSubtaskDone(st)"
                      @change="toggleSubtaskDoneFromModal(st)"
                    />
                    <input
                      v-if="editingSubtaskId === st.id"
                      v-model="editingSubtaskTitle"
                      @blur="saveSubtaskRename(st)"
                      @keydown.enter="saveSubtaskRename(st)"
                      @keydown.escape="editingSubtaskId = null"
                      class="subtask-rename-input text-sm flex-1 border border-indigo-300 rounded px-1.5 py-0.5 outline-none focus:ring-2 focus:ring-indigo-200"
                    />
                    <span
                      v-else
                      class="text-sm flex-1 cursor-pointer hover:text-indigo-600"
                      :class="isSubtaskDone(st) ? 'line-through text-gray-400' : 'text-gray-700'"
                      @dblclick="startSubtaskRename(st)"
                    >{{ st.title }}</span>
                    <button
                      v-if="editingSubtaskId !== st.id"
                      @click="startSubtaskRename(st)"
                      class="p-0.5 text-gray-300 hover:text-indigo-500 opacity-0 group-hover/st:opacity-100 transition-all"
                      title="Rename"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button
                      @click="deleteSubtask(st)"
                      class="p-0.5 text-gray-300 hover:text-red-500 opacity-0 group-hover/st:opacity-100 transition-all"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>
                </div>
                <div class="flex gap-2">
                  <input
                    v-model="newSubtaskTitle"
                    @keydown.enter="addSubtask"
                    placeholder="Add a subtask..."
                    class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
                  />
                  <button
                    @click="addSubtask"
                    :disabled="!newSubtaskTitle.trim()"
                    class="px-3 py-1.5 text-xs font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:shadow-md transition-all disabled:opacity-50"
                  >Add</button>
                </div>
              </div>

              <!-- Links -->
              <div>
                <h4 class="text-sm font-semibold text-gray-600 mb-2">Links</h4>
                <div class="space-y-1.5 mb-2">
                  <div
                    v-for="link in (modalTask.links || [])"
                    :key="link.id"
                    class="flex items-center gap-2 group text-sm"
                  >
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    <a
                      :href="link.url"
                      target="_blank"
                      rel="noopener"
                      class="text-indigo-500 hover:text-indigo-700 hover:underline truncate flex-1"
                    >
                      {{ link.title || link.url }}
                    </a>
                    <button
                      @click="deleteLink(link.id)"
                      class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-all p-0.5"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                  <div v-if="!(modalTask.links || []).length" class="text-xs text-gray-400">No links</div>
                </div>
                <!-- Add Link -->
                <div v-if="addingLink" class="flex gap-2 items-end">
                  <div class="flex-1 space-y-1">
                    <input
                      v-model="newLinkTitle"
                      placeholder="Title"
                      class="w-full text-sm rounded-lg border border-gray-200 px-3 py-1.5 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
                    />
                    <input
                      v-model="newLinkUrl"
                      @keydown.enter="addLink"
                      placeholder="https://..."
                      class="w-full text-sm rounded-lg border border-gray-200 px-3 py-1.5 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
                    />
                  </div>
                  <div class="flex gap-1">
                    <button @click="addLink" class="text-xs font-medium text-indigo-500 hover:text-indigo-700 px-2 py-1.5">Add</button>
                    <button @click="addingLink = false" class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1.5">Cancel</button>
                  </div>
                </div>
                <button
                  v-else
                  @click="addingLink = true"
                  class="text-xs text-indigo-500 hover:text-indigo-700 font-medium"
                >
                  + Add link
                </button>
              </div>

              <!-- Time Tracking -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <h4 class="text-sm font-semibold text-gray-600">Time Tracking</h4>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Total: {{ formatDuration(modalTaskTotalTime) }}</span>
                    <button
                      v-if="modalTaskTimeEntries.length"
                      @click="exportTaskCsv"
                      class="text-xs text-indigo-500 hover:text-indigo-700 font-medium"
                    >
                      Export CSV
                    </button>
                  </div>
                </div>
                <!-- Timer Control -->
                <div class="mb-3">
                  <button
                    @click="toggleModalTaskTimer"
                    class="flex items-center gap-2 text-sm px-4 py-2 rounded-lg border transition-all"
                    :class="isTimerRunningForTask(modalTask.id)
                      ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100'
                      : 'border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100'"
                  >
                    <svg v-if="!isTimerRunningForTask(modalTask.id)" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    {{ isTimerRunningForTask(modalTask.id) ? 'Stop timer' : 'Start timer' }}
                  </button>
                </div>
                <!-- Time Entries List -->
                <div v-if="modalTaskTimeEntries.length" class="space-y-1 max-h-40 overflow-y-auto">
                  <div
                    v-for="entry in modalTaskTimeEntries"
                    :key="entry.id"
                    class="flex items-center justify-between text-xs text-gray-500 py-1 px-2 bg-gray-50 rounded-lg"
                  >
                    <div class="flex items-center gap-1">
                      <span>{{ formatDateTime(entry.start_time) }}</span>
                      <span v-if="entry.subtask_name" class="text-indigo-500 text-[10px] font-medium px-1 bg-indigo-50 rounded">{{ entry.subtask_name }}</span>
                    </div>
                    <span v-if="entry.end_time">{{ formatDuration(entryDuration(entry)) }}</span>
                    <span v-else class="text-green-500 font-medium">Running...</span>
                  </div>
                </div>
                <div v-else class="text-xs text-gray-400">No time entries</div>
              </div>

              <!-- Delete Task -->
              <div class="pt-3 border-t border-gray-100">
                <button
                  v-if="!confirmingDelete"
                  @click="confirmingDelete = true"
                  class="text-sm text-red-500 hover:text-red-700 font-medium"
                >
                  Delete task
                </button>
                <div v-else class="flex items-center gap-3">
                  <span class="text-sm text-red-600">Are you sure?</span>
                  <button
                    @click="deleteTask"
                    class="text-sm bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 transition-colors"
                  >
                    Yes, delete
                  </button>
                  <button
                    @click="confirmingDelete = false"
                    class="text-sm text-gray-500 hover:text-gray-700"
                  >
                    Cancel
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>
      </Teleport>

    </div>


  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuth } from '@/composables/useAuth';
import { useApi } from '@/composables/useApi';
import { useProjects } from '@/composables/useProjects';
import { useTimer } from '@/composables/useTimer';
import { useBackground } from '@/composables/useBackground';
import { useBoard } from '@/composables/useBoard';

const { user } = useAuth();
const api = useApi();
const { projects, loadProjects } = useProjects();
const { runningEntry, checkRunning, start: startTimer, stop: stopTimer } = useTimer();
const { loadBoardBackground } = useBackground();
const { activeBoardId, loadBoards } = useBoard();

// ─── Board State ───────────────────────────────────────────────────
// Board switching now lives in the top nav (useBoard); this page just reacts to
// the active board. selectedBoardId is kept as an alias so existing references work.
const selectedBoardId = activeBoardId;
const board = ref(null);

watch(activeBoardId, () => {
  loadBoard();
});

const columns = computed(() => board.value?.columns || []);
const totalTaskCount = computed(() =>
  columns.value.reduce((sum, c) => sum + (c.tasks || []).length, 0)
);

// ─── Filters ──────────────────────────────────────────────────────
const showFilterPanel = ref(false);
const filterSearch = ref('');
const filterSearchInput = ref(null);
const filterDueDate = ref('');
const filterLabelIds = ref([]);

const dueDateOptions = [
  { value: 'none', label: 'No due date' },
  { value: 'overdue', label: 'Overdue' },
  { value: 'day', label: 'Due in next day' },
  { value: 'week', label: 'Due in next week' },
  { value: 'month', label: 'Due in next month' },
];

const matchedLabels = computed(() => {
  const q = filterSearch.value.trim().toLowerCase();
  if (!q) return [];
  return globalLabels.value.filter(gl => gl.name.toLowerCase().includes(q));
});

const hasActiveFilters = computed(() =>
  filterSearch.value.trim() !== '' || filterDueDate.value !== '' || filterLabelIds.value.length > 0
);

const activeFilterCount = computed(() => {
  let n = 0;
  if (filterSearch.value.trim()) n++;
  if (filterDueDate.value) n++;
  n += filterLabelIds.value.length;
  return n;
});

function toggleFilterLabel(id) {
  const idx = filterLabelIds.value.indexOf(id);
  if (idx >= 0) {
    filterLabelIds.value.splice(idx, 1);
  } else {
    filterLabelIds.value.push(id);
  }
}

function toggleDueDateFilter(value) {
  filterDueDate.value = filterDueDate.value === value ? '' : value;
}

function toggleFilterPanel() {
  showFilterPanel.value = !showFilterPanel.value;
  if (showFilterPanel.value) {
    nextTick(() => filterSearchInput.value?.focus());
  }
}

function clearFilters() {
  filterSearch.value = '';
  filterDueDate.value = '';
  filterLabelIds.value = [];
}

function taskMatchesFilters(task) {
  // Text search
  const q = filterSearch.value.trim().toLowerCase();
  if (q && !task.title.toLowerCase().includes(q)) return false;

  // Due date filter
  if (filterDueDate.value) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const due = task.due_date ? new Date(task.due_date) : null;

    switch (filterDueDate.value) {
      case 'none':
        if (due) return false;
        break;
      case 'overdue':
        if (!due || due >= today) return false;
        break;
      case 'day': {
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        if (!due || due < today || due > tomorrow) return false;
        break;
      }
      case 'week': {
        const nextWeek = new Date(today);
        nextWeek.setDate(nextWeek.getDate() + 7);
        if (!due || due < today || due > nextWeek) return false;
        break;
      }
      case 'month': {
        const nextMonth = new Date(today);
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        if (!due || due < today || due > nextMonth) return false;
        break;
      }
    }
  }

  // Label filter
  if (filterLabelIds.value.length > 0) {
    const taskLabelIds = (task.labels || []).map(l => l.global_label_id || l.id);
    if (!filterLabelIds.value.some(id => taskLabelIds.includes(id))) return false;
  }

  return true;
}

const filteredColumns = computed(() => {
  if (!hasActiveFilters.value) return columns.value;
  return columns.value.map(col => ({
    ...col,
    tasks: (col.tasks || []).filter(taskMatchesFilters),
  }));
});

async function loadBoard() {
  if (!selectedBoardId.value) return;
  board.value = await api.get(`/boards/${selectedBoardId.value}`);
  if (board.value?.columns) {
    await Promise.all(
      board.value.columns.map(async (col) => {
        col.tasks = await api.get(`/columns/${col.id}/tasks`);
      })
    );
  }
  try {
    globalLabels.value = await api.get(`/boards/${selectedBoardId.value}/labels`);
  } catch {
    globalLabels.value = [];
  }
  loadBoardBackground(selectedBoardId.value);
}

// ─── Column Management ─────────────────────────────────────────────

const openColumnMenu = ref(null);
const addingColumn = ref(false);
const newColumnName = ref('');
const newColumnInput = ref(null);
const renamingColumn = ref(null);
const renameColumnName = ref('');
const deletingColumn = ref(null);

function toggleColumnMenu(colId) {
  openColumnMenu.value = openColumnMenu.value === colId ? null : colId;
}

function startAddColumn() {
  addingColumn.value = true;
  newColumnName.value = '';
  nextTick(() => newColumnInput.value?.focus());
}

async function createColumn() {
  const name = newColumnName.value.trim();
  if (!name || !selectedBoardId.value) return;
  await api.post('/columns', {
    board_id: selectedBoardId.value,
    name,
    position: columns.value.length,
  });
  addingColumn.value = false;
  newColumnName.value = '';
  await loadBoard();
}

function startRenameColumn(col) {
  openColumnMenu.value = null;
  renamingColumn.value = col;
  renameColumnName.value = col.name;
}

async function doRenameColumn() {
  if (!renamingColumn.value || !renameColumnName.value.trim()) return;
  await api.put(`/columns/${renamingColumn.value.id}`, {
    name: renameColumnName.value.trim(),
  });
  renamingColumn.value = null;
  await loadBoard();
}

function confirmDeleteColumn(col) {
  openColumnMenu.value = null;
  deletingColumn.value = col;
}

async function doDeleteColumn() {
  if (!deletingColumn.value) return;
  await api.del(`/columns/${deletingColumn.value.id}`);
  deletingColumn.value = null;
  await loadBoard();
}

// ─── Column Reordering (drag header left/right) ────────────────────

const draggedColumn = ref(null);
const columnDropTarget = ref(null); // { columnId, before }

function onColumnDragStart(event, column) {
  draggedColumn.value = column;
  columnDropTarget.value = null;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', `column:${column.id}`);
}

function onColumnDragOver(event, column) {
  if (!draggedColumn.value) return; // ignore task drags
  event.preventDefault();
  event.dataTransfer.dropEffect = 'move';
  if (column.id === draggedColumn.value.id) {
    columnDropTarget.value = null;
    return;
  }
  const rect = event.currentTarget.getBoundingClientRect();
  const before = event.clientX < rect.left + rect.width / 2;
  columnDropTarget.value = { columnId: column.id, before };
}

function onColumnDragEnd() {
  draggedColumn.value = null;
  columnDropTarget.value = null;
}

async function onColumnDrop(event, column) {
  if (!draggedColumn.value) return; // ignore task drags
  event.preventDefault();
  const dragged = draggedColumn.value;
  const target = columnDropTarget.value;
  draggedColumn.value = null;
  columnDropTarget.value = null;
  if (!target || target.columnId === dragged.id) return;

  // Build the new ordering from the full (unfiltered) column list
  const ordered = columns.value.filter(c => c.id !== dragged.id);
  const targetIdx = ordered.findIndex(c => c.id === target.columnId);
  if (targetIdx === -1) return;
  ordered.splice(target.before ? targetIdx : targetIdx + 1, 0, dragged);

  const ids = ordered.map(c => c.id);
  // Optimistic reorder so the board updates immediately
  if (board.value?.columns) {
    board.value.columns = ids.map(id => board.value.columns.find(c => c.id === id));
  }
  await api.patch(`/boards/${selectedBoardId.value}/columns/reorder`, { columnIds: ids });
}

// ─── Column Sorting ────────────────────────────────────────────────

const priorityOrder = { high: 3, medium: 2, low: 1 };

function sortColumn(columnId, mode) {
  openColumnMenu.value = null;
  const col = columns.value.find(c => c.id === columnId);
  if (!col?.tasks) return;

  const tasks = [...col.tasks];
  switch (mode) {
    case 'due-asc':
      tasks.sort((a, b) => (a.due_date || '9999') < (b.due_date || '9999') ? -1 : 1);
      break;
    case 'due-desc':
      tasks.sort((a, b) => (b.due_date || '') < (a.due_date || '') ? -1 : 1);
      break;
    case 'created-asc':
      tasks.sort((a, b) => (a.created_at || '') < (b.created_at || '') ? -1 : 1);
      break;
    case 'created-desc':
      tasks.sort((a, b) => (b.created_at || '') < (a.created_at || '') ? -1 : 1);
      break;
    case 'priority-desc':
      tasks.sort((a, b) => (priorityOrder[b.priority] || 0) - (priorityOrder[a.priority] || 0));
      break;
    case 'priority-asc':
      tasks.sort((a, b) => (priorityOrder[a.priority] || 0) - (priorityOrder[b.priority] || 0));
      break;
    case 'name-asc':
      tasks.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
      break;
  }

  col.tasks = tasks;
  // Persist new positions
  tasks.forEach((task, idx) => {
    api.patch(`/tasks/${task.id}/move`, { column_id: columnId, position: idx });
  });
}

// ─── Task CRUD ─────────────────────────────────────────────────────

const addingTaskColumnId = ref(null);
const newTaskTitle = ref('');
const newTaskInput = ref(null);

function startAddTask(columnId) {
  addingTaskColumnId.value = columnId;
  newTaskTitle.value = '';
  nextTick(() => {
    const el = Array.isArray(newTaskInput.value) ? newTaskInput.value[0] : newTaskInput.value;
    el?.focus();
  });
}

function cancelAddTask() {
  addingTaskColumnId.value = null;
  newTaskTitle.value = '';
}

async function createTask(columnId) {
  const title = newTaskTitle.value.trim();
  if (!title) return;
  const col = columns.value.find(c => c.id === columnId);
  await api.post('/tasks', {
    column_id: columnId,
    title,
    position: (col?.tasks || []).length,
  });
  cancelAddTask();
  await loadBoard();
}

function isTaskDone(task) {
  return !!task.completed_at;
}

async function markTaskDone(task) {
  const wasDone = isTaskDone(task);
  // Stop timer if running on this task (only when marking done, not when un-checking)
  if (!wasDone && isTimerRunningForTask(task.id)) {
    await stopTimer();
  }
  const updated = await api.patch(`/tasks/${task.id}/toggle-complete`);
  task.completed_at = updated.completed_at;
  // Marking done can trigger automations (move card, add label, shift due date).
  // Reload so those server-side changes show without a manual refresh.
  if (!wasDone) {
    await loadBoard();
  }
}

// ─── Drag & Drop ───────────────────────────────────────────────────

const draggedTask = ref(null);
const dragSourceColumnId = ref(null);
const dropTarget = ref(null);
const draggedCardHeight = ref(60);
let draggedCardEl = null;

function onDragStart(event, task, columnId) {
  draggedTask.value = task;
  dragSourceColumnId.value = columnId;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', String(task.id));

  // Capture the card element and height
  draggedCardEl = event.target.closest('.task-card');
  if (draggedCardEl) {
    draggedCardHeight.value = draggedCardEl.offsetHeight;
  }

  // Capture subtask card heights too
  const subtaskCount = visibleSubtasks(task).length;
  if (subtaskCount && draggedCardEl) {
    // Include subtask height in placeholder
    let nextEl = draggedCardEl.nextElementSibling;
    let extraHeight = 0;
    for (let i = 0; i < subtaskCount && nextEl; i++) {
      if (nextEl.classList.contains('subtask-card')) {
        extraHeight += nextEl.offsetHeight + 8; // card + margin
      }
      nextEl = nextEl.nextElementSibling;
    }
    draggedCardHeight.value += extraHeight;
  }

  // Let the browser capture the drag ghost image, THEN hide the card
  requestAnimationFrame(() => {
    if (draggedCardEl && draggedTask.value?.id === task.id) {
      draggedCardEl.classList.add('dragging');
      // Also hide subtask cards
      let nextEl = draggedCardEl.nextElementSibling;
      while (nextEl && nextEl.classList.contains('subtask-card')) {
        nextEl.classList.add('dragging');
        nextEl = nextEl.nextElementSibling;
      }
    }
  });
}

function onDragEnd() {
  // Restore the card + subtask visibility
  if (draggedCardEl) {
    draggedCardEl.classList.remove('dragging');
    // Unhide subtask cards
    document.querySelectorAll('.subtask-card.dragging').forEach(el => el.classList.remove('dragging'));
    draggedCardEl = null;
  }
  draggedTask.value = null;
  dragSourceColumnId.value = null;
  dropTarget.value = null;
}

function onDragOver(event, columnId) {
  event.dataTransfer.dropEffect = 'move';
  if (!draggedTask.value) return;

  const col = columns.value.find(c => c.id === columnId);
  if (!col) return;

  const container = event.currentTarget;
  const mouseY = event.clientY;

  // Get visible parent task cards (not dragged, not subtasks)
  const parentCards = Array.from(container.querySelectorAll('.task-card:not(.dragging)'));

  // For each parent card, compute its group bounds (parent + visible subtasks below it)
  const groups = parentCards.map(card => {
    const top = card.getBoundingClientRect().top;
    let bottom = card.getBoundingClientRect().bottom;
    let next = card.nextElementSibling;
    while (next && next.classList.contains('subtask-card') && !next.classList.contains('dragging')) {
      bottom = next.getBoundingClientRect().bottom;
      next = next.nextElementSibling;
    }
    return { top, bottom };
  });

  // Build slots (gaps between task groups)
  const slots = [];
  for (let i = 0; i <= groups.length; i++) {
    let slotY;
    if (groups.length === 0) {
      const cr = container.getBoundingClientRect();
      slotY = cr.top + cr.height / 2;
    } else if (i === 0) {
      slotY = groups[0].top;
    } else if (i === groups.length) {
      slotY = groups[groups.length - 1].bottom;
    } else {
      slotY = (groups[i - 1].bottom + groups[i].top) / 2;
    }
    slots.push({ index: i, slotY });
  }

  // Find closest slot to mouse
  let bestSlot = slots[0];
  let bestDist = Math.abs(mouseY - bestSlot.slotY);
  for (let s = 1; s < slots.length; s++) {
    const dist = Math.abs(mouseY - slots[s].slotY);
    if (dist < bestDist) {
      bestDist = dist;
      bestSlot = slots[s];
    }
  }

  // Map visible slot index back to data index (account for hidden dragged card)
  const tasks = col.tasks || [];
  let dataIndex = bestSlot.index;
  if (dragSourceColumnId.value === columnId) {
    let visibleCount = 0;
    let actualIndex = 0;
    for (let t = 0; t < tasks.length; t++) {
      if (tasks[t].id === draggedTask.value.id) continue;
      if (visibleCount === bestSlot.index) {
        actualIndex = t;
        break;
      }
      visibleCount++;
      actualIndex = t + 1;
    }
    dataIndex = actualIndex;
  }

  dropTarget.value = { columnId, position: dataIndex };
}

function onDragLeave(event, columnId) {
  // Only clear if we actually left the container
  const container = event.currentTarget;
  const related = event.relatedTarget;
  if (related && container.contains(related)) return;
  if (dropTarget.value?.columnId === columnId) {
    dropTarget.value = null;
  }
}

async function onDrop(event, columnId) {
  event.preventDefault();
  if (!draggedTask.value || !dropTarget.value) return;

  const taskId = draggedTask.value.id;
  const { position } = dropTarget.value;
  const sourceColumnId = dragSourceColumnId.value;

  // Optimistic local move
  const sourceCol = columns.value.find(c => c.id === sourceColumnId);
  const destCol = columns.value.find(c => c.id === columnId);
  if (sourceCol && destCol) {
    const taskIndex = (sourceCol.tasks || []).findIndex(t => t.id === taskId);
    if (taskIndex >= 0) {
      const [movedTask] = sourceCol.tasks.splice(taskIndex, 1);
      movedTask.column_id = columnId;
      if (!destCol.tasks) destCol.tasks = [];
      const insertAt = Math.min(position, destCol.tasks.length);
      destCol.tasks.splice(insertAt, 0, movedTask);
    }
  }

  // Clear drag state
  draggedTask.value = null;
  dragSourceColumnId.value = null;
  dropTarget.value = null;

  // Persist to API then refresh
  try {
    await api.patch(`/tasks/${taskId}/move`, {
      column_id: columnId,
      position,
    });
  } catch (err) {
    console.error('Failed to move task:', err);
  }
  await loadBoard();
}

// ─── Task Modal ────────────────────────────────────────────────────

const modalTask = ref(null);
const modalOverlay = ref(null);
const editingDescription = ref(false);
const descriptionTextarea = ref(null);
const showLabelPicker = ref(false);
const globalLabels = ref([]);
const confirmingDelete = ref(false);

// Subtasks
const newSubtaskTitle = ref('');
const editingSubtaskId = ref(null);
const editingSubtaskTitle = ref('');

// Links
const addingLink = ref(false);
const newLinkTitle = ref('');
const newLinkUrl = ref('');

// Time entries
const modalTaskTimeEntries = ref([]);

const modalTaskTotalTime = computed(() => {
  return modalTaskTimeEntries.value.reduce((sum, e) => sum + entryDuration(e), 0);
});

const availableLabels = computed(() => {
  const existing = (modalTask.value?.labels || []).map(l => l.global_label_id || l.id);
  return globalLabels.value.filter(gl => !existing.includes(gl.id));
});

async function openTaskModal(task) {
  if (saveTimeout) clearTimeout(saveTimeout);
  modalDirty = false;
  confirmingDelete.value = false;
  editingDescription.value = false;
  showLabelPicker.value = false;
  addingLink.value = false;
  newSubtaskTitle.value = '';
  newLinkTitle.value = '';
  newLinkUrl.value = '';

  // Load full task data
  const fullTask = await api.get(`/tasks/${task.id}`);
  modalTask.value = {
    ...fullTask,
    due_date: fullTask.due_date ? fullTask.due_date.substring(0, 10) : null,
  };

  // Load time entries
  try {
    modalTaskTimeEntries.value = await api.get(`/tasks/${task.id}/time-entries`);
  } catch {
    modalTaskTimeEntries.value = [];
  }

  // Labels are already loaded from loadBoard; refresh in case they changed
  if (selectedBoardId.value && !globalLabels.value.length) {
    try {
      globalLabels.value = await api.get(`/boards/${selectedBoardId.value}/labels`);
    } catch {
      globalLabels.value = [];
    }
  }

  // Focus modal overlay so keyboard shortcuts work
  nextTick(() => {
    modalOverlay.value?.focus();
  });
}

async function closeTaskModal() {
  if (saveTimeout) clearTimeout(saveTimeout);
  if (modalTask.value && modalDirty) {
    await saveModalTask();
  }
  modalTask.value = null;
  modalTaskTimeEntries.value = [];
  modalDirty = false;
  await loadBoard();
}

// Auto-save debounce
let saveTimeout = null;
let modalDirty = false;

function debouncedSave() {
  modalDirty = true;
  if (saveTimeout) clearTimeout(saveTimeout);
  saveTimeout = setTimeout(() => saveModalTask(), 1000);
}

async function saveModalTask() {
  if (!modalTask.value) return;
  if (saveTimeout) clearTimeout(saveTimeout);
  try {
    await api.put(`/tasks/${modalTask.value.id}`, {
      title: modalTask.value.title,
      description: modalTask.value.description,
      priority: modalTask.value.priority,
      due_date: modalTask.value.due_date || null,
      project_id: modalTask.value.project_id || null,
      column_id: modalTask.value.column_id,
      position: modalTask.value.position,
    });
    modalDirty = false;
  } catch (err) {
    console.error('Failed to save task:', err);
  }
}

function cyclePriority() {
  const order = ['none', 'low', 'medium', 'high'];
  const current = modalTask.value.priority || 'none';
  const idx = order.indexOf(current);
  modalTask.value.priority = order[(idx + 1) % order.length];
  debouncedSave();
}

// Labels
function isLabelApplied(gl) {
  return (modalTask.value?.labels || []).some(l => (l.global_label_id || l.id) === gl.id);
}

async function addLabel(globalLabel) {
  showLabelPicker.value = false;
  await api.post(`/tasks/${modalTask.value.id}/labels`, {
    global_label_id: globalLabel.id,
  });
  const fullTask = await api.get(`/tasks/${modalTask.value.id}`);
  modalTask.value.labels = fullTask.labels;
  await loadBoard();
}

async function removeLabel(labelId) {
  await api.del(`/labels/${labelId}`);
  modalTask.value.labels = (modalTask.value.labels || []).filter(l => l.id !== labelId);
  await loadBoard();
}

// Subtasks
function isSubtaskDone(subtask) {
  return subtask._done || !!subtask.completed_at;
}

function subtaskDoneCount(task) {
  return (task.subtasks || []).filter(st => isSubtaskDone(st)).length;
}

function visibleSubtasks(task) {
  return (task.subtasks || []).filter(st => !isSubtaskDone(st));
}

async function addSubtask() {
  const title = newSubtaskTitle.value.trim();
  if (!title || !modalTask.value) return;
  await api.post('/tasks', {
    column_id: modalTask.value.column_id,
    parent_task_id: modalTask.value.id,
    title,
    position: (modalTask.value.subtasks || []).length,
  });
  newSubtaskTitle.value = '';
  // Refresh modal task to get updated subtasks
  const fullTask = await api.get(`/tasks/${modalTask.value.id}`);
  modalTask.value.subtasks = fullTask.subtasks || [];
  await loadBoard();
}

async function deleteSubtask(subtask) {
  await api.del(`/tasks/${subtask.id}`);
  modalTask.value.subtasks = (modalTask.value.subtasks || []).filter(st => st.id !== subtask.id);
  await loadBoard();
}

async function markSubtaskDone(subtask, parentTask) {
  if (!isSubtaskDone(subtask)) {
    if (isTimerRunningForTask(subtask.id)) {
      await stopTimer();
      await checkRunning();
    }
  }
  await api.patch(`/tasks/${subtask.id}/toggle-complete`);
  await loadBoard();
}

async function toggleSubtaskDoneFromModal(subtask) {
  if (!isSubtaskDone(subtask)) {
    if (isTimerRunningForTask(subtask.id)) {
      await stopTimer();
      await checkRunning();
    }
  }
  const updated = await api.patch(`/tasks/${subtask.id}/toggle-complete`);
  subtask.completed_at = updated.completed_at;
  await loadBoard();
}

function startSubtaskRename(st) {
  editingSubtaskId.value = st.id;
  editingSubtaskTitle.value = st.title;
  nextTick(() => {
    const el = document.querySelector('.subtask-rename-input');
    el?.focus();
    el?.select();
  });
}

async function saveSubtaskRename(st) {
  const title = editingSubtaskTitle.value.trim();
  editingSubtaskId.value = null;
  if (!title || title === st.title) return;
  await api.put(`/tasks/${st.id}`, { ...st, title });
  st.title = title;
  await loadBoard();
}

async function moveSubtask(index, direction) {
  const subtasks = modalTask.value.subtasks || [];
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= subtasks.length) return;
  const item = subtasks.splice(index, 1)[0];
  subtasks.splice(newIndex, 0, item);
  const ids = subtasks.map(st => st.id);
  await api.patch(`/tasks/${modalTask.value.id}/reorder-subtasks`, { subtask_ids: ids });
  await loadBoard();
}

// Links
async function addLink() {
  const url = newLinkUrl.value.trim();
  if (!url) return;
  const link = await api.post(`/tasks/${modalTask.value.id}/links`, {
    title: newLinkTitle.value.trim() || url,
    url,
  });
  if (!modalTask.value.links) modalTask.value.links = [];
  modalTask.value.links.push(link);
  newLinkTitle.value = '';
  newLinkUrl.value = '';
  addingLink.value = false;
}

async function deleteLink(id) {
  await api.del(`/links/${id}`);
  modalTask.value.links = (modalTask.value.links || []).filter(l => l.id !== id);
}

// Time tracking
async function toggleModalTaskTimer() {
  await toggleTaskTimer(modalTask.value);
  try {
    modalTaskTimeEntries.value = await api.get(`/tasks/${modalTask.value.id}/time-entries`);
  } catch {
    modalTaskTimeEntries.value = [];
  }
}

async function exportTaskCsv() {
  window.open(`/api/entries/export/csv?task_id=${modalTask.value.id}`, '_blank');
}

// Delete task
async function deleteTask() {
  if (!modalTask.value) return;
  await api.del(`/tasks/${modalTask.value.id}`);
  modalTask.value = null;
  modalTaskTimeEntries.value = [];
  await loadBoard();
}

// Keyboard shortcuts
function onModalKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    event.stopPropagation();
    if (editingDescription.value) {
      exitEditDescription();
    } else {
      closeTaskModal();
    }
    return;
  }
  if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
    event.preventDefault();
    closeTaskModal();
    return;
  }
  // 1-9 to toggle labels (only when not typing in an input/textarea)
  if (event.key >= '1' && event.key <= '9' && !event.metaKey && !event.ctrlKey && !event.altKey) {
    const tag = event.target.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || event.target.isContentEditable) return;
    event.preventDefault();
    const index = parseInt(event.key) - 1;
    toggleLabelByIndex(index);
  }
}

function toggleLabelByIndex(index) {
  if (index < 0 || index >= globalLabels.value.length) return;
  const gl = globalLabels.value[index];
  const existing = (modalTask.value?.labels || []).find(l => (l.global_label_id || l.id) === gl.id);
  if (existing) {
    removeLabel(existing.id);
  } else {
    addLabel(gl);
  }
}

// ─── Timer Helpers ─────────────────────────────────────────────────

function isTimerRunningForTask(taskId) {
  return runningEntry.value?.task_id === taskId;
}

async function toggleTaskTimer(task) {
  if (isTimerRunningForTask(task.id)) {
    await stopTimer();
  } else {
    if (runningEntry.value) {
      await stopTimer();
    }
    await startTimer({
      task_id: task.id,
      project_id: task.project_id || null,
    });
  }
  await checkRunning();
}

// ─── Formatting Helpers ────────────────────────────────────────────

function priorityEmoji(priority) {
  switch (priority) {
    case 'high': return '\uD83D\uDD34';
    case 'medium': return '\uD83D\uDFE1';
    case 'low': return '\uD83D\uDFE2';
    default: return '';
  }
}

function priorityLabel(priority) {
  if (!priority || priority === 'none') return 'No priority';
  return priority.charAt(0).toUpperCase() + priority.slice(1) + ' priority';
}

function isOverdue(dateStr) {
  if (!dateStr) return false;
  return new Date(dateStr) < new Date(new Date().toDateString());
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatDateTime(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}

function formatDuration(seconds) {
  if (!seconds || seconds <= 0) return '0m';
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}

function entryDuration(entry) {
  const start = new Date(entry.start_time).getTime();
  const end = entry.end_time ? new Date(entry.end_time).getTime() : Date.now();
  return Math.max(0, Math.floor((end - start) / 1000));
}

function startEditDescription() {
  editingDescription.value = true;
  nextTick(() => {
    descriptionTextarea.value?.focus();
  });
}

function exitEditDescription() {
  editingDescription.value = false;
  debouncedSave();
}

function renderMarkdown(text) {
  if (!text) return '';
  let html = text
    // Escape HTML
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  // Process line by line for headings
  html = html.split('\n').map(line => {
    // Headings
    if (line.match(/^### /)) return '<h3 class="text-base font-semibold text-gray-800 mt-2 mb-1">' + line.slice(4) + '</h3>';
    if (line.match(/^## /)) return '<h2 class="text-lg font-semibold text-gray-800 mt-3 mb-1">' + line.slice(3) + '</h2>';
    if (line.match(/^# /)) return '<h1 class="text-xl font-bold text-gray-800 mt-3 mb-1">' + line.slice(2) + '</h1>';
    // Unordered list
    if (line.match(/^[-*] /)) return '<li class="ml-4 list-disc text-sm">' + line.slice(2) + '</li>';
    return line;
  }).join('\n');
  html = html
    // Bold
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    // Italic
    .replace(/\*(.+?)\*/g, '<em>$1</em>')
    // Inline code
    .replace(/`(.+?)`/g, '<code class="bg-gray-100 text-indigo-600 px-1 rounded text-xs">$1</code>')
    // Markdown links [text](url)
    .replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2" target="_blank" rel="noopener" class="text-indigo-500 hover:underline">$1</a>')
    // Bare URLs (not already inside an href="..." or >...</a>)
    .replace(/(^|[^"'>=])(https?:\/\/[^\s<]+)/g, '$1<a href="$2" target="_blank" rel="noopener" class="text-indigo-500 hover:underline">$2</a>')
    // Line breaks (but not after block elements)
    .replace(/\n(?!<[hlu])/g, '<br>');
  return html;
}

// ─── Board Drag-to-Scroll ─────────────────────────────────────────

const boardContainer = ref(null);
let boardDragging = false;
let boardStartX = 0;
let boardStartY = 0;
let boardScrollLeft = 0;
let boardScrollTop = 0;
let scrollTarget = null;

function onBoardMouseDown(event) {
  // Skip interactive elements: buttons, inputs, links, draggable cards
  const tag = event.target.tagName;
  if (tag === 'BUTTON' || tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'A' || tag === 'SELECT') return;
  if (event.target.closest('.task-card') || event.target.closest('button') || event.target.closest('a')) return;
  // Column headers are drag handles for reordering — don't start a board pan there
  if (event.target.closest('.column-header')) return;

  // Determine scroll target: tasks-list (vertical) or board-container (horizontal)
  const tasksList = event.target.closest('.tasks-list');
  if (tasksList) {
    scrollTarget = tasksList;
    boardStartY = event.pageY;
    boardScrollTop = tasksList.scrollTop;
    boardStartX = event.pageX;
    boardScrollLeft = boardContainer.value.scrollLeft;
  } else {
    scrollTarget = boardContainer.value;
    boardStartX = event.pageX;
    boardScrollLeft = boardContainer.value.scrollLeft;
    boardStartY = 0;
  }

  boardDragging = true;
  document.body.style.cursor = 'grabbing';
  document.addEventListener('mousemove', onBoardMouseMove);
  document.addEventListener('mouseup', onBoardMouseUp);
  event.preventDefault();
}

function onBoardMouseMove(event) {
  if (!boardDragging) return;
  const dx = event.pageX - boardStartX;
  boardContainer.value.scrollLeft = boardScrollLeft - dx;
  if (scrollTarget && scrollTarget !== boardContainer.value && boardStartY) {
    const dy = event.pageY - boardStartY;
    scrollTarget.scrollTop = boardScrollTop - dy;
  }
}

function onBoardMouseUp() {
  boardDragging = false;
  scrollTarget = null;
  document.body.style.cursor = '';
  document.removeEventListener('mousemove', onBoardMouseMove);
  document.removeEventListener('mouseup', onBoardMouseUp);
}

// ─── Click Outside Handling ────────────────────────────────────────

function onClickOutside(event) {
  if (openColumnMenu.value) {
    openColumnMenu.value = null;
  }
  if (showLabelPicker.value) {
    showLabelPicker.value = false;
  }
  if (showFilterPanel.value && !event.target.closest('.filter-panel-container')) {
    showFilterPanel.value = false;
  }
}

// ─── Global Keyboard Shortcuts ────────────────────────────────────

function onGlobalKeydown(event) {
  // Skip if typing in input/textarea or modal is open
  const tag = event.target.tagName;
  if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || event.target.isContentEditable) return;
  if (modalTask.value) return;

  if (event.key === '/') {
    event.preventDefault();
    toggleFilterPanel();
  }
}

function onFilterKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    event.stopPropagation();
    if (filterSearch.value) {
      filterSearch.value = '';
    } else {
      showFilterPanel.value = false;
    }
  }
}

// ─── Lifecycle ─────────────────────────────────────────────────────

onMounted(async () => {
  document.addEventListener('click', onClickOutside, true);
  document.addEventListener('keydown', onGlobalKeydown);
  await loadBoards();
  await Promise.all([
    loadBoard(),
    loadProjects(activeBoardId.value),
    checkRunning(),
  ]);
});

onUnmounted(() => {
  document.removeEventListener('click', onClickOutside, true);
  document.removeEventListener('keydown', onGlobalKeydown);
  document.removeEventListener('mousemove', onBoardMouseMove);
  document.removeEventListener('mouseup', onBoardMouseUp);
  if (saveTimeout) clearTimeout(saveTimeout);
});
</script>

<style scoped>
.tasks-list {
  padding-top: 4px;
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE/Edge */
}

.task-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  margin-bottom: 0.75rem;
  user-select: none;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.task-card.dragging {
  display: none !important;
}

.task-card-done {
  background-color: #f3f4f6;
  opacity: 0.7;
}

.task-done-checkbox {
  appearance: none;
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  min-width: 18px;
  border: 2px solid #d1d5db;
  border-radius: 5px;
  background: white;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  margin-top: 1px;
  position: relative;
}

.task-done-checkbox:hover:not(:checked) {
  border-color: #10b981;
}

.task-done-checkbox:checked {
  background: #10b981;
  border-color: #10b981;
}

.task-done-checkbox:checked::after {
  content: '';
  position: absolute;
  left: 4px;
  top: 1px;
  width: 5px;
  height: 9px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.subtask-card {
  margin-bottom: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border-left: 3px solid #a5b4fc;
}

.subtask-card:hover {
  background-color: #f0f0ff;
}

.subtask-card.dragging {
  display: none !important;
}

.subtask-done-checkbox {
  appearance: none;
  -webkit-appearance: none;
  width: 16px;
  height: 16px;
  min-width: 16px;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  position: relative;
}

.subtask-done-checkbox:hover {
  border-color: #10b981;
}

.subtask-done-checkbox:checked {
  background: #10b981;
  border-color: #10b981;
}

.subtask-done-checkbox:checked::after {
  content: '';
  position: absolute;
  left: 4px;
  top: 1px;
  width: 5px;
  height: 9px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.drop-placeholder {
  border: 2px dashed #a5b4fc;
  border-radius: 8px;
  background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.75rem;
  animation: placeholderAppear 0.15s ease-out;
}

@keyframes placeholderAppear {
  from {
    opacity: 0;
    transform: scaleY(0.5);
  }
  to {
    opacity: 1;
    transform: scaleY(1);
  }
}

.column-drag-over {
  background-color: #e0f2fe !important;
  border-radius: 0.75rem;
}

/* Column reordering */
.column-wrapper {
  position: relative;
  transition: opacity 0.15s ease;
}

.column-being-dragged {
  opacity: 0.4;
}

.column-drop-before::before,
.column-drop-after::after {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  width: 4px;
  border-radius: 4px;
  background: #6366f1;
  box-shadow: 0 0 8px rgba(99, 102, 241, 0.6);
}

.column-drop-before::before {
  left: -10px;
}

.column-drop-after::after {
  right: -10px;
}

.board-container {
  overflow-x: auto;
  cursor: grab;
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE/Edge */
  /* Center the columns when they fit; the `safe` keyword falls back to
     start-alignment when they overflow so scrolling still reaches the first one. */
  justify-content: safe center;
}

.board-container::-webkit-scrollbar {
  display: none; /* Chrome/Safari */
}

.tasks-list::-webkit-scrollbar {
  display: none; /* Chrome/Safari */
}
</style>
