
import Vue from "vue";
import VueI18n from 'vue-i18n'

Vue.use(VueI18n);

export const messages = {};
export const dateTimeFormats = {};
export const numberFormats = {};

export const i18n = new VueI18n({
    locale: 'en',
    messages,
    dateTimeFormats,
    numberFormats,
});

export default i18n;
