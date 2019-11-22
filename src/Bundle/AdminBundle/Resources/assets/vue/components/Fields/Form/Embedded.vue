<template>
  <form-row :domID="domID" :field="field" :alerts="!embeddedView ? [{ level: 'warning', message: $t('field.embedded.missing_view_warning') }] : []">
    <multi-field v-if="embeddedView" :field="field" :val="val" @addRow="val.push('')" @removeRow="removeByKey" v-slot:default="multiProps">
      <div class="uk-input-group">
        <component :key="field.id" v-for="field in embeddedView.formFields()" :is="$unite.getFormFieldType(field.type)" :field="field" :value="values[multiProps.rowKey || 0] ? values[multiProps.rowKey || 0][field.id] : undefined" @input="setFieldValue(field.id, arguments, multiProps.rowKey)" />
      </div>
    </multi-field>
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
          return `${ field.id } { ${ getAdminViewByType(unite, field.returnType).queryFormData().join("\n") } }`
      },
      normalizeData(inputData, field, unite) {
          return removeIntroSpecType(inputData);
      },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow, MultiField, Modal },
      computed: {
          embeddedView() {
              return getAdminViewByType(this.$unite, this.field.returnType);
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
