<template>
  <form-row :domID="domID" :field="field" :alerts="!referencedView ? [{ level: 'warning', message: $t('field.reference_of.missing_view_warning') }] : []">
    <div class="uk-input-group">
      <div class="uk-placeholder">TODO: Implement</div>
    </div>
  </form-row>
</template>
<script>
  import _abstract from "./_abstract";
  import FormRow from './_formRow';
  import MultiField from './_multiField';
  import Modal from "../../Modal";
  import { getAdminViewByType, removeIntroSpecType } from '../../../plugins/unite';

  export default {

      // Static query methods for unite system.
      queryData(field, unite) {
          return `${ field.id } { __typename }`
      },
      normalizeData(inputData, field, unite) {
          return removeIntroSpecType(inputData);
      },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow, MultiField, Modal },
      computed: {
          referencedView() {
              return getAdminViewByType(this.$unite, this.field.config.content_type);
          }
      },
      methods: {
          setFieldValue(field, args, key) {
              if(this.field.list_of) {
                  this.val[key] = this.val[key] || {};
                  this.$set(this.val[key], field, args[0]);
              } else {
                  this.val = this.val || {};
                  this.$set(this.val, field, args[0]);
              }
          }
      }
  }
</script>
