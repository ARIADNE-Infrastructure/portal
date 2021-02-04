// store/modules/MyStoreModule.ts
import { Vue } from 'vue-property-decorator';
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import utils from '@/utils/utils';
import router from '@/router';
import { General } from './General';
import { sortOptions } from './Search/static';

@Module
export class Search extends VuexModule {
  private general: General;

  constructor(general: General, options: RegisterOptions) {
    super(options);
    this.general = general;
  }

  private result: any = {};
  private autocomplete: any = {};
  private params: any = {};
  private sortOptions: any[] = sortOptions;

  @Action
  async setAutocomplete(query: string) {
    let data: any = '';

    try {
      const url = process.env.apiUrl + '/autocomplete';
      const res = await axios.get(utils.paramsToString(url, { q: query }));

      if (res?.data?.length && Array.isArray(res.data)) {
        data = res.data;
      }
    } catch (ex) {}

    this.updateAutocomplete({ data, query });
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


    if (utils.objectEquals(params, currentParams) && path === currentPath) {
      return;
    }

    this.general.updateLoading(true);
    this.updateParams(params);

    if (updateUrl) {
      router.push(utils.paramsToString(path, params));
    }

    if (!params.q && !params.sort && !params.order) {
      params = utils.getCopy(params);
      params.sort = 'issued';
      params.order = 'desc';
    }

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

        this.general.updateLoading(false);

        return;
      }
    } catch (ex) {}

    this.general.updateLoading(false);

    this.updateResult({
      error: 'Internal error. Search failed..'
    });
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
    this.autocomplete[res.query] = res.data;
  }

  get hasParams(): boolean {
    const params = utils.getCopy(this.params);
    const clear = ['mapq', 'bbox', 'size', 'ghp', 'zoomLevel', 'mapCenter'];

    clear.forEach((item: any) => delete params[item]);

    return JSON.stringify(params) !== JSON.stringify({ q: ''});
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

  get getSortOptions(): any[] {
    return this.sortOptions;
  }
}
