// store/modules/MyStoreModule.ts
import axios from 'axios';
import { LoadingStatus, GeneralModule } from './General';
import { fields, types, typesTemporary, thematicals, ctsCertified, validFromPaths } from './Resource/static';
import utils from "@/utils/utils";
import router from '@/router';

export class ResourceModule {
  generalModule: GeneralModule;
  resource: any = null;
  fields: any[] = fields;
  types: any = types;
  typesTemporary: any = typesTemporary;
  ctsCertified: string[] = ctsCertified;
  thematicals: any = thematicals;
  fromPath: any = validFromPaths[0];
  resourceParams: any = { thematical: '' };

  constructor(generalModule: GeneralModule) {
    this.generalModule = generalModule;
  }

  navigateToResource(id: string) {
    router.push('/resource/'+id);
  }

  setResourceParamsThematical(value: string) {
    this.updateResourceParamsThematical(value);
  }

  async setResource(id: string) {
    let data: any = null;

    this.generalModule.updateLoadingStatus(LoadingStatus.Locked);

    try {
      const url = utils.paramsToString(`${ process.env.apiUrl }/getRecord/${ id }`, this.resourceParams);
      const res = await axios.get(url);

      if (res?.data?.id) {
        data = res.data;
        for (let key in data) {
          if (Array.isArray(data[key])) {
            data[key] = data[key].filter(v => v);
          }
        }
        if (Array.isArray(data.ariadneSubject)) {
          data.ariadneSubject.forEach(s => {
            if (s.prefLabel === 'Date') {
              s.prefLabel = 'Dating';
            }
          });
        }
        if (data.resourceType === 'date') {
          data.resourceType = 'dating';
        }
      }
    } catch (ex) {}

    this.updateResource(data);
    this.generalModule.updateLoadingStatus(LoadingStatus.None);
  }

  async checkCors(url: string) {
    const res = await axios.get(url);

    return false;
  }

  updateResourceParamsThematical(value: string) {
    this.resourceParams.thematical = value;
  }

  updateResource(resource: any) {
    this.resource = resource;
  }

  maybeUpdateFromPath(payload: any) {
    if (!payload?.to?.startsWith('/resource')) {
      return;
    }
    const fromPath = validFromPaths.find(from => payload?.from?.startsWith(from.path));
    if (fromPath) {
      this.fromPath = { ...fromPath, path: payload.from };
    }
  }

  get getResource() {
    return this.resource;
  }

  get getFromPath(): string {
    return this.fromPath;
  }

  get getTypes() {
    return this.types;
  }

  get getTypeById() {
    return (id: number) => {
      return this.types.hasOwnProperty(id) ? this.types[id] : null;
    }
  }

  get getIconByTypeId() {
    return (id: number) => {
      const defaultIcon = 'site';
      const resourceIcon = this.getTypeById(id);

      let icon = resourceIcon ?? defaultIcon;

      return `${ this.generalModule.getAssetsDir }/resource/${ icon }.png`;
    }
  }

  get getIconByTypeName() {
    return (name: string) => {
      const defaultIcon = 'sitemonument';
      const list: any = this.typesTemporary;
      const resourceIcon = list.hasOwnProperty(name) ? list[name] : null;
      let icon = resourceIcon ?? defaultIcon;

      return `${ this.generalModule.getAssetsDir }/resource/${ icon }.png`;
    }
  }

  get getThematicals() {
    return this.thematicals;
  }

  get getCtsCertified(): string[] {
    return this.ctsCertified;
  }

  // cts certified
  get getIsCtsCertified() {
    return (resource: any) => {
      const publisherName: string = resource.publisher?.[0]?.name ?? '';
      return publisherName ? this.ctsCertified.includes(publisherName) : false;
    }
  }

  // params
  get getResourceParams() {
    return this.resourceParams;
  }

  // title
  get getMainTitle() {
    return (resource: any) => {
      if( resource?.title?.text ) {
        return resource.title.text.trim();
      }
      return 'No title';
    }
  }

  get getNativeTitle() {
    return (resource: any) => {
      const text = resource.titleOther?.find((item: any) => {
        return item.language === this.resource.language;
      });

      return text ? text.text : '';
    }
  }

  get getNonNativeTitles() {
    return (resource: any) => {
      const items = resource.titleOther?.filter((item: any) => {
        return item.language !== this.resource.language;
      });

      return items ? items : [];
    }
  }

  // description
  get getMainDescription() {
    return (resource: any) => {
      const text = resource?.description?.text;

      return text ? utils.cleanText(text, true) : 'Resource has no description';
    }
  }

  get getNativeDescription() {
    return (resource: any) => {
      const text = resource.descriptionOther?.find((item: any) => {
        return item.language === this.resource.language;
      });

      return text ? utils.cleanText(text.text, true) : '';
    }
  }

  get getNonNativeDescriptions() {
    return (resource: any) => {
      const items = resource.descriptionOther?.filter((item: any) => {
        return item.language !== this.resource.language;
      });

      return items ? items : [];
    }
  }

  // audio
  get getAudioUrls() {
    return (resource: any) => {
      let urls: any[] = [];
      if (Array.isArray(resource?.digitalMedia)) {
        resource.digitalMedia.forEach((it: any) => {
          if (it?.url && it.type === 'audio' && utils.validUrl(it.url) && !urls.some(u => u.url === it.url) && it.url.startsWith('https://')) {
            urls.push({ url: it.url, name: utils.urlName(it.url) });
          }
        });
      }
      return urls;
    }
  }

  // video
  get getVideoUrls() {
    return (resource: any) => {
      let urls: any[] = [];
      if (Array.isArray(resource?.digitalMedia)) {
        resource.digitalMedia.forEach((it: any) => {
          if (it?.url && it.type === 'video' && utils.validUrl(it.url) && !urls.some(u => u.url === it.url) && it.url.startsWith('https://')) {
            urls.push({ url: it.url, name: utils.urlName(it.url) });
          }
        });
      }
      return urls;
    }
  }

  // images
  get getDigitalImages() {
    return (resource: any, onlyPrimary?: boolean) => {
      const imgs: string[] = [];
      const addImg = (img: any, prop: string) => {
        if (img && utils.validUrl(img[prop]) && !imgs.includes(img[prop]) && (!onlyPrimary || img.primary)) {
          const media = Array.isArray(resource.digitalMedia) ? resource.digitalMedia.find((it: any) => it.url === img[prop]) : null;
          if (!media || (media && (!media.type || media.type === 'image'))) {
            imgs.push(img[prop]);
          }
        }
      };
      if (Array.isArray(resource?.digitalImage)) {
        resource.digitalImage.forEach((img: any) => ['ariadneUri', 'providerUri'].forEach(prop => addImg(img, prop)));
      }
      if (Array.isArray(resource?.digitalMedia)) {
        resource.digitalMedia.forEach((it: any) => it?.url && (!it.type || it.type === 'image') ? addImg(it, 'url') : null)
      }
      return imgs;
    }
  }

  // Iiif manifest urls
  get getIiifUrls() {
    return (resource: any) => {
      const urls: any[] = [];
      if (Array.isArray(resource?.digitalMedia)) {
        resource.digitalMedia.forEach((it: any) => {
          if (it?.url && it.type === 'iiif' && utils.validUrl(it.url) && !urls.some(u => u.url === it.url)) {
            urls.push({ url: it.url, name: it.url, download: (new URLSearchParams('?' + (it.url.split('?')[1] ?? ''))).get('manifests') });
          }
        });
      }
      return urls;
    }
  }

  // 3d data urls
  get get3dObjectUrls() {
    return (resource: any) => {
      const objectUrls: any[] = [];
      if (Array.isArray(resource?.digitalMedia)) {
        resource.digitalMedia.forEach((it: any) => {
          if (it?.url && (it.type === 'iframe' || it.type === 'periscope') && utils.validUrl(it.url) && !objectUrls.some(u => u.url === it.url) && it.url.startsWith('https://')) {
            objectUrls.push({ url: it.url, type: it.type, name: utils.urlName(it.url) });
          }
        });
      }
      return objectUrls;
    }
  }

  get getFields() {
    return this.fields;
  }
}
