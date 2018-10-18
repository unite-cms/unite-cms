<template>
    <div>
        <textarea v-if="!is_disabled" :name="field_name" v-model="config"></textarea>
        <input type="hidden" v-if="is_disabled" :name="field_name" v-model="config" />
        <div class="uk-flex" v-if="!is_disabled">
            <div class="uk-width-1-1">
                <div uk-height-viewport offset-top="true" :id="editor_id + '_config'"></div>
            </div>
        </div>
    </div>
</template>

<script>
    import ace from 'brace';
    import aceDiff from 'brace-diff';
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
                feather: feather
            };
        },
        props: [
            'name',
            'value',
            'disabled',
        ],
        mounted() {
            if(!this.is_disabled) {
                this.editors = [];
                this.createEditorInstance("config")
            }
        },

        methods: {
            createEditorInstance(name) {
                this.editors[name] = ace.edit(this.editor_id + '_' + name);
                this.editors[name].getSession().setMode('ace/mode/json');
                this.editors[name].setTheme('ace/theme/monokai');
                this.editors[name].setOptions({
                    enableBasicAutocompletion: true,
                    enableSnippets: true,
                    enableLiveAutocompletion: true,
                    highlightActiveLine: false,
                    tabSize: 2,
                    useSoftTabs: true
                });

                this.editors[name].$blockScrolling = 'Infinity';
                this.editors[name].getSession().setValue(JSON.stringify(JSON.parse(this[name]), null, 2));
                this.editors[name].getSession().on('change', () => {
                    this[name] = this.editors[name].getSession().getValue();
                });

                // For the moment we use a simple selector to enable / disable form submit, this should be refactored!
                let submitButtons = this.$el.closest('form').querySelector('*[type="submit"]');
                this.editors[name].getSession().on('changeAnnotation', () => {
                    if (this.editors[name].getSession().getAnnotations().filter((a) => {
                        return a.type === 'error';
                    }).length > 0) {
                        submitButtons.setAttribute('disabled', 'disabled');
                    } else {
                        submitButtons.removeAttribute('disabled');
                    }
                });

                let langTools = ace.acequire("ace/ext/language_tools");
                langTools.addCompleter({
                    getCompletions: this.autoCompleter
                });
            },
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