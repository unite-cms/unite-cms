<template>
    <content-detail :loading="loading || $apollo.loading" @submit="submit" :can-go-back="view.category !== 'single_content'">
        <h1 class="uk-card-title">{{ $t('content.update.headline', { contentTitle, view }) }}</h1>
        <alerts-list :alerts="globalViolations" />

        <form-fields :view="view" :form-data="formData" :root-form-data="formData" :content-id="$route.params.id" @input="data => formData = data" />

        <button slot="footer" class="uk-button uk-button-primary" type="submit" @click="submitClick">{{ $t('content.update.actions.submit') }}</button>
    </content-detail>
</template>

<script>
    import gql from 'graphql-tag';

    import Alerts from "../../state/Alerts";
    import AlertsList from '../../components/Alerts';
    import Route from "../../state/Route";
    import ContentDetail from './_detail';
    import FormFields from "../../components/Form/_formFields";
    import Form from "../../state/Form";

    export default {
        components: {ContentDetail, AlertsList, FormFields},
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
            fieldGroups() {
                return this.view.formFieldGroups();
            },
            contentTitle() {
                return this.view.contentTitle(this.formData);
            },
            globalViolations() {
                return Alerts.violationsWithoutPrefix(this.view.formFields().map(field => field.id)).map((v) => {
                    return Object.assign({}, v, { message: v.path[0] + ': ' + v.message });
                });
            },
        },
        apollo: {
            formData: {
                fetchPolicy: 'network-only',
                query() {

                    if(this.view.category === 'single_content') {
                        return gql`query {
                            get${ this.view.type } {
                                  ${ this.view.queryFormData() }
                            }
                        }`;
                    } else {
                        return gql`query($id: ID!) {
                            get${ this.view.type }(id: $id) {
                                  ${ this.view.queryFormData() }
                            }
                        }`;
                    }
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
            submitClick(event) { Form.checkHTML5Valid(event) },
            submit() {
                Alerts.$emit('clear');
                this.loading = true;
                this.$apollo.mutate({
                    mutation: (this.view.category === 'single_content') ?
                        gql`mutation($persist: Boolean!, $data: ${ this.view.type }Input!) {
                            update${ this.view.type }(persist:$persist, data:$data) {
                                __typename
                            }
                        }` : gql`mutation($id: ID!, $persist: Boolean!, $data: ${ this.view.type }Input!) {
                            update${ this.view.type }(id: $id, persist:$persist, data:$data) {
                                __typename
                            }
                        }`
                    ,
                    variables: {
                        id: this.$route.params.id,
                        persist: true,
                        data: this.view.normalizeMutationData(this.formData),
                    }
                }).then((data) => {

                    if(this.view.category !== 'single_content') {
                        Route.back({updated: this.$route.params.id});
                    }

                    Alerts.$emit('push', 'success', this.$t('content.update.success', { contentTitle: this.contentTitle, view: this.view }));
                }).finally(() => { this.loading = false }).catch((e) => {
                    Alerts.apolloErrorHandler(e);
                    Alerts.$emit('push', 'danger', this.$t('content.update.errors', { view: this.view, contentTitle: this.contentTitle }));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        }
    }
</script>
