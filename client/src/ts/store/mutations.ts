import { MutationTree } from 'vuex';
import State from './state';

export default <MutationTree<State>> {

  setParams(state, params: any) {
    state.params = params;
  },

  setResult(state, result: any) {
    state.result = result;
  },

  setResource(state, resource: any) {
    state.resource = resource;
  },

  setLoading(state, loading: boolean) {
    state.loading = loading;
  },

  setMeta(state, meta: any) {
    state.meta = meta;
  },

  setWordCloud(state, cloud: any) {
    state.wordCloud = cloud;
  },

  setAutocomplete(state, res: any) {
    state.autocomplete[res.query] = res.data;
  },
};
