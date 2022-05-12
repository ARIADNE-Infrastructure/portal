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

<script setup lang="ts">
import { onMounted } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { contactModule } from "@/store/modules";

let isSending: boolean = $ref(false);
const captcha: any = $ref(null);

const headerClasses: string = 'text-hg mb-xs';
const fieldClasses: string = 'w-full p-sm border-base rounded-base';

const mail = $computed(() => contactModule.getMail);
const errorMsg: string = $computed(() => contactModule.getErrorMsg);
const successMsg: string = $computed(() => contactModule.getSuccessMsg);
const captchaPublicKey: string = $computed(() => contactModule.getCaptchaPublicKey);

onMounted(() => {
  contactModule.clearMessages();

  if ((<any>window).grecaptcha) {
    captcha.innerHTML = '';
    (<any>window).grecaptcha.render(captcha, {
      sitekey: captchaPublicKey
    });
  } else {
    const captchaScript: HTMLScriptElement = document.createElement('script');
    captchaScript.src = 'https://www.google.com/recaptcha/api.js';
    captchaScript.async = true;
    document.body.appendChild(captchaScript);
  }
});

const sendMail = async () => {
  if (isSending) {
    return;
  }

  let captchaResponse = (<any>window)?.grecaptcha?.getResponse();
  if (!captchaResponse) {
    return;
  }

  isSending = true;

  await contactModule.sendMail(captchaResponse);

  (<any>window).grecaptcha.reset(captcha, {
    sitekey: captchaPublicKey
  });

  isSending = false;
}
</script>
