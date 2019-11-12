<template>
  <div class="schema-editor uk-height-1-1 uk-flex uk-flex-column">
    <div class="tab-bar uk-flex">
      <div class="file-tabs uk-flex-1">
        <button class="uk-button uk-button-secondary file-tab" v-for="model in models()" :class="{ active: isCurrentModel(model) }">
          <span @click="setCurrentModel(model)">
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
          Add file
        </button>
      </div>
      <div class="action-links uk-flex">
        <button class="uk-button uk-button-primary uk-button-small" :disabled="!changed || loading" @click="save">
          <div v-if="loading" class="uk-margin-small-right" uk-spinner="ratio: 0.4"></div>
          <icon v-else-if="!saveSuccess" class="uk-margin-small-right" name="save" />
          <icon v-else class="uk-margin-small-right" name="check" />
          Save
        </button>
      </div>
    </div>
    <MonacoEditor ref="monaco" class="uk-flex-1" value="" :options="monacoOptions" @editorWillMount="editorWillMount" @change="changed = true" />
  </div>
</template>

<script>
    import MonacoEditor from 'vue-monaco'
    import { graphQLLanguageProvider, editorOptions } from "../plugins/monacoUnite";

    import Icon from "../components/Icon"
    import IconGraphQL from '../components/Icons/GraphQL'
    import UIkit from 'uikit';

    export default {
        components: {MonacoEditor, Icon, IconGraphQL},
        data() {
            return {
                monaco: null,
                changed: false,
                loading: false,
                saveSuccess: false,
                uniteReferenceModel: null
            }
        },
        computed: {
            monacoOptions() {
                return editorOptions;
            }
        },
        mounted() {

            this.$refs.monaco.getEditor().addAction({
                id: 'save',
                label: 'Save',
                keybindings: [
                    this.monaco.KeyMod.CtrlCmd | this.monaco.KeyCode.KEY_S
                ],
                run: this.save
            });

            // TODO: Load real schema files
            this.createModel('type Foo { id: ID! }', 'schema');
        },
        methods: {

            models() {
                return this.monaco ? this.monaco.editor.getModels().filter((model) => {
                    return model.uri.scheme === 'unite' && !model.isDisposed();
                }) : [];
            },

            editorWillMount(monaco) {
                this.monaco = monaco;
                graphQLLanguageProvider(this.monaco);
                this.uniteReferenceModel = this.monaco.editor.createModel("type Test { id: ID! }\n\n type Baa { id: ID! }", 'graphql', new this.monaco.Uri('unite-vendor', `unite-cms.graphql`));
            },

            createModel(value, filename) {
                let model = this.monaco.editor.createModel(value, 'graphql', new this.monaco.Uri('unite', `${filename}.graphql`));
                this.setCurrentModel(model);
            },

            setCurrentModel(model) {
                this.$refs.monaco.getEditor().setModel(model);
                this.$forceUpdate();
            },

            isCurrentModel(model) {
                return model === this.$refs.monaco.getEditor().getModel();
            },

            composeModel() {
                UIkit.modal.prompt('Create new schema file (filename without .graphql)', 'untitled').then((filename) => {
                    filename = filename.replace(/[^A-Za-z0-9-_]/gi, '_');
                    this.createModel("\n", filename);
                    this.changed = true;
                })
            },

            updateModelUri(model) {
                UIkit.modal.prompt('Update filename (without .graphql)', model.uri.authority.replace('.graphql', '')).then((filename) => {
                    filename = filename.replace(/[^A-Za-z0-9-_]/gi, '_');
                    let value = model.getValue();
                    model.dispose();
                    this.createModel(value, filename);
                    this.changed = true;
                    this.$forceUpdate();
                })
            },

            deleteModel(model) {
                UIkit.modal.confirm(`Do you really want to delete schema file "${ model.uri.authority }"?`).then(() => {
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

            save() {
                this.loading = true;

                // TODO
                setTimeout(() => {
                    this.loading = false;
                    this.saveSuccess = true;
                    this.changed = false;
                }, 1000);
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