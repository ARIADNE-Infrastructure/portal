// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import utils from '@/utils/utils';
import router from '@/router';
import { LoadingStatus, GeneralModule } from './General';
import { sortOptions, helpTexts } from './Search/static';

export interface helpText {
  id: string,
  title: string,
  text: string,
}

@Module
export class SearchModule extends VuexModule {
  private generalModule: GeneralModule;

  constructor(generalModule: GeneralModule, options: RegisterOptions) {
    super(options);
    this.generalModule = generalModule;
  }

  private params: any = {};
  private result: any = {};
  private autocomplete: any = {};
  private sortOptions: any[] = sortOptions;
  private helpTexts: helpText[] = helpTexts;

  // minimap
  private miniMapSearchParams: any = {};
  private miniMapSearchResult: any = {};

  @Action
  async setAutocomplete(payload: any) {
    let { type, q } = payload;
    let data: any = '';

    try {
      const url = process.env.apiUrl + '/autocomplete';
      const res = await axios.get(utils.paramsToString(url, { fields: type, q }));

      if (res?.data?.hits.length && Array.isArray(res.data.hits)) {
        data = res.data;
      }
    } catch (ex) {}

    this.updateAutocomplete({ data, type, q });
  }

  // sets mini map search
  @Action
  async setMiniMapSearch(payload?: any) {
    // update params
    this.updateMiniMapSearchParams(payload);

    // update result
    try {
      const url = process.env.apiUrl + '/search';
      const res = await axios.get(utils.paramsToString(url, {...this.miniMapSearchParams, size: 500}));
      this.updateMiniMapSearchResult(res?.data);
    }
    catch (ex) {}
  }

  @Action
  async setSearch(payload: any) {
    let currentPath = router.currentRoute.path;
    currentPath = currentPath.endsWith('/') ? currentPath.slice(0, -1) : currentPath;

    let path = payload?.path ?? currentPath;

    let params: any = utils.getCopy(this.params);
    let currentParams = utils.getCopy(params);
    let updateUrl = true;

    if (payload.clear) {
      params = {};
    }
    if (payload.fromRoute) {
      updateUrl = false;
      params = {};
      payload = {};
      let urlParams = new URLSearchParams(location.search);
      urlParams.forEach((val: any, key: string) => payload[key] = val );
    }

    for (let key in payload) {
      if (payload[key] && key !== 'clear' && key !== 'path') {
        params[key] = payload[key];

      } else {
        if (key === 'subjectUri' && params.subjectLabel) {
          delete params.subjectLabel;
        }
        delete params[key];
      }
    }

    if (params.page) {
      params.page = parseInt(params.page)
    }
    if (!params.q) {
      params.q = '';
    }
    params.q = params.q.toLowerCase();

    if (utils.objectEquals(params, currentParams) && path === currentPath && !payload?.forceReload) {
      return;
    }

    if (updateUrl) {
      // only update url if it's not identifcal to current
      const stringParams = utils.objectConvertNumbersToStrings(params);

      if (!utils.objectEquals(router.currentRoute.query, stringParams) || path !== currentPath) {
        router.push(utils.paramsToString(path, params));
      }
    }

    if (path === '/search') {
      // reset to default sorting if current option is disabled
      if (params.sort && this.getSortOptionDisabled(params.q, params.sort)) {
        params = { ...params, ...this.getDefaultSort(params.q) };
      }

      // set to default sorting if none is specified
      else if (!params.sort) {
        params = { ...params, ...this.getDefaultSort(params.q) };
      }
    }

    // default to locked loading status
    this.generalModule.updateLoadingStatus(payload?.loadingStatus ?? LoadingStatus.Locked);

    this.updateParams(params);

    const time = Date.now();

    try {
      const url = process.env.apiUrl + '/search';
      const res = await axios.get(utils.paramsToString(url, params));
      let data = res?.data;

      if (utils.objectIsNotEmpty(data)) {
        if (data.hits) {
          data.hits.forEach((hit: any) => {
            if (hit.data.aatSubjects?.length && hit.data.derivedSubject?.length) {
              hit.data.derivedSubject = [...hit.data.derivedSubject, ...hit.data.aatSubjects];
              hit.data.aatSubjects = [];
            }
          });
        }
        if (data.aggregations) {
          if (data.aggregations.temporal?.temporal) {
            data.aggregations.temporal = data.aggregations.temporal.temporal;
          }
          const extras = ['subjectUri', 'fields', 'range', 'bbox', 'ghp'];
          extras.forEach((extra: any) => {
            if (params[extra]) {
              data.aggregations[extra] = {
                buckets: [{ key: params[extra], doc_count: data.total?.value || 0 }]
              };
            }
          });
        }

        this.updateResult({
          total: data.total,
          hits: data.hits,
          aggs: data.aggregations,
          time: Math.round(((Date.now() - time) / 1000) * 100) / 100
        });

        this.generalModule.updateLoadingStatus(LoadingStatus.None);

        return;
      }
    } catch (ex) {}

    this.generalModule.updateLoadingStatus(LoadingStatus.None);

    this.updateResult({
      error: 'Internal error. Search failed..'
    });
  }

  @Mutation
  updateMiniMapSearchParams(params: any) {
    this.miniMapSearchParams = params;
  }

  @Mutation
  updateMiniMapSearchResult(resultMap: any) {
    this.miniMapSearchResult = resultMap;
  }

  @Mutation
  updateParams(params: any) {
    this.params = params;
  }

  @Mutation
  updateResult(result: any) {
    this.result = result;
  }

  @Mutation
  updateAutocomplete(res: any) {
    this.autocomplete[res.type + res.q] = res.data;
  }

  /**
   * Used for displaying what user has filtered and/or search on.
   * Not all uri params are valid for this.
   */
  get displayResultString(): string {

    const valid = ['q', 'nativeSubject', 'ariadneSubject', 'derivedSubject',  'contributor', 'publisher', 'temporal'];
    const params = utils.getCopy(this.params);
    let display:string = '';

    valid.forEach(function (value) {
      let val:string = params[value];
      if(val) {
        val = val.replace('|',', ');
        display += val.trim()+', ';
      }
    });

    return display.substring(0, display.length-2);

  }

  get getParams(): any {
    return this.params;
  }

  get getResult(): any {
    return this.result;
  }

  get getAutoComplete(): any {
    return this.autocomplete;
  }

  get getDefaultSort(): any {
    return (q: any) => {
      if (q) {
        return {
          sort: '_score',
          order: 'desc',
        }
      }

      return {
        sort: 'issued',
        order: 'desc',
      }
    }
  }

  get getSortOptions(): any[] {
    return this.sortOptions.map((item: any) => {
      const disabled = this.getSortOptionDisabled(this.params.q, item.val);
      return { ...{ disabled }, ...item };
    });
  }

  get getHelpTexts(): helpText[] {
    return this.helpTexts;
  }

  get getSortOptionDisabled() {
    return (q: any, option: string) => {
      if (option.includes('_score')) {
        if (!q) {
          return true;
        }

        return false;
      }

      return false;
    }
  }

  /**
   * Get params for mini map residing on result page filters.
   */
  get getMiniMapSearchParams(): any {
    return this.miniMapSearchParams;
  }

  /**
   * Get results for mini map residing on result page filters.
   */
  get getMiniMapSearchResult() {
    return this.miniMapSearchResult;
  }
}
