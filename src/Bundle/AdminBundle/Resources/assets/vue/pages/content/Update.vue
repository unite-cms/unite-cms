<template>
  <content-detail :loading="loading || $apollo.loading" @submit="submit">
    <h1 class="uk-card-title">{{ $t('content.update.headline', { contentTitle, view }) }}</h1>
    <alerts-list :alerts="globalViolations" />
    <component :key="field.id" v-for="field in view.formFields()" :is="$unite.getFormFieldType(field.fieldType)" :content-id="$route.params.id" :field="field" v-model="formData[field.id]" :violations="fieldViolations(field.id)" />
    <button slot="footer" class="uk-button uk-button-primary" type="submit">{{ $t('content.update.actions.submit') }}</button>
  </content-detail>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from "../../components/Icon";
    import Alerts from "../../state/Alerts";
    import AlertsList from '../../components/Alerts';
    import Route from "../../state/Route";
    import ContentDetail from './_detail';

    export default {
        components: {Icon, ContentDetail, AlertsList},
        data(){
            return {
                loading: false,
                formData: {}
            }
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            },
            contentTitle() {
                return this.view.contentTitle(this.formData);
            },
            globalViolations() {
                return Alerts.violationsWithoutPrefix(this.view.formFields().map(field => field.id)).map((v) => {
                    return Object.assign({}, v, { message: v.path[0] + ': ' + v.message });
                });
            }
        },
        apollo: {
            formData: {
                fetchPolicy: 'network-only',
                query() {
                    return gql`query($id: ID!) {
                        get${ this.view.type }(id: $id) {
                            ${ this.view.queryFormData() }
                        }
                    }`;
                },
                variables() {
                    return {
                        id: this.$route.params.id,
                    };
                },
                update(data) {
                    return this.view.normalizeQueryData(data[`get${ this.view.type }`]);
                }
            }
        },
        methods: {
            submit() {
                Alerts.$emit('clear');
                this.loading = true;
                this.$apollo.mutate({
                    mutation: gql`mutation($id: ID!, $persist: Boolean!, $data: ${ this.view.type }Input!) {
                        update${ this.view.type }(id: $id, persist:$persist, data:$data) {
                            id
                        }
                    }`,
                    variables: {
                        id: this.$route.params.id,
                        persist: true,
                        data: this.view.normalizeMutationData(JSON.parse(JSON.stringify(this.formData))),
                    }
                }).then((data) => {
                    Route.back({ updated: this.$route.params.id });
                    Alerts.$emit('push', 'success', this.$t('content.update.success', { contentTitle: this.contentTitle, view: this.view }));
                }).finally(() => { this.loading = false }).catch((e) => {
                    Alerts.apolloErrorHandler(e);
                    Alerts.$emit('push', 'danger', this.$t('content.update.errors', { view: this.view, contentTitle: this.contentTitle }));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            },
            fieldViolations(prefix) {
                return Alerts.violationsForPrefix(prefix);
            }
        }
    }
</script>
