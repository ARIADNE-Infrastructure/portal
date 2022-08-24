<template>
  <label class="cursor-pointer group">
    <input
      class="opacity-0 absolute"
      type="radio"
      :name="name"
      :value="value"
      @input="$emit('input', $event)"
    >

    <div class="flex items-center">
      <span
        class="relative block border-2 rounded-1/2 w-base h-base mr-sm transition-all duration-300 border-white-80"
        :class="{
          'group-hover:border-white': !isChecked
        }"
      >
        <span
          class="transform scale-0"
          :class="{
            'block bg-white rounded-1/2 w-base h-base absolute left-1/2 bottom-1/2 -ml-sm -mb-sm transform scale-50 transition-transform duration-150': isChecked,
          }"
        />
      </span>

      {{ label }}
    </div>
  </label>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';

const props = defineProps<{
  name: string,
  currentValue: string,
  value: string,
  label: string
}>();

const isChecked: boolean = $computed(() => props.currentValue === props.value)
</script>
