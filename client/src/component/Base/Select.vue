<template>
  <div
    class="whitespace-no-wrap select-none bg-white text-left"
  >
    <div
      class="border-base h-full flex justify-between items-center overflow-x-hidden"
      :class="selectedClass"
      @click="toggle"
      v-on:keydown.esc="leave"
      v-on-clickaway="leave"
    >
      <input
        class="rounded-base flex-grow truncate outline-none cursor-default p-sm pr-none text-md"
        :class="{ 'placeholder-black': !open }"
        :placeholder="selectedText"
        ref="search"
        v-model="search"
      />

      <i
        v-if="!search && getOptionIconFromValue(localValue)"
        class="-ml-3x text-md"
        :class="getOptionIconFromValue(localValue)"
      />

      <i
        class="fa-chevron-down fas mr-xs duration-300 px-sm text-sm"
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
          <template
            v-for="(group, groupId) of groupedFilteredOptions"
          >
            <li
              v-for="(option, optionIndex) of group"
              :key="`${ groupId }-${ optionIndex }`"
              class="p-sm relative w-full block truncate text-md"
              :class="{
                [altGroupFirstItemClass]: groupId !== 'default' && !optionIndex,
                'cursor-pointer hover:bg-lightGray': !option.disabled,
                'cursor-not-allowed text-gray': option.disabled
              }"
              @click="select(option)"
            >
              <span
                class="flex items-center"
                :class="{ 'font-bold': option.val === localValue }"
              >
                {{ option.text }}

                <i
                  v-if="getOptionIconFromValue(option.val)"
                  class="pl-sm mb-1"
                  :class="getOptionIconFromValue(option.val)"
                />
              </span>
            </li>
          </template>
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
import utils from '@/utils/utils';

@Component({
  directives: {
    onClickaway
  }
})
export default class BSelect extends Vue {
  @Prop() color!: string;
  @Prop() options!: any[];
  @Prop() value!: string;
  @Prop() selectClass?: string;

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

  getOptionIconFromValue(value: string): string {
    if (value.includes('-desc')) {
      return 'fa-arrow-down fas';
    }
    else if (value.includes('-asc')) {
      return 'fa-arrow-up fas';
    }

    return '';
  }

  get selectedClass(): string {
    let classes = '';

    if (this.selectClass) {
      classes = `${ this.selectClass } `;
    }

    if (this.open) {
      classes += `rounded-base rounded-b-0 border-${this.color}`;
    }
    else {
      classes += 'rounded-base border-gray';
    }

    return classes;
  }

  get dropdownClass(): string {
    return `rounded-base rounded-t-0 border-${this.color}`;
  }

  get altGroupFirstItemClass(): string {
    return `border-t-base border-${this.color}`;
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
      return this.options.filter((item: any) => item.text.toLowerCase().includes(search));
    }
    else {
      return this.options;
    }
  }

  get groupedFilteredOptions(): any[] {
    return utils.groupListByKey(this.filteredOptions, 'group');
  }

  select(option: any): void {
    if (option?.disabled) {
      return;
    }

    // update local value
    this.localValue = option.val;

    // exit focus state
    this.leave();

    // update for prop
    this.$emit('input', option.val);

    // close
    this.open = false;
  }

  // set local value to match prop
  @Watch('value', { immediate: true })
  updateLocalValue() {
    this.localValue = this.value;
    this.$emit('change', this.value);
  }
}
</script>