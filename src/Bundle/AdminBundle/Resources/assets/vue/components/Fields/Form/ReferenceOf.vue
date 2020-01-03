<template>
  <form-row :domID="domID" :field="field" :alerts="!referencedView ? [{ level: 'warning', message: $t('field.reference_of.missing_view_warning') }] : violations">
    <template v-if="contentId">
      <div class="uk-input-group" v-for="value in values">
        <span v-if="value.total !== null" class="uk-margin-small-right uk-label uk-label-muted">{{ value.total }}</span>
        <a class="uk-icon-button uk-button-light uk-icon-button-small" @click.prevent="modalIsOpen = true"><icon name="more-horizontal" /></a>
      </div>
      <modal v-if="modalIsOpen" @hide="modalIsOpen = false" :title="$t('field.reference_of.modal.headline', { name: field.name, contentTitle: contentTitle })">
        <component :is="$unite.getViewType(referencedView)" :view="referencedView" :embedded="true" :highlight-row="highlightRow" :filter="filter" :order-by="referencedView.orderBy" :initial-create-data="initialCreateData" @onCreate="onCreate" />
      </modal>
    </template>
    <div v-else class="uk-placeholder uk-padding-small">{{ $t('field.reference_of.no_content_id') }}</div>
  </form-row>
</template>
<script>
  import FormRow from './_formRow';
  import MultiField from './_multiField';
  import Modal from "../../Modal";
  import Icon from "../../Icon";
  import { getAdminViewByType } from '../../../plugins/unite';
  import _abstract from "./_abstract";

  export default {

      // Static query methods for unite system.
      queryData(field, unite, depth) {
          return `${ field.id } { total }`
      },
      normalizeQueryData(queryData, field, unite) { return queryData; },
      normalizeMutationData(formData, field, unite) { return undefined; },

      // Vue properties for this component.
      extends: _abstract,
      components: { Icon, FormRow, MultiField, Modal },
      data() {
          return {
              modalIsOpen: false,
              highlightRow: null,
          }
      },

      watch: {
          modalIsOpen(val) {
              if(val) {
                  this.highlightRow = null;
              }
          }
      },

      computed: {
          referencedView() {
              return getAdminViewByType(this.$unite, this.field.config.content_type);
          },
          contentTitle() {
              let refContent = Object.assign({}, this.formData, { _meta: { id: this.contentId } });
              return this.field.view().contentTitle(refContent);
          },
          filter() {
              return {
                  field: this.field.config.reference_field,
                  operator: 'EQ',
                  value: this.contentId
              }
          },
          initialCreateData() {
              let formData = {};
              formData[this.field.config.reference_field] = this.contentId;
              return formData;
          }
      },
      methods: {
          onCreate(id) {
              this.highlightRow = id;
          }
      },
  }
</script>
<style scoped lang="scss">
  .uk-placeholder {
    padding: 10px;
  }
</style>
