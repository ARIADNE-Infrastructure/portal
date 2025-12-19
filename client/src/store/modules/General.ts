// store/modules/MyStoreModule.ts
import { frontPageLinks, mainNavigation, frontPageImagesTotal, frontPageImageTexts } from './General/static';
import axios from 'axios';
import utils from '@/utils/utils';

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
  noFormats: any = {};
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

  async setServicesAndPublishers() {
    try {
      const res: any = await axios.get(process.env.apiUrl + '/getAllServicesAndPublishers');
      this.updateServicesAndPublishers(res?.data);
      const noFormatPublishers = this.publishers.filter(p => p.noFormat).map(p => p.title);
      if (noFormatPublishers.length) {
        const res2: any = await axios.get(process.env.apiUrl + utils.paramsToString('/getAllNoFormats', { publishers: noFormatPublishers.join('|') }));
        this.updateNoFormats(res2?.data);
      }
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
    this.publishers = Array.isArray(data?.publishers) ? data.publishers.sort((a: any, b: any) => a.id - b.id).map((p: any) => ({ ...p, noFormat: p.noFormat || p.title.includes('xxNoFormatxx'), title: p.title.replaceAll('xxNoFormatxx', '') })) : [];
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

  updateNoFormats(payload: any) {
    const noFormats = {}
    payload?.forEach(s => noFormats[s] = true);
    this.noFormats = noFormats;
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

  get getNoFormat() {
    return (publishers: any) => (Array.isArray(publishers) ? publishers : [publishers]).some(p => this.findPublisher(p?.name)?.noFormat);
  }

  get isNoFormat() {
    return (title: string) => this.noFormats[title?.toLowerCase()];
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
