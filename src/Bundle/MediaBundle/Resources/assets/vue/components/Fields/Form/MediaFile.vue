<template>
  <form-row :domID="domID" :field="field">
    <file-pond :allow-multiple="field.list_of" :id="domID" :server="filePondServer" :files="initialFiles" @updatefiles="onFilesUpdated" />
  </form-row>
</template>
<script>
  import _abstract from "../../../../../../../AdminBundle/Resources/assets/vue/components/Fields/Form/_abstract";
  import FormRow from "../../../../../../../AdminBundle/Resources/assets/vue/components/Fields/Form/_formRow";

  import gql from 'graphql-tag';

  import vueFilePond from 'vue-filepond';
  import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
  import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
  import "filepond/dist/filepond.min.css";
  import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

  const FilePond = vueFilePond( FilePondPluginFileValidateType, FilePondPluginImagePreview );

  const PreSignMutation = gql`mutation($type: String!, $field: String!, $filename: String!) {
      uniteMediaPreSignedUrl(type: $type, field: $field, filename: $filename)
  }`;

  export default {

      // Static query methods for unite system.
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow, FilePond },

      computed: {
          initialFiles() {
              return this.values.map((value) => {
                  console.log(value);
                  return [];
              });
          },

          filePondServer() {
              return {
                  process:(fieldName, file, metadata, load, error, progress, abort, transfer, options) => {

                      const request = new XMLHttpRequest();

                      // First ask unite cms for a pre-signed url to upload this file.
                      this.$apollo.mutate({
                          mutation: PreSignMutation,
                          variables: {
                              type: this.$unite.adminViews[this.$route.params.type].type,
                              field: this.field.id,
                              filename: file.name
                          }
                      }).then((data) => {
                          console.log();

                          const formData = new FormData();
                          formData.append(fieldName, file, file.name);

                          request.open('POST', data.data.uniteMediaPreSignedUrl);
                          request.upload.onprogress = (e) => {
                              progress(e.lengthComputable, e.loaded, e.total);
                          };
                          request.onload = function() {
                              if (request.status >= 200 && request.status < 300) {
                                  // the load method accepts either a string (id) or an object
                                  load(request.responseText);
                              }
                              else {
                                  // Can call the error method if something is wrong, should exit after
                                  error('Could not upload file.');
                              }
                          };

                          request.send(formData);

                      }).catch(error);

                      return {
                          abort: () => {
                              request.abort();
                              abort();
                          }
                      };
                  }
              };
          }
      },

      methods: {
          onFilesUpdated(foo) {
              console.log(foo);
          }
      },
  }
</script>
