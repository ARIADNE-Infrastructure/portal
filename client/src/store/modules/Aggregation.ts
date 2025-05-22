import axios from 'axios';
import utils from '@/utils/utils';
import { SearchModule } from './Search';
import { PeriodsModule } from "./Periods";
import { titles, descriptions, resultTitles, types } from './Aggregation/static';

export interface iKeyVal {
  key: string,
  val: string,
}

export class AggregationModule {
  searchModule: SearchModule;
  periodsModule: PeriodsModule;
  options: any = {};
  titles: any = titles;
  descriptions: any = descriptions;
  resultTitles: any = resultTitles;
  types: any[] = types;

  constructor(searchModule: SearchModule, periodsModule: PeriodsModule) {
    this.searchModule = searchModule;
    this.periodsModule = periodsModule;
  }

  async setSearch(payload: any) {
    if (payload.value.search || payload.value.size) {
      const searchParams = this.searchModule.getParams;
      const params = { ...{ filterQuery: payload.value.search?.toLowerCase().replace(/\-/g, ' '), filterName: payload.id }, ...searchParams };
      if (payload.value.size) {
        params.filterSize = payload.value.size;
      }
      if (params.filterName === 'ariadneSubject' && /^dati/.test(params.filterQuery)) {
        params.filterQuery = 'date';
      }
      if (params.ariadneSubject?.includes('Dating')) {
        params.ariadneSubject = params.ariadneSubject.replace('Dating', 'Date')
      }
      const url = process.env.apiUrl + '/autocompleteFilter';

      try {
        const reqId = ++this.searchModule.reqMap.auto;
        const res = await axios.get(utils.paramsToString(url, params));
        if (payload.value.search && !this.options[payload.id]?.search) {
          return;
        }
        let data = res?.data?.filtered_agg;
        if (utils.objectIsNotEmpty(data)) {
          data.buckets?.forEach(b => {
            if (b?.key?.toLowerCase() === 'date') {
              b.key = 'Dating';
            }
          });
          if ((params.filterName === 'temporalRegion' || params.filterName === 'culturalPeriods') && data.buckets) {
            data.buckets = await this.periodsModule.getMissingBuckets({ key: params.filterName, buckets: data.buckets });
          }
          const value = { ...payload.value, ...{ data } };
          if (reqId === this.searchModule.reqMap.auto) {
            this.updateOptions({ id: payload.id, value, fromSearch: true });
          }
          if (params.filterName === 'culturalPeriods' && Array.isArray(data.buckets)) {
            this.periodsModule.updateCachedPeriods(data.buckets)
          }
        }
      } catch (ex) {}
    }
  }

  setOptionsToDefault() {
    this.clearOptions();
  }

  setOptions(payload: any) {
    this.updateOptions(payload);
  }

  setActive(payload: any) {
    const params: any = this.searchModule.getParams;
    let { key, value, add } = payload;
    const origValue = value

    if (params[key]) {
      let aggs = params[key].split('|');

      if (add) {
        aggs.push(value)

      } else {
        aggs = aggs.filter((agg: any) => {
          return String(agg || '').toLowerCase() !== String(value || '').toLowerCase();
        });
      }

      value = aggs.join('|');
    }

    const data: any = { [key]: value, page: 0 }

    if ((key === 'temporalRegion' || key === 'culturalPeriods')) { // clear range filter if selecting periods
      data.range = '';
      if (key === 'culturalPeriods') { // store label in url, add / remove here
        const labels = (params.culturalLabels ? params.culturalLabels.split('|') : []).filter((l: string) => (value || '').split('|').some((v: string) => v === l.split(':')[0]));
        if (add) {
          const label = this.periodsModule.getCachedPeriods?.find((b: any) => b?.key === origValue);
          if (label) {
            const langCode = label.region ? utils.getCountryCode(label.region) : '';
            labels.push(origValue + ':' + label.filterLabel + (langCode ? ' (' + langCode + ')' : ''));
          }
        }
        data.culturalLabels = labels.length ? labels.join('|') : ''
      }
    }
    if (key === 'range') { // clear periods if selecting range
      data.temporalRegion = '';
      data.culturalPeriods = '';
      data.culturalLabels = '';
    }

    this.searchModule.setSearch(data);
  }

  clearFilterOptions(keep?: string) {
    for (let key in this.descriptions) {
      if (this.options[key]) {
        let data = {};
        let search = '';
        if (keep && keep === key && this.options[key].search?.trim() && this.searchModule.getAggsResult.aggs?.[key]) {
          search = this.options[key].search.trim();
          data = this.searchModule.getAggsResult.aggs[key];
        }
        const payload = {
          id: key,
          value: {
            data,
            search,
            size: 0,
            sortBy: this.options[key].sortBy || 'doc_count',
            sortOrder: this.options[key].sortOrder || 'desc'
          }
        };
        if (search) {
          this.setSearch(payload);
        } else {
          this.updateOptions(payload);
        }
      }
    }
  }

  updateOptions(payload: any) {
    const { id, value, fromSearch } = payload;
    if (fromSearch) {
      value.search = this.options[id]?.search || value.search;
    }
    this.options[id] = value
  }

  clearOptions() {
    this.options = {};
  }

  get getTitles(): any {
    return this.titles;
  }

  get getTypes(): any[] {
    return this.types;
  }

  get hasAggs(): boolean {
    return utils.objectIsNotEmpty(this.searchModule.getAggsResult.aggs);
  }

  get getResultTitle() {
    return (key: string) => {
      return this.resultTitles[key] || utils.sentenceCase(utils.splitCase(key));
    }
  }

  get getTitle() {
    return (key: string) => {
      return this.titles[key] || utils.sentenceCase(utils.splitCase(key));
    }
  }

  get getDescription() {
    return (key: string) => {
      return this.descriptions[key] || '';
    }
  }

  get getDescriptions() {
    return this.descriptions;
  }

  get getSorted() {
    const startOrder = [
      'ariadneSubject',
      'derivedSubject',
      'publisher',
      'contributor',
      'nativeSubject',
      'country',
      'dataType',
    ];

    const result = this.searchModule.getAggsResult?.aggs;
    let sorted: any = {};

    if (this.hasAggs) {
      const typesToSkip = ['fields', 'geogrid', 'bbox', 'range'];

      startOrder.forEach((key: string) => sorted[key] = result[key]);

      for (let key in result) {
        if (!sorted[key] && !typesToSkip.includes(key)) {
          sorted[key] = result[key];
        }
      }
    }

    return sorted;
  }

  get getAnyActive() {
    return (key: string, items: string[]) => {
      if (!items) {
        return false;
      }

      return items.some((item: any) => this.getIsActive(key, item.key));
    }
  }

  get getIsActive() {
    return (key: string, bucketKey: string) => {
      const params = this.searchModule.getParams;

      if (params[key]) {
        return params[key].split('|').some((k: any) => {
          return String(k || '').toLowerCase() === String(bucketKey || '').toLowerCase();
        });
      }

      return false;
    }
  }

  get getOptions() {
    return this.options;
  }

  // Used for displaying what user has filtered and/or search on. Not all uri params are valid for this.
  get activeFilters(): Array<iKeyVal> {
    const valid = Object.keys(this.getTitles).concat(['q', 'range', 'geogrid', 'responsible', 'periodName', 'bbox', 'temporalRegion', 'culturalPeriods']);
    const params = utils.getCopy(this.searchModule.getParams);
    const filters: Array<iKeyVal> = [];

    valid.forEach((key: string) => {
      if (typeof params[key] === 'string') {
        params[key].split('|').forEach((val: string) => {
          val = val.trim();
          if (val) {
            filters.push({ key, val });
          }
        });
      }
    });
    return filters;
  }

}
