<template>
  <div>
    <div v-if="subject && !loading">
      <filter-mini-map
        title="Where"
        :noTopBar="true"
      />
    </div>
    <div v-else style="height:250px" class="bg-lightGray"></div>

    <article class="py-3x px-base mx-auto max-w-screen-xl text-mmd" v-if="subject">
      <div class="lg:flex">
        <div class="lg:w-2/3 lg:mr-xl">
          <h1 class="text-2xl mb-md">
            {{ utils.sentenceCase(subject.prefLabel || 'No title') }}
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
            icon="fas fa-tag mr-xs"
            title="Broader"
          />

          <resource-filtered-items
            :items="subject.subSubjects"
            :class="itemClass"
            filter="prefLabel"
            slotType="subject"
            icon="fas fa-tag mr-xs"
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

      <!-- Results -->
      <div class="mt-3x">
        <p>
          <b-link :to="utils.paramsToString('/search', { derivedSubject: subject.prefLabel })">
            <i class="fas fa-search mr-sm"></i>
            Show {{ result?.total?.value || 0 }} results in catalogue
          </b-link>
        </p>

        <section class="block mt-lg">
          <result-info class="py-base pt-none" :hideFilters="true" />

          <div class="md:flex mt-sm items-center justify-between pt-none">
            <result-paginator />
            <result-sort-order class="mt-lg md:mt-none" />
          </div>

          <div class="mb-base mt-xl">
            <result-list />
          </div>

          <result-paginator class="pt-base" :scrollTop="true" />
        </section>
      </div>
    </article>
  </div>
</template>

<script setup lang="ts">
import { onMounted, nextTick, watch } from 'vue';
import { $computed } from 'vue/macros';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { searchModule, generalModule, subjectModule } from "@/store/modules";
import ResourceFilteredItems from '@/component/Resource/FilteredItems.vue';
import ResultList from '@/component/Result/List.vue';
import ResultInfo from '@/component/Result/Info.vue';
import ResultPaginator from '@/component/Result/Paginator.vue';
import ResultSortOrder from '@/component/Result/SortOrder.vue';
import FilterMiniMap from '@/component/Filter/MiniMap.vue';
import BLink from '@/component/Base/Link.vue';
import utils from '@/utils/utils';

const props = defineProps<{
  id: string,
}>();

const router = useRouter();
const route = useRoute();
const itemClass: string = 'border-b-base border-gray mb-md pb-md last:border-b-0 last:pb-none last:mb-none';
const loading: boolean = $computed(() => generalModule.getIsLoading);
const subject = $computed(() => subjectModule.getSubject);
const result = $computed(() => searchModule.getResult);

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
    if (!subject) {
      router.replace('/404');

    } else if (!(new URLSearchParams(location.search).has('derivedSubject'))) {
      location.href = utils.paramsToString(`/subject/${ id }`, { derivedSubject: subject.prefLabel });

    } else {
      generalModule.setMeta({
        title: subject.prefLabel || '',
        description: (subject.scopeNote || '').slice(0, 155)
      });
      searchModule.setSearch({ fromRoute: true });
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
