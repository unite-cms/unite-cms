<template>
  <content-detail :loading="loading || $apollo.loading" @submit="submit">
    <h1 class="uk-card-title">{{ $t('content.delete.headline', view) }}</h1>
    <p>{{ $t('content.delete.message') }}</p>
    <button slot="footer" class="uk-button uk-button-danger" type="submit">{{ $t('content.delete.actions.submit') }}</button>
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
                content: {},
                loading: false,
            }
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            }
        },
        apollo: {
            content: {
                fetchPolicy: 'network-only',
                query() {
                    return gql`query($id: ID!) {
                        get${ this.view.type }(id: $id) {
                            id
                        }
                    }`;
                },
                variables() {
                    return {
                        id: this.$route.params.id,
                    };
                },
                update(data) {
                    return data[`get${ this.view.type }`];
                }
            }
        },
        methods: {
            submit() {
                this.loading = true;
                this.$apollo.mutate({
                    mutation: gql`mutation($id: ID!, $persist: Boolean!) {
                        delete${ this.view.type }(id: $id, persist:$persist) {
                            id
                        }
                    }`,
                    variables: {
                        id: this.$route.params.id,
                        persist: true,
                    }
                }).then((data) => {
                    Route.back({ updated: this.$route.params.id, deleted: true });
                    Alerts.$emit('push', 'success', this.$t('content.delete.success', this.view));
                }).finally(() => { this.loading = false })
            }
        }
    }
</script>
