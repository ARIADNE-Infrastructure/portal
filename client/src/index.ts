// style
import './style.css';
import '@babel/polyfill';
import 'url-search-params-polyfill';

// main
import { createApp } from 'vue';
import router from './router';
import App from './App.vue';
import VueClickAway from 'vue3-click-away';

// app
createApp(App)
  .use(router)
  .use(VueClickAway)
  .mount('#app')
