// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import utils from '@/utils/utils';
import router from '@/router';
import { LoadingStatus, GeneralModule } from './General';
import { sortOptions, perPageOptions, helpTexts } from './Search/static';
import { aggregationModule } from "../modules";

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
  private perPageOptions: any[] = perPageOptions;
  private helpTexts: helpText[] = helpTexts;
  private totalRecordsCount: any = '';
  private reqMap: any = { hits: 0, aggs: 0, map: 0 };
  private reqWaiting: any = {};
  private filterUpdate: any = null;
  private aggsResult: any = {};
  private aggsLoading: boolean = false;
  private timelineLoading: boolean = false;
  private mapLoading: boolean = false;
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
    const reqId = ++this.reqMap.map;
    this.updateMapLoading(true);
    let res;
    try {
      if (mapParams.ariadneSubject?.includes('Dating')) {
        mapParams = { ...mapParams, ariadneSubject: mapParams.ariadneSubject.replace('Dating', 'Date') };
      }
      const url = process.env.apiUrl + '/getMiniMapData';
      res = await axios.get(utils.paramsToString(url, mapParams));
    }
    catch (ex) {}
    if (reqId === this.reqMap.map) {
      this.updateMiniMapSearchResult(res?.data || {});
      this.updateMapLoading(false);
    }
  }

  @Action
  async setSearch(payload: any) {
    let currentPath = router.currentRoute.value.path;
    currentPath = currentPath.endsWith('/') ? currentPath.slice(0, -1) : currentPath;

    let path = payload.path ?? currentPath;

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
      if (payload[key] && key !== 'clear' && key !== 'path' && key !== 'replaceRoute') {
        params[key] = payload[key];
      } else {
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

    if (utils.objectEquals(params, currentParams) && path === currentPath && !payload.forceReload) {
      return;
    }

    let filterUpdate: any = null;
    if ((params.q || null) !== (currentParams.q || null)) {
      filterUpdate = { q: '' };
    } else {
      for (let key in aggregationModule.getDescriptions) {
        if ((params[key] || null) !== (currentParams[key] || null)) {
          if (!filterUpdate) {
            filterUpdate = {};
          }
          filterUpdate[key] = params[key];
        }
      }
    }
    if (filterUpdate || this.filterUpdate) {
      this.updateFilterUpdate(filterUpdate);
    }

    if (updateUrl) {
      // only update url if it's not identifcal to current
      const stringParams = utils.objectConvertNumbersToStrings(params);

      if (!utils.objectEquals(router.currentRoute.value.query, stringParams) || path !== currentPath) {
        if (currentPath === '/browse/where' && !payload.path) {
          router.replace(utils.paramsToString(path, params));
        } else {
          router.push(utils.paramsToString(path, params));
        }
      }
    }

    const time = Date.now();

    let data: any;
    const reqId = ++this.reqMap.hits;
    this.generalModule.updateLoadingStatus(payload.loadingStatus ?? LoadingStatus.Locked);
    this.updateParams(params);

    try {
      const sendParams = { ...params, ...this.getDefaultSort() }
      if (sendParams.ariadneSubject?.includes('Dating')) {
        sendParams.ariadneSubject = sendParams.ariadneSubject.replace('Dating', 'Date')
      }
      const url = process.env.apiUrl + '/search';
      const res = await axios.get(utils.paramsToString(url, sendParams));
      data = res?.data;
    } catch (ex) {}

    if (reqId !== this.reqMap.hits) {
      return;
    }

    if (data?.hits) {
      data.hits.forEach((hit: any) => {
        if (hit.data.aatSubjects?.length && hit.data.derivedSubject?.length) {
          hit.data.derivedSubject = [...hit.data.derivedSubject, ...hit.data.aatSubjects];
          hit.data.aatSubjects = [];
        }
        for (let key in hit.data) {
          if (Array.isArray(hit.data[key])) {
            hit.data[key] = hit.data[key].filter(v => v);
          }
        }
        if (hit.data.resourceType?.toLowerCase() === 'date') {
          hit.data.resourceType = 'dating';
        }
        if (Array.isArray(hit.data.ariadneSubject)) {
          hit.data.ariadneSubject.forEach(s => {
            if (s.prefLabel === 'Date') {
              s.prefLabel = 'Dating';
            }
          });
        }
      });
    }

    if (data && !data.error) {
      this.updateResult({
        total: data.total,
        hits: data.hits,
        time: Math.round(((Date.now() - time) / 1000) * 100) / 100,
        aggs: data.aggregations
      });
      if (currentPath === '/browse/where') {
        this.updateAggsResult({
          total: data.total,
          hits: data.hits,
          aggs: data.aggregations,
        });
      }
    } else if (data.error && data.error.msg == 'Scrolling exceeded maximum') {
      this.updateResult({ error: 'Scrolling exceeded maximum. Please use filters and/or search to narrow down your search.' });
    } else {
      this.updateResult({ error: 'Internal error. Search failed..' });
    }

    this.generalModule.updateLoadingStatus(LoadingStatus.None);
  }


  /**
   * Aggregations are fetched and queried separately backend
   * Aggs are used for filters on frontend
   */
   @Action
  setAggregationSearch(routerQuery: any) {
    const currentPath = router.currentRoute.value.path;
    let sendParams = { ...routerQuery };

    const reqId = ++this.reqMap.aggs;
    this.updateAggsLoading(true);

    try {
      const url = process.env.apiUrl + '/getSearchAggregationData';
      sendParams = { ...sendParams, ...this.getDefaultSort() }
      if (currentPath === '/browse/when') {
        sendParams.timeline = 'true';
      }
      if (sendParams.ariadneSubject?.includes('Dating')) {
        sendParams.ariadneSubject = sendParams.ariadneSubject.replace('Dating', 'Date')
      }
      axios.get(utils.paramsToString(url, sendParams)).then(res => {
        if (reqId === this.reqMap.aggs) {
          let data = res?.data;
          if (utils.objectIsNotEmpty(data?.aggregations)) {
            if (data.aggregations.temporal?.temporal) {
              data.aggregations.temporal = data.aggregations.temporal.temporal;
            }
            if (Array.isArray(data?.aggregations?.ariadneSubject?.buckets)) {
              data?.aggregations.ariadneSubject.buckets.forEach(b => {
                if (b?.key === 'Date') {
                  b.key = 'Dating';
                }
              });
            }
            if (currentPath === '/search') {
              if (this.timelineLoading) {
                data.aggregations.range_buckets = {};
              } else {
                data.aggregations.range_buckets = this.reqWaiting[reqId];
                this.clearReqWaiting();
              }
            }
            this.updateAggsResult({
              total: data.total,
              hits: data.hits,
              aggs: data.aggregations,
            });
          } else {
            this.updateAggsResult({});
          }
          this.updateAggsLoading(false);
        }
      }).catch(ex => {
        if (reqId === this.reqMap.aggs) {
          this.updateAggsResult({ error: 'Internal error. Search failed..' });
          this.updateAggsLoading(false);
        }
      });
    } catch (ex) {
      if (reqId === this.reqMap.aggs) {
        this.updateAggsResult({ error: 'Internal error. Search failed..' });
        this.updateAggsLoading(false);
      }
    }

    if (currentPath === '/search') {
      this.setTimelineSearch({ sendParams, reqId });
    }
  }

  // this is sometimes very slow - so do separately or search page
  @Action
  async setTimelineSearch(payload: any) {
    const params = payload.sendParams;
    const reqId = payload.reqId;

    this.updateTimelineLoading(true);

    try {
      const url = process.env.apiUrl + '/getSearchAggregationData';
      const res = await axios.get(utils.paramsToString(url, { ...params, ...{ timeline: 'true', onlyTimeline: 'true' }}));

      if (reqId === this.reqMap.aggs && res?.data?.aggregations?.range_buckets) {
        if (this.aggsLoading) {
          this.reqWaiting[reqId] = res.data.aggregations.range_buckets;
        } else {
          this.aggsResult.aggs.range_buckets = res.data.aggregations.range_buckets;
          this.clearReqWaiting();
          this.updateAggsResult({
            total: this.aggsResult.total,
            hits: this.aggsResult.hits,
            aggs: this.aggsResult.aggs,
          });
        }
      }
    } catch (ex) {}

    if (reqId === this.reqMap.aggs) {
      this.updateTimelineLoading(false);
    }
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
  clearReqWaiting() {
    this.reqWaiting = {};
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
    if (this.filterUpdate) {
      for (let key in this.filterUpdate) {
        aggregationModule.clearFilterOptions(key);
      }
      this.filterUpdate = null;
    }
  }

  @Mutation
  updateAggsLoading(loading: boolean) {
    this.aggsLoading = loading;
  }

  @Mutation
  updateFilterUpdate(payload: any) {
    this.filterUpdate = payload;
  }

  @Mutation
  updateTimelineLoading(loading: boolean) {
    this.timelineLoading = loading;
  }

  @Mutation
  updateMapLoading(loading: boolean) {
    this.mapLoading = loading;
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

  get getIsTimelineLoading(): boolean {
    return this.timelineLoading;
  }

  get getIsMapLoading(): boolean {
    return this.mapLoading;
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

  get getPerPageOptions(): any[] {
    return this.perPageOptions;
  }

  get getPerPage(): string {
    return this.params?.size || '20';
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
