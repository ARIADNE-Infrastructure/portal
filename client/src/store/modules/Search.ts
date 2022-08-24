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

  private params: any = {};
  private result: any = {};
  private autocomplete: any = {};
  private sortOptions: any[] = sortOptions;
  private helpTexts: helpText[] = helpTexts;
  private totalRecordsCount: any = '';

  // aggregations
  private aggsResult: any = {};
  private aggsLoading: boolean = false;

  // minimap
  private miniMapSearchResult: any = {};

  constructor(generalModule: GeneralModule, options: RegisterOptions) {
    super(options);
    this.generalModule = generalModule;
  }

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
  async setMiniMapSearch(mapParams: any) {
    let res;
    try {
      const url = process.env.apiUrl + '/getMiniMapData';
      res = await axios.get(utils.paramsToString(url, {...mapParams}));
    }
    catch (ex) {

    }
    this.updateMiniMapSearchResult(res?.data || {});
  }

  @Action
  async setSearch(payload: any) {

    let currentPath = router.currentRoute.value.path;
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
        if (key === 'derivedSubjectId' && params.derivedSubjectIdLabel) {
          delete params.derivedSubjectIdLabel;
        }
        delete params[key];
      }

      // makes sure previous periods filters are reseted if year range is selected
      // todo: switch to periodo regions & periods when available
      if (key === 'range') {
        delete params.publisher;
        delete params.ariadneSubject;
      }
      // reverse of above check
      else if (key === 'publisher' || key === 'ariadneSubject') {
        delete params.range;
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

      if (!utils.objectEquals(router.currentRoute.value.query, stringParams) || path !== currentPath) {
        router.push(utils.paramsToString(path, params));
      }
    }

    const time = Date.now();

    let data: any;
    // default to locked loading status
    this.generalModule.updateLoadingStatus(payload?.loadingStatus ?? LoadingStatus.Locked);
    this.updateParams(params);

    try {
      const url = process.env.apiUrl + '/search';
      const res = await axios.get(utils.paramsToString(url, { ...params, ...this.getDefaultSort() }));
      data = res?.data;
    } catch (ex) {}

    if (data?.hits) {
      data.hits.forEach((hit: any) => {
        if (hit.data.aatSubjects?.length && hit.data.derivedSubject?.length) {
          hit.data.derivedSubject = [...hit.data.derivedSubject, ...hit.data.aatSubjects];
          hit.data.aatSubjects = [];
        }
      });
    }
    if (data) {
      this.updateResult({
        total: data.total,
        hits: data.hits,
        time: Math.round(((Date.now() - time) / 1000) * 100) / 100,
        aggs: data.aggregations
      });
    } else {
      this.updateResult({ error: 'Internal error. Search failed..' });
      this.generalModule.updateLoadingStatus(LoadingStatus.None);
      return;
    }

    this.generalModule.updateLoadingStatus(LoadingStatus.None);

  }


  /**
   * Aggregations are fetched and queried separately backend
   * Aggs are used for filters on frontend
   */
   @Action
  async setAggregationSearch(routerQuery:any) {

    // Get timline aggregation only if route matches one of these
    const timelineRoutes = ['/search','/browse/when'];
    let getTimeline: boolean = timelineRoutes.find(element => element == router.currentRoute.value.path)?true:false;
    let params = {
      ...routerQuery,
      timeline: getTimeline
    };

    this.updateAggsLoading(true);

    try {
      const url = process.env.apiUrl + '/getSearchAggregationData';
      const res = await axios.get(utils.paramsToString(url, { ...params, ...this.getDefaultSort() } ));

      let data = res?.data;
      if (utils.objectIsNotEmpty(data?.aggregations)) {
        if (data.aggregations.temporal?.temporal) {
          data.aggregations.temporal = data.aggregations.temporal.temporal;
        }
        this.updateAggsResult({
          total: data.total,
          hits: data.hits,
          aggs: data.aggregations,
        });

      } else {
        this.updateAggsResult({});
      }
    } catch (ex) {
      this.updateAggsResult({ error: 'Internal error. Search failed..' });
    }

    this.updateAggsLoading(false);

  }

  @Action
  async setTotalRecordsCount() {
    const res = await axios.get(process.env.apiUrl + '/getTotalRecordsCount');
    this.updateTotalRecordsCount( res.data );
  }

  @Action
  actionResetResultState() {
    this.resetResultState();
  }

  @Mutation
  updateTotalRecordsCount(count: any) {
    this.totalRecordsCount = count;
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
  resetResultState() {
    this.result = {};
    this.aggsResult = {};
    this.params = {};
  }

  @Mutation
  updateAggsResult(result: any) {
    this.aggsResult = result;
  }

  @Mutation
  updateAggsLoading(loading: boolean) {
    this.aggsLoading = loading;
  }

  @Mutation
  updateAutocomplete(res: any) {
    this.autocomplete[res.type + res.q] = res.data;
  }

  get getTotalRecordsCount(): any {
    return new Intl.NumberFormat('en',{style: 'decimal'}).format(this.totalRecordsCount);
  }

  get getParams(): any {
    return this.params;
  }

  get getResult(): any {
    return this.result;
  }

  get getAggsResult(): any {
    return this.aggsResult;
  }

  get getIsAggsLoading(): boolean {
    return this.aggsLoading;
  }

  get getAutoComplete(): any {
    return this.autocomplete;
  }

  get getDefaultSort(): any {
    return () => {
      if (this.params.sort && this.params.order) {
        return {
          sort: this.params.sort,
          order: this.params.order,
        };
      }
      if (this.params.q) {
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
    return this.sortOptions;
  }

  get getHelpTexts(): helpText[] {
    return this.helpTexts;
  }

  /**
   * Get results for mini map.
   */
  get getMiniMapSearchResult() {
    return this.miniMapSearchResult;
  }

}
