<template>
  <form-row :domID="domID" :field="field">
    <file-pond name="file" :allow-multiple="field.list_of" :id="domID" :server="filePondServer" :files="files" @processfile="onFileProcess" @removefile="onFileRemove"  />
  </form-row>
</template>
<script>
  import _abstract from "../../../../../../../AdminBundle/Resources/assets/vue/components/Fields/Form/_abstract";
  import FormRow from "../../../../../../../AdminBundle/Resources/assets/vue/components/Fields/Form/_formRow";

  import gql from 'graphql-tag';
  import jwtDecode from 'jwt-decode';

  import vueFilePond from 'vue-filepond';
  import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
  import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
  import FilePondPluginFilePoster from 'filepond-plugin-file-poster';
  import "filepond/dist/filepond.min.css";
  import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';
  import 'filepond-plugin-file-poster/dist/filepond-plugin-file-poster.css';

  const FilePond = vueFilePond( FilePondPluginFileValidateType, FilePondPluginImagePreview, FilePondPluginFilePoster );

  const PreSignMutation = gql`mutation($type: String!, $field: String!, $filename: String!) {
      uniteMediaPreSignedUrl(type: $type, field: $field, filename: $filename)
  }`;

  const valueToFile = function(value) {
      console.log(value);
      return {};
      //return values.;
      /*return [
          {
              source: 'XXX-YYY-ZZZ',
              options: {
                  type: 'local',
                  file: {
                      name: 'my-file.png',
                      size: 3001025,
                      type: 'image/png'
                  },
                  metadata: {
                      poster: 'http://10.1.29.15:9000/test/A.png'
                  }
              }
          }
      ];*/
  };

  const uploadFile = function(request, token, fieldName, file, progress){
      return new Promise((resolve, reject) => {

          const payload = jwtDecode(token);
          const formData = new FormData();

          formData.append(fieldName, file, file.name);
          request.open('PUT', payload.u);

          request.upload.onprogress = (e) => {
              progress(e.lengthComputable, e.loaded, e.total);
          };

          request.onload = function() {
              if (request.status >= 200 && request.status < 300) {
                  resolve(payload);
              }
              else {
                  reject(request.response);
              }
          };

          request.send(formData);
      });
  };

  export default {

      // Static query methods for unite system.
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow, FilePond },

      computed: {
          files() {
              return this.values.map(valueToFile);
          },
          filePondServer() {
              return {
                  fetch: null,
                  revert: null,
                  load: null,
                  restore: null,
                  patch: null,
                  process:(fieldName, file, metadata, load, error, progress, abort, transfer, options) => {

                      let request = new XMLHttpRequest();

                      // First ask unite cms for a pre-signed url to upload this file.
                      this.$apollo.mutate({
                          mutation: PreSignMutation,
                          variables: {
                              type: this.$unite.adminViews[this.$route.params.type].type,
                              field: this.field.id,
                              filename: file.name
                          }

                      // Then upload the file directly to the endpoint
                      }).then((data) => {
                          uploadFile(request, data.data.uniteMediaPreSignedUrl, fieldName, file, progress).then((payload) => {
                              load(payload.i);
                          }).catch(error)
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
          onFileRemove(t, file) {
              console.log(file);
          },
          onFileProcess(t, file) {
              console.log(file);
          }
      }
  }
</script>
