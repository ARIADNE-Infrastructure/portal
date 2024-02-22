import axios from 'axios';
import { LoadingStatus, GeneralModule } from './General';

export class ContactModule {
  captchaPublicKey = '6Ld9wPQZAAAAABdFgzuc2NDjhb9kz1pjHx8dkQAu';
  generalModule: GeneralModule;
  errorMsg = '';
  successMsg = '';
  mail = {
    name: '',
    email: '',
    subject: '',
    message: ''
  };

  constructor (generalModule: GeneralModule) {
    this.generalModule = generalModule;
  }

  async sendMail (captchaResponse: string) {
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

  clearMail () {
    this.mail.name = '';
    this.mail.email = '';
    this.mail.subject = '';
    this.mail.message = '';
  }

  clearMessages () {
    this.errorMsg = '';
    this.successMsg = '';
  }

  setErrorMsg (msg: string) {
    this.errorMsg = msg;
  }

  setSuccessMsg (msg: string) {
    this.successMsg = msg;
  }

  setSubject (subject: string) {
    this.mail.subject = subject;
  }

  get getMail () {
    return this.mail;
  }

  get getErrorMsg () {
    return this.errorMsg;
  }

  get getSuccessMsg () {
    return this.successMsg;
  }

  get getCaptchaPublicKey () {
    return this.captchaPublicKey;
  }
}
