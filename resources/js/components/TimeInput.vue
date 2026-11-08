<template>
  <input
    :value="text"
    type="text"
    inputmode="numeric"
    placeholder="HH:MM"
    maxlength="5"
    autocomplete="off"
    @input="onInput"
    @blur="onBlur"
  />
</template>

<script setup>
// 24-hour time field. Native <input type="time"> follows the OS locale and
// shows AM/PM on many systems, so we use a plain text field instead.
// v-model value is "HH:MM" (same format a native time input produces).
import { ref, watch } from 'vue';

const props = defineProps({ modelValue: { type: String, default: '' } });
const emit = defineEmits(['update:modelValue']);

const text = ref(props.modelValue || '');

watch(() => props.modelValue, (v) => {
  if ((v || '') !== normalize(text.value)) text.value = v || '';
});

function normalize(raw) {
  const m = String(raw).trim().match(/^(\d{1,2})(?::?(\d{2}))?$/);
  if (!m) return null;
  const h = Number(m[1]);
  const min = Number(m[2] ?? 0);
  if (h > 23 || min > 59) return null;
  return `${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
}

function onInput(e) {
  let v = e.target.value.replace(/[^\d:]/g, '');
  if (/^\d{3,4}$/.test(v)) v = `${v.slice(0, v.length - 2)}:${v.slice(-2)}`;
  text.value = v;
  e.target.value = v;
  if (/^\d{1,2}:\d{2}$/.test(v)) {
    const n = normalize(v);
    if (n) emit('update:modelValue', n);
  }
}

function onBlur() {
  const n = normalize(text.value);
  if (n) {
    text.value = n;
    emit('update:modelValue', n);
  } else {
    text.value = props.modelValue || '';
  }
}
</script>
