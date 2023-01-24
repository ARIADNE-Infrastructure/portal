/**
 * Synonyms
 */
import ISO6391 from 'iso-639-1';
import utils from './utils';

let lookup: any = null;

export default {

  /**
   * Languages are given as ISO6391-1. Present them as whole names on screen.
   * @param lang The language short name. Example, 'EN|en' will be translted to 'English'
   */
  getLanguage(lang: string) {
    if(lang) {
      if( (lang.trim() == 'und') || (lang.trim() == '')) {
        return 'N/A';
      }
      // Native language representation - ISO6391.getNativeName(lang)
      return utils.sentenceCase(ISO6391.getName(lang.trim().toLowerCase()));
    }
    return 'N/A';
  },

  /**
   * Get country short name
   * @param country The language short name. Example, 'EN|en' will be translted to 'English'
   */
  getCountryCode(country: string) {
    if (!lookup) {
      lookup = require('country-code-lookup');
    }
    return lookup.byCountry(country)?.iso3 ?? country;
  }
}
