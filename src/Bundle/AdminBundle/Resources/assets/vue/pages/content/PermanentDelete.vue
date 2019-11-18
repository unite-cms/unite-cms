<template>
  <section class="uk-section uk-position-relative">
    <div class="uk-container">
      <div class="uk-flex uk-flex-middle uk-margin-bottom">
        <button @click="goBack" class="uk-button uk-button-small uk-button-default uk-margin-right"><icon name="arrow-left" /> {{ $t('general.back') }}</button>
        <div class="uk-flex-1">
          <h2 class="uk-margin-remove">{{ $t('content.permanent_delete.headline', view) }}</h2>
        </div>
      </div>
      <form class="uk-card uk-card-default" @submit.prevent="submit">

        <div class="uk-card-body">
          <div class="uk-text-center">
            <button class="uk-button uk-button-danger" type="submit">{{ $t('content.permanent_delete.actions.submit') }}</button>
          </div>
        </div>

        <div class="uk-overlay-default uk-position-cover" v-if="loading || $apollo.loading">
          <div uk-spinner class="uk-position-center"></div>
        </div>
      </form>
    </div>
  </section>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from "../../components/Icon";
    import Alerts from "../../state/Alerts";
    import Route from "../../state/Route";

    export default {
        components: {Icon},
        data(){
            return {
                content: {},
                loading: false,
            }
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            },
        },
        apollo: {
            content: {
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
            goBack() {
                Route.back();
            },
            submit() {
                this.loading = true;
                this.$apollo.mutate({
                    mutation: gql`mutation($id: ID!, $persist: Boolean!) {
                        permanent_delete${ this.view.type }(id: $id, persist:$persist) {
                            id
                        }
                    }`,
                    variables: {
                        id: this.$route.params.id,
                        persist: true,
                    }
                }).then((data) => {
                    Route.back({ updated: this.$route.params.id });
                    Alerts.$emit('push', 'success', this.$t('content.permanent_delete.success', this.view));
                }).finally(() => { this.loading = false })
            }
        }
    }
</script>
