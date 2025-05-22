// @ts-ignore
import striptags from 'striptags';
import ISO6391 from 'iso-639-1';

const debounceMap: any = {};
let lookup: any = null;

export default {

  debounce (key: string, callback: Function, timeout: number) {
    clearTimeout(debounceMap[key]);
    debounceMap[key] = setTimeout(callback, timeout);
  },

  delay (ms: number) {
    return new Promise(resolve => setTimeout(resolve, ms));
  },

  last (arr: any[]) {
    return arr[arr.length - 1];
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
    return str?.length ? (str[0].toUpperCase() + str.slice(1)) : '';
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

  getSorted (arr: any, prop: string) {
    if (Array.isArray(arr)) {
      if (!prop) {
        return arr.slice().sort((a: any, b: any) => a?.toLowerCase() < b?.toLowerCase() ? -1 : (a?.toLowerCase() > b?.toLowerCase() ? 1 : 0));
      }
      return arr.slice().sort((a: any, b: any) => a[prop]?.toLowerCase() < b[prop]?.toLowerCase() ? -1 : (a[prop]?.toLowerCase() > b[prop]?.toLowerCase() ? 1 : 0));
    }
    return null;
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

  addMerged (arr: Array<any>, item: any, prop: string) {
    const index = arr.findIndex(val => val[prop] === item[prop]);
    if (index > -1) {
      arr[index] = item;
    } else {
      arr.push(item);
    }
    return arr;
  },

  tmpId: 1,
  getUniqueId(): number {
    return this.tmpId++
  },

  // Trim text to given max length, If text is short than length return text, else return substring text to length
  trimString(text: string, maxLength: number): string {
    return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
  },

  // Escapes html
  escHtml (html: string) {
    const map: any = {
      '<': '&lt;',
      "'": '&#39;',
      '"': '&quot;',
      '>': '&gt;'
    }
    return String(html || '').replace(/[<'">]/g, char => map[char]);
  },

  // simple auto link text
  autolinkText (text: string) {
    return text.replace(/(?:https?):\/\/[a-z0-9_\.\:\-\+\/]*[a-z0-9\/]/gi, url => '<a class="text-blue hover:underline word-break" target="_blank" href="' + this.escHtml(url) + '">' + this.escHtml(url) + '</a>');
  },

  // Returns common used marker types
  getMarkerTypes (generalModule: any) {
    return {
      point: {
        marker: generalModule.getAssetsDir + '/leaflet/marker-icon-geopoint.png',
        shape: generalModule.getAssetsDir + '/leaflet/marker-icon-geoshape.png',
        current: generalModule.getAssetsDir + '/leaflet/marker-icon-current.png'
      },
      approx: {
        marker: generalModule.getAssetsDir + '/leaflet/marker-icon-geopoint-approx.png',
        shape: generalModule.getAssetsDir + '/leaflet/marker-icon-geoshape-approx.png'
      },
      shadow: {
        marker: generalModule.getAssetsDir + '/leaflet/marker-shadow.png'
      },
      combo: {
        marker: generalModule.getAssetsDir + '/leaflet/marker-icon-combo.png'
      }
    };
  },

  // returns current leaflet available tile layers
  getTileLayers (L: any, maxZoom: boolean, allOps: boolean) {
    const googleAttr = 'Map data &copy; ' + (new Date().getFullYear()) + ' Google';

    return {
      'OSM': L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', Object.assign({
        attribution: '&copy; <a href="https://osm.org/copyright" target="_blank">OpenStreetMap</a> contributors',
      }, maxZoom ? { maxZoom: 20 } : {}, allOps ? { maxNativeZoom: 19, noWrap: true } : {})),
      'Open topo.': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', Object.assign({
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org" target="_blank">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org" target="_blank">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/" target="_blank">CC-BY-SA</a>)',
      }, maxZoom ? { maxZoom: 20 } : {}, allOps ? { maxNativeZoom: 17 } : {})),
      'Google sat.': L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', Object.assign({
        attribution: googleAttr,
        subdomains:['mt0','mt1','mt2','mt3'],
      }, maxZoom ? { maxZoom: 20 } : {}, allOps ? { maxNativeZoom: 20 } : {})),
      'Google terr.': L.tileLayer('http://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}', Object.assign({
        attribution: googleAttr,
        subdomains:['mt0','mt1','mt2','mt3'],
      }, maxZoom ? { maxZoom: 20 } : {}, allOps ? { maxNativeZoom: 20 } : {})),
      'Google street': L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', Object.assign({
        attribution: googleAttr,
        subdomains:['mt0','mt1','mt2','mt3']
      }, maxZoom ? { maxZoom: 20 } : {}, allOps ? { maxNativeZoom: 20 } : {})),
      'Google hybr.': L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', Object.assign({
        attribution: googleAttr,
        subdomains:['mt0','mt1','mt2','mt3']
      }, maxZoom ? { maxZoom: 20 } : {}, allOps ? { maxNativeZoom: 20 } : {})),
    };
  },

  // Languages are given as ISO6391-1. Present them as whole names on screen.
  getLanguage(lang: string) {
    if (lang) {
      if ((lang.trim() == 'und') || (lang.trim() == '')) {
        return 'N/A';
      }
      // Native language representation - ISO6391.getNativeName(lang)
      return this.sentenceCase(ISO6391.getName(lang.trim().toLowerCase()));
    }
    return 'N/A';
  },

  // Get country short name
  getCountryCode(country: string) {
    if (!lookup) {
      lookup = require('country-code-lookup');
    }
    return lookup.byCountry(country)?.iso3 ?? country;
  }
};
