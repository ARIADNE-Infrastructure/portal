<template>
  <div
    class="whitespace-no-wrap select-none bg-white text-left"
  >
    <div
      class="border-base h-full flex justify-between items-center overflow-x-hidden"
      :class="selectedClass"
      @click="toggle"
      v-on:keydown.esc="leave"
      v-click-away="leave"
    >
      <input
        class="rounded-base flex-grow truncate outline-none cursor-default p-sm pr-none text-md"
        :class="{ 'placeholder-black': !open }"
        :placeholder="selectedText"
        ref="searchRef"
        v-model="search"
      />

      <i
        v-if="!search && getOptionIconFromValue(value)"
        class="-ml-3x text-md"
        :class="getOptionIconFromValue(value)"
      />

      <i
        class="fa-chevron-down fas mr-sm duration-300 px-sm text-sm"
        :class="{ 'transform rotate-180': open }"
      />
    </div>

    <div class="relative">
      <div
        v-if="open"
        class="border-base absolute left-0 w-full bg-white border-t-0 z-30 shadow-bottom"
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
                :class="{ 'font-bold': option.val === value }"
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

<script setup lang="ts">
import { $ref, $computed } from 'vue/macros';
import utils from '@/utils/utils';

const props = defineProps({
  color: String,
  selectClass: String,
  options: { type: Array, required: true },
  value: { type: String, default: '' },
});

const emit = defineEmits(['input', 'change']);

let open: boolean = $ref(false);
let search: string = $ref('');
const searchRef: any = $ref(null);

const dropdownClass: string = $computed(() => `rounded-base rounded-t-0 border-${props.color}`);
const altGroupFirstItemClass: string = $computed(() => `border-t-base border-${props.color}`);
const selected: any = $computed(() => props.options.find((item: any) => item.val === props.value));
const selectedText: string = $computed(() => selected ? selected.text : '');

const filteredOptions: Array<any> = $computed(() => {
  const s = search.toLowerCase();
  if (s) {
    return props.options.filter((item: any) => item.text.toLowerCase().includes(s));
  }
  return props.options;
});

const selectedClass: string = $computed(() => {
  let classes = '';

  if (props.selectClass) {
    classes = props.selectClass + ' ';
  }
  if (open) {
    classes += `rounded-base rounded-b-0 border-${props.color}`;
  } else {
    classes += 'rounded-base border-gray';
  }
  return classes;
});

const groupedFilteredOptions: Array<any> = $computed(() => utils.groupListByKey(filteredOptions, 'group'));

const enter = () => {
  searchRef.focus();
  open = true;
}

const leave = () => {
  open = false;
  search = '';
}

const toggle = () => {
  open ? leave() : enter();
}

const getOptionIconFromValue = (value: string): string => {
  if (value.includes('-desc')) {
    return 'fa-arrow-down fas';
  }
  if (value.includes('-asc')) {
    return 'fa-arrow-up fas';
  }
  return '';
}

const select = (option: any) => {
  if (option?.disabled) {
    return;
  }

  // exit focus state
  leave();

  // update for prop
  emit('input', option.val);

  // close
  open = false;
}
</script>
