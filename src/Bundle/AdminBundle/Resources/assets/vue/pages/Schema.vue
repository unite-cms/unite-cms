<template>
  <div class="schema-editor uk-height-1-1 uk-flex uk-flex-column">
    <div class="tab-bar uk-flex">
      <div class="file-tabs uk-flex-1">
        <button class="uk-button uk-button-secondary file-tab" v-for="file in files" :class="{ active: file.name === selected }">
          <span @click="selected = file.name">
            <icon-graph-q-l />
            {{ file.name }}.graphql
          </span>
          <span @click="updateFile(file)" v-if="file.name === selected" class="uk-margin-medium-left">
            <icon :width="12" :height="12" name="edit" />
          </span>
          <span @click="deleteFile(file)" v-if="file.name === selected" class="uk-text-danger">
            <icon :width="12" :height="12" name="trash" />
          </span>
        </button>
        <button class="uk-button uk-button-secondary add-file" @click="createFile">
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
    <MonacoEditor ref="monaco" class="uk-flex-1" :options="monacoOptions" :value="currentSchema.schema" @change="setCurrentSchema" />
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
                changed: false,
                loading: false,
                saveSuccess: false,
                selected: 'schema',
                files: [
                    {
                        name: "schema",
                        schema: "\n\n",
                    }
                ]
            }
        },
        mounted() {
            graphQLLanguageProvider(monaco);
        },
        computed: {
            monacoOptions() {
                return editorOptions;
            },

            currentSchema() {
                return this.getSchema(this.selected, { schema: '' });
            }
        },
        watch: {
            files: {
                deep: true,
                handler() {
                    this.changed = true;
                    this.saveSuccess = false;
                }
            }
        },
        methods: {

            getSchema(filename, defaultValue = null) {
                let found = this.files.filter((file) => {
                    return file.name === filename;
                });
                return found.length > 0 ? found[0] : defaultValue;
            },

            removeSchema(filename) {
                this.files = this.files.filter((file) => {
                    return file.name !== filename;
                });
            },

            setCurrentSchema(data) {
                if(!this.currentSchema.name) {
                    return;
                }

                if(this.currentSchema.schema !== data) {
                    this.currentSchema.schema = data;
                }
            },

            createFile() {
                UIkit.modal.prompt('Name (without .graphql)', 'new').then((filename) => {

                    filename = filename.replace(/[^A-Za-z0-9-_]/gi, '_');

                    if(this.getSchema(filename)) {
                        UIkit.modal.alert(`A schema with the name "${ filename }"already exists.`);
                    } else {
                        this.files.push({
                            name: filename,
                            schema: "\n\n",
                        });
                        this.selected = filename;
                    }

                })
            },

            updateFile(file) {
                UIkit.modal.prompt('Update filename (without .graphql)', file.name).then((filename) => {

                    filename = filename.replace(/[^A-Za-z0-9-_]/gi, '_');

                    if(filename !== file.name && this.getSchema(filename)) {
                        UIkit.modal.alert(`Another schema with the name "${ filename }"already exists.`);
                    } else {
                        this.removeSchema(file.name);
                        this.files.push({
                            name: filename,
                            schema: file.schema,
                        });
                        this.selected = filename;
                    }

                })
            },

            deleteFile(file) {
                UIkit.modal.confirm(`Do you really want to delete schema file "${ file.name }"?`).then(() => {
                    this.removeSchema(file.name);
                    if(this.files.length > 0) {
                        this.selected = this.files[0].name;
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