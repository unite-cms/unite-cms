<template>
    <div class="js-upload uk-placeholder uk-text-center">

        <div v-if="error" class="uk-alert-danger" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <p>{{ error }}</p>
        </div>

        <div v-if="fileName" class="uk-flex uk-flex-middle">
            <div class="uk-margin-small-right">
                <span v-if="!this.hasThumbnailUrl()" uk-icon="icon: file; ratio: 2"></span>
                <img class="uk-border-rounded" style="max-height: 100px; margin-right: 20px;" v-if="this.hasThumbnailUrl()" :src="this.actualThumbnailUrl" />
            </div>
            <a class="uk-text-left uk-flex-auto" :href="fileUrl" target="_blank">
                {{ fileName }}<br />
                <small>{{ fileSizeHuman }}</small>
            </a>
            <div>
                <button uk-close v-on:click.prevent="clearFile"></button>
            </div>

            <input type="hidden" :name="name + '[name]'" :value="fileName" />
            <input type="hidden" :name="name + '[type]'" :value="fileType" />
            <input type="hidden" :name="name + '[size]'" :value="fileSize" />
            <input type="hidden" :name="name + '[id]'" :value="fileId" />
            <input type="hidden" :name="name + '[checksum]'" :value="checksum" />
        </div>
        <div v-else>
            <span uk-icon="icon: cloud-upload"></span>
            <span class="uk-text-middle">Add file by dropping it here or</span>
            <div uk-form-custom>
                <input type="file">
                <span class="uk-link">selecting one</span>
            </div>
        </div>

        <div v-if="loading" class="uk-text-center" style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; background: rgba(255,255,255,0.75);">
            <div style="position: absolute; top: 50%; margin-top: -15px;" uk-spinner></div>
        </div>
    </div>
</template>

<script>

    import UIkit from 'uikit';

    export default {
        data() {
            var value = JSON.parse(this.value);

            return {
                fileName: value.name,
                fileType: value.type,
                fileSize: value.size,
                fileId: value.id,
                checksum: value.checksum,
                error: null,
                loading: false
            };
        },
        computed: {
            fileSizeHuman: function() {
                let size = (this.fileSize / 1024);

                if(size < 1000) {
                    return Math.floor(size) + 'Kb';
                }
                size = size / 1000;
                if(size < 1000) {
                    return Math.floor(size) + 'Mb';
                }
                size = size / 1000;
                return Math.floor(size) + 'Gb';
            },
            fileUrl: function(){
                if(!this.fileName || !this.fileId) {
                    return null;
                }
                return this.endpoint + '/' + this.fileId + '/' + this.fileName;
            },
            actualThumbnailUrl: function() {
                if(!this.hasThumbnailUrl()) {
                    return null;
                }

                return this.thumbnailUrl
                    .replace('{endpoint}', this.endpoint)
                    .replace('{id}', this.fileId)
                    .replace('{name}', this.fileName);
            }
        },

        mounted() {

            // Init upload element.
            let tmpFileName = null;
            let tmpFileSize = null;
            let tmpFileType = null;
            let tmpChecksum = null;
            let tmpId = null;
            let t = this;

            let uploader = UIkit.upload(this.$el, {

                multiple: false,
                name: 'file',
                type: 'PUT',
                allow: '*.' + (this.fileTypes ? this.fileTypes : '*').split(',').join('|*.'),

                beforeAll: () => {
                    this.error = null;
                    this.loading = true;
                },
                completeAll: () => {
                    this.fileName = tmpFileName;
                    this.fileSize = tmpFileSize;
                    this.fileType = tmpFileType;
                    this.fileId = tmpId;
                    this.checksum = tmpChecksum;
                    tmpFileName = null;
                    tmpFileSize = null;
                    tmpChecksum = null;
                    tmpId = null;
                    this.loading = false;
                },
                error: (error) => {
                    this.error = error;
                    tmpFileName = null;
                    tmpFileSize = null;
                    tmpFileType = null;
                    tmpChecksum = null;
                    tmpId = null;
                    this.loading = false;
                },
                fail: (error) => {
                    this.error = error;
                    tmpFileName = null;
                    tmpFileSize = null;
                    tmpFileType = null;
                    tmpChecksum = null;
                    tmpId = null;
                    this.loading = false;
                }
            });
            uploader.upload = function(files){
                if(files.length === 0) {
                    return;
                }

                if(t.fileName) {
                    t.error = 'To upload a new file, delete the current file first.';
                    console.log(this);
                    return;
                }

                let tmpFile = files[0];

                function match(pattern, path) {
                    return path.match(new RegExp(`^${pattern.replace(/\//g, '\\/').replace(/\*\*/g, '(\\/[^\\/]+)*').replace(/\*/g, '[^\\/]+').replace(/((?!\\))\?/g, '$1.')}$`, 'i'));
                }

                if (this.allow && !match(this.allow, tmpFile.name)) {
                    this.fail(this.msgInvalidName.replace('%s', this.allow));
                    return;
                }

                let data = new FormData();
                data.append('pre_sign_form[filename]', tmpFile.name);
                data.append('pre_sign_form[field]', t.fieldPath);
                data.append('pre_sign_form[_token]', t.uploadSignCsrfToken);

                UIkit.util.ajax(t.uploadSignUrl, {
                    method: 'POST',
                    data: data,
                    headers: { "Authentication-Fallback": true }
                }).then((result) => {

                    // Temporary save the parameter of this file. If upload is successful, we save them to the component.
                    let preSignedUrl = JSON.parse(result.responseText);
                    this.url = preSignedUrl.pre_signed_url;
                    tmpId = preSignedUrl.uuid;
                    tmpFileSize = tmpFile.size;
                    tmpFileType = tmpFile.type;
                    tmpFileName = preSignedUrl.filename;
                    tmpChecksum = preSignedUrl.checksum;

                    UIkit.util.trigger(this.$el, 'upload', [files]);
                    this.beforeAll(this);

                    UIkit.util.ajax(this.url, {
                        data: tmpFile,
                        method: this.type,
                        beforeSend: env => {
                            const {xhr} = env;
                            xhr.upload && UIkit.util.on(xhr.upload, 'progress', this.progress);
                            ['loadStart', 'load', 'loadEnd', 'abort'].forEach(type =>
                                UIkit.util.on(xhr, type.toLowerCase(), this[type])
                            );

                            this.beforeSend(env);
                        }
                    }).then(
                        xhr => {
                            this.complete(xhr);
                            this.completeAll(xhr);
                        },
                        e => this.error(e.message)
                    );
                }, () => {
                    t.error = 'Cannot sign file for uploading';
                });
            };
        },

        props: [
            'name',
            'value',
            'fileTypes',
            'fieldPath',
            'uploadSignUrl',
            'uploadSignCsrfToken',
            'thumbnailUrl',
            'endpoint'
        ],
        methods: {
            hasThumbnailUrl : function() {
                return this.thumbnailUrl && this.thumbnailUrl.length > 0;
            },
            clearFile: function(){
                this.error = null;
                UIkit.modal.confirm('Do you really want to delete the selected file?').then(() => {
                    this.fileName = null;
                    this.fileSize = null;
                    this.fileId = null;
                    this.fileType = null;
                    this.checksum = null;
                }, () => {});
            }
        }
    };
</script>

<style lang="scss" scoped>
</style>