// store/modules/MyStoreModule.ts
import { frontPageLinks, mainNavigation, frontPageImagesTotal, frontPageImageTexts } from './General/static';
import axios from 'axios';

export enum LoadingStatus { None, Locked, Background };

export class GeneralModule {
  meta: any = {};
  loadingStatus: LoadingStatus = LoadingStatus.None;
  assetsDir: any = process.env.ARIADNE_ASSET_PATH;
  mainNavigation: any[] = mainNavigation;
  services: any[] = [];
  frontPageLinks: any[] = frontPageLinks;
  frontPageImagesTotal: number = frontPageImagesTotal;
  frontPageImageTexts: any = frontPageImageTexts;
  publishers: any[] = [];
  window: any = {};
  waiting: Function[] = [];
  loaded: boolean = false;
  formPw: string = '';

  setWindow() {
    this.updateWindow();
  }

  setMeta(meta: any) {
    let metaEl = document.head.querySelector('meta[name="description"]');

    this.updateMeta({
      title: (meta.title || this.meta.title) + ' - Archsearch',
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

  async setServicesAndPublishers() {
    try {
      const url = process.env.apiUrl + '/getAllServicesAndPublishers';
      const res: any = await axios.get(url);
      this.updateServicesAndPublishers(res?.data)
    } catch (ex) {}
  }

  callAfterLoadedServices(callback: Function) {
    if (this.loaded) {
      callback();
    } else {
      this.waiting.push(callback);
    }
  }

  updateServicesAndPublishers(data: any) {
    this.publishers = Array.isArray(data?.publishers) ? data.publishers.sort((a: any, b: any) => a.id - b.id) : [];
    this.services = Array.isArray(data?.services) ? data.services.sort((a: any, b: any) => a.id - b.id) : [];
    this.loaded = true;
    this.waiting.forEach((cb: Function) => cb());
    this.waiting = [];
  }

  updateWindow() {
    // needs to create a new object for reactivity
    this.window = { ...window };
  }

  updateLoadingStatus(loadingStatus: LoadingStatus) {
    this.loadingStatus = loadingStatus;
  }

  updateMeta(meta: any) {
    this.meta = meta;
  }

  updateFormPw(formPw: string) {
    this.formPw = formPw;
  }

  get getFormPw(): string {
    return this.formPw;
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
      key = (key || '').trim().toLowerCase()
      return this.publishers.find((p: any) => p.title.trim().toLowerCase() === key) || null;
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
