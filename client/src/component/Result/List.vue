<template>
  <div>
    <p v-if="result.error" class="mt-xl text-red text-mmd">
      {{ result.error }}
    </p>
    <p v-else-if="utils.objectIsEmpty(result) && isLoading" class="mt-xl text-mmd">
      Searching..
    </p>
    <p v-else-if="!result.hits || !result.hits.length" class="mt-xl text-mmd">
      No results found
    </p>
    <div v-else>
      <div
        v-for="(res, key) in result.hits"
        v-bind:key="key"
        class="relative"
      >
        <!-- icon -->
        <div class="absolute left-md top-base flex flex-col">
          <help-tooltip
            v-if="getResourceTypeName(res)"
            :title="getResourceTypeName(res)"
            :isCenter="false"
            left="3rem"
          >
            <b-link
              :to="`/resource/${ res.id }`"
              class="leading-0"
            >
              <img
                :src="getResourceIconTemporary(res)"
                alt="icon"
                width="35"
                height="35"
              >
            </b-link>
          </help-tooltip>

          <help-tooltip
            v-if="getIsCtsCertified(res)"
            title="CoreTrustSeal Certified"
            :isCenter="false"
            top="1rem"
            left="2.5rem"
          >
            <a
              href="https://www.coretrustseal.org/"
              target="_blank"
              class="mt-base w-2x h-2x"
            >
              <img
                :src="`${ assets }/CTS-logo.png`"
                alt="CoreTrustSeal Certified"
                class="w-full"
              >
            </a>
          </help-tooltip>
        </div>

        <b-link
          :to="`/resource/${ res.id }`"
          class="block border-t-base border-gray p-base transition-all duration-300 hover:bg-lightGray-60 group"
        >
          <div class="ml-4x pl-sm">
            <!-- title -->
            <h3
              class="text-blue text-mmd font-bold mb-base group-hover:underline"
              v-html="getTitle(res.data) || 'No title'"
            />

            <!-- description -->
            <div
              v-if="!!res.data.description"
              class="mb-base text-mmd"
            >
              <p v-html="getDescription(res.data)"></p>
            </div>

            <div>
              <!-- aggregations -->
              <div
                v-for="(agg, key) in getAggregations(res.data)"
                :key="key"
              >
                <div
                  v-if="agg && agg.data"
                  class="text-mmd"
                >
                  <b>{{ getAggResultTitle(agg.id) }}</b>:
                  <span v-html="agg.data"></span>
                </div>
              </div>

            </div>
          </div>
        </b-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule, searchModule, aggregationModule, resourceModule } from "@/store/modules";

// base & utils
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

// general
import HelpTooltip from '@/component/Help/Tooltip.vue';

const params = $computed(() => searchModule.getParams);
const result = $computed(() => searchModule.getResult);
const assets: string = $computed(() => generalModule.getAssetsDir);
const isLoading: boolean = $computed(() => generalModule.getIsLoading);

const getTitle = (data: any): string => {
  return  getMarked( data?.title?.text ) || 'No title';
}

const getDescription = (data: any): string => {
  const t = getMarked(data?.description?.text);
  return utils.cleanText(t, false).slice(0, 400) + '..';
}

const getAggResultTitle = (key: string): string => {
  return aggregationModule.getResultTitle(key);
}

const getResourceTypeName = (item: any): string => {
  return item.data?.ariadneSubject?.[0].prefLabel ?? '';
}

const getResourceIcon = (item: any): string => {
  const typeId = item.data?.ariadneSubject?.prefLabel;
  return resourceModule.getIconByTypeId(typeId);
}

const getResourceIconTemporary = (item: any): string => {
  const type = item.data?.ariadneSubject?.[0]?.prefLabel;
  return resourceModule.getIconByTypeNameTemporary(type);
}

const getIsCtsCertified = (item: any): boolean => {
  return resourceModule.getIsCtsCertified(item.data);
}

const getAggregations = (data: any): Array<any> => {
  return aggregationModule.getTypes.map((agg: any) => {
    let d = data[agg.id];

    if (d) {
      if (agg.id === 'ariadneSubject') {
        d = [d[0]];
      }
      if (Array.isArray(d) && d.length) {
        if (!agg.prop || d.some((p: any) => p[agg.prop])) {
          let str = getMarked(joinMatching(d, agg));
          if (agg.always || str.includes('<span class="bg-')) {
            return {
              id: agg.id,
              data: str
            };
          }
        }
      }
    }
    return null;
  });
}

const joinMatching = (data: any, agg: any): string => {
  let q = (params.q || '').trim().toLowerCase(),
      a = (params[agg.param || agg.id] || '').trim().toLowerCase(),
      newData: any[] = [],
      max = 5;

  if (agg.prop) {
    data = data.filter((d: any) => d[agg.prop]).map((d: any) => String(d[agg.prop]));
  }
  if (a.includes('|')) {
    a = a.split('|');
  }

  data.forEach((d: string) => {
    d = d.toLowerCase();

    if (!utils.isInvalid(d) && !newData.includes(d)) {
      if ((q && d.includes(q)) || (a && (Array.isArray(a) ? a.some(p => d.includes(p)) : d.includes(a)))) {
        newData.unshift(d);
      } else {
        newData.push(d);
      }
    }
  });

  return newData.slice(0, max).map((s: string) => {
    if (agg.unformatted) {
      return data.find((d: any) => d.toLowerCase() === s);
    }
    return utils.sentenceCase(s);
  }).join(', ');
}

const getMarked = (text: string): string => {
  let q = [];
  let valid = [
    'q', 'isPartOf', 'temporal', 'range', 'ariadneSubject', 'derivedSubject', 'nativeSubject',
    'keyword', 'publisher', 'contributor', 'geogrid', 'isPartOfLabel', 'derivedSubjectIdLabel',
  ];

  for (let key in params) {
    let val = params[key];
    if (val && valid.includes(key) && val.trim()) {
      if (val.includes('|')) {
        val.split('|').forEach((v: string) => {
          if (v && v.trim()) {
            q.push(v.trim());
          }
        });
      } else {
        q.push(val.trim());
      }
    }
  }
  q.sort((a: string, b: string) => b.length - a.length);
  return utils.getMarked(text, q.join('|'));
}
</script>
