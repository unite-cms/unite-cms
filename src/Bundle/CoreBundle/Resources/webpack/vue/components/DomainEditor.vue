<template>
    <div>
        <textarea v-if="!is_disabled" :name="field_name" v-model="config"></textarea>
        <input type="hidden" v-if="is_disabled" :name="field_name" v-model="config" />

        <div v-if="diffValue && !is_disabled" class="uk-flex">
            <h4 class="uk-width-1-2">Actual config</h4>
            <h4 class="uk-width-1-2" style="padding-left: 30px;">Filesystem config</h4>
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
                feather: feather
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
                this.editors = [];

                if(!this.diffValue) {
                    let editor = ace.edit(this.editor_id + '_config');
                    editor.getSession().setValue(this.normalizeValue(this.value));
                    this.configureEditor(editor, true);
                }
                else {
                    let aceDiff = new AceDiff({
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

                    this.configureEditor(aceDiff.editors.left.ace, true);
                    this.configureEditor(aceDiff.editors.right.ace);
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
            configureEditor(editor, bindToConfig) {
                console.log(editor);
                editor.getSession().setMode('ace/mode/json');
                editor.setTheme('ace/theme/monokai');
                editor.setOptions({
                    enableBasicAutocompletion: true,
                    enableSnippets: true,
                    highlightActiveLine: false,
                    tabSize: 2,
                    useSoftTabs: true,
                    //enableLiveAutocompletion: true,
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

                    // This is not working at the moment.
                    /*let langTools = ace.acequire("ace/ext/language_tools");
                    langTools.addCompleter({
                        getCompletions: this.autoCompleter
                    });*/
                }
            }/*,
            autoCompleter: (editor, session, pos, prefix, callback) => {
                if (prefix.length === 0) {
                    callback(null, []);
                    return
                }

                let fields = [
                    {name: "title", value: "title", score: 300, meta: "rhyme"},
                    {name: "identifier", value: "identifier", score: 300, meta: "rhyme"},
                    {name: "fields", value: "fields", score: 300, meta: "rhyme"}
                ];

                let selection = [];
                for (let i = 0; i < fields.length; i++) {
                    if (fields[i].name.toLowerCase().indexOf(prefix.toLowerCase()) !== -1) {
                        selection.push(fields[i]);
                    }
                }

                callback(null, selection);
            }*/
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

    h4 {
        margin: 0;
        background: #2F3129;
        color: #ddd;
        padding: 5px 10px;
        font-size: 13px;
        text-transform: uppercase;

        span {
            display: inline-block;
            vertical-align: middle;
            margin-top: -3px;
        }
    }
</style>