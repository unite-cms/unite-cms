
import Vue from "vue";
import VueI18n from 'vue-i18n'

Vue.use(VueI18n);

export const messages = {};
export const dateTimeFormats = {};

export const i18n = new VueI18n({
    locale: 'en',
    messages,
    dateTimeFormats
});

export default i18n;
