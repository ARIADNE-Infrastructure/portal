// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import { LoadingStatus, GeneralModule } from './General';

@Module
export class ContactModule extends VuexModule {
  private captchaPublicKey = '6Ld9wPQZAAAAABdFgzuc2NDjhb9kz1pjHx8dkQAu';
  private generalModule: GeneralModule;
  private errorMsg = '';
  private successMsg = '';
  private mail = {
    name: '',
    email: '',
    subject: '',
    message: ''
  };

  constructor (generalModule: GeneralModule, options: RegisterOptions) {
    super(options);
    this.generalModule = generalModule;
  }

  @Action
  public async sendMail (captchaResponse: string) {
    this.generalModule.updateLoadingStatus(LoadingStatus.Locked);

    try {
      let data = new FormData();
      data.append('captcha', captchaResponse);

      for (let key in this.mail) {
        // @ts-ignore
        data.append(key, this.mail[key]);
      }

      const res = await axios.post(process.env.apiUrl + '/mail', data);

      if (res?.data?.ok) {
        const sendingEmail = this.mail.email;
        this.clearMail();
        this.clearMessages();
        this.setSuccessMsg('Your mail has been sent and a copy has been sent to '+sendingEmail+'. Thank you.');

      } else if (res?.data?.error) {
        this.setErrorMsg(res.data.error);

      } else {
        throw new Error();
      }

    } catch (ex) {
      this.setErrorMsg('Error: Could not send the mail. Please try again later.');
    }

    this.generalModule.updateLoadingStatus(LoadingStatus.None);
  }

  @Mutation
  public clearMail () {
    this.mail.name = '';
    this.mail.email = '';
    this.mail.subject = '';
    this.mail.message = '';
  }

  @Mutation
  public clearMessages () {
    this.errorMsg = '';
    this.successMsg = '';
  }

  @Mutation
  public setErrorMsg (msg: string) {
    this.errorMsg = msg;
  }

  @Mutation
  public setSuccessMsg (msg: string) {
    this.successMsg = msg;
  }

  @Mutation
  public setSubject (subject: string) {
    this.mail.subject = subject;
  }

  public get getMail () {
    return this.mail;
  }

  public get getErrorMsg () {
    return this.errorMsg;
  }

  public get getSuccessMsg () {
    return this.successMsg;
  }

  public get getCaptchaPublicKey () {
    return this.captchaPublicKey;
  }
}
