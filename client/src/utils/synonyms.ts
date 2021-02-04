
/**
 * Synonyms
 */

export default {

  /**
   * Languages are sometimes given in short names.
   * We want to present them as whole names on screen.
   * @param lang The language short name. Example, 'EN' will be translted to 'English'
   */
  getLanguage(lang: string) {
    enum Language {
      EN   = "English",
      SV   = "Swedish",
      IT   = "Italian",
      ES   = "Spanish",
      CHA  = "Chalcatongo"
    }
    return Language[lang.toUpperCase() as keyof typeof Language];
  }

}