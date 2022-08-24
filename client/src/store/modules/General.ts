// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action } from "vuex-class-modules";
import { frontPageLinks, mainNavigation, services, frontPageImagesTotal, frontPageImageTexts } from './General/static';
import { publishers } from './General/publishers';

export enum LoadingStatus { None, Locked, Background };

@Module
export class GeneralModule extends VuexModule {
  private meta: any = {};
  private loadingStatus: LoadingStatus = LoadingStatus.None;

  private assetsDir: any = process.env.ARIADNE_ASSET_PATH;
  private mainNavigation: any[] = mainNavigation;
  private services: any = services;
  private frontPageLinks: any[] = frontPageLinks;
  private frontPageImagesTotal: number = frontPageImagesTotal;
  private frontPageImageTexts: any = frontPageImageTexts;
  private publishers: any[] = publishers;
  private window: any = {};

  @Action
  setWindow() {
    this.updateWindow();
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
  updateWindow() {
    // needs to create a new object for reactivity
    this.window = { ...window };
  }

  @Mutation
  updateLoadingStatus(loadingStatus: LoadingStatus) {
    this.loadingStatus = loadingStatus;
  }

  @Mutation
  updateMeta(meta: any) {
    this.meta = meta;
  }

  get getIsLoading(): boolean {
    return this.loadingStatus !== LoadingStatus.None;
  }

  get getLoadingStatus(): LoadingStatus {
    return this.loadingStatus;
  }

  get getMainNavigation(): any[] {
    return this.mainNavigation;
  }

  get getFrontPageLinks(): any[] {
    return this.frontPageLinks;
  }

  get getFrontPageImageTotal(): number {
    return this.frontPageImagesTotal;
  }

  get getFrontPageImageTexts(): any {
    return this.frontPageImageTexts;
  }

  get getPublishers(): any[] {
    return this.publishers;
  }

  get findPublisher() {
    return (key: string) => {
      key = (key || '').toLowerCase().split(/[^a-z\s]/)[0];
      const publisher = this.publishers.find((p: any) => p.title.toLowerCase().startsWith(key));
      return publisher || null;
    }
  }

  get getAssetsDir(): string {
    return this.assetsDir;
  }

  get getMeta(): any {
    return this.meta;
  }

  get getServices(): any {
    return this.services;
  }

  get getWindow(): any {
    return this.window;
  }
}
