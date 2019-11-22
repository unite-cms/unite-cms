<template>
  <content-detail :loading="loading || $apollo.loading" @submit="submit">
    <h1 class="uk-card-title">{{ $t('content.create.headline', view) }}</h1>
    <component :key="field.id" v-for="field in view.formFields()" :is="$unite.getFormFieldType(field.type)" :field="field" v-model="formData[field.id]" />
    <button slot="footer" class="uk-button uk-button-primary" type="submit">{{ $t('content.create.actions.submit') }}</button>
  </content-detail>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from "../../components/Icon";
    import ContentDetail from './_detail';
    import Alerts from "../../state/Alerts";
    import Route from "../../state/Route";

    export default {
        components: {Icon, ContentDetail},
        data(){
            return {
                loading: false,
                formData: {},
            }
        },
        mounted() {
            this.formData = this.view.normalizeFormData();
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            }
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
                        data: this.formData
                    }
                }).then((data) => {
                    Route.back({updated: data.data[`create${this.view.type}`].id});
                    Alerts.$emit('push', 'success', this.$t('content.create.success', this.view));
                }).finally(() => { this.loading = false }).catch(Alerts.apolloErrorHandler);
            }
        }
    }
</script>
