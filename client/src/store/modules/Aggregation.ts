// store/modules/MyStoreModule.ts
import { Vue } from 'vue-property-decorator';
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import utils from '@/utils/utils';
import { Search } from './Search';
import { titles, resultTitles, types } from './Aggregation/static';

@Module
export class Aggregation extends VuexModule {
  private search: Search;

  constructor(search: Search, options: RegisterOptions) {
    super(options);
    this.search = search;
  }

  private options: any = {};
  private titles: any = titles;
  private resultTitles: any = resultTitles;
  private types: any[] = types;

  @Action
  setOptions(payload: any) {
    this.updateOptions(payload);
  }

  @Action
  setActive(payload: any) {
    const params: any = this.search.getParams;

    let { key, value, add } = payload;

    if (params[key]) {
      let aggs = params[key].split('|');

      if (add) {
        aggs.push(value)

      } else {
        aggs = aggs.filter((agg: any) => {
          return String(agg || '').toLowerCase() !== String(value || '').toLowerCase();
        });
      }

      value = aggs.join('|');
    }

    this.search.setSearch({ [key]: value, page: 0 });
  }

  @Mutation
  public updateOptions(payload: any) {
    const { id, value } = payload;

    // we use vue.set to make the state object reactive
    Vue.set(this.options, id, value);
  }

  get getTitles(): any {
    return this.titles;
  }

  get getTypes(): any[] {
    return this.types;
  }
  
  get hasAggs(): boolean {
    return utils.objectIsNotEmpty(this.search.getResult.aggs);
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

  get getSorted() {
    const startOrder = [
      'archaeologicalResourceType',
      'derivedSubject',
      'keyword',
      'publisher',
      'contributor',
      'nativeSubject'
    ];

    const result = this.search.getResult;

    let sorted: any = {};

    if (this.hasAggs) {
      startOrder.forEach((key: string) => sorted[key] = result.aggs[key]);

      for (let key in result.aggs) {
        if (!sorted[key] && key !== 'geogrid' && key !== 'bbox' && key !== 'ghp' && key !== 'range') {
          sorted[key] = result.aggs[key];
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
      const params = this.search.getParams;

      if (params[key]) {
        return params[key].split('|').some((k: any) => {
          return String(k || '').toLowerCase() === String(bucketKey || '').toLowerCase();
        });
      }

      return false;
    }
  }

  get getOptionsById() {
    return (id: string) => {
      return this.options.hasOwnProperty(id) ? this.options[id] : null;
    }
  }
}