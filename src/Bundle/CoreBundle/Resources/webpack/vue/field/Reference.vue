<template>
    <div>
        <input type="hidden" :name="name + '[domain]'" :value="domain" />
        <input type="hidden" :name="name + '[content_type]'" :value="contentType" />
        <input type="hidden" :name="name + '[content]'" :value="content" />

        <div class="uk-modal-container" :id="modalId" uk-modal>
            <div class="uk-modal-dialog" uk-overflow-auto>
                <button class="uk-modal-close-default" type="button" uk-close></button>
                <div v-html="modalHtml"></div>
            </div>
        </div>

        <button v-if="!content" class="uk-button uk-button-default" v-on:click.prevent="openModal">
            <span uk-icon="icon: plus"></span>
            Select
        </button>
        <div v-if="content" class="uk-placeholder uk-padding-small">
            <div v-if="loading" uk-spinner></div>
            {{ title }}
            <button uk-close class="uk-modal-close-default" v-on:click.prevent="clearSelection"></button>
        </div>

    </div>
</template>

<script>
    import { GraphQLClient } from 'graphql-request'
    import UIkit from 'uikit';

    export default {
        data() {
            var value = JSON.parse(this.value);

            // Find out all content label fields that we need to get form the API.
            var contentLabelFieldRegEx = /{([0-9a-z_]+)}/g;
            var matches, contentLabelFields = [];
            while (matches = contentLabelFieldRegEx.exec(this.contentLabel)) {
                contentLabelFields.push(matches[1]);
            }

            return {
                modal: null,
                domain: value.domain,
                contentType: value.content_type,
                content: value.content ? value.content : null,
                contentLabelFields: contentLabelFields,
                loading: false,
                title: ''
            };
        },
        props: [
            'name',
            'value',
            'modalHtml',
            'baseUrl',
            'contentLabel'
        ],
        created() {

            this.client = new GraphQLClient(this.baseUrl + this.domain + '/api', {
                credentials: "same-origin",
                headers: {
                    "Authentication-Fallback": true
                },
            });

            // Load by getting the content object from the API.
            if(this.content) {
                this.findHumanReadableName();
            }
        },
        mounted() {
            this.modal = UIkit.modal(this.$el.querySelector('#' + this.modalId));
        },
        computed: {
            modalId() {
                return 'modal_' + this._uid;
            }
        },
        methods: {
            openModal() {

                // When opening a modal, we start listen to contentSelected events.
                window.UnitedCMSEventBus.$on('contentSelected', (data) => {
                    // For the moment, we can only handle single selections
                    if(data.length > 0) {
                        this.content = data[0].row.id;
                        this.findHumanReadableName();
                        this.closeModal();
                    }
                });

                this.modal.show();
            },
            closeModal() {

                // When closing a modal, we stop listen to contentSelected events.
                window.UnitedCMSEventBus.$off('contentSelected');

                this.modal.hide();
            },
            clearSelection() {
                this.content = null;
                this.title = null;
            },
            findHumanReadableName() {
                if(this.content) {
                    this.loading = true;
                    let schemaType = this.contentType.charAt(0).toUpperCase() + this.contentType.slice(1);
                    let queryMethod = 'get' + schemaType;
                    let label = this.contentLabel;

                    this.client.request(`
                            query($id : ID!) {` + queryMethod + `(id: $id) {
                                ` + this.contentLabelFields.join(',') + `
                            }
                        }`, { 'id': this.content }).then((data) => {

                        this.contentLabelFields.forEach((field) => {
                            label = label.replace('{' + field + '}', data[queryMethod][field]);
                        });
                        this.title = label;
                        this.loading = false;
                    });
                }
            }
        }
    };
</script>

<style lang="scss" scoped>
</style>