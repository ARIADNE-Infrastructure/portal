<template>
<div class="pt-3x px-base max-w-screen-md mx-auto text-hg">
  <!-- title & pw -->
  <h1 class="text-2xl mb-md">Update services and publishers</h1>
  <label class="mb-md block">Password <input type="password" class="w-full p-xs border-base" v-model="pw"></label>

  <!-- loading -->
  <div v-if="loading">Loading..</div>

  <div v-else>
    <!-- options -->
    <div class="block mb-md">
      <label class="inline-flex align-middle mr-sm">
        <input name="type" type="radio" :value="FormType.Services" v-model="type" v-on:change="optionChange(-1)">
        <span class="pl-xs">Services</span>
      </label>
      <label class="inline-flex align-middle">
        <input name="type" type="radio" :value="FormType.Publishers" v-model="type" v-on:change="optionChange(-1)">
        <span class="pl-xs">Publishers</span>
      </label>
    </div>
    <div>
      <select class="w-full p-xs border-base mb-md block" v-model="select" v-on:change="selectChange">
        <option :key="-1" :value="-1">Add new</option>
        <option v-for="item in (type === FormType.Services ? services : publishers)" :key="item.id" :value="item.id">
          {{ item.title }} (ID: {{ item.id }})
        </option>
      </select>
    </div>

    <!-- services -->
    <div v-if="type === FormType.Services">
      <label class="mb-md block">Title <input type="text" class="w-full p-xs border-base" v-model="data.title"></label>
      <label class="mb-md block">Topic <input type="text" class="w-full p-xs border-base" v-model="data.topic"></label>
      <label class="mb-md block">Url <input type="text" class="w-full p-xs border-base" v-model="data.url"></label>
      <label class="mb-md block">Img <input type="text" class="w-full p-xs border-base" v-model="data.img"></label>
      <label class="mb-md block">Text <textarea class="block w-full p-xs border-base" rows="10" v-model="data.description"></textarea></label>
    </div>

    <!-- publishers -->
    <div v-else>
      <label class="mb-md block">Title <input type="text" class="w-full p-xs border-base" v-model="data.title"></label>
      <label class="mb-md block">Url <input type="text" class="w-full p-xs border-base" v-model="data.url"></label>
      <label class="mb-md block">Img <input type="text" class="w-full p-xs border-base" v-model="data.img"></label>
      <label class="mb-md block">Text <textarea class="block w-full p-xs border-base" rows="10" v-model="data.text"></textarea></label>
    </div>

    <!-- error & ok text -->
    <div v-if="error" class="text-red mb-md">{{ error }}</div>
    <div v-if="ok" class="text-green mb-md">{{ ok }}</div>

    <!-- buttons -->
    <div class="flex align-middle justify-between">
      <button type="button" class="py-sm px-2x bg-black text-white border-base border-black cursor-pointer transition-all duration-300 hover:bg-white hover:text-black" v-on:click.prevent="saveItem">
        Save
      </button>
      <button type="button" class="text-red" v-if="select > -1" v-on:click.prevent="deleteItem">
        Delete
      </button>
    </div>
  </div>
</div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { generalModule } from '@/store/modules';
import utils from '@/utils/utils';
import axios from 'axios';

const publishers = $computed(() => generalModule.getPublishers);
const services = $computed(() => generalModule.getServices);

enum FormType {
  Services,
  Publishers,
}

let type: FormType = $ref(FormType.Services);
let pw = $ref('');
let select = $ref(-1);
let data: any = $ref({});
let loading: boolean = $ref(true);
let error: string = $ref('');
let ok: string = $ref('');
let sending: boolean = false;

onMounted(() => {
  generalModule.callAfterLoadedServices(() => {
    pw = (new URLSearchParams(location.search)).get('id') || generalModule.getFormPw;
    loading = false;
  });
});

const optionChange = (val: number) => {
  select = val;
  data = {};
};

const selectChange = () => {
  const item = (type === FormType.Services ? services : publishers).find((it: any) => it.id === select);
  data = item ? { ...item } : {}
};

const saveItem = () => sendRequest('update');
const deleteItem = () => {
  if (window.confirm('Delete item: ' + utils.escHtml(data.title) + ' (ID: ' + utils.escHtml(data.id) + ')?')) {
    sendRequest('delete');
  }
}

const sendRequest = async (cmd: string) => {
  if (sending) {
    return;
  }
  sending = true;
  const d = new FormData();
  for (let key in data) {
    d.append(key, data[key] || '');
  }
  d.append('cmd', cmd);
  d.append('id', select.toString());
  d.append('index', type === FormType.Services ? 'services' : 'publishers');

  const url = utils.paramsToString(process.env.apiUrl + '/updateServices', { id: pw });
  const res: any = (await axios.post(url, d))?.data?.trim();

  if (res !== 'ok') {
    error = res || 'Unknown error. Wrong password?';
  } else {
    if (!generalModule.getFormPw) {
      generalModule.updateFormPw(pw);
    }
    if (cmd === 'delete') {
      generalModule.updateServicesAndPublishers({
        publishers: type === FormType.Publishers ? publishers.filter((p: any) => p.id !== select) : publishers,
        services: type === FormType.Services ? services.filter((s: any) => s.id !== select) : services,
      });
      optionChange(-1);
    } else {
      if (!data.id || data.id < 0) {
        data.id = Math.max.apply(null, (type === FormType.Publishers ? publishers : services).map((it: any) => it.id)) + 1;
      }
      generalModule.updateServicesAndPublishers({
        publishers: type === FormType.Publishers ? utils.addMerged(publishers, data, 'id') : publishers,
        services: type === FormType.Services ? utils.addMerged(services, data, 'id') : services,
      });
      optionChange(data.id);
      selectChange();
    }
    ok = 'Saved changes';
  }
  setTimeout(() => {
    ok = '';
    error = '';
    sending = false;
  }, 3000);
}
</script>
