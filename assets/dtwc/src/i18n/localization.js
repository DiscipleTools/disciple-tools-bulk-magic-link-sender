import {configureLocalization} from '@lit/localize';
import {sourceLocale, targetLocales} from './locale-codes.js';

export const {getLocale, setLocale} = configureLocalization({
  sourceLocale,
  targetLocales,
  loadLocale: (locale) => import(`./generated/${locale}.js`),
});
