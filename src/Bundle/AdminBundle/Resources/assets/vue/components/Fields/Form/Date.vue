<template>
  <form-row :domID="domID" :field="field">
    <multi-field :field="field" :val="val" @addRow="val.push('')" @removeRow="removeByKey" v-slot:default="multiProps">
      <date-picker v-if="field.list_of" :id="domID" input-class="uk-input" v-model="val[multiProps.rowKey]" :language="$t('field.date')" format="d MMMM yyyy" />
      <date-picker v-else :id="domID" input-class="uk-input" v-model="val" :language="$t('field.date')" format="d MMMM yyyy" />
    </multi-field>
  </form-row>
</template>
<script>
    import _abstract from "./_abstract";
    import FormRow from './_formRow';
    import MultiField from './_multiField';
    import DatePicker from 'vuejs-datepicker';

    export default {

        // Static query methods for unite system.
        queryData(field) { return field.id },
        normalizeData(inputData, field) { return inputData; },

        // Vue properties for this component.
        extends: _abstract,
        components: { DatePicker, MultiField, FormRow },
    }
</script>
