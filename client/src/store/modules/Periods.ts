// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';

@Module
export class PeriodsModule extends VuexModule {
  private periodRegions: any = null;
  private periods: any = null;

  constructor(options: RegisterOptions) {
    super(options);
  }

  @Action
  async setRegions() {
    const url = process.env.apiUrl + '/getPeriodRegions';
    const res = await axios.get(url);
    this.updateRegions({
      total: res.data.total,
      aggs: res.data.aggregations,
    });
  }

  @Action
  async setPeriods(payload: any) {
    const url = process.env.apiUrl + '/getPeriodsForCountry?temporalRegion=' + (payload.temporalRegion || '');
    const res = await axios.get(url);
    this.updatePeriods(!res.data.filtered_agg.buckets.length ? null : {
      sum_other_doc_count: res.data.filtered_agg.sum_other_doc_count,
      buckets: res.data.filtered_agg.buckets,
    });
  }

  @Mutation
  updateRegions(result: any) {
    this.periodRegions = result;
  }

  @Mutation
  updatePeriods(result: any) {
    this.periods = result;
  }

  get getRegions(): any {
    return this.periodRegions?.aggs?.periodCountry;
  }

  get getPeriods(): any {
    return this.periods;
  }

}
