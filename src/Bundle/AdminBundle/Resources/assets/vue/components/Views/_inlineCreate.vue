<template>
    <form ref="form" class="uk-form-horizontal uk-margin-bottom" :class="formDataIsEmpty ? 'uk-placeholder uk-padding-small' : 'uk-card uk-card-default uk-card-body'" @submit.prevent="submit">

        <alerts-list :alerts="inlineCreateAlerts" class="uk-margin" />

        <div>
            <h4>{{ $t('content.create.headline', { contentTitle, view }) }}</h4>
            <component :key="field.id" v-for="field in fields" :is="$unite.getFormFieldType(field)" :field="field" v-model="formData[field.id]" :violations="fieldViolations(field.id)" />
        </div>

        <div class="uk-text-right" v-if="!formDataIsEmpty">
            <button class="uk-button uk-button-primary" type="submit">{{ $t('content.list.actions.create') }}</button>
        </div>

        <div class="uk-overlay-default uk-position-cover" v-if="loading || $apollo.loading">
            <div uk-spinner class="uk-position-center"></div>
        </div>
    </form>
</template>

<script>

    import gql from 'graphql-tag';
    import Alerts from "../../state/Alerts";
    import AlertsList from '../../components/Alerts';
    import Route from "../../state/Route";

    export default {
        components: { AlertsList },
        data() {
            return {
                loading: false,
                formData: Object.assign({}, this.initialData),
            }
        },
        props: {
            view: Object,
            initialData: {
                type: Object,
                default() { return {} }
            }
        },
        computed: {
            fields() {
                return this.view.fields.filter(field => field.inline_create);
            },
            globalViolations() {
                return Alerts.violationsWithoutPrefix(this.view.formFields().map(field => field.id)).map((v) => {
                    return Object.assign({}, v, { message: v.path[0] + ': ' + v.message });
                });
            },

            inlineCreateAlerts() {
                return Alerts.alerts.filter((alert) => {
                    return alert.category === 'inlineCreate';
                }).concat(this.globalViolations);
            },

            formDataIsEmpty() {
                let empty = true;

                Object.keys(this.formData).forEach((key) => {
                    if(this.formData[key] && this.initialData[key] != this.formData[key]) {
                        empty = false;
                    }
                });

                return empty;
            },
            contentTitle() {
                return this.view.contentTitle(this.formData);
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
                    this.$emit('onCreate', data.data[`create${this.view.type}`].id);
                    this.formData = Object.assign({}, this.initialData);
                    this.$refs.form.reset();

                }).finally(() => { this.loading = false }).catch((e) => {
                    Alerts.apolloErrorHandler(e);
                    Alerts.$emit('push', 'danger', this.$t('content.create.errors', { view: this.view, contentTitle: this.contentTitle }), 'inlineCreate');
                });
            },
            fieldViolations(prefix) {
                return Alerts.violationsForPrefix(prefix);
            }
        }
    }
</script>
