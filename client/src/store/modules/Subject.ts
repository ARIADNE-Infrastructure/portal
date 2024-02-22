import axios from 'axios';
import { LoadingStatus, GeneralModule } from './General';

export class SubjectModule {
  subject: any = null;
  generalModule: GeneralModule;

  constructor (generalModule: GeneralModule) {
    this.generalModule = generalModule;
  }

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

  updateSubject(subject: any) {
    this.subject = subject;
  }

  get getSubject() {
    return this.subject;
  }
}
