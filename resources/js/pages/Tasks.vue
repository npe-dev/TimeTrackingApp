<template>
  <AppLayout>
    <div class="max-w-full mx-auto px-2">

      <!-- Board Selector -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg px-6 py-4 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <label class="text-sm font-medium text-gray-600">Board</label>
          <select
            v-model="selectedBoardId"
            @change="loadBoard"
            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none transition-all"
          >
            <option v-for="b in boards" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>
        <div class="text-sm text-gray-400">
          {{ board?.columns?.length || 0 }} columns &middot;
          {{ totalTaskCount }} tasks
        </div>
      </div>

      <!-- Kanban Board -->
      <div class="flex gap-4 overflow-x-auto pb-4 items-start" style="min-height: 70vh;">

        <!-- Columns -->
        <div
          v-for="column in columns"
          :key="column.id"
          class="flex-shrink-0 w-80"
        >
          <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg flex flex-col max-h-[80vh]">
            <!-- Column Header -->
            <div class="px-4 py-3 flex items-center justify-between border-b border-gray-100">
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
              class="flex-1 overflow-y-auto p-2 space-y-0.5"
              @dragover.prevent="onDragOver($event, column.id)"
              @dragleave="onDragLeave(column.id)"
              @drop="onDrop($event, column.id)"
              :data-column-id="column.id"
            >
              <template v-for="(task, idx) in (column.tasks || [])" :key="task.id">
                <!-- Drop indicator before card -->
                <div
                  v-if="dropTarget?.columnId === column.id && dropTarget?.position === idx"
                  class="h-1 bg-indigo-400 rounded-full mx-2 my-1 transition-all"
                ></div>

                <!-- Task Card -->
                <div
                  :draggable="true"
                  @dragstart="onDragStart($event, task, column.id)"
                  @dragend="onDragEnd"
                  class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 cursor-grab active:cursor-grabbing hover:shadow-md transition-all group"
                  :class="{ 'opacity-40': draggedTask?.id === task.id }"
                  @click="openTaskModal(task)"
                >
                  <!-- Card Top Row -->
                  <div class="flex items-start justify-between gap-2 mb-1">
                    <h4 class="text-sm font-medium text-gray-800 flex-1 leading-snug">{{ task.title }}</h4>
                    <span class="text-sm flex-shrink-0" :title="priorityLabel(task.priority)">
                      {{ priorityEmoji(task.priority) }}
                    </span>
                  </div>

                  <!-- Labels -->
                  <div v-if="task.labels && task.labels.length" class="flex flex-wrap gap-1 mb-2">
                    <span
                      v-for="label in task.labels"
                      :key="label.id"
                      class="text-xs px-2 py-0.5 rounded-full font-medium"
                      :style="{ backgroundColor: label.color + '20', color: label.color }"
                    >
                      {{ label.name }}
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
                      <!-- Project -->
                      <span v-if="task.project" class="truncate max-w-[100px]" :title="task.project.name">
                        {{ task.project.name }}
                      </span>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <!-- Timer button -->
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
                      <!-- Mark done -->
                      <button
                        @click.stop="markTaskDone(task)"
                        class="p-1 rounded-lg text-gray-400 hover:bg-green-50 hover:text-green-500 transition-colors"
                        title="Mark as done"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </template>

              <!-- Drop indicator at bottom -->
              <div
                v-if="dropTarget?.columnId === column.id && dropTarget?.position === (column.tasks || []).length"
                class="h-1 bg-indigo-400 rounded-full mx-2 my-1 transition-all"
              ></div>

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
        <div v-if="modalTask" class="fixed inset-0 z-50 flex items-center justify-center" @keydown="onModalKeydown">
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
                  <span>{{ priorityEmoji(modalTask.priority) }}</span>
                  <span class="text-gray-600 capitalize">{{ modalTask.priority || 'low' }}</span>
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
                    @click="descriptionPreview = !descriptionPreview"
                    class="text-xs text-indigo-500 hover:text-indigo-700 font-medium"
                  >
                    {{ descriptionPreview ? 'Edit' : 'Preview' }}
                  </button>
                </div>
                <div v-if="descriptionPreview" class="prose prose-sm max-w-none p-3 bg-gray-50 rounded-lg min-h-[80px]" v-html="renderMarkdown(modalTask.description || '')"></div>
                <textarea
                  v-else
                  v-model="modalTask.description"
                  @input="debouncedSave"
                  placeholder="Add a description... (supports **bold**, *italic*, `code`, [links](url))"
                  rows="4"
                  class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none resize-y"
                ></textarea>
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
                    {{ label.name }}
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
                    <div v-if="!availableLabels.length" class="text-xs text-gray-400">No global labels available</div>
                    <button
                      v-for="gl in availableLabels"
                      :key="gl.id"
                      @click="addLabel(gl)"
                      class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 text-left text-sm"
                    >
                      <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: gl.color }"></span>
                      {{ gl.name }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Checklist -->
              <div>
                <h4 class="text-sm font-semibold text-gray-600 mb-2">Checklist</h4>
                <!-- Progress bar -->
                <div v-if="(modalTask.checklist_items || []).length" class="mb-2">
                  <div class="flex items-center gap-2 mb-1">
                    <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full transition-all"
                        :style="{ width: checklistProgress + '%' }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-400">{{ checklistProgress }}%</span>
                  </div>
                </div>
                <!-- Items -->
                <div class="space-y-1 mb-2">
                  <div
                    v-for="item in (modalTask.checklist_items || [])"
                    :key="item.id"
                    class="flex items-center gap-2 group"
                  >
                    <button
                      @click="toggleChecklistItem(item)"
                      class="flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                      :class="item.completed
                        ? 'bg-indigo-500 border-indigo-500 text-white'
                        : 'border-gray-300 hover:border-indigo-400'"
                    >
                      <svg v-if="item.completed" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                      </svg>
                    </button>
                    <span
                      class="flex-1 text-sm"
                      :class="item.completed ? 'line-through text-gray-400' : 'text-gray-700'"
                    >
                      {{ item.title }}
                    </span>
                    <button
                      @click="deleteChecklistItem(item.id)"
                      class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-all p-0.5"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
                <!-- Add Checklist Item -->
                <div class="flex gap-2">
                  <input
                    v-model="newChecklistTitle"
                    @keydown.enter="addChecklistItem"
                    placeholder="Add item..."
                    class="flex-1 text-sm rounded-lg border border-gray-200 px-3 py-1.5 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none"
                  />
                  <button
                    @click="addChecklistItem"
                    :disabled="!newChecklistTitle.trim()"
                    class="text-xs font-medium text-indigo-500 hover:text-indigo-700 disabled:text-gray-300 px-2"
                  >
                    Add
                  </button>
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
                    <span>{{ formatDateTime(entry.start_time) }}</span>
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

const { user } = useAuth();
const api = useApi();
const { projects, loadProjects } = useProjects();
const { runningEntry, checkRunning, start: startTimer, stop: stopTimer } = useTimer();

// ─── Board State ───────────────────────────────────────────────────

const boards = ref([]);
const selectedBoardId = ref(null);
const board = ref(null);

const columns = computed(() => board.value?.columns || []);
const totalTaskCount = computed(() =>
  columns.value.reduce((sum, c) => sum + (c.tasks || []).length, 0)
);

async function loadBoards() {
  boards.value = await api.get('/boards');
  if (boards.value.length && !selectedBoardId.value) {
    selectedBoardId.value = boards.value[0].id;
  }
  if (selectedBoardId.value) {
    await loadBoard();
  }
}

async function loadBoard() {
  if (!selectedBoardId.value) return;
  board.value = await api.get(`/boards/${selectedBoardId.value}`);
  // Load tasks for each column
  if (board.value?.columns) {
    await Promise.all(
      board.value.columns.map(async (col) => {
        col.tasks = await api.get(`/columns/${col.id}/tasks`);
      })
    );
  }
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
  nextTick(() => newTaskInput.value?.focus());
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

async function markTaskDone(task) {
  // Move to last column (assumed to be "Done")
  const lastColumn = columns.value[columns.value.length - 1];
  if (!lastColumn) return;
  await api.patch(`/tasks/${task.id}/move`, {
    column_id: lastColumn.id,
    position: (lastColumn.tasks || []).length,
  });
  await loadBoard();
}

// ─── Drag & Drop ───────────────────────────────────────────────────

const draggedTask = ref(null);
const dragSourceColumnId = ref(null);
const dropTarget = ref(null);

function onDragStart(event, task, columnId) {
  draggedTask.value = task;
  dragSourceColumnId.value = columnId;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', task.id);
}

function onDragEnd() {
  draggedTask.value = null;
  dragSourceColumnId.value = null;
  dropTarget.value = null;
}

function onDragOver(event, columnId) {
  event.dataTransfer.dropEffect = 'move';
  const col = columns.value.find(c => c.id === columnId);
  if (!col) return;

  const container = event.currentTarget;
  const cards = Array.from(container.querySelectorAll('[draggable="true"]'));
  const mouseY = event.clientY;

  let position = (col.tasks || []).length;
  for (let i = 0; i < cards.length; i++) {
    const rect = cards[i].getBoundingClientRect();
    const midY = rect.top + rect.height / 2;
    if (mouseY < midY) {
      position = i;
      break;
    }
  }

  dropTarget.value = { columnId, position };
}

function onDragLeave(columnId) {
  if (dropTarget.value?.columnId === columnId) {
    dropTarget.value = null;
  }
}

async function onDrop(event, columnId) {
  event.preventDefault();
  if (!draggedTask.value || !dropTarget.value) return;

  const { position } = dropTarget.value;
  await api.patch(`/tasks/${draggedTask.value.id}/move`, {
    column_id: columnId,
    position,
  });

  draggedTask.value = null;
  dragSourceColumnId.value = null;
  dropTarget.value = null;
  await loadBoard();
}

// ─── Task Modal ────────────────────────────────────────────────────

const modalTask = ref(null);
const descriptionPreview = ref(false);
const showLabelPicker = ref(false);
const globalLabels = ref([]);
const confirmingDelete = ref(false);

// Checklist
const newChecklistTitle = ref('');

// Links
const addingLink = ref(false);
const newLinkTitle = ref('');
const newLinkUrl = ref('');

// Time entries
const modalTaskTimeEntries = ref([]);

const checklistProgress = computed(() => {
  const items = modalTask.value?.checklist_items || [];
  if (!items.length) return 0;
  const done = items.filter(i => i.completed).length;
  return Math.round((done / items.length) * 100);
});

const modalTaskTotalTime = computed(() => {
  return modalTaskTimeEntries.value.reduce((sum, e) => sum + entryDuration(e), 0);
});

const availableLabels = computed(() => {
  const existing = (modalTask.value?.labels || []).map(l => l.global_label_id || l.id);
  return globalLabels.value.filter(gl => !existing.includes(gl.id));
});

async function openTaskModal(task) {
  confirmingDelete.value = false;
  descriptionPreview.value = false;
  showLabelPicker.value = false;
  addingLink.value = false;
  newChecklistTitle.value = '';
  newLinkTitle.value = '';
  newLinkUrl.value = '';

  // Load full task data
  const fullTask = await api.get(`/tasks/${task.id}`);
  modalTask.value = { ...fullTask };

  // Load time entries
  try {
    modalTaskTimeEntries.value = await api.get(`/tasks/${task.id}/time-entries`);
  } catch {
    modalTaskTimeEntries.value = [];
  }

  // Load global labels
  try {
    globalLabels.value = await api.get('/global-labels');
  } catch {
    globalLabels.value = [];
  }
}

function closeTaskModal() {
  if (modalTask.value) {
    saveModalTask();
  }
  modalTask.value = null;
  modalTaskTimeEntries.value = [];
}

// Auto-save debounce
let saveTimeout = null;

function debouncedSave() {
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
    });
    await loadBoard();
  } catch (err) {
    console.error('Failed to save task:', err);
  }
}

function cyclePriority() {
  const order = ['low', 'medium', 'high'];
  const current = modalTask.value.priority || 'low';
  const idx = order.indexOf(current);
  modalTask.value.priority = order[(idx + 1) % order.length];
  debouncedSave();
}

// Labels
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

// Checklist
async function addChecklistItem() {
  const title = newChecklistTitle.value.trim();
  if (!title) return;
  const position = (modalTask.value.checklist_items || []).length;
  const item = await api.post(`/tasks/${modalTask.value.id}/checklist`, { title, position });
  if (!modalTask.value.checklist_items) modalTask.value.checklist_items = [];
  modalTask.value.checklist_items.push(item);
  newChecklistTitle.value = '';
}

async function toggleChecklistItem(item) {
  await api.patch(`/checklist/${item.id}/toggle`);
  item.completed = !item.completed;
}

async function deleteChecklistItem(id) {
  await api.del(`/checklist/${id}`);
  modalTask.value.checklist_items = (modalTask.value.checklist_items || []).filter(i => i.id !== id);
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
    closeTaskModal();
  }
  if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
    event.preventDefault();
    closeTaskModal();
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
    default: return '\uD83D\uDFE2';
  }
}

function priorityLabel(priority) {
  return (priority || 'low').charAt(0).toUpperCase() + (priority || 'low').slice(1) + ' priority';
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

function renderMarkdown(text) {
  if (!text) return '';
  let html = text
    // Escape HTML
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    // Bold
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    // Italic
    .replace(/\*(.+?)\*/g, '<em>$1</em>')
    // Inline code
    .replace(/`(.+?)`/g, '<code class="bg-gray-100 text-indigo-600 px-1 rounded text-xs">$1</code>')
    // Links
    .replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2" target="_blank" rel="noopener" class="text-indigo-500 hover:underline">$1</a>')
    // Line breaks
    .replace(/\n/g, '<br>');
  return html;
}

// ─── Click Outside Handling ────────────────────────────────────────

function onClickOutside(event) {
  if (openColumnMenu.value) {
    openColumnMenu.value = null;
  }
  if (showLabelPicker.value) {
    showLabelPicker.value = false;
  }
}

// ─── Lifecycle ─────────────────────────────────────────────────────

onMounted(async () => {
  document.addEventListener('click', onClickOutside, true);
  await Promise.all([
    loadBoards(),
    loadProjects(),
    checkRunning(),
  ]);
});

onUnmounted(() => {
  document.removeEventListener('click', onClickOutside, true);
  if (saveTimeout) clearTimeout(saveTimeout);
});
</script>
