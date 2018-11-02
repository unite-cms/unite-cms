<template>
    <div>
        <textarea v-if="!is_disabled" :name="field_name" v-model="config"></textarea>
        <input type="hidden" v-if="is_disabled" :name="field_name" v-model="config" />

        <div v-if="diffValue && !is_disabled" class="uk-flex headlines-header">
            <h4 class="uk-width-1-2 uk-flex"><span class="uk-flex-1">Actual config</span></h4>
            <h4 class="uk-width-1-2 uk-flex">
                <span class="uk-flex-1">Filesystem config</span>
                <button v-on:click.prevent="takeOverAllChanges" class="uk-flex-0 uk-button uk-button-small">Take over all changes</button>
            </h4>
        </div>
        <div style="position: relative" v-if="!is_disabled" class="uk-flex">
            <div class="uk-width-1-1">
                <div uk-height-viewport offset-top="true" :id="editor_id + '_config'"></div>
            </div>
        </div>
    </div>
</template>

<script>
    import ace from 'brace';
    import AceDiff from 'ace-diff';
    import 'brace/mode/json';
    import 'brace/theme/monokai';
    import 'brace/ext/language_tools';
    import 'brace/ext/searchbox';
    import 'brace/snippets/json';

    import feather from 'feather-icons';
    import 'ace-diff/dist/ace-diff-dark.min.css';

    export default {
        data() {
            return {
                field_name: this.name,
                editor_id: 'domain-editor' + this._uid,
                config: this.value,
                is_disabled: this.disabled,
                feather: feather,
                aceDiff: null
            };
        },
        props: [
            'name',
            'value',
            'diffValue',
            'disabled',
        ],
        mounted() {
            if(!this.is_disabled) {
                if(!this.diffValue) {
                    let editor = ace.edit(this.editor_id + '_config');
                    editor.getSession().setValue(this.normalizeValue(this.value));
                    this.configureEditor(editor, true);
                }
                else {
                    this.aceDiff = new AceDiff({
                        element: '#' + this.editor_id + '_config',
                        left: {
                            content: this.normalizeValue(this.value, true),
                            copyLinkEnabled: false,
                        },
                        right: {
                            content: this.normalizeValue(this.diffValue, true),
                            editable: false,
                            copyLinkEnabled: true,
                        },
                    });

                    this.configureEditor(this.aceDiff.editors.left.ace, true);
                    this.configureEditor(this.aceDiff.editors.right.ace);
                }
            }
        },

        methods: {
            normalizeValue(value, diffOptimized = false) {
                value = JSON.stringify(JSON.parse(value), null, 2);

                if(diffOptimized) {
                    value = value.replace(/^( *)(.*\[)(\],*)$/gm, "$1$2\n$1$3");
                    value = value.replace(/^( *)(.*\{)(\},*)$/gm, "$1$2\n$1$3");
                }

                return value;
            },
            takeOverAllChanges() {
                if(this.aceDiff) {
                    this.aceDiff.editors.left.ace.getSession().setValue(this.aceDiff.editors.right.ace.getSession().getValue());
                }
            },
            configureEditor(editor, bindToConfig) {
                editor.getSession().setMode('ace/mode/json');
                editor.setTheme('ace/theme/monokai');
                editor.setOptions({
                    enableBasicAutocompletion: true,
                    enableSnippets: true,
                    highlightActiveLine: false,
                    tabSize: 2,
                    useSoftTabs: true,
                });

                editor.$blockScrolling = 'Infinity';

                if(bindToConfig) {
                    editor.getSession().on('change', () => {
                        this.config = editor.getSession().getValue();
                    });

                    // For the moment we use a simple selector to enable / disable form submit, this should be refactored!
                    let submitButtons = this.$el.closest('form').querySelector('*[type="submit"]');
                    editor.getSession().on('changeAnnotation', () => {
                        if (editor.getSession().getAnnotations().filter((a) => {
                            return a.type === 'error';
                        }).length > 0) {
                            submitButtons.setAttribute('disabled', 'disabled');
                        } else {
                            submitButtons.removeAttribute('disabled');
                        }
                    });
                }
            }
        }
    };
</script>

<style lang="scss" scoped>
    textarea {
        display: none;
    }

    .uk-width-2-3 {
        padding-right: 1px;
    }

    .headlines-header {
        background: #2F3129;
    }

    h4 {
        width: calc(50% - 30px);
        margin: 0 30px 0 0;
        padding: 5px 5px 5px 10px;
        font-size: 13px;
        text-transform: uppercase;
        align-items: center;
        border-right: 1px solid #000;
        color: #ddd;

        &:last-child {
            margin-right: 0;
            margin-left: 30px;
            border-right: none;
            border-left: 1px solid #000;
        }

        .uk-button.uk-button-small {
            background: none;
            border: 1px solid #fff;
            text-transform: unset;
            font-size: 12px;
            padding: 0 10px;
            line-height: 20px;
            color: #fff;

            &:hover {
                background: #fff;
                color: #2F3129;
            }
        }
    }
</style>