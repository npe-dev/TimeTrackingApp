<template>
  <div class="flex gap-2">
    <input
      :value="datePart"
      type="date"
      :class="[inputClass, 'flex-1 min-w-0']"
      @input="setDate($event.target.value)"
    />
    <TimeInput
      :model-value="timePart"
      :class="[inputClass, 'w-24 text-center']"
      @update:model-value="setTime"
    />
  </div>
</template>

<script setup>
// 24-hour replacement for <input type="datetime-local">.
// v-model value is "YYYY-MM-DDTHH:MM", same as the native input.
import { computed } from 'vue';
import TimeInput from './TimeInput.vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  inputClass: { type: [String, Array, Object], default: '' },
});
const emit = defineEmits(['update:modelValue']);

const datePart = computed(() => (props.modelValue || '').split('T')[0] || '');
const timePart = computed(() => ((props.modelValue || '').split('T')[1] || '').slice(0, 5));

function setDate(d) {
  emit('update:modelValue', d ? `${d}T${timePart.value || '00:00'}` : '');
}

function setTime(t) {
  if (datePart.value) emit('update:modelValue', `${datePart.value}T${t}`);
}
</script>
