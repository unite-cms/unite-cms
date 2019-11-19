<template>
  <div class="schema-editor uk-height-viewport uk-flex uk-flex-column uk-position-relative">
    <div class="tab-bar uk-flex">
      <div class="file-tabs uk-flex-1">
        <button @click="setCurrentModel(model)" class="uk-button uk-button-secondary file-tab" v-for="model in models()" :class="{ active: isCurrentModel(model) }">
          <span>
            <icon-graph-q-l />
            {{ model.uri.authority }}
          </span>
          <span @click="updateModelUri(model)" v-if="isCurrentModel(model)" class="uk-margin-medium-left">
            <icon :width="12" :height="12" name="edit" />
          </span>
          <span @click="deleteModel(model)" v-if="isCurrentModel(model)" class="uk-text-danger">
            <icon :width="12" :height="12" name="trash" />
          </span>
        </button>
        <button class="uk-button uk-button-secondary add-file" @click="composeModel">
          <icon name="plus" />
          {{ $t('schema.add') }}
        </button>
      </div>
      <div class="action-links uk-flex">
        <button class="uk-button uk-button-primary uk-button-small" :disabled="!changed || loading" @click.prevent="save(false)" :class="{ 'uk-button-danger': (saveStatus === false) }">
          <div v-if="loading" class="uk-margin-small-right" uk-spinner="ratio: 0.4"></div>
          <icon v-else-if="saveStatus === null" class="uk-margin-small-right" name="save" />
          <icon v-else-if="saveStatus === true" class="uk-margin-small-right" name="check" />
          <icon v-else-if="saveStatus === false" class="uk-margin-small-right" name="x" />
          {{ $t('schema.save') }}
        </button>
      </div>
    </div>
    <MonacoEditor ref="monaco" class="uk-flex-1" value="" :options="monacoOptions" @editorWillMount="editorWillMount" @change="changed = true" />
    <div class="uk-overlay-default uk-position-cover" v-if="initialLoading">
      <div uk-spinner class="uk-position-center"></div>
    </div>

    <modal v-if="changed && saveStatus === true" @hide="saveStatus = null" :title="$t('schema.diff.headline')">
      <div class="uk-flex">
        <pre>{{ originalSchemaFiles }}</pre>
        <pre>{{ schemaFiles() }}</pre>
      </div>
      <slot name="footer">
        <button class="uk-button uk-button-primary" @click="save(true)">{{ $t('schema.diff.save') }}</button>
      </slot>
    </modal>
  </div>
</template>

<script>
    import gql from 'graphql-tag';
    import MonacoEditor from 'vue-monaco'
    import { graphQLLanguageProvider, editorOptions } from "../plugins/monacoUnite";

    import Icon from "../components/Icon"
    import IconGraphQL from '../components/Icons/GraphQL'
    import UIkit from 'uikit';
    import Alerts from "../state/Alerts";
    import Modal from "../components/Modal";

    export default {
        components: {Modal, MonacoEditor, Icon, IconGraphQL},
        data() {
            return {
                originalSchemaFiles: [],
                monaco: null,
                changed: false,
                initialLoading: true,
                loading: false,
                saveStatus: null,
                uniteReferenceModel: null
            }
        },
        computed: {
            monacoOptions() {
                return editorOptions;
            },
        },
        mounted() {

            this.$refs.monaco.getEditor().addAction({
                id: 'save',
                label: 'Save',
                keybindings: [
                    this.monaco.KeyMod.CtrlCmd | this.monaco.KeyCode.KEY_S
                ],
                run: () => {
                    this.save(false);
                }
            });

            this.loadModels();
        },

        beforeDestroy() {
            this.unloadModels();
        },

        methods: {

            models() {
                return this.monaco ? this.monaco.editor.getModels().filter((model) => {
                    return model.uri.scheme === 'unite' && !model.isDisposed();
                }) : [];
            },

            schemaFiles() {
                return this.models().map((model) => {
                    return {
                        name: model.uri.authority,
                        value: model.getValue(),
                    };
                })
            },

            unloadModels() {
                if(!this.monaco){
                    return;
                }

                this.monaco.editor.getModels().forEach((model) => {
                    model.dispose();
                });
            },

            loadModels() {
                this.unloadModels();

                this.$apollo.query({
                    query: gql`query {
                  unite {
                    schemaFiles {
                      name
                      value
                    }
                  }
                }
                `
                }).then((data) => {
                    this.originalSchemaFiles = [];
                    data.data.unite.schemaFiles.forEach((schemaFile) => {
                        this.originalSchemaFiles.push({
                            name: schemaFile.name,
                            value: schemaFile.value,
                        });
                        this.createModel(schemaFile.value, schemaFile.name);
                    });
                    this.initialLoading = false;
                });
            },

            editorWillMount(monaco) {
                this.monaco = monaco;
                graphQLLanguageProvider(this.monaco);
                this.createModel("interface UniteContent { id: ID! }\n", 'unite-cms.graphql', 'unite-vendor');
            },

            createModel(value, filename, scheme = 'unite') {
                let model = this.monaco.editor.createModel(value, 'graphql', new this.monaco.Uri(scheme, filename));
                this.setCurrentModel(model);
            },

            setCurrentModel(model) {

                if(!this.$refs.monaco.getEditor()) {
                    return;
                }

                this.$refs.monaco.getEditor().setModel(model);
                this.$forceUpdate();
            },

            isCurrentModel(model) {
                return model === this.$refs.monaco.getEditor().getModel();
            },

            composeModel() {
                UIkit.modal.prompt(this.$t('schema.compose'), 'untitled').then((filename) => {
                    filename = filename.replace(/[^A-Za-z0-9-_]/gi, '_');
                    this.createModel("\n", `${filename}.graphql`);
                    this.changed = true;
                })
            },

            updateModelUri(model) {
                UIkit.modal.prompt(this.$t('schema.rename'), model.uri.authority.replace('.graphql', '')).then((filename) => {
                    filename = filename.replace(/[^A-Za-z0-9-_]/gi, '_');
                    let value = model.getValue();
                    model.dispose();
                    this.createModel(value, `${filename}.graphql`);
                    this.changed = true;
                    this.$forceUpdate();
                })
            },

            deleteModel(model) {
                UIkit.modal.confirm(this.$t('schema.delete', { filename: model.uri.authority })).then(() => {
                    model.dispose();
                    this.changed = true;

                    let allModels = this.models();

                    if(allModels.length > 0) {
                        this.setCurrentModel(allModels[0]);
                    } else {
                        this.$forceUpdate();
                    }
                });
            },

            save(persist = false) {
                Alerts.$emit('clear');
                this.saveStatus = null;
                this.loading = true;

                this.$apollo.mutate({
                    mutation: gql`mutation($schemaFiles: [UniteSchemaFileInput!]!, $persist: Boolean!) {
                        unite {
                            updateSchemaFiles(schemaFiles: $schemaFiles, persist: $persist)
                        }
                    }`,
                    variables: {
                        persist: persist,
                        schemaFiles: this.schemaFiles()
                    },
                }).then((data) => {
                    this.saveStatus = data.data.unite.updateSchemaFiles;

                    if (persist && this.saveStatus) {
                        this.changed = false;
                        this.loading = true;
                        window.location.reload();
                    }

                }).catch(Alerts.apolloErrorHandler)
                .finally(() => {
                    this.loading = false;
                });
            }

        }
    }
</script>
<style scoped lang="scss">
  .schema-editor {
    background: #1e1e1e;

    .tab-bar {
      background: rgba(255,255,255,0.05);
    }

    .file-tabs {
      overflow: auto;
      white-space: nowrap;

      .file-tab,
      .add-file {
        background: none;
        opacity: 0.5;
        text-transform: none;
        padding-left: 15px;
        padding-right: 10px;

        .uk-icon {
          margin-right: 3px;
        }

        &:hover {
          background: #1e1e1e;
        }

        &.active {
          background: #1e1e1e;
          opacity: 1;
        }
      }

      .add-file {
        padding-right: 20px;
      }
    }

    .action-links {
      padding: 5px;
    }

    .monaco-editor {

    }
  }
</style>