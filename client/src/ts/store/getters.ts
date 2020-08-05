import { GetterTree } from 'vuex';
import State from './state';
import utils from '../utils/utils';

export default <GetterTree<State, any>>{
  loading: state => (): boolean => {
    return state.loading;
  },

  params: state => (): any => {
    return utils.getCopy(state.params);
  },

  result: state => (): any => {
    return state.result;
  },

  autocomplete: state => (): any => {
    return state.autocomplete;
  },

  resource: state => (): any => {
    return state.resource;
  },

  fields: state => (): any[] => {
    return state.fields;
  },

  mainNavigation: state => (): any[] => {
    return state.mainNavigation;
  },

  sortOptions: state => (): any[] => {
    return state.sortOptions;
  },

  frontPageLinks: state => (): any[] => {
    return state.frontPageLinks;
  },

  assets: state => (): string => {
    return state.assetsDir;
  },

  wordCloud: state => (): any => {
    return state.wordCloud;
  },

  meta: state => (): any => {
    return state.meta;
  },

  services: state => (): any => {
    return state.services;
  }
};
