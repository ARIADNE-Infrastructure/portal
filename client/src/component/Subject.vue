<template>
  <div>
    <div>
      <article class="py-3x px-base mx-auto max-w-screen-xl text-mmd" v-if="subject">
        <div class="lg:flex">
          <div class="lg:w-2/3 lg:mr-xl">
            <h1 class="text-2xl mb-md">
              {{ utils.sentenceCase(subject.prefLabel || 'No title') }}
            </h1>
            <div class="whitespace-pre-line mb-xl" v-if="subject.scopeNote">{{ utils.cleanText(subject.scopeNote, true) }}</div>

            <div :class="itemClass">
              <b>Getty AAT ID</b>: {{ subject.id }}
            </div>

            <div v-if="utils.validUrl(subject.uri)" :class="itemClass">
              <b>URI</b>:
              <b-link :href="subject.uri" target="_blank"  :breakWords="true">
                {{ subject.uri }}
              </b-link>
            </div>

            <resource-filtered-items
              :items="subject.broader"
              :class="itemClass"
              filter="prefLabel"
              slotType="subject"
              icon="fas fa-tag"
              title="Broader"
            />

            <resource-filtered-items
              :items="subject.subSubjects"
              :class="itemClass"
              filter="prefLabel"
              slotType="subject"
              icon="fas fa-tag"
              title="Narrower"
            />

            <div v-if="subject.providerMappings && subject.providerMappings.length">
              <h3 class="text-lg font-bold mt-2x mb-md">Provider mapping</h3>
              <div v-for="(item, key) in subject.providerMappings" :key="key" :class="itemClass">
                <p><b>{{ item.sourceLabel }}</b></p>
                <p v-if="utils.validUrl(item.matchURI)">
                  Match URI:
                  <b-link :href="item.matchURI" target="_blank" :breakWords="true">
                    {{ item.matchURI }}
                  </b-link>
                </p>
                <p v-if="utils.validUrl(item.sourceURI)">
                  Source URI:
                  <b-link :href="item.sourceURI" target="_blank" :breakWords="true">
                    {{ item.sourceURI }}
                  </b-link>
                </p>
              </div>
            </div>
          </div>

          <div class="lg:w-1/3 lg:ml-xl mt-xl lg:mt-none">
            <h3 class="text-lg font-bold mt-2x mb-md">Connected resources</h3>

            <router-link
              :to="utils.paramsToString('/search', { derivedSubjectId: subject.id, derivedSubjectIdLabel: subject.prefLabel })"
              class="block md:w-auto p-md bg-blue hover:bg-blue-80 text-white rounded-base py-xs transition-all duration-300 focus:outline-none text-center">
              <i class="p-sm pl-none fas fa-search"></i>
              Search connected resources ({{ subject.connectedTotal }})
            </router-link>

            <div v-if="mappedTerms">
              <h3 class="text-lg font-bold mt-2x mb-md">Terms</h3>

              <div v-for="(items, key) in mappedTerms" :key="key" :class="itemClass">
                <resource-filtered-items
                  :items="items"
                  :class="itemClass"
                  :title="key"
                  slotType="prop"
                  prop="label"
                />
              </div>
            </div>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, nextTick, watch } from 'vue';
import { $computed } from 'vue/macros';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { searchModule, generalModule, subjectModule } from "@/store/modules";
import ResourceFilteredItems from '@/component/Resource/FilteredItems.vue';
import BLink from '@/component/Base/Link.vue';
import utils from '@/utils/utils';

const props = defineProps<{
  id: string,
}>();

const router = useRouter();
const route = useRoute();
const itemClass: string = 'border-b-base border-gray mb-md pb-md last:border-b-0 last:pb-none last:mb-none';
const subject = $computed(() => subjectModule.getSubject);

const mappedTerms = $computed(() => {
  if (subject?.prefLabels?.length) {
    const map: any = {};
    subject.prefLabels.forEach((pref: any) => {
      if (!map[pref.lang]) {
        map[pref.lang] = [];
      }
      map[pref.lang].push(pref);
    });
    return map;
  }
  return null;
});

const initSubject = async (id: string) => {
  id = String(id);

  if (subject?.id && subject.id === id) {
    return;
  }

  await subjectModule.setSubject(id);

  nextTick(() => {
    if (subject) {
      generalModule.setMeta({
        title: subject.prefLabel || '',
        description: (subject.scopeNote || '').slice(0, 155)
      });
    } else {
      router.replace('/404');
    }
  });
}

onMounted(() => initSubject(props.id));

const unwatch = watch(route, async (to: any) => {
    if (to?.params?.id) {
      await initSubject(to.params.id);
    }
});

onBeforeRouteLeave(unwatch);
</script>
