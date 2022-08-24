// @ts-ignore
import striptags from 'striptags';
const debounceMap: any = {};

export default {

  debounce (key: string, callback: Function, timeout: number) {
    clearTimeout(debounceMap[key]);
    debounceMap[key] = setTimeout(callback, timeout);
  },

  delay (ms: number) {
    return new Promise(resolve => setTimeout(resolve, ms));
  },

  getCopy (obj: any) {
    return JSON.parse(JSON.stringify(obj));
  },

  objectIsNotEmpty (obj: any): boolean {
    return obj && typeof obj === 'object' && Object.keys(obj).length > 0;
  },

  objectIsEmpty (obj: any): boolean {
    return !this.objectIsNotEmpty(obj);
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

  objectEquals (aRaw: any, bRaw: any, ignoreParams?: string[]) {
    let a = { ...aRaw };
    let b = { ...bRaw };

    ignoreParams = ignoreParams ?? [];

    for (const ignore of ignoreParams) {
      delete a[ignore];
      delete b[ignore];
    }

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

  objectConvertNumbersToStrings (params: any) {
    let newObject: any = this.getCopy(params);

    if (params) {
      for (const [key, value] of Object.entries(params)) {
        const typedKey: any = key;
        const typedValue: any = value;

        newObject[typedKey] = typedValue === undefined ? '' : typedValue.toString();
      }
    }

    return newObject;
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

  ucFirst (str: string): string {
    return str[0].toUpperCase() + str.slice(1);
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
      text = text.trim();
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

  sortListByValue (list: any[], key: string | number, order: string): any[] {
    if (order === 'asc') {
      return [...list].sort((a, b) => (a[key] > b[key]) ? 1 : -1);
    }

    return [...list].sort((a, b) => (a[key] < b[key]) ? 1 : -1);
  },

  groupListByKey (list: any[], key: string | number): any[] {
    return list.reduce((hash, obj) => ({...hash, [obj[key]]:( hash[obj[key]] || [] ).concat(obj)}), {});
  },

  shuffle (arr: any[]) {
    for (let i = arr.length - 1; i > 0; i--) {
        let j = Math.floor(Math.random() * (i + 1));
        let tmp = arr[i];
        arr[i] = arr[j];
        arr[j] = tmp;
    }
    return arr;
  },

  getGradient(opacity: number) {
    let gradient: any = {};

    for (let i = 1; i <= 10; i++) {
      let key = Math.round(((opacity * i) / 10) * 100) / 100,
        val = Math.round((1.0 - i / 10) * 240);

      gradient[key] = `hsl(${val}, 90%, 50%)`;
    }

    return gradient;
  },

  tmpId: 1,
  getUniqueId(): number {
    return this.tmpId++
  },

  /**
   * Trim text to given max length
   *
   * @param text Text to trim
   * @param length Max length of string
   * @returns If text is short than length return text, else return substring text to length
   */
  trimString(text: string, maxLength: number): string {
    return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
  }
};
