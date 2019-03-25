<template>
    <div>
        <textarea v-if="!is_disabled" :name="field_name" v-model="config"></textarea>
        <input type="hidden" v-if="is_disabled" :name="field_name" v-model="config" />
        <div style="position: relative" v-if="!is_disabled" class="uk-flex">
            <div class="uk-width-1-1">
                <div uk-height-viewport offset-top="true" :id="editor_id + '_config'"></div>
            </div>
        </div>
    </div>
</template>

<script>
    import ace from 'brace';
    import 'brace/mode/json';
    import 'brace/theme/monokai';
    import 'brace/ext/language_tools';
    import 'brace/ext/searchbox';
    import 'brace/snippets/json';

    import feather from 'feather-icons';

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
            'disabled',
        ],
        mounted() {
            if(!this.is_disabled) {
                let editor = ace.edit(this.editor_id + '_config');
                editor.getSession().setValue(this.normalizeValue(this.value));
                this.configureEditor(editor, true);
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