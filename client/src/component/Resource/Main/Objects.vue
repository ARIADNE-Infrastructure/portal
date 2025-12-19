<template>
  <div v-if="(loading || objectUrls.length) && !failed">
    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-cube mr-sm"></i> 3D
    </h3>
    <div v-if="loading">
      Loading..
    </div>
    <div v-if="!loading">
      <iframe
        :src="displayUrl"
        loading="lazy"
        referrerpolicy="no-referrer"
        style="width:100%;height:450px"
        :onload="handleLoad"></iframe>
      <ul v-if="objectUrls.length > 1" class="mt-md w-full">
        <li v-for="(url, key) in objectUrls" class="mb-xs" :key="key">
          <i class="fas fa-cube mr-sm"></i>
          <span v-if="model && model.name === url.name" class="underline">
            {{ model.name }}
          </span>
          <b-link v-else :href="'/resource/' + resource.id + (key ? '?model=' + url.name : '')" class="text-blue transition-colors duration-300 hover:text-darkGray hover:underline">
            {{ url.name }}
          </b-link>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed, $ref } from 'vue/macros';
import { onMounted } from 'vue';
import { resourceModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

const emit = defineEmits(['error']);
const resource = $computed(() => resourceModule.getResource);
const objectUrls = $computed(() => resourceModule.get3dObjectUrls(resource));
let displayUrl = $ref('');
let model = $ref(null);
let loading = $ref(true);
let failed = $ref(false);

const handleLoad = (e: any) => {
  if (!e?.target?.contentWindow) {
    failed = true;
    emit('error', failed && objectUrls.length <= 1);
  } else {
    e.target.onload = null;
  }
}

onMounted(() => {
  const modelParam = (new URLSearchParams(location.search)).get('model');
  model = (modelParam ? objectUrls.find(url => url.name === modelParam) : null) || objectUrls[0];
  failed = !model?.url || !model?.name;
  if (failed) {
    emit('error', failed && objectUrls.length <= 1);
  } else if (model.type === 'periscope') {
    displayUrl = model.url;
  } else {
    displayUrl = `https://vcg.isti.cnr.it/varie/remote/viewer.html?url=${encodeURIComponent(model.url)}`;
  }
  loading = false;
});
</script>
