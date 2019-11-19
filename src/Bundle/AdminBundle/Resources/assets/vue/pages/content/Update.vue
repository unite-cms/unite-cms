<template>
  <content-detail :loading="loading || $apollo.loading" @submit="submit">
    <h1 class="uk-card-title">{{ $t('content.update.headline', view) }}</h1>
    <component :key="field.id" v-for="field in view.formFields()" :is="$unite.getFormFieldType(field.type)" :field="field" v-model="formData[field.id]" />
    <button slot="footer" class="uk-button uk-button-primary" type="submit">{{ $t('content.update.actions.submit') }}</button>
  </content-detail>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from "../../components/Icon";
    import Alerts from "../../state/Alerts";
    import Route from "../../state/Route";
    import ContentDetail from './_detail';

    export default {
        components: {Icon, ContentDetail},
        data(){
            return {
                loading: false,
                formData: {}
            }
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            }
        },
        apollo: {
            formData: {
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
                    return this.view.normalizeFormData(data[`get${ this.view.type }`]);
                }
            }
        },
        methods: {
            submit() {
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
                        data: this.formData
                    }
                }).then((data) => {
                    Route.back({ updated: this.$route.params.id });
                    Alerts.$emit('push', 'success', this.$t('content.update.success', this.view));
                }).finally(() => { this.loading = false })
            }
        }
    }
</script>
