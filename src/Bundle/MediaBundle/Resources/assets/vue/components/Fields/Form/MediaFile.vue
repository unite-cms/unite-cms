<template>
  <form-row :domID="domID" :field="field">
    <multi-field :field="field" :val="val" @addRow="val.push('')" @removeRow="removeByKey" v-slot:default="multiProps">
      <file-pond :options="filePondOptions" :files="initialFiles[multiProps.rowKey || 0]" @updatefiles="args => onFilesUpdated(multiProps.rowKey || 0, args)" />
    </multi-field>
  </form-row>
</template>
<script>
  import _abstract from "../../../../../../../AdminBundle/Resources/assets/vue/components/Fields/Form/_abstract";
  import FormRow from "../../../../../../../AdminBundle/Resources/assets/vue/components/Fields/Form/_formRow";
  import MultiField from "../../../../../../../AdminBundle/Resources/assets/vue/components/Fields/Form/_multiField";

  import vueFilePond from 'vue-filepond';
  const FilePond = vueFilePond();

  export default {

      // Static query methods for unite system.
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; },

      // Vue properties for this component.
      extends: _abstract,
      components: { MultiField, FormRow, FilePond },

      computed: {
          initialFiles() {

              if(this.values.length === 0) {
                  return [[]];
              }

              return this.values.map((value) => {
                  console.log(value);
                  return [];
              });
          },

          filePondOptions() {
              return {};
          }
      },

      methods: {
          onFilesUpdated(key, foo) {
              console.log(key, foo);
          }
      },
  }
</script>
