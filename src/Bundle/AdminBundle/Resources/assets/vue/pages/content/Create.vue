<template>
  <section class="uk-section uk-position-relative">
    <div class="uk-container">
      <div class="uk-flex uk-flex-middle uk-margin-bottom">
        <router-link :to="goBack" class="uk-button uk-button-small uk-button-default uk-margin-right"><icon name="arrow-left" /> {{ $t('general.back') }}</router-link>
        <div class="uk-flex-1">
          <h2 class="uk-margin-remove">{{ $t('content.create.headline', view) }}</h2>
        </div>
      </div>
      <form class="uk-card uk-card-default" @submit.prevent="submit">

        <div class="uk-card-body">
          <component :key="field.id" v-for="field in view.formFields()" :is="$unite.getFormFieldType(field.type)" :field="field" v-model="formData[field.id]" />

          <div class="uk-text-right">
            <button class="uk-button uk-button-primary" type="submit">{{ $t('content.create.actions.submit') }}</button>
          </div>
        </div>

        <div class="uk-overlay-default uk-position-cover" v-if="loading">
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
            goBack() {
                Route.back();
            },
            submit() {
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
                    Route.back({ updated: data.data[`create${ this.view.type }`].id });
                    Alerts.$emit('push', 'success', this.$t('content.create.success', this.view));
                }).finally(() => { this.loading = false })
            }
        }
    }
</script>
