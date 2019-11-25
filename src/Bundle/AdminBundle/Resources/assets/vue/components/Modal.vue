<template>
  <div>
    <div :id="'modal' + _uid" ref="modal" :class="{ 'uk-modal-container': this.container }" uk-modal="stack: true">
      <div class="uk-modal-dialog uk-background-muted">
        <button class="uk-modal-close-default" type="button" uk-close></button>

        <div v-if="title" class="uk-modal-header">
          <h2 class="uk-modal-title">{{ title }}</h2>
        </div>

        <div class="uk-modal-body" :uk-overflow-auto="overflowAuto" :class="{ 'uk-padding-remove': !overflowAuto }">
          <slot></slot>
        </div>

        <div class="uk-modal-footer" v-if="$slots.footer">
          <slot name="footer"></slot>
        </div>

      </div>
    </div>
  </div>
</template>

<script>

    import UIkit from 'uikit';

    export default {
        name: "Modal",
        props: {
            title: String,
            container: {
                type: Boolean,
                default: true
            },
            overflowAuto: {
                type: Boolean,
                default: true
            }
        },
        mounted() {
            UIkit.modal(this.$refs.modal).show();
            UIkit.util.on(this.$refs.modal, 'hide', (e) => {
                this.$emit('hide');
            });
        },
        beforeDestroy() {
            let modal = this.$refs.modal;
            UIkit.modal(modal).hide().finally(() => {
                modal.remove();
            });
        }
    }
</script>
