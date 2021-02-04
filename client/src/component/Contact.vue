<!-- Front page -->
<template>
	<div class="pt-3x px-base max-w-screen-md mx-auto text-mmd">
    <h1 class="text-2xl mb-md">Contact</h1>

    <form v-show="!successMsg" v-on:submit.prevent="sendMail">
      <p :class="headerClasses">
        Name
        <span class="text-red text-lg">*</span>
      </p>
      <p class="mb-md">
        <input type="text"
          required
          :class="fieldClasses"
          placeholder="Name.."
          v-model="mail.name">
      </p>

      <p :class="headerClasses">
        Email
        <span class="text-red text-lg">*</span>
      </p>
      <p class="mb-md">
        <input type="email"
          required
          :class="fieldClasses"
          placeholder="Email.."
          v-model="mail.email">
      </p>

      <p :class="headerClasses">
        Subject
      </p>
      <p class="mb-md">
        <input type="text"
          :class="fieldClasses"
          placeholder="Subject.."
          v-model="mail.subject">
      </p>

      <p :class="headerClasses">
        Message
        <span class="text-red text-lg">*</span>
      </p>
      <p class="mb-md">
        <textarea placeholder="Message.."
          required
          :class="fieldClasses"
          rows="5"
          v-on:keypress.enter.shift.prevent="sendMail"
          v-model="mail.message"></textarea>
      </p>

      <div class="mb-lg" ref="captcha">
        <div class="g-recaptcha" :data-sitekey="captchaPublicKey"></div>
      </div>
    
      <p v-if="errorMsg" class="p-sm mb-lg bg-red-80 text-white">
        {{ errorMsg }}
      </p>

      <p>
        <button type="submit"
          class="py-md px-2x bg-black text-white border-base border-black cursor-pointer transition-all duration-300 hover:bg-white hover:text-black">
          Send <i class="fas fa-paper-plane ml-sm"></i>
        </button>
      </p>
    </form>
    <p v-if="successMsg" class="p-sm mb-lg bg-blue-80 text-white">
      {{ successMsg }}
    </p>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { contact } from "@/store/modules";

@Component
export default class About extends Vue {
  isSending = false;

  mounted () {
    contact.clearMessages();

    if ((<any>window).grecaptcha) {
      (this.$refs.captcha as any).innerHTML = '';
      (<any>window).grecaptcha.render(this.$refs.captcha, {
        sitekey: this.captchaPublicKey
      });
    } else {
      let captchaScript: any = document.createElement('script');
      captchaScript.src = 'https://www.google.com/recaptcha/api.js';
      captchaScript.async = 'true';
      document.body.appendChild(captchaScript);
    }
  }

  get mail (): any {
    return contact.getMail;
  }
  get errorMsg (): string {
    return contact.getErrorMsg;
  }
  get successMsg (): string {
    return contact.getSuccessMsg;
  }
  get captchaPublicKey (): string {
    return contact.getCaptchaPublicKey;
  }
  get headerClasses (): string {
    return 'text-hg mb-xs';
  }
  get fieldClasses (): string {
    return 'w-full p-sm border-base rounded-base';
  }

  async sendMail () {
    if (this.isSending) {
      return;
    }

    let captchaResponse = (<any>window)?.grecaptcha?.getResponse();
    if (!captchaResponse) {
      return;
    }

    this.isSending = true;

    await contact.sendMail(captchaResponse);

    (<any>window).grecaptcha.reset(this.$refs.captcha, {
      sitekey: this.captchaPublicKey
    });

    this.isSending = false;
  }
}
</script>
