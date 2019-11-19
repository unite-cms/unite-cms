<template>
  <section class="uk-section uk-position-relative">
    <div class="uk-container">
      <div class="uk-flex uk-flex-middle uk-margin-bottom">
        <button @click="goBack" class="uk-button uk-button-small uk-button-default uk-margin-right"><icon name="arrow-left" /> {{ $t('general.back') }}</button>
      </div>
      <component :is="$listeners.submit ? 'form' : 'div'" :class="card ? 'uk-card uk-card-default' : null" @submit.prevent="submit">

        <div :class="card ? 'uk-card-header' : 'detail-header'" v-if="$slots.header">
          <slot name="header"></slot>
        </div>

        <div :class="card ? 'uk-card-body' : 'detail-body'">
          <slot></slot>
        </div>

        <div :class="(card ? 'uk-card-footer' : 'detail-footer') + ' uk-text-' + alignFooter" v-if="$slots.footer">
          <slot name="footer"></slot>
        </div>

        <div class="uk-overlay-default uk-position-cover" v-if="loading">
          <div uk-spinner class="uk-position-center"></div>
        </div>

      </component>
    </div>
  </section>
</template>

<script>
    import Icon from "../../components/Icon";
    import Route from "../../state/Route";

    export default {
        components: {Icon},
        props: {
            loading: Boolean,
            alignFooter: {
                type: String,
                default: 'right'
            },
            card: {
                type: Boolean,
                default: true
            }
        },
        methods: {
            goBack() {
                if(this.$listeners.back) {
                    this.$emit('back');
                } else {
                    Route.back();
                }
            },
            submit() {
                if(this.$listeners.submit) {
                    this.$emit('submit');
                }
            },
        }
    }
</script>
