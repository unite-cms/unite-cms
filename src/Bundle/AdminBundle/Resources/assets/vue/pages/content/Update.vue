<template>
  <section class="uk-section uk-position-relative">
    <div class="uk-container">
      <div class="uk-flex uk-flex-middle uk-margin-bottom">
        <button @click="goBack" class="uk-button uk-button-small uk-button-default uk-margin-right"><icon name="arrow-left" /> {{ $t('general.back') }}</button>
        <div class="uk-flex-1">
          <h2 class="uk-margin-remove">{{ $t('content.update.headline', view) }}</h2>
        </div>
      </div>
      <form class="uk-card uk-card-default" @submit.prevent="submit">

        <div class="uk-card-body">
          <component :key="field.id" v-for="field in view.formFields()" :is="$unite.getFormFieldType(field.type)" :field="field" v-model="formData[field.id]" />

          <div class="uk-text-right">
            <button class="uk-button uk-button-primary" type="submit">{{ $t('content.update.actions.submit') }}</button>
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
            goBack() {
                Route.back();
            },
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
