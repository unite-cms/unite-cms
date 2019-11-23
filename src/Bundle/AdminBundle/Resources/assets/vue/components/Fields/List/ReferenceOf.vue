<template>
  <div>
    <div class="uk-flex uk-flex-middle">
      <span v-if="total" class="uk-margin-small-right uk-label uk-label-muted">{{ total }}</span>
      <button type="button" class="uk-button-light uk-icon-button uk-icon-button-small" @click.prevent="modalIsOpen = true"><icon name="menu" /></button>
    </div>
    <modal v-if="modalIsOpen" @hide="modalIsOpen = false" :title="$t('field.referenceOf.modal.headline')">
      <component :is="$unite.getViewType(referencedView.viewType)" :view="referencedView" />
    </modal>
  </div>
</template>
<script>
    import _abstract from "./_abstract";
    import Icon from "../../Icon";
    import Modal from "../../Modal";
    import { getAdminViewByType } from "../../../plugins/unite";

    export default {
        components: {Modal, Icon},
        extends: _abstract,
        data() {
            return {
                modalIsOpen: false,
            }
        },
        computed: {
            referencedView() {
                return getAdminViewByType(this.$unite, this.field.config.content_type);
            },
            total() {
                return this.row[this.field.id].total || null;
            }
        },
    }
</script>
