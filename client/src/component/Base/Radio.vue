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
        class="relative block border-2 rounded-1/2 w-base h-base mr-sm transition-all duration-300"
        :class="{
          'border-blue': isChecked,
          'border-gray group-hover:border-blue-50': !isChecked
        }"
      >
        <span
          class="transform scale-0"
          :class="{
            'block bg-blue rounded-1/2 w-base h-base absolute left-1/2 bottom-1/2 -ml-sm -mb-sm transform scale-50 transition-transform duration-150': isChecked,
          }"
        />
      </span>

      {{ label }}
    </div>
  </label>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';

@Component
export default class BRadio extends Vue {
  @Prop() name!: string;
  @Prop() currentValue!: string;
  @Prop() value!: string;
  @Prop() label!: string;

  get isChecked(): boolean {
    return this.currentValue === this.value;
  }
}
</script>