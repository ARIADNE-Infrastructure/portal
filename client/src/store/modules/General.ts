// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action } from "vuex-class-modules";
import axios from 'axios';
import { frontPageLinks, mainNavigation, services } from './General/static';

@Module
export class General extends VuexModule {
  private title: string = '';
  private meta: any = {};
  private wordCloud: any = null;
  private loading: boolean = false;
  private assetsDir: any = process.env.ARIADNE_ASSET_PATH;
  private mainNavigation: any[] = mainNavigation;
  private services: any = services;
  private frontPageLinks: any[] = frontPageLinks;

  @Action
  async setWordCloud() {
    if (this.wordCloud) {
      return;
    }

    const url = process.env.apiUrl;
    this.updateLoading(true);

    try {
      const res = await axios.get(`${ url }/cloud`);
      const words = res?.data;

      if (words && words.length) {
        const max = Math.max.apply(null, words.map((word: any) => word.doc_count));

        this.updateWordCloud(words.map((word: any) => {
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

    this.updateLoading(false);
  }

  @Action
  setMeta(meta: any) {
    let metaEl;

    this.updateMeta({
      title: (meta.title || this.meta.title) + ' - Ariadne portal',
      description: typeof meta.description === 'string' ? meta.description : (this.meta.description || '')
    });

    if (!metaEl) {
      metaEl = document.createElement('meta');
      metaEl.name = 'description';
      document.head.appendChild(metaEl);
    }

    document.title = this.meta.title;
    metaEl.content = this.meta.description;
  }

  @Mutation
  updateLoading(loading: boolean) {
    this.loading = loading;
  }

  @Mutation
  updateMeta(meta: any) {
    this.meta = meta;
  }

  @Mutation
  updateWordCloud(cloud: any) {
    this.wordCloud = cloud;
  }

  get getLoading(): boolean {
    return this.loading;
  }

  get getMainNavigation(): any[] {
    return this.mainNavigation;
  }

  get getFrontPageLinks(): any[] {
    return this.frontPageLinks;
  }

  get getAssetsDir(): string {
    return this.assetsDir;
  }

  get getWordCloud(): any {
    return this.wordCloud;
  }

  get getMeta(): any {
    return this.meta;
  }

  get getServices(): any {
    return this.services;
  }
}