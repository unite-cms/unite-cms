<template>
  <div>
    <div class="uk-flex uk-flex-middle">
      <span v-if="total" class="uk-margin-small-right uk-label uk-label-muted">{{ total }}</span>
      <button v-if="total !== 0" type="button" class="uk-button-light uk-icon-button uk-icon-button-small" @click.prevent="modalIsOpen = true"><icon name="menu" /></button>
    </div>
    <modal v-if="modalIsOpen" @hide="modalIsOpen = false" :title="$t('field.referenceOf.modal.headline', field)">
      <component :is="$unite.getViewType(referencedView.viewType)" :view="referencedView" :header="false" :filter="filter" />
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
            filter() {
                return {
                    field: this.field.config.reference_field,
                    operator: 'EQ',
                    value: this.id
                }
            },
            total() {

                if(!this.row[this.field.id]) {
                    return null;
                }

                return this.row[this.field.id].total;
            }
        },
    }
</script>
