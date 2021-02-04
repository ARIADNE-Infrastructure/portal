// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import { General } from './General';

@Module
export class Subject extends VuexModule {

  constructor (general: General, options: RegisterOptions) {
    super(options);
    this.general = general;
  }

  private subject: any = null;
  private general: General;

  @Action
  async setSubject (id: string) {
    let data: any = null;

    this.general.updateLoading(true);

    try {
      const url = process.env.apiUrl;
      const res = await axios.get(`${ url }/getSubject/${ id }`);

      if (res?.data?.id) {
        data = res.data;
      }
    } catch (ex) {}

    this.updateSubject(data);

    this.general.updateLoading(false);
  }

  @Mutation
  public updateSubject(subject: any) {
    this.subject = subject;
  }

  get getSubject() {
    return this.subject;
  }
}
