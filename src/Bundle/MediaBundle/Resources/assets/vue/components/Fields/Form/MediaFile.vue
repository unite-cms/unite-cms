<template>
  <form-row :domID="domID" :field="field">
    <file-pond name="file" ref="pond" :allow-multiple="field.list_of" :id="domID" :server="filePondServer" :files="files" @processfiles="setValuesFromFiles" @removefile="setValuesFromFiles" />
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
      if(value.file) {
          return value.file;
      } else {
          console.log(value);
          return {
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
          };
      }
  };

  const fileToValue = function(file, fileInfos) {
      return {
          file: file,
          token: fileInfos[file.serverId].token,
          payload: fileInfos[file.serverId].payload,
      };
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
      normalizeData(inputData, field) {
          console.log(inputData);
          return inputData;
      },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow, FilePond },

      data() {
          return {
              fileInformation: [],
          }
      },

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

                              // Save payload for later use
                              this.fileInformation[payload.i] = {
                                  token: data.data.uniteMediaPreSignedUrl,
                                  payload: payload,
                              };

                              // Tell file pond to load the file
                              load(payload.i);
                          }).catch((e) => { console.log(e); error(e); });
                      }).catch((e) => { console.log(e); error(e); });

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
          setValuesFromFiles() {

              this.val = this.field.list_of ? [] : null;

              if(this.field.list_of) {
                  this.$refs.pond.getFiles().forEach((file, delta) => {
                      console.log(delta, file);
                      this.$set(this.val, delta, fileToValue(file, this.fileInformation));
                  });
              } else {
                  this.val = this.$refs.pond.getFile(0) ? fileToValue(this.$refs.pond.getFile(0), this.fileInformation) : null;
              }
          },
      }
  }
</script>
