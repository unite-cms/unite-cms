<template>
    <div class="js-upload">

        <div v-if="error" class="uk-alert-danger" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <p>{{ error }}</p>
        </div>

        <div v-if="fileName" class="content-holder">
            <a class="filename uk-flex uk-flex-middle" :href="fileUrl" target="_blank">
                <div class="thumbnail uk-margin-small-right">
                    <span v-if="!this.hasThumbnailUrl()" v-html="feather.icons['file'].toSvg({ width: 24, height: 24 })"></span>
                    <img v-if="this.hasThumbnailUrl()" :src="this.actualThumbnailUrl" />
                </div>

                <div class="uk-flex-1">
                    <div class="meta">Size: {{ fileSizeHuman }}, Type: {{ fileType }}</div>
                    {{ fileName }}
                </div>
            </a>

            <button class="close-button" v-html="feather.icons['x'].toSvg({ width: 20, height: 20 })" v-on:click.prevent="clearFile"></button>

            <input type="hidden" :name="name + '[name]'" :value="fileName" />
            <input type="hidden" :name="name + '[type]'" :value="fileType" />
            <input type="hidden" :name="name + '[size]'" :value="fileSize" />
            <input type="hidden" :name="name + '[id]'" :value="fileId" />
            <input type="hidden" :name="name + '[checksum]'" :value="checksum" />
        </div>
        <div v-else class="uk-placeholder">
            <span v-html="feather.icons['upload-cloud'].toSvg({ width: 18, height: 18 })"></span>
            Add file by dropping it here or
            <div uk-form-custom>
                <input type="file" multiple>
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
    import feather from 'feather-icons';

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
                loading: false,
                feather: feather
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

<style lang="scss">
    @import "../../../../../CoreBundle/Resources/webpack/sass/base/variables";

    united-cms-storage-file-field {
        padding: 5px 0;
        display: block;

        .uk-placeholder {
            padding: 20px;
            display: block;
            width: 100%;
            border-color: map-get($colors, grey-dark);
            color: map-get($colors, grey-dark);
            font-size: 1rem;
            cursor: pointer;
            border-radius: 2px;
            margin: 0;
            box-sizing: border-box;
            text-align: center;

            svg.feather {
                margin-top: -3px;
            }

            a, .uk-link {
                color: map-get($colors, grey-very-dark);
                text-decoration: underline;
            }
        }

        .uk-dragover {
            .uk-placeholder {
                background: map-get($colors, white);
                border: 1px solid map-get($colors, grey-medium);
                color: map-get($colors, grey-very-dark);
            }
        }

        .content-holder {
            position: relative;
            background: map-get($colors, white);
            border: 1px solid map-get($colors, grey-medium);
            box-shadow: 0 2px 4px 0 rgba(0,0,0,0.06);
            padding: 10px;
            border-radius: 2px;

            a {
                color: #666;
                text-decoration: none;
                margin-right: 40px;

                &:hover {
                    color: map-get($colors, grey-very-dark);
                }

                > * {
                    overflow: hidden;
                    white-space: nowrap;
                    text-overflow: ellipsis;

                    .meta {
                        text-overflow: ellipsis;
                        overflow: hidden;
                    }
                }
            }

            img {
                height: 75px;
                border-radius: 2px;
            }

            .uk-spinner {
                svg {
                    width: 20px;
                    height: 20px;
                }
            }

            .close-button {
                right: 5px;
                top: 50%;
                margin-top: -20px;
            }

            &:hover {
                .close-button {
                    color: map-get($colors, red);
                }
            }

            .meta {
                font-size: 0.6rem;
                line-height: normal;
                color: darken(map-get($colors, grey-medium), 10%);
                text-transform: uppercase;
            }
        }
    }
</style>