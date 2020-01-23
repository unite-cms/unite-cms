<template>
  <form-row :domID="domID" :field="field" :alerts="violations">
    <multi-field :field="field" :val="val" @addRow="val.push(today)" @removeRow="removeByKey" :span-row="false" v-slot:default="multiProps">
      <div class="uk-flex uk-flex-middle">

        <div class="uk-flex-1 date-picker-input">
          <date-picker :required="field.required" :id="domID" input-class="uk-input" :value="values[multiProps.rowKey || 0]" @input="setValue(arguments, multiProps.rowKey)" :full-month-name="true" :monday-first="$t('field.date.mondayFirst') === 'true'" initial-view="year" :format="$t('field.date.format')" :language="$t('field.date.picker')" />
        </div>

        <div v-if="!field.list_of && !field.required && val" class="uk-margin-small-left">
          <a class="uk-icon-link uk-text-danger" @click.prevent="setValue(null)"><icon name="x" /></a>
        </div>

      </div>
    </multi-field>
  </form-row>
</template>
<script>
    import _abstract from "./_abstract";
    import FormRow from './_formRow';
    import MultiField from './_multiField';
    import DatePicker from 'vuejs-datepicker';
    import Icon from "../../Icon";

    export default {

        // Static query methods for unite system.
        queryData(field, unite, depth) { return field.id },
        normalizeQueryData(queryData, field, unite) { return queryData; },
        normalizeMutationData(formData, field, unite) { return formData; },

        // Vue properties for this component.
        extends: _abstract,
        components: { DatePicker, MultiField, FormRow, Icon },
        computed: {
            today() {
                let today = new Date();
                today.setHours(0);
                today.setMinutes(0);
                today.setSeconds(0);
                return today;
            },
        }
    }
</script>
<style scoped lang="scss">
  .date-picker-input {
    max-width: 200px;
  }
</style>

