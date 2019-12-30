<template>
  <form-row :domID="domID" :field="field">
    <file-pond name="file" ref="pond" :allow-multiple="field.list_of" :id="domID" :server="filePondServer" :files="values" @addfile="onFileAdded" @processfiles="onFilesProcessed" @removefile="onFileRemoved" />
  </form-row>
</template>
<script>
  import _abstract from "@unite/admin/Resources/assets/vue/components/Fields/Form/_abstract";
  import FormRow from "@unite/admin/Resources/assets/vue/components/Fields/Form/_formRow";
  import i18n from "@unite/admin/Resources/assets/vue/plugins/i18n";

  import gql from 'graphql-tag';
  import jwtDecode from 'jwt-decode';

  import vueFilePond, { setOptions } from 'vue-filepond';
  import { FileOrigin } from 'filepond';
  import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
  import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
  import FilePondPluginFilePoster from 'filepond-plugin-file-poster';
  import FilePondPluginGetFile from 'filepond-plugin-get-file';

  import "filepond/dist/filepond.min.css";
  import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';
  import 'filepond-plugin-file-poster/dist/filepond-plugin-file-poster.css';
  import 'filepond-plugin-get-file/dist/filepond-plugin-get-file.css';

  const FilePond = vueFilePond( FilePondPluginFileValidateType, FilePondPluginImagePreview, FilePondPluginFilePoster, FilePondPluginGetFile );
  setOptions({
      allowDownloadByUrl: true,
      allowReorder: true,
      labelIdle: i18n.t('field.mediaFile.label.idle'),
      labelInvalidField: i18n.t('field.mediaFile.label.invalidField'),
      labelFileWaitingForSize: i18n.t('field.mediaFile.label.fileWaitingForSize'),
      labelFileSizeNotAvailable: i18n.t('field.mediaFile.label.fileSizeNotAvailable'),
      labelFileLoading: i18n.t('field.mediaFile.label.fileLoading'),
      labelFileLoadError: i18n.t('field.mediaFile.label.fileLoadError'),
      labelFileProcessing: i18n.t('field.mediaFile.label.fileProcessing'),
      labelFileProcessingComplete: i18n.t('field.mediaFile.label.fileProcessingComplete'),
      labelFileProcessingAborted: i18n.t('field.mediaFile.label.fileProcessingAborted'),
      labelFileProcessingError: i18n.t('field.mediaFile.label.fileProcessingError'),
      labelFileProcessingRevertError: i18n.t('field.mediaFile.label.fileProcessingRevertError'),
      labelFileRemoveError: i18n.t('field.mediaFile.label.fileRemoveError'),
      labelTapToCancel: i18n.t('field.mediaFile.label.tapToCancel'),
      labelTapToRetry: i18n.t('field.mediaFile.label.tapToRetry'),
      labelTapToUndo: i18n.t('field.mediaFile.label.tapToUndo'),
      labelButtonRemoveItem: i18n.t('field.mediaFile.label.buttonRemoveItem'),
      labelButtonAbortItemLoad: i18n.t('field.mediaFile.label.buttonAbortItemLoad'),
      labelButtonRetryItemLoad: i18n.t('field.mediaFile.label.buttonRetryItemLoad'),
      labelButtonAbortItemProcessing: i18n.t('field.mediaFile.label.buttonAbortItemProcessing'),
      labelButtonUndoItemProcessing: i18n.t('field.mediaFile.label.buttonUndoItemProcessing'),
      labelButtonRetryItemProcessing: i18n.t('field.mediaFile.label.buttonRetryItemProcessing'),
      labelButtonProcessItem: i18n.t('field.mediaFile.label.buttonProcessItem'),
      labelButtonDownloadItem: i18n.t('field.mediaFile.label.buttonDownloadItem'),
  });


  const PreSignMutation = gql`mutation($type: String!, $field: String!, $filename: String!) {
      uniteMediaPreSignedUrl(type: $type, field: $field, filename: $filename)
  }`;

  /**
   * Convert API response to filepond initial file format.
   *
   * @param value
   */
  const queryValueToFileValue = function(value){

    return {
      source: value.id,
      options: {
        type: 'local',
        file: {
          name: value.filename,
          size: value.filesize,
          type: value.mimetype
        },
        metadata: {
          url: value.url,
          poster: value.preview
        }
      }
    };
  };

  /**
   * Convert filepond file format to unite API mutation input.
   *
   * @param value
   */
  const fileValueToMutationValue = function(value) {

    // File is already uploaded, we just need to return the uuid of the file for unite.
    if(value.origin === FileOrigin.LOCAL) {
      return value.serverId;
    }

    // File is a new input, so we need to pass the upload token, created by unite cms.
    else if(value.origin === FileOrigin.INPUT) {
      return value.getMetadata('uniteInformation').token;
    }

    throw "Invalid file origin";
  };

  /**
   * Upload a new file to the url, specified in the given upload token.
   *
   * @param request
   * @param token
   * @param fieldName
   * @param file
   * @param progress
   * @returns {Promise<unknown>}
   */
  const uploadFile = function(request, token, fieldName, file, progress){
      return new Promise((resolve, reject) => {

          const payload = jwtDecode(token);
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

          request.send(file);
      });
  };

  export default {

      // Static query methods for unite system.
      queryData(field, unite, depth) {
          return `${field.id} {
            id
            filename
            driver
            filesize
            mimetype
            url(pre_sign: true)
            preview(pre_sign: true)
          }`;
      },
      normalizeQueryData(queryData, field, unite) {

          if(!queryData) {
              return queryData;
          }

          return field.list_of ? queryData.map(queryValueToFileValue) : queryValueToFileValue(queryData);
      },
      normalizeMutationData(formData, field, unite) {

          if(!formData) {
              return formData;
          }

          return field.list_of ? formData.map(fileValueToMutationValue) : fileValueToMutationValue(formData);
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
                              type: this.field.view().type,
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
          syncFiles(files) {
              files.forEach((file, delta) => {
                  if(file.serverId) {
                      // First check if we have any extra file information and add it to the files
                      if (this.fileInformation[file.serverId]) {
                        file.setMetadata('uniteInformation', this.fileInformation[file.serverId], true);
                      }

                      // Then sync filepond files with internal field value
                      this.setValue([file], delta);
                  }
              });
          },

          onFileAdded(t, file) {
              if(file.serverId) {
                  this.syncFiles(this.$refs.pond.getFiles());
              };
          },

          onFilesProcessed() {
              this.$nextTick(() => {
                  this.syncFiles(this.$refs.pond.getFiles());
              });
          },

          onFileRemoved(t, file) {

              this.$nextTick(() => {
                let deletedFile = this.values.filter(val => val.id === file.id);
                this.removeValue(deletedFile[0]);
              });
          }
      }
  }
</script>
