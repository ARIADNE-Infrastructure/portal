// store/modules/MyStoreModule.ts
import { SearchModule } from './Search';
import { aggregationModule } from "../modules";
import axios from 'axios';

export class PeriodsModule {
  searchModule: SearchModule;
  periodRegions: any = null;
  periods: any = null;
  cachedPeriods: any = null;
  hasUpdated: boolean = false;
  loaded: boolean = false;

  constructor(searchModule: SearchModule) {
    this.searchModule = searchModule;
  }

  async setRegions() {
    try {
      const url = process.env.apiUrl + '/getPeriodRegions';
      const res = await axios.get(url);
      if (res.data?.aggregations?.periodCountry?.buckets) {
        res.data.aggregations.periodCountry.buckets = await this.getMissingBuckets({
          key: 'temporalRegion',
          buckets: res.data.aggregations.periodCountry.buckets,
        });
      }
      this.updateRegions({
        total: res.data.total,
        aggs: res.data.aggregations,
        sum_other_doc_count: res.data?.aggregations?.periodCountry?.sum_other_doc_count,
      });
    } catch (ex) {}
  }

  async setPeriods(payload: any) {
    try {
      const url = process.env.apiUrl + '/getPeriodsForCountry?temporalRegion=' + (payload.temporalRegion || '');
      const res = await axios.get(url);
      if (res?.data?.filtered_agg?.buckets) {
        res.data.filtered_agg.buckets = await this.getMissingBuckets({
          key: 'culturalPeriods',
          buckets: res.data.filtered_agg.buckets,
        });
      }
      this.updatePeriods(!res?.data?.filtered_agg?.buckets?.length ? null : {
        sum_other_doc_count: res.data.filtered_agg.sum_other_doc_count,
        buckets: res.data.filtered_agg.buckets,
      });
      if (this.periods && Array.isArray(this.periods.buckets)) {
        this.updateCachedPeriods(this.periods.buckets);
        if (!this.hasUpdated && this.periods.buckets.some((b: any) => b?.hasUpdate)) {
          this.updateHasUpdated(true);
          axios.get(process.env.apiUrl + '/maybeUpdatePeriods');
        }
      }
    } catch (ex) {}
  }

  async getMissingBuckets(payload: any) {
    if (Array.isArray(payload.buckets)) {
      const params = this.searchModule.getParams[payload.key];
      if (params) {
        params.split('|').forEach((val: string) => {
          val = val.trim().toLowerCase();
          if (!payload.buckets.some((b: any) => (b.key || '').trim().toLowerCase() === val)) {
            if (payload.key === 'temporalRegion') {
              payload.buckets.push({ key: val, doc_count: 0 })
            } else {
              const cached = this.cachedPeriods?.find((p: any) => p.key === val);
              if (cached) {
                payload.buckets.push(cached);
              } else {
                const paramLabel = (this.searchModule.getParams.culturalLabels || '').split('|').find((p: any) => (p.split(':')[0] || '').trim().toLowerCase() === val);
                const label = (paramLabel ? paramLabel.split(':')[1] : '') || val;
                payload.buckets.push({ key: val, region: '', extraLabels: null, start: 0, timespan: '', filterLabel: label, doc_count: 0 })
              }
            }
          }
        });
      }
    }
    return payload.buckets;
  }

  updateHasUpdated(value: boolean) {
    this.hasUpdated = value;
  }

  updateRegions(result: any) {
    this.periodRegions = result;
  }

  setLoaded() {
    this.loaded = true;
  }

  updatePeriods(result: any) {
    this.periods = result;
    if (aggregationModule.getOptions?.culturalPeriods) {
      aggregationModule.getOptions.culturalPeriods.search = '';
      if (aggregationModule.getOptions.culturalPeriods.data?.buckets) {
        aggregationModule.getOptions.culturalPeriods.data = this.periods;
      }
      aggregationModule.updateOptions({ id: 'culturalPeriods', value: aggregationModule.getOptions.culturalPeriods });
    }
  }

  updateCachedPeriods(data: any) {
    if (!this.cachedPeriods) {
      this.cachedPeriods = data.slice();
    } else {
      data.forEach((b: any) => {
        if (!this.cachedPeriods.some(((p: any) => p.key === b.key))) {
          this.cachedPeriods.push(b);
        }
      });
    }
  }

  get getRegions(): any {
    return this.periodRegions?.aggs?.periodCountry;
  }

  get getPeriods(): any {
    return this.periods;
  }

  get getLoaded(): boolean {
    return this.loaded;
  }

  get getCachedPeriods(): any {
    return this.cachedPeriods;
  }
}
