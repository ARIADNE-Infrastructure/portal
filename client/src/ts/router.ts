import Vue from 'vue';
import Router from 'vue-router';
import FrontPage from './pages/FrontPage.vue';
import Result from './pages/Result/Result.vue';
import NotFound from './pages/NotFound.vue';
import About from './pages/About.vue';
import Services from './pages/Services.vue';
import Resource from './pages/Resource/Resource.vue';
import BrowseWhen from './pages/Browse/BrowseWhen.vue';
import BrowseWhere from './pages/Browse/BrowseWhere.vue';
import BrowseWhat from './pages/Browse/BrowseWhat.vue';
import Theme from './pages/Theme.vue';
import store from './store/store';

/**
 * Router - component url paths
 */
Vue.use(Router);

const router = new Router({
  mode: 'history',
  routes: [
    {
      path: '/',
      component: FrontPage,
      meta: {
        title: 'Welcome',
        description: 'ARIADNE brings together and integrates existing archaeological research data infrastructures so that researchers can use the various distributed datasets and new and powerful technologies as an integral component of the archaeological research methodology.',
      }
    },
    {
      path: '/search',
      component: Result
    },
    {
      path: '/resource/:id',
      props: true,
      component: Resource,
    },
    {
      path: '/page/:id',
      redirect: '/resource/:id',
    },
    {
      path: '/page/:id/json',
      redirect: '/resource/:id/json',
    },
    {
      path: '/about',
      component: About,
      meta: {
        title: 'About',
        description: 'About page'
      }
    },
    {
      path: '/services',
      component: Services,
      meta: {
        title: 'Services',
        description: 'Services page'
      }
    },
    {
      path: '/browse/when',
      component: BrowseWhen,
      meta: {
        title: 'Browse / When',
        description: 'Browse when'
      }
    },
    {
      path: '/browse/where',
      component: BrowseWhere,
      meta: {
        title: 'Browse / Where',
        description: 'Browse where'
      }
    },
    {
      path: '/browse/what',
      component: BrowseWhat,
      meta: {
        title: 'Browse / What',
        description: 'Browse what'
      }
    },
    {
      path: '/theme',
      component: Theme,
      meta: {
        title: 'Theme',
        description: 'Site theme',
      }
    },
    {
      path: '*',
      component: NotFound,
      meta: {
        title: '404',
        description: '404 the page was not found',
      }
    },
  ],

  scrollBehavior(to, from, pos) {
    if (from.path !== to.path) {
      return { x: 0, y: 0 };
    }
    return pos;
  },
});

router.beforeEach((to: any, from: any, next: any) => {
  if (to?.meta?.title) {
    store.dispatch('setMeta', {
      title: to.meta.title,
      description: to.meta.description,
    });
  }
  next();
});

export default router;
