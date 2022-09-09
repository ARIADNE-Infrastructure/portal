import { createRouter, createWebHistory } from 'vue-router';
import { generalModule, resourceModule } from "@/store/modules";
import { breadCrumbModule } from "@/store/modules";

// components
import About from './component/About.vue';
import Browse from './component/Browse.vue';
import BrowseWhat from './component/BrowseWhat.vue';
import BrowseWhen from './component/BrowseWhen.vue';
import BrowseWhere from './component/BrowseWhere.vue';
import FrontPage from './component/FrontPage.vue';
import NotFound from './component/NotFound.vue';
import Resource from './component/Resource.vue';
import Subject from './component/Subject.vue';
import Publisher from './component/Publisher.vue';
import Result from './component/Result.vue';
import Services from './component/Services.vue';
import Theme from './component/Theme.vue';
import Contact from './component/Contact.vue';
import Infographic from './component/Infographic.vue';
import Guide from './component/Guide.vue';
import MaintenancePage from './component/MaintenancePage.vue';

/**
 * Router - component url paths
 */

const router = createRouter({
  history: createWebHistory(process.env.ARIADNE_PUBLIC_PATH),
  routes: [
    /*  Maintenance mode */
    // {
    //   path: '/:pathMatch(.*)*',
    //   component: MaintenancePage
    // },
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
      path: '/subject/:id',
      props: true,
      component: Subject,
    },
    {
      path: '/publisher/:id',
      props: true,
      component: Publisher,
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
      path: '/contact',
      component: Contact,
      meta: {
        title: 'Contact',
        description: 'Contact ARIADNE for questons, problems or resport data quality issues'
      }
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
      path: '/guide',
      component: Guide,
      meta: {
        title: 'Guide',
        description: 'Guide over the ARIADNE portal'
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
      path: '/browse',
      component: Browse,
      meta: {
        title: 'Browse',
        description: 'Browse'
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
      path: '/infographic',
      component: Infographic,
      meta: {
        title: 'Infographic',
        description: 'Infographic',
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
      path: '/:pathMatch(.*)*',
      component: NotFound,
      meta: {
        title: '404',
        description: '404 the page was not found',
      }
    },
  ],

  scrollBehavior(to: any, from: any, pos: any) {
    if (from.path !== to.path) {
      return { top: 0 };
    }
    return pos;
  },
});

router.beforeEach((to: any, from: any, next: any) => {
  if (to?.meta?.title) {
    generalModule.setMeta({
      title: to.meta.title,
      description: to.meta.description,
    });
  }

  // maybe update resource back link
  resourceModule.maybeUpdateFromPath({
    from: from?.path,
    to: to?.path,
  });

  // Let breadCrumbModule module know from where user is landing on page
  if (from?.path) {
    breadCrumbModule.setFrom(from.path);
  }

  next();
});

export default router;
