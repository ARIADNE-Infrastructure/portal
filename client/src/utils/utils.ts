// @ts-ignore
import striptags from 'striptags';

export default {

  delay (ms: number) {
    return new Promise(resolve => setTimeout(resolve, ms));
  },

  getCopy (obj: any) {
    return JSON.parse(JSON.stringify(obj));
  },

  objectIsNotEmpty (obj: any): boolean {
    return obj && typeof obj === 'object' && Object.keys(obj).length > 0;
  },

  isMobile () {
    return innerWidth <= 767 || 'ontouchstart' in window;
  },

  blurMobile () {
    if (this.isMobile()) {
      let focused: any = document.activeElement;
      if (focused && typeof focused.blur === 'function') {
        focused.blur();
      }
    }
  },

  objectEquals (a: any, b: any) {
    let aArr = Object.getOwnPropertyNames(a),
        bArr = Object.getOwnPropertyNames(b);

    if (aArr.length !== bArr.length) {
      return false;
    }

    for (let i = 0; i < aArr.length; i++) {
      let prop = aArr[i];
      if (a[prop] !== b[prop] && prop !== '__ob__') {
        return false;
      }
    }
    return true;
  },

  paramsToString (base: string, params: any) {
    let strParams = ''
    if (params) {
      for (let key in params) {
        strParams += `${ encodeURIComponent(key) }=${ encodeURIComponent(params[key]) }&`;
      }
    }
    return base + '?' + strParams.slice(0, -1);
  },

  sentenceCase (str: string): string {
    let string = String(str);

    if (string) {
      string = string.toLowerCase();
      return string[0].toUpperCase() + string.slice(1);
    }

    return '';
  },

  validUrl (url: any) {
    return /^https?\:\/\/[^\<\>\"\'\`]+$/.test(url);
  },

  splitCase (str: string): string {
    return str.replace(/([a-z])([A-Z])/g, '$1 $2');
  },

  formatDate (date: string): string {
    return date.split('T')[0];
  },

  escReg (reg: string): string {
    return reg.replace(/[|\\{}()[\]^$+*?.]/g, '\\$&');
  },

  getMarked (text: string, word: string): string {
    if (text) {
      text = striptags(text.trim());
      word = word.trim();

      if (word) {
        if (word.includes('|')) {
          word = word.split('|').map((w: string) => this.escReg(w)).join('|');

        } else {
          word = this.escReg(word);
        }

        return text.replace(new RegExp(word, 'ig'), (m: string) => {
          return `<span class="bg-lightYellow">${ m }</span>`;
        });
      }
      return text;
    }
    return '';
  },

  cleanText (text: string, allowNewline: boolean): string {
    if (text) {
      text = text.trim();

      if (!allowNewline && !/\<br\s*\/?>/g.test(text)) {
        text = text.replace(/(\n|\r)/g, '');

      } else {
        text = text.replace(/\r/g, '');
      }

      text = text.replace(/\&nbsp;/g, '')
        .replace(/\<br\s*\/?>/g, '\n\n')
        .replace(/\n+\s*/g , '\n\n');

      return striptags(text);
    }
    return '';
  },

  isInvalid (val: any): boolean {
    if (val && typeof val === 'string') {
      let invalid = ['not provided', 'unknown'];
      return invalid.includes(val.toLowerCase());
    }
    return false;
  },

  sortListByValue (list: any[], key: string, order: string): any[] {
    if (order === 'asc') {
      return list.sort((a, b) => (a[key] > b[key]) ? 1 : -1);
    }

    return list.sort((a, b) => (a[key] < b[key]) ? 1 : -1);
  },
};
