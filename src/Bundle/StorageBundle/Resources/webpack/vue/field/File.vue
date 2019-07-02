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
            {{ tr.select }}
            <div uk-form-custom>
                <input type="file" :multiple="multiFileCollectionRow">
                <span class="uk-link">{{ multiFileCollectionRow ? tr.select_multiple : tr.select_one }}</span>
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
                multiFileCollectionRow: null,
                error: null,
                loading: false,
                feather: feather,
                tmpFileName: null,
                tmpFileSize: null,
                tmpFileType: null,
                tmpChecksum: null,
                tmpId: null,
                tr: JSON.parse(this.messages),
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

            // If this file input is inside a collection, we can allow multi-upload.
            this.multiFileCollectionRow = this.findClosestCollectionRow(this.$el);

            // Init upload element.
            let t = this;
            let uploader = UIkit.upload(this.$el, {

                multiple: this.multiFileCollectionRow,
                name: 'file',
                type: 'PUT',
                allow: '*.' + (this.fileTypes ? this.fileTypes : '*').split(',').join('|*.'),

                beforeAll: () => {
                    this.error = null;
                    this.loading = true;
                },
                completeAll: () => {
                    this.fileName = this.tmpFileName;
                    this.fileSize = this.tmpFileSize;
                    this.fileType = this.tmpFileType;
                    this.fileId = this.tmpId;
                    this.checksum = this.tmpChecksum;
                    this.tmpFileName = null;
                    this.tmpFileSize = null;
                    this.tmpChecksum = null;
                    this.tmpId = null;
                    this.loading = false;
                },
                error: (error) => {
                    this.error = error;
                    this.tmpFileName = null;
                    this.tmpFileSize = null;
                    this.tmpFileType = null;
                    this.tmpChecksum = null;
                    this.tmpId = null;
                    this.loading = false;
                },
                fail: (error) => {
                    this.error = error;
                    this.tmpFileName = null;
                    this.tmpFileSize = null;
                    this.tmpFileType = null;
                    this.tmpChecksum = null;
                    this.tmpId = null;
                    this.loading = false;
                }
            });
            uploader.upload = function(files){
                if(files.length === 0) {
                    return;
                }

                if(t.fileName) {
                    t.error = tr.error_delete_first;
                    return;
                }

                if(t.multiFileCollectionRow) {

                    let rowInstance = t.multiFileCollectionRow.getVueInstance();
                    files.forEach((file, delta) => {
                        if (delta < (files.length - 1)) {
                            rowInstance.$emit('add', { delta: rowInstance.delta + delta, cb: (row) => {
                                let newFileInstance = row.querySelector('unite-cms-storage-file-field[field-path="' + t.fieldPath + '"]').getVueInstance();
                                newFileInstance.$emit('upload', file);
                            } });
                        }
                    });
                }
                t.uploadFile(files[files.length - 1], this);
            };

            // Allow external components to upload files.
            this.$on('upload', (file) => { this.uploadFile(file, uploader); });
        },

        props: [
            'name',
            'value',
            'fileTypes',
            'fieldPath',
            'uploadSignUrl',
            'uploadSignCsrfToken',
            'thumbnailUrl',
            'endpoint',
            'acl',
            'messages',
        ],
        methods: {
            hasThumbnailUrl : function() {
                return this.thumbnailUrl && this.thumbnailUrl.length > 0;
            },
            clearFile: function(){
                this.error = null;
                UIkit.modal.confirm(tr.confirm_delete).then(() => {
                    this.fileName = null;
                    this.fileSize = null;
                    this.fileId = null;
                    this.fileType = null;
                    this.checksum = null;
                }, () => {});
            },
            findClosestCollectionRow: function($el){
                if($el.parentElement.tagName === 'UNITE-CMS-COLLECTION-FIELD-ROW') {
                    return $el.parentElement;
                }
                else if ($el.parentElement.tagName === 'FORM' || !$el.parentElement) {
                    return null;
                }
                else {
                    return this.findClosestCollectionRow($el.parentElement);
                }
            },
            uploadFile(file, uiKitUpload) {

                function match(pattern, path) {
                    return path.match(new RegExp(`^${pattern.replace(/\//g, '\\/').replace(/\*\*/g, '(\\/[^\\/]+)*').replace(/\*/g, '[^\\/]+').replace(/((?!\\))\?/g, '$1.')}$`, 'i'));
                }

                if (uiKitUpload.allow && !match(uiKitUpload.allow, file.name)) {
                    this.fail(uiKitUpload.msgInvalidName.replace('%s', uiKitUpload.allow));
                    return;
                }

                let data = new FormData();
                data.append('pre_sign_form[filename]', file.name);
                data.append('pre_sign_form[field]', this.fieldPath);
                data.append('pre_sign_form[_token]', this.uploadSignCsrfToken);

                UIkit.util.ajax(this.uploadSignUrl, {
                    method: 'POST',
                    data: data,
                    headers: { "Authentication-Fallback": true }
                }).then((result) => {
                    // Temporary save the parameter of this file. If upload is successful, we save them to the component.
                    let preSignedUrl = JSON.parse(result.responseText);
                    uiKitUpload.url = preSignedUrl.pre_signed_url;
                    this.tmpId = preSignedUrl.uuid;
                    this.tmpFileSize = file.size;
                    this.tmpFileType = file.type;
                    this.tmpFileName = preSignedUrl.filename;
                    this.tmpChecksum = preSignedUrl.checksum;

                    let headers = {};

                    if (this.acl) {
                        headers['x-amz-acl'] = t.acl;
                    }

                    UIkit.util.trigger(this.$el, 'upload', [file]);
                    uiKitUpload.beforeAll(uiKitUpload);

                    UIkit.util.ajax(uiKitUpload.url, {
                        data: file,
                        method: uiKitUpload.type,
                        headers,
                        beforeSend: env => {
                            const {xhr} = env;
                            xhr.upload && UIkit.util.on(xhr.upload, 'progress', uiKitUpload.progress);
                            ['loadStart', 'load', 'loadEnd', 'abort'].forEach(type =>
                                UIkit.util.on(xhr, type.toLowerCase(), uiKitUpload[type])
                            );

                            uiKitUpload.beforeSend(env);
                        }
                    }).then(
                        xhr => {
                            uiKitUpload.complete(xhr);
                            uiKitUpload.completeAll(xhr);
                        },
                        e => uiKitUpload.error(e.message)
                    );
                }, () => {
                    this.error = tr.error_sign;
                });
            }
        }
    };
</script>

<style lang="scss">
    @import "../../../../../CoreBundle/Resources/webpack/sass/base/variables";

    unite-cms-storage-file-field {
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
