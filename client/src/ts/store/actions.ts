import axios from 'axios';
import { ActionTree } from 'vuex';
import State from './state';
import router from '../router';
import utils from '../utils/utils';

let metaEl: any;

export default <ActionTree<State, any>> {

  async setResource (context, id: string) {
    let data: any = null;

    context.commit('setLoading', true);

    try {
      const url = process.env.apiUrl;
      const res = await axios.get(`${ url }/getRecord/${ id }`);

      if (res?.data?.id) {
        data = res.data;
      }
    } catch (ex) {}

    context.commit('setResource', data);
    context.commit('setLoading', false);
  },

  async setAutocomplete (context, query: string) {
    let data: any = '';

    try {
      const url = process.env.apiUrl + '/autocomplete';
      const res = await axios.get(utils.paramsToString(url, { q: query }));

      if (res?.data?.length && Array.isArray(res.data)) {
        data = res.data;
      }
    } catch (ex) {}

    context.commit('setAutocomplete', { data, query });
  },

  async setSearch (context, payload: any) {
    let params: any = context.getters.params();
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
      if (payload[key] && key !== 'clear') {
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

    if (utils.objectEquals(params, currentParams) && location.pathname.includes('search')) {
      return;
    }

    context.commit('setLoading', true);
    context.commit('setParams', params);
    if (updateUrl) {
      router.push(utils.paramsToString('/search', params));
    }

    const time = Date.now();

    try {
      const url = process.env.apiUrl + '/search';
      const res = await axios.get(utils.paramsToString(url, params));
      let data = res?.data;

      if (utils.objectIsNotEmpty(data)) {
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

        context.commit('setResult', {
          total: data.total,
          hits: data.hits,
          aggs: data.aggregations,
          time: Math.round(((Date.now() - time) / 1000) * 100) / 100
        });
        context.commit('setLoading', false);
        return;
      }
    } catch (ex) {}

    context.commit('setLoading', false);
    context.commit('setResult', {
      error: 'Internal error. Search failed..'
    });
  },

  async setWordCloud (context) {
    if (context.getters.wordCloud()) {
      return;
    }

    const url = process.env.apiUrl;
    context.commit('setLoading', true);

    try {
      const res = await axios.get(`${ url }/cloud`);
      const words = res?.data;

      if (words && words.length) {
        const max = Math.max.apply(null, words.map((word: any) => word.doc_count));

        context.commit('setWordCloud', words.map((word: any) => {
          let count = Math.round((word.doc_count / max) * 100);
          if (count > 100) {
            count = 100;
          } else if (count < 10) {
            count = 10;
          }
          return [word.key, count];
        }));
      }
    } catch (ex) {}

    context.commit('setLoading', false);
  },

  setMeta (context, meta: any) {
    context.commit('setMeta', {
      title: (meta.title || context.getters.meta().title) + ' - Ariadne portal',
      description: typeof meta.description === 'string' ? meta.description : (context.getters.meta().description || '')
    });

    if (!metaEl) {
      metaEl = document.createElement('meta');
      metaEl.name = 'description';
      document.head.appendChild(metaEl);
    }

    document.title = context.getters.meta().title;
    metaEl.content = context.getters.meta().description;
  }
};
