<template>
  <div
    class="whitespace-no-wrap select-none bg-white text-left"
  >
    <div
      class="border-base h-full flex justify-between items-center overflow-x-hidden"
      :class="selectedClass"
      @click="toggle"
      @keydown.esc="leave"
      v-on-clickaway="leave"
    >
      <input
        class="rounded-base flex-grow truncate outline-none cursor-default p-sm pr-none"
        :class="{ 'placeholder-black': !open }"
        :placeholder="selectedText"
        ref="search"
        v-model="search"
      />

      <i
        class="fa-chevron-down fas mr-xs duration-300 px-sm"
        :class="{ 'transform rotate-180': open }"
      />
    </div>

    <div class="relative">
      <div
        v-if="open"
        class="border-base absolute left-0 w-full bg-white border-t-0 z-1003 shadow-bottom"
        :class="dropdownClass"
      >
        <ul
          v-if="filteredOptions.length"
        >
          <li
            v-for="(option, i) of filteredOptions"
            :key="i"
            class="cursor-pointer hover:bg-lightGray p-sm relative w-full block truncate"
            @click="select(option)"
          >
            {{ option.text }}
          </li>
        </ul>

        <div
          v-else
          class="p-sm text-md"
          @click="leave"
        >
          No matches..
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { directive as onClickaway } from 'vue-clickaway';
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';

@Component({
  directives: {
    onClickaway
  }
})
export default class BaseSelect extends Vue {
  @Prop() color!: string;
  @Prop() options!: any[];
  @Prop() value!: string;

  localValue: string = '';
  open: boolean = false;
  search: string = '';

  enter() {
    const search = this.$refs.search as HTMLInputElement;
    search.focus();

    this.open = true;
  }

  leave() {
    this.open = false;
    this.search = '';
  }

  toggle() {
    if (!this.open) {
      this.enter();
    }
    else {
      this.leave();
    }
  }

  get selectedClass() {
    if (this.open) {
      return `rounded-base rounded-b-0 border-${this.color}`;
    }

    return 'rounded-base border-gray';
  }

  get dropdownClass() {
    return `rounded-base rounded-t-0 border-${this.color}`;
  }

  get selected(): any {
    return this.options.find((item: any) => item.val === this.localValue);
  }

  get selectedText(): string {
    return this.selected ? this.selected.text : '';
  }

  get filteredOptions(): any[] {
    const search = this.search.toLowerCase();

    if (search) {
      return this.options
        .filter((item: any) => item.text.toLowerCase().includes(search));
    }
    else {
      return this.options;
    }
  }

  select(option: any): void {
    // update local value
    this.localValue = option.val;

    // exit focus state
    this.leave();

    // update for prop
    this.$emit('input', option.val);
  }

  // set local value to match prop
  @Watch('value', { immediate: true })
  updateLocalValue() {
    this.localValue = this.value;
  }
}
</script>
