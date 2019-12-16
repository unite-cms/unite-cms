<template>
  <div>
    <div class="uk-flex uk-flex-middle">
      <span v-if="total !== null" class="uk-margin-small-right uk-label uk-label-muted">{{ total }}</span>
      <button type="button" class="uk-button-light uk-icon-button uk-icon-button-small" @click.prevent="modalIsOpen = true"><icon name="menu" /></button>
    </div>
    <modal v-if="modalIsOpen" @hide="modalIsOpen = false" :title="$t('field.reference_of.modal.headline', { name: field.name, contentTitle: contentTitle })">
      <component :is="$unite.getViewType(referencedView.viewType)" :view="referencedView" :embedded="true" :filter="filter" :initial-create-data="initialCreateData" />
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
            contentTitle() {
                return this.referencedView.contentTitle(this.row);
            },
            filter() {
                return {
                    field: this.field.config.reference_field,
                    operator: 'EQ',
                    value: this.id
                }
            },
            initialCreateData() {
                let formData = {};
                formData[this.field.config.reference_field] = this.id;
                return formData;
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
