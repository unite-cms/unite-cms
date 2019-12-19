<template>
  <content-detail :loading="loading || $apollo.loading" @submit="submit">
    <h1 class="uk-card-title">{{ $t('content.create.headline', { contentTitle, view }) }}</h1>
    <alerts-list :alerts="globalViolations" />
    <component :key="field.id" v-for="field in view.formFields()" :is="$unite.getFormFieldType(field)" :field="field" v-model="formData[field.id]" :violations="fieldViolations(field.id)" />
    <button slot="footer" class="uk-button uk-button-primary" type="submit">{{ $t('content.create.actions.submit') }}</button>
  </content-detail>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from "../../components/Icon";
    import ContentDetail from './_detail';
    import Alerts from "../../state/Alerts";
    import AlertsList from '../../components/Alerts';
    import Route from "../../state/Route";

    export default {
        components: {Icon, ContentDetail, AlertsList},
        data(){
            return {
                loading: false,
                formData: {},
            }
        },
        mounted() {
            this.formData = this.view.normalizeQueryData();
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
            },
        },
        methods: {
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
                        data: this.view.normalizeMutationData(Object.assign({}, this.formData)),
                    }
                }).then((data) => {
                    Route.back({updated: data.data[`create${this.view.type}`].id, offset: 0});
                    Alerts.$emit('push', 'success', this.$t('content.create.success', { view: this.view, contentTitle: this.contentTitle }));
                }).finally(() => { this.loading = false }).catch((e) => {
                    Alerts.apolloErrorHandler(e);
                    Alerts.$emit('push', 'danger', this.$t('content.create.errors', { view: this.view, contentTitle: this.contentTitle }));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            },
            fieldViolations(prefix) {
                return Alerts.violationsForPrefix(prefix);
            }
        }
    }
</script>
