// style
import './style.css';
import '@babel/polyfill';
import 'url-search-params-polyfill';

// main
import Vue from 'vue';
import router from './router';
import store from './store';
import App from './App.vue';

// vue img
import VueImg from 'v-img';
Vue.use(VueImg);

// app
new Vue({
  el: '#app',
  router,
  store,
  render: h => h(App),
});
