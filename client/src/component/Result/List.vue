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

            <!-- image -->
            <div v-if="hasValidPrimaryImage(res.data)" class="pb-base">
              <img
                :src="getPrimaryImage(res.data)"
                class="backface-hidden transition-filter duration-300 grayscale group-hover:grayscale-0"
                style="max-width:100px;max-height:60px;transform:translateZ(0)"
              />
            </div>

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
              <p v-if="res.data.issued" class="text-mmd">
                <b>Issued</b>: {{ res.data.issued }}
              </p>
            </div>
          </div>
        </b-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed, $ref } from 'vue/macros';
import { generalModule, searchModule, aggregationModule, resourceModule } from "@/store/modules";
import { themeAggregationTypesToSkip } from '@/theme/settings';

// base & utils
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

// general
import HelpTooltip from '@/component/Help/Tooltip.vue';

const params = $computed(() => searchModule.getParams);
const result = $computed(() => searchModule.getResult);
const assets: string = $computed(() => generalModule.getAssetsDir);
const isLoading: boolean = $computed(() => generalModule.getIsLoading);
const validPrimaryImages: any = $ref({});

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
      if (agg.id === 'resourceType') {
        d = [d];
      }
      if (Array.isArray(d) && d.length) {
        if (!agg.prop || d.some((p: any) => p[agg.prop])) {
          let str = getMarked(joinMatching(d, agg));
          if (agg.always || str.includes('<span class="bg-')) {
            if(!themeAggregationTypesToSkip.includes(agg.id)) {
              return {
                id: agg.id,
                data: str
              };
            }
          }
        }
      }
    }
    return null;
  });
}

// returns primary image url for resource, if any
const getPrimaryImage = (data: any): string => {
  return resourceModule.getDigitalImages(data, true)[0] || '';
}

// returns if has valid primary image - checks remote and sets if ok in obj, many returns 404 so don't want to display those
const hasValidPrimaryImage = (data: any): boolean => {
  const url = getPrimaryImage(data);

  if (url && validPrimaryImages[url] === undefined) {
    validPrimaryImages[url] = false;
    const img = new Image()
    const done = (status: boolean) => {
      validPrimaryImages[url] = status;
      img.onload = null;
      img.onerror = null;
    }
    img.onload = () => done(true);
    img.onerror = () => done(false);
    img.src = url;
  }

  return url && validPrimaryImages[url];
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
    'q', 'isPartOf', 'placeName', 'temporal', 'range', 'ariadneSubject', 'resourceType', 'derivedSubject', 'nativeSubject',
    'keyword', 'publisher', 'contributor', 'owner', 'responsible', 'creator', 'geogrid', 'isPartOfLabel', 'culturalLabels'
  ];

  for (let key in params) {
    let val = params[key];
    if (val && valid.includes(key) && val.trim()) {
      if (val.includes('|')) {
        val.split('|').forEach((v: string) => {
          if (v && v.trim()) {
            if (key === 'culturalLabels') {
              v = utils.sentenceCase((v.split(':')[1] || '').split('(')[0].trim());
            }
            q.push(v.trim());
          }
        });
      } else {
        if (key === 'culturalLabels') {
          val = utils.sentenceCase((val.split(':')[1] || '').split('(')[0].trim());
        }
        q.push(val.trim());
      }
    }
  }
  q.sort((a: string, b: string) => b.length - a.length);
  return utils.getMarked(text, q.join('|'));
}
</script>
