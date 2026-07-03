<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- List View -->
      <template v-if="!showBuilder">
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">Automations</h2>
            <button
              @click="openBuilder()"
              class="px-4 py-2 text-sm font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg transition-all"
            >
              + New Automation
            </button>
          </div>
          <!-- Automation Cards -->
          <div v-if="automations.length" class="space-y-3">
            <div
              v-for="automation in automations"
              :key="automation.id"
              class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <h3 class="text-sm font-semibold text-gray-800">
                    {{ automation.name || 'Unnamed Automation' }}
                  </h3>
                  <p class="text-xs text-gray-500 mt-1">
                    <span class="font-medium">Trigger:</span> {{ describeTrigger(automation.trigger) }}
                  </p>
                  <div v-if="automation.actions && automation.actions.length" class="mt-1">
                    <p
                      v-for="(action, i) in automation.actions"
                      :key="i"
                      class="text-xs text-gray-500"
                    >
                      <span class="font-medium">Action {{ i + 1 }}:</span> {{ describeAction(action) }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <button
                    @click="toggleAutomation(automation)"
                    class="relative w-10 h-5 rounded-full transition-colors"
                    :class="automation.enabled ? 'bg-indigo-500' : 'bg-gray-300'"
                  >
                    <span
                      class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"
                      :class="automation.enabled ? 'left-5' : 'left-0.5'"
                    ></span>
                  </button>
                  <button
                    @click="openHistory(automation)"
                    class="p-1.5 text-gray-400 hover:text-indigo-500 transition-colors"
                    title="History"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  </button>
                  <button
                    @click="openBuilder(automation)"
                    class="p-1.5 text-gray-400 hover:text-indigo-500 transition-colors"
                    title="Edit"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                  </button>
                  <button
                    @click="deleteAutomation(automation)"
                    class="p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                    title="Delete"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <p class="text-gray-400 text-sm">No automations yet. Create one to automate your workflow.</p>
          </div>
        </div>
      </template>

      <!-- Builder View -->
      <template v-else>
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6">
          <h2 class="text-xl font-bold text-gray-800 mb-6">
            {{ editingAutomation ? 'Edit Automation' : 'New Automation' }}
          </h2>
          <div class="space-y-6">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Name (optional)</label>
              <input
                v-model="builderForm.name"
                type="text"
                placeholder="My automation"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
              />
            </div>

            <!-- Step 1: Trigger -->
            <div>
              <h3 class="text-sm font-semibold text-gray-700 mb-3">Step 1: When this happens...</h3>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <button
                  v-for="trigger in triggerTypes"
                  :key="trigger.type"
                  @click="selectTrigger(trigger.type)"
                  class="p-3 rounded-xl border-2 text-center transition-all hover:shadow-md"
                  :class="builderForm.trigger.type === trigger.type
                    ? 'border-indigo-500 bg-indigo-50'
                    : 'border-gray-200 bg-white hover:border-indigo-300'"
                >
                  <span class="text-2xl block mb-1">{{ trigger.icon }}</span>
                  <span class="text-xs font-medium text-gray-700">{{ trigger.label }}</span>
                </button>
              </div>

              <!-- Trigger Config: Column -->
              <div
                v-if="triggerNeedsColumn"
                class="mt-3"
              >
                <label class="block text-sm font-medium text-gray-600 mb-1">Column</label>
                <select
                  v-model="builderForm.trigger.column_id"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                >
                  <option :value="null" disabled>Select column</option>
                  <option v-for="col in selectedBoardColumns" :key="col.id" :value="col.id">{{ col.name }}</option>
                </select>
              </div>

              <!-- Trigger Config: Due Date Relative -->
              <div
                v-if="builderForm.trigger.type === 'due_date_relative'"
                class="mt-3 flex items-center gap-3"
              >
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Days</label>
                  <input
                    v-model.number="builderForm.trigger.days"
                    type="number"
                    min="1"
                    class="w-20 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">When</label>
                  <select
                    v-model="builderForm.trigger.direction"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  >
                    <option value="before">Before due date</option>
                    <option value="after">After due date</option>
                  </select>
                </div>
              </div>

              <!-- Trigger Config: Schedule -->
              <div
                v-if="builderForm.trigger.type === 'schedule'"
                class="mt-3 flex flex-wrap items-end gap-3"
              >
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Frequency</label>
                  <select
                    v-model="builderForm.trigger.frequency"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  >
                    <option value="daily">Every day</option>
                    <option value="weekly">Every week</option>
                    <option value="monthly">Every month</option>
                  </select>
                </div>

                <!-- Weekly: day of week -->
                <div v-if="builderForm.trigger.frequency === 'weekly'">
                  <label class="block text-sm font-medium text-gray-600 mb-1">Day</label>
                  <select
                    v-model.number="builderForm.trigger.day_of_week"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  >
                    <option v-for="d in weekdays" :key="d.value" :value="d.value">{{ d.label }}</option>
                  </select>
                </div>

                <!-- Monthly: day of month -->
                <div v-if="builderForm.trigger.frequency === 'monthly'">
                  <label class="block text-sm font-medium text-gray-600 mb-1">Day of month</label>
                  <input
                    v-model.number="builderForm.trigger.day_of_month"
                    type="number"
                    min="1"
                    max="31"
                    class="w-20 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">At (time)</label>
                  <input
                    v-model="builderForm.trigger.time"
                    type="time"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  />
                </div>
                <p class="w-full text-xs text-gray-400">Times are in your local timezone ({{ userTimezone }}).</p>
              </div>
            </div>

            <!-- Step 2: Actions -->
            <div>
              <h3 class="text-sm font-semibold text-gray-700 mb-3">Step 2: Do this...</h3>
              <div class="space-y-3">
                <div
                  v-for="(action, index) in builderForm.actions"
                  :key="index"
                  class="p-4 bg-gray-50 rounded-xl space-y-3"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-400">Action {{ index + 1 }}</span>
                    <button
                      @click="removeAction(index)"
                      class="text-gray-400 hover:text-red-500 transition-colors"
                      title="Remove action"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>
                  <select
                    v-model="action.type"
                    @change="onActionTypeChange(index)"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  >
                    <option :value="null" disabled>Select action type</option>
                    <!-- Scheduled (time-based) triggers act on the board, not a single card -->
                    <template v-if="isScheduleTrigger">
                      <option value="create_card">Create card</option>
                      <option value="bulk_move">Move all cards between columns</option>
                      <option value="due_cards">Act on due/overdue cards</option>
                      <option value="archive_cards">Archive cards</option>
                    </template>
                    <!-- Event triggers act on the card that fired them -->
                    <template v-else>
                      <option value="move_card">Move card</option>
                      <option value="add_label">Add label</option>
                      <option value="remove_due_date">Remove due date</option>
                      <option value="update_due_date">Update due date</option>
                      <option value="mark_done">Mark done</option>
                    </template>
                  </select>

                  <!-- Move Card Config -->
                  <template v-if="action.type === 'move_card'">
                    <div class="flex gap-3">
                      <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">To Column</label>
                        <select
                          v-model="action.column_id"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option :value="null" disabled>Select column</option>
                          <option v-for="col in selectedBoardColumns" :key="col.id" :value="col.id">{{ col.name }}</option>
                        </select>
                      </div>
                      <div class="w-32">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Position</label>
                        <select
                          v-model="action.position"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option value="top">Top</option>
                          <option value="bottom">Bottom</option>
                        </select>
                      </div>
                    </div>
                  </template>

                  <!-- Add Label Config -->
                  <template v-if="action.type === 'add_label'">
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">Label</label>
                      <select
                        v-model="action.label_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                      >
                        <option :value="null" disabled>Select label</option>
                        <option v-for="label in globalLabels" :key="label.id" :value="label.id">
                          {{ label.name }}
                        </option>
                      </select>
                    </div>
                  </template>

                  <!-- Update Due Date Config -->
                  <template v-if="action.type === 'update_due_date'">
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">Days offset (positive = future, negative = past)</label>
                      <input
                        v-model.number="action.days_offset"
                        type="number"
                        class="w-32 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                      />
                    </div>
                  </template>

                  <!-- Create Card Config -->
                  <template v-if="action.type === 'create_card'">
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">Card title</label>
                      <input
                        v-model="action.title"
                        type="text"
                        placeholder="e.g. Daily standup notes"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                      />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">Description (optional)</label>
                      <textarea
                        v-model="action.description"
                        rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                      ></textarea>
                    </div>
                    <div class="flex flex-wrap gap-3">
                      <div class="flex-1 min-w-[10rem]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">In column</label>
                        <select
                          v-model="action.column_id"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option :value="null" disabled>Select column</option>
                          <option v-for="col in selectedBoardColumns" :key="col.id" :value="col.id">{{ col.name }}</option>
                        </select>
                      </div>
                      <div class="w-32">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Priority</label>
                        <select
                          v-model="action.priority"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option value="none">None</option>
                          <option value="low">Low</option>
                          <option value="medium">Medium</option>
                          <option value="high">High</option>
                        </select>
                      </div>
                      <div class="w-36">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Due in (days)</label>
                        <input
                          v-model.number="action.due_in_days"
                          type="number"
                          min="0"
                          placeholder="—"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        />
                      </div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">Label (optional)</label>
                      <select
                        v-model="action.label_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                      >
                        <option :value="null">No label</option>
                        <option v-for="label in globalLabels" :key="label.id" :value="label.id">{{ label.name }}</option>
                      </select>
                    </div>
                  </template>

                  <!-- Bulk Move Config -->
                  <template v-if="action.type === 'bulk_move'">
                    <div class="flex flex-wrap gap-3">
                      <div class="flex-1 min-w-[10rem]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">From column</label>
                        <select
                          v-model="action.from_column_id"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option :value="null" disabled>Select column</option>
                          <option v-for="col in selectedBoardColumns" :key="col.id" :value="col.id">{{ col.name }}</option>
                        </select>
                      </div>
                      <div class="flex-1 min-w-[10rem]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">To column</label>
                        <select
                          v-model="action.to_column_id"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option :value="null" disabled>Select column</option>
                          <option v-for="col in selectedBoardColumns" :key="col.id" :value="col.id">{{ col.name }}</option>
                        </select>
                      </div>
                      <div class="w-32">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Position</label>
                        <select
                          v-model="action.position"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option value="top">Top</option>
                          <option value="bottom">Bottom</option>
                        </select>
                      </div>
                    </div>
                  </template>

                  <!-- Due Cards Config -->
                  <template v-if="action.type === 'due_cards'">
                    <div class="flex flex-wrap gap-3">
                      <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Which cards</label>
                        <select
                          v-model="action.scope"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option value="overdue">Overdue</option>
                          <option value="due_today">Due today</option>
                          <option value="due_within">Due within N days</option>
                        </select>
                      </div>
                      <div v-if="action.scope === 'due_within'" class="w-28">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Days</label>
                        <input
                          v-model.number="action.within_days"
                          type="number"
                          min="0"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        />
                      </div>
                      <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Then</label>
                        <select
                          v-model="action.then"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option value="move">Move to column</option>
                          <option value="add_label">Add label</option>
                          <option value="mark_done">Mark done</option>
                          <option value="archive">Archive</option>
                        </select>
                      </div>
                    </div>
                    <div v-if="action.then === 'move'">
                      <label class="block text-xs font-medium text-gray-500 mb-1">Target column</label>
                      <select
                        v-model="action.column_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                      >
                        <option :value="null" disabled>Select column</option>
                        <option v-for="col in selectedBoardColumns" :key="col.id" :value="col.id">{{ col.name }}</option>
                      </select>
                    </div>
                    <div v-if="action.then === 'add_label'">
                      <label class="block text-xs font-medium text-gray-500 mb-1">Label</label>
                      <select
                        v-model="action.label_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                      >
                        <option :value="null" disabled>Select label</option>
                        <option v-for="label in globalLabels" :key="label.id" :value="label.id">{{ label.name }}</option>
                      </select>
                    </div>
                  </template>

                  <!-- Archive Cards Config -->
                  <template v-if="action.type === 'archive_cards'">
                    <div class="flex flex-wrap gap-3">
                      <div class="flex-1 min-w-[10rem]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Archive which cards</label>
                        <select
                          v-model="action.scope"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option value="completed">Completed cards</option>
                          <option value="done">Cards in "Done" column</option>
                          <option value="column">Cards in a specific column</option>
                        </select>
                      </div>
                      <div v-if="action.scope === 'column'" class="flex-1 min-w-[10rem]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Column</label>
                        <select
                          v-model="action.column_id"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                          <option :value="null" disabled>Select column</option>
                          <option v-for="col in selectedBoardColumns" :key="col.id" :value="col.id">{{ col.name }}</option>
                        </select>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
              <button
                @click="addAction"
                class="mt-3 px-4 py-2 text-sm font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-all"
              >
                + Add Action
              </button>
            </div>

            <!-- Save / Cancel -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
              <button
                @click="cancelBuilder"
                class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all"
              >
                Cancel
              </button>
              <button
                @click="saveAutomation"
                :disabled="!builderForm.board_id || !builderForm.trigger.type"
                class="px-5 py-2 text-sm font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ editingAutomation ? 'Update' : 'Create' }}
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Execution History Modal -->
    <div
      v-if="showHistory"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="closeHistory"
    >
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div>
            <h3 class="text-lg font-bold text-gray-800">Execution history</h3>
            <p class="text-xs text-gray-500">{{ historyAutomation?.name || 'Unnamed Automation' }}</p>
          </div>
          <button @click="closeHistory" class="p-1.5 text-gray-400 hover:text-gray-700 transition-colors" title="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="overflow-y-auto px-6 py-4">
          <div v-if="historyLoading" class="text-center py-10 text-gray-400 text-sm">Loading…</div>
          <div v-else-if="!runs.length" class="text-center py-10 text-gray-400 text-sm">
            No executions recorded yet.
          </div>
          <ul v-else class="space-y-2">
            <li
              v-for="run in runs"
              :key="run.id"
              class="flex items-start gap-3 p-3 rounded-lg bg-gray-50"
            >
              <span
                class="mt-0.5 inline-block w-2 h-2 rounded-full shrink-0"
                :class="run.status === 'error' ? 'bg-red-500' : 'bg-green-500'"
                :title="run.status"
              ></span>
              <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-800 break-words">{{ run.message }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ formatRunTime(run.created_at) }}</p>
              </div>
            </li>
          </ul>
        </div>
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
const { get, post, put, del, patch } = useApi();
const { activeBoardId } = useBoard();

// --- List View State ---
const automations = ref([]);

// --- History Modal State ---
const showHistory = ref(false);
const historyAutomation = ref(null);
const historyLoading = ref(false);
const runs = ref([]);

// --- Builder State ---
const showBuilder = ref(false);
const editingAutomation = ref(null);
const selectedBoardColumns = ref([]);
const globalLabels = ref([]);

const emptyTrigger = () => ({
  type: null,
  column_id: null,
  days: 1,
  direction: 'before',
  // schedule trigger
  frequency: 'daily',
  time: '09:00',
  day_of_week: 1,
  day_of_month: 1,
});
const emptyAction = () => ({
  type: null,
  column_id: null,
  position: 'bottom',
  label_id: null,
  days_offset: 0,
  // create_card
  title: '',
  description: '',
  priority: 'none',
  due_in_days: null,
  // bulk_move
  from_column_id: null,
  to_column_id: null,
  // due_cards / archive_cards
  scope: 'overdue',
  within_days: 1,
  then: 'move',
});

// Display only; the server evaluates schedules in this zone (config app.user_timezone).
const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'local time';

const weekdays = [
  { value: 1, label: 'Monday' },
  { value: 2, label: 'Tuesday' },
  { value: 3, label: 'Wednesday' },
  { value: 4, label: 'Thursday' },
  { value: 5, label: 'Friday' },
  { value: 6, label: 'Saturday' },
  { value: 7, label: 'Sunday' },
];

const builderForm = ref({
  name: '',
  board_id: null,
  trigger: emptyTrigger(),
  actions: [emptyAction()],
});

const triggerTypes = [
  { type: 'card_done', icon: '\u2705', label: 'Card Done' },
  { type: 'card_deleted', icon: '\uD83D\uDDD1\uFE0F', label: 'Card Deleted' },
  { type: 'card_created', icon: '\u2728', label: 'Card Created' },
  { type: 'card_added_to', icon: '\uD83D\uDCE5', label: 'Added To' },
  { type: 'card_moved_into', icon: '\u27A1\uFE0F', label: 'Moved Into' },
  { type: 'card_moved_out', icon: '\u2B05\uFE0F', label: 'Moved Out' },
  { type: 'due_date_set', icon: '\uD83D\uDCC5', label: 'Due Date Set' },
  { type: 'due_date_relative', icon: '\u23F0', label: 'Due Date Relative' },
  { type: 'schedule', icon: '\uD83D\uDDD3\uFE0F', label: 'Schedule' },
];

const triggerNeedsColumn = computed(() =>
  ['card_added_to', 'card_moved_into', 'card_moved_out'].includes(builderForm.value.trigger.type)
);

// Time-based triggers act on the whole board (no single card), so they use a
// different set of actions than event-based triggers.
const isScheduleTrigger = computed(() => builderForm.value.trigger.type === 'schedule');

// --- Trigger/Action Descriptions ---
function describeTrigger(trigger) {
  if (!trigger?.type) return 'None';
  const names = {
    card_done: 'When a card is marked done',
    card_deleted: 'When a card is deleted',
    card_created: 'When a card is created',
    card_added_to: 'When a card is added to a column',
    card_moved_into: 'When a card is moved into a column',
    card_moved_out: 'When a card is moved out of a column',
    due_date_set: 'When a due date is set',
    due_date_relative: `${trigger.days || '?'} day(s) ${trigger.direction || 'before'} due date`,
    schedule: describeSchedule(trigger),
  };
  return names[trigger.type] || trigger.type;
}

function describeSchedule(trigger) {
  const time = trigger.time || '09:00';
  if (trigger.frequency === 'weekly') {
    const day = weekdays.find(d => d.value === trigger.day_of_week)?.label || 'Monday';
    return `Every ${day} at ${time}`;
  }
  if (trigger.frequency === 'monthly') {
    return `Every month on day ${trigger.day_of_month || 1} at ${time}`;
  }
  return `Every day at ${time}`;
}

function describeAction(action) {
  if (!action?.type) return 'None';
  const names = {
    move_card: 'Move card to column',
    add_label: 'Add label',
    remove_due_date: 'Remove due date',
    update_due_date: `Shift due date by ${action.days_offset ?? '?'} day(s)`,
    mark_done: 'Mark card as done',
    create_card: `Create card "${action.title || 'Untitled'}"`,
    bulk_move: 'Move all cards to another column',
    due_cards: `On ${describeScope(action.scope)} cards: ${action.then || 'move'}`,
    archive_cards: `Archive ${describeArchiveScope(action.scope)}`,
  };
  return names[action.type] || action.type;
}

function describeScope(scope) {
  return { overdue: 'overdue', due_today: 'due-today', due_within: 'soon-due' }[scope] || scope;
}

function describeArchiveScope(scope) {
  return { completed: 'completed cards', done: 'cards in Done', column: 'cards in a column' }[scope] || scope;
}

// --- API ---
async function loadAutomations() {
  try {
    const params = {};
    if (activeBoardId.value) params.board_id = activeBoardId.value;
    automations.value = await get('/automations', params);
  } catch (e) {
    console.error('Failed to load automations', e);
  }
}

async function loadBoardColumns(boardId) {
  if (!boardId) {
    selectedBoardColumns.value = [];
    return;
  }
  try {
    const board = await get(`/boards/${boardId}`);
    selectedBoardColumns.value = board.columns || [];
  } catch (e) {
    console.error('Failed to load board columns', e);
    selectedBoardColumns.value = [];
  }
}

async function loadGlobalLabels() {
  if (!activeBoardId.value) {
    globalLabels.value = [];
    return;
  }
  try {
    globalLabels.value = await get(`/boards/${activeBoardId.value}/labels`);
  } catch (e) {
    console.error('Failed to load labels', e);
  }
}

async function toggleAutomation(automation) {
  try {
    await patch(`/automations/${automation.id}/toggle`);
    automation.enabled = !automation.enabled;
  } catch (e) {
    console.error('Failed to toggle automation', e);
  }
}

async function openHistory(automation) {
  historyAutomation.value = automation;
  showHistory.value = true;
  historyLoading.value = true;
  runs.value = [];
  try {
    runs.value = await get(`/automations/${automation.id}/runs`);
  } catch (e) {
    console.error('Failed to load history', e);
  } finally {
    historyLoading.value = false;
  }
}

function closeHistory() {
  showHistory.value = false;
  historyAutomation.value = null;
  runs.value = [];
}

// Timestamps arrive as UTC; render in the browser's local timezone.
function formatRunTime(ts) {
  if (!ts) return '';
  const d = new Date(ts);
  return isNaN(d) ? ts : d.toLocaleString();
}

async function deleteAutomation(automation) {
  if (!confirm(`Delete automation "${automation.name || 'Unnamed'}"?`)) return;
  try {
    await del(`/automations/${automation.id}`);
    await loadAutomations();
  } catch (e) {
    console.error('Failed to delete automation', e);
  }
}

// --- Builder ---
function openBuilder(automation = null) {
  editingAutomation.value = automation;
  if (automation) {
    builderForm.value = {
      name: automation.name || '',
      board_id: automation.board_id,
      trigger: { ...emptyTrigger(), ...(automation.trigger || {}) },
      actions: automation.actions?.length
        ? automation.actions.map(a => ({ ...emptyAction(), ...a }))
        : [emptyAction()],
    };
    loadBoardColumns(automation.board_id);
  } else {
    builderForm.value = {
      name: '',
      board_id: activeBoardId.value,
      trigger: emptyTrigger(),
      actions: [emptyAction()],
    };
    loadBoardColumns(activeBoardId.value);
  }
  loadGlobalLabels();
  showBuilder.value = true;
}

function cancelBuilder() {
  showBuilder.value = false;
  editingAutomation.value = null;
}

function selectTrigger(type) {
  const wasSchedule = builderForm.value.trigger.type === 'schedule';
  const isSchedule = type === 'schedule';
  builderForm.value.trigger = { ...emptyTrigger(), type };
  // Schedule and event triggers use different action sets, so a category switch
  // would leave stale, invalid actions behind — reset them.
  if (wasSchedule !== isSchedule) {
    builderForm.value.actions = [emptyAction()];
  }
}

function onActionTypeChange(index) {
  const type = builderForm.value.actions[index].type;
  const action = { ...emptyAction(), type };
  // Archive uses the same `scope` field as due_cards but with its own options.
  if (type === 'archive_cards') action.scope = 'completed';
  builderForm.value.actions[index] = action;
}

function addAction() {
  builderForm.value.actions.push(emptyAction());
}

function removeAction(index) {
  if (builderForm.value.actions.length <= 1) return;
  builderForm.value.actions.splice(index, 1);
}

async function saveAutomation() {
  const payload = {
    name: builderForm.value.name,
    board_id: builderForm.value.board_id,
    trigger: builderForm.value.trigger,
    actions: builderForm.value.actions,
  };
  try {
    if (editingAutomation.value) {
      await put(`/automations/${editingAutomation.value.id}`, payload);
    } else {
      await post('/automations', payload);
    }
    showBuilder.value = false;
    editingAutomation.value = null;
    await loadAutomations();
  } catch (e) {
    console.error('Failed to save automation', e);
  }
}

// Reload automations when the active board changes.
watch(activeBoardId, () => loadAutomations());

onMounted(async () => {
  await fetchUser();
  loadAutomations();
});
</script>
