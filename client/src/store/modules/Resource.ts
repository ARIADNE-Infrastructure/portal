// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import { General } from './General';
import { fields, types, typesTemporary, thematicals } from './Resource/static';
import utils from "@/utils/utils";

@Module
export class Resource extends VuexModule {
  private general: General;

  constructor(general: General, options: RegisterOptions) {
    super(options);
    this.general = general;
  }

  private resource: any = null;
  private fields: any[] = fields;
  private types: any = types;
  private typesTemporary: any = typesTemporary;
  private thematicals: any = thematicals;
  private resourceParams: any = {
    thematical: ''
  };

  @Action
  async setResource(id: string) {
    let data: any = null;

    this.general.updateLoading(true);

    try {
      const url = utils.paramsToString(`${ process.env.apiUrl }/getRecord/${ id }`, this.resourceParams);
      const res = await axios.get(url);

      if (res?.data?.id) {
        data = res.data;
      }
    } catch (ex) {}

    this.updateResource(data);

    this.general.updateLoading(false);
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

      return `${ this.general.getAssetsDir }/resource/${ icon }.png`;
    }
  }

  get getIconByTypeNameTemporary() {
    return (name: string) => {
      name = name.replace(/\s/g, '');

      const defaultIcon = 'site';
      const list: any = this.typesTemporary;
      const resourceIcon = list.hasOwnProperty(name) ? list[name] : null;

      let icon = resourceIcon ?? defaultIcon;

      return `${ this.general.getAssetsDir }/resource/${ icon }.png`;
    }
  }

  get getThematicals () {
    return this.thematicals;
  }
  get getResourceParams () {
    return this.resourceParams;
  }

  get getFields() {
    return this.fields;
  }
}
