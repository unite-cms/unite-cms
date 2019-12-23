<template>
    <content-detail :loading="loading || $apollo.loading" :card="false">
        <h3>
            {{ $t('content.translate.headline', { contentTitle, view }) }}
            <span class="uk-label uk-label-muted">{{ locales[content.locale] }}</span>
        </h3>
        <div v-if="!content.locale" class="uk-alert-warning" uk-alert>{{ $t('content.translate.no_locale_warning') }}</div>
        <div v-else class="uk-overflow-auto">
            <table class="uk-table uk-table-small uk-table-divider uk-table-middle">
                <thead>
                <tr>
                    <th>{{ $t('content.translate.header.locale') }}</th>
                    <th v-for="field in fields">{{ field.name }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="uk-card uk-card-default uk-table-striped">
                <tr v-for="(label, locale) in locales" :key="locale" :class="{ updated: highlightRow === locale }">
                    <td class="uk-table-shrink uk-text-nowrap">
                        <span v-if="contentForLocale(locale)" class="uk-label uk-label-muted">
                            {{ label }}
                            <a v-if="locale !== content.locale" href="" @click.prevent="removeRow(locale)" style="padding-right: 15px;" class="uk-icon-link uk-text-danger" ><icon name="x" /></a>
                        </span>
                        <button v-else class="uk-button uk-button-primary uk-button-small" style="padding-right: 30px;" @click="localeToAdd = locale"><icon name="plus" class="fix-line-height" /> {{ label }}</button>
                    </td>
                    <td v-for="field in fields">
                        <component v-if="contentForLocale(locale)" :is="$unite.getListFieldType(field)" :row="contentForLocale(locale)" :field="field" />
                    </td>
                    <td class="uk-table-shrink">
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <modal v-if="localeToAdd" @hide="localeToAdd = null" :title="$t('content.translate.select_translation')">
            <component :is="$unite.getViewType(view)" :view="view" :embedded="true" :select="'SINGLE'" @select="onSelect" :filter="addTranslationFilter" :order-by="view.orderBy" :initial-create-data="initialCreateData" />
        </modal>

    </content-detail>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from "../../components/Icon";
    import Modal from "../../components/Modal";
    import ContentDetail from './_detail';
    import Alerts from "../../state/Alerts";

    export default {
        components: { Modal, Icon, ContentDetail },
        data() {
            return {
                loading: false,
                localeToAdd: null,
                highlightRow: null,
                locales: {
                    de: 'Deutsch',
                    en: 'Englisch',
                },
                content: {
                    translations: []
                }
            };
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            },
            contentTitle() {
                return this.view.contentTitle(this.content);
            },
            fields() {
                return this.view.listFields().filter(field => field.id !== 'locale');
            },
            missingTranslations() {
                return this.locales.filter((locale) => {
                    return this.contentForLocale(locale) !== null;
                });
            },
            initialCreateData() {
                return {
                    locale: this.localeToAdd
                }
            },
            addTranslationFilter() {
                return {
                    AND: [
                        {
                            field: "locale",
                            operator: 'EQ',
                            value: this.localeToAdd
                        },
                        {
                            field: "translate",
                            operator: 'IS',
                            value: null
                        }
                    ]
                }
            }
        },
        apollo: {
            content: {
                fetchPolicy: 'network-only',
                query() {
                    return gql`
                        ${ this.view.fragment }
                        query($id: ID!) {
                            get${ this.view.type }(id: $id) {
                                _meta {
                                    id
                                }
                                locale

                                ... ${ this.view.id }
                                translations(includeSelf: true) {
                                    ... on ${ this.view.type } {
                                        locale

                                        _meta {
                                            id
                                        }
                                    }
                                    ... ${ this.view.id }
                                }
                            }
                        }`;
                },
                update(data) {
                    return data[`get${ this.view.type }`]
                },
                variables() {
                    return {
                        id: this.$route.params.id,
                    };
                }
            }
        },
        methods: {
            contentForLocale(locale) {
                let found = this.content.translations.filter((t) => {
                    return t.locale === locale;
                });

                return found.length > 0 ? found[0] : null;
            },
            removeRow(locale) {
                let content = this.contentForLocale(locale);
                this.loading = true;
                this.$apollo.mutate({
                    mutation: gql`mutation($id: ID!) {
                        update${this.view.type}(
                            id: $id,
                            data: { _translate: null },
                            persist: true
                        ) {
                            ... on ${ this.view.type } {
                                locale
                            }
                        }
                    }`,
                    variables: {
                        id: content._meta.id,
                    }
                }).then((data) => {
                    let translations = this.content.translations;
                    translations = translations.filter(t => { return t._meta.id !== content._meta.id; });
                    this.$set(this.content, 'translations', translations);
                    this.highlightRow = locale;
                }).catch((e) => { Alerts.apolloErrorHandler(e);
                }).finally(() => { this.loading = false; });
            },
            onSelect(id) {
                this.loading = true;
                this.localeToAdd = false;

                this.$apollo.mutate({
                    mutation: gql`${ this.view.fragment } mutation($id: ID!, $translate: ID!) {
                        update${this.view.type}(
                            id: $id,
                            data: { _translate: $translate },
                            persist: true
                        ) {
                            ... on ${ this.view.type } {
                                locale

                                _meta {
                                    id
                                }
                            }
                            ... ${ this.view.id }
                        }
                    }`,
                    variables: {
                        id: id,
                        translate: this.$route.params.id
                    }
                }).then((data) => {
                    let translations = this.content.translations;
                    translations.push(data.data[`update${this.view.type}`]);
                    this.$set(this.content, 'translations', translations);
                    this.highlightRow = data.data[`update${this.view.type}`].locale;
                }).catch((e) => { Alerts.apolloErrorHandler(e);
                }).finally(() => { this.loading = false; });
            }
        }
    }
</script>
