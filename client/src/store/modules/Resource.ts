// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import { LoadingStatus, GeneralModule } from './General';
import { fields, types, typesTemporary, thematicals, ctsCertified } from './Resource/static';
import utils from "@/utils/utils";
import router from '@/router';

@Module
export class ResourceModule extends VuexModule {
  private generalModule: GeneralModule;

  constructor(generalModule: GeneralModule, options: RegisterOptions) {
    super(options);
    this.generalModule = generalModule;
  }

  private resource: any = null;
  private fields: any[] = fields;
  private types: any = types;
  private typesTemporary: any = typesTemporary;
  private ctsCertified: string[] = ctsCertified;
  private thematicals: any = thematicals;
  private resourceParams: any = {
    thematical: ''
  };

  @Action
  navigateToResource(id: string) {
    router.push('/resource/'+id);
  }

  @Action
  setResourceParamsThematical(value: string) {
    this.updateResourceParamsThematical(value);
  }

  @Action
  async setResource(id: string) {
    let data: any = null;

    this.generalModule.updateLoadingStatus(LoadingStatus.Locked);

    try {
      const url = utils.paramsToString(`${ process.env.apiUrl }/getRecord/${ id }`, this.resourceParams);
      const res = await axios.get(url);

      if (res?.data?.id) {
        data = res.data;
      }
    } catch (ex) {}

    this.updateResource(data);

    this.generalModule.updateLoadingStatus(LoadingStatus.None);

  }

  @Mutation
  updateResourceParamsThematical(value: string) {
    this.resourceParams.thematical = value;
  }

  @Mutation
  public updateResource(resource: any) {
    this.resource = resource;
  }

  get getResource() {
    return this.resource;
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

  get getIconByTypeNameTemporary() {
    return (name: string) => {
      name = (name || '').replace(/\s/g, '');

      const defaultIcon = 'site';
      const list: any = this.typesTemporary;
      const resourceIcon = list.hasOwnProperty(name) ? list[name] : null;

      let icon = resourceIcon ?? defaultIcon;

      return `${ this.generalModule.getAssetsDir }/resource/${ icon }.png`;
    }
  }

  get getThematicals() {
    return this.thematicals;
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
      const text = resource?.title?.text;

      return text.trim() ?? 'No title';
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

  // images
  get getDigitalImages() {
    return (resource: any) => {
      let imgs: string[] = [];

      if (Array.isArray(resource?.digitalImage)) {
        resource.digitalImage.forEach((img: any) => {
          ['ariadneUri', 'providerUri'].forEach((prop: string) => {
            if (img && utils.validUrl(img[prop]) && !imgs.includes(img[prop])) {
              imgs.push(img[prop]);
            }
          });
        });
      }

      return imgs;
    }
  }

  get getFields() {
    return this.fields;
  }
}
