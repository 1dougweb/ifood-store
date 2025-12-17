import { createI18n } from 'vue-i18n';
import en from '../locales/en';
import pt from '../locales/pt';

const i18n = createI18n({
    legacy: false,
    locale: 'pt',
    fallbackLocale: 'en',
    messages: {
        pt,
        en,
    },
});

export default i18n;

