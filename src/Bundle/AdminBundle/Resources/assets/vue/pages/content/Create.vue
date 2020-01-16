<template>
    <content-detail :loading="loading || $apollo.loading" @submit="submit">
        <h1 class="uk-card-title">{{ $t('content.create.headline', { contentTitle, view }) }}</h1>
        <alerts-list :alerts="globalViolations" />

        <form-fields :view="view" :form-data="formData" :root-form-data="formData" @input="data => formData = data" />

        <button slot="footer" class="uk-button uk-button-primary" type="submit" @click="submitClick">{{ $t('content.create.actions.submit') }}</button>
    </content-detail>
</template>

<script>
    import gql from 'graphql-tag';

    import ContentDetail from './_detail';
    import FormFields from "../../components/Form/_formFields";
    import Alerts from "../../state/Alerts";
    import AlertsList from '../../components/Alerts';
    import Route from "../../state/Route";
    import Form from "../../state/Form";

    export default {
        components: {ContentDetail, FormFields, AlertsList},
        data(){
            return {
                loading: false,
                formData: {},
            }
        },
        mounted() {
            this.formData = this.view.normalizeQueryData();
            Object.keys(this.$route.query).forEach((key) => {
                if(key.startsWith('initial_value_')) {
                    this.formData[key.substr('initial_value_'.length)] = JSON.parse(this.$route.query[key]);
                }
            });
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
        methods: {
            submitClick(event) { Form.checkHTML5Valid(event) },
            submit() {
                Alerts.$emit('clear');
                this.loading = true;
                this.$apollo.mutate({
                    mutation: gql`mutation($persist: Boolean!, $data: ${ this.view.type }Input!) {
                        create${ this.view.type }(persist:$persist, data:$data) {
                            id
                        }
                    }`,
                    variables: {
                        persist: true,
                        data: this.view.normalizeMutationData(this.formData),
                    }
                }).then((data) => {
                    let updatedId = this.$route.query.updated || data.data[`create${this.view.type}`].id;
                    Route.back({updated: updatedId, offset: 0});
                    Alerts.$emit('push', 'success', this.$t('content.create.success', { view: this.view, contentTitle: this.contentTitle }));
                }).finally(() => { this.loading = false }).catch((e) => {
                    Alerts.apolloErrorHandler(e);
                    Alerts.$emit('push', 'danger', this.$t('content.create.errors', { view: this.view, contentTitle: this.contentTitle }));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        }
    }
</script>
