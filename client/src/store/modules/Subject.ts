// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import { LoadingStatus, GeneralModule } from './General';

@Module
export class SubjectModule extends VuexModule {

  constructor (generalModule: GeneralModule, options: RegisterOptions) {
    super(options);
    this.generalModule = generalModule;
  }

  private subject: any = null;
  private generalModule: GeneralModule;

  @Action
  async setSubject (id: string) {
    let data: any = null;

    this.generalModule.updateLoadingStatus(LoadingStatus.Locked);

    try {
      const url = process.env.apiUrl;
      const res = await axios.get(`${ url }/getSubject/${ id }`);

      if (res?.data?.id) {
        data = res.data;
      }
    } catch (ex) {}

    this.updateSubject(data);

    this.generalModule.updateLoadingStatus(LoadingStatus.None);
  }

  @Mutation
  public updateSubject(subject: any) {
    this.subject = subject;
  }

  get getSubject() {
    return this.subject;
  }
}
