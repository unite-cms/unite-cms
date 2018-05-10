<template>
    <div>
        <textarea :name="field_name" v-model="definition"></textarea>
        <div uk-height-viewport offset-top="true" :id="editor_id"></div>
    </div>
</template>

<script>
    import ace from 'brace';
    import 'brace/mode/json';
    import 'brace/theme/monokai';
    import 'brace/ext/language_tools';
    import 'brace/snippets/json';

    export default {
        data() {
            return {
                field_name: this.name,
                editor_id: 'domain-editor' + this._uid,
                definition: this.value
            };
        },
        props: [
            'name',
            'value'
        ],
        mounted: function() {
            this.editor = ace.edit(this.editor_id);
            this.editor.getSession().setMode('ace/mode/json');
            this.editor.setTheme('ace/theme/monokai');
            this.editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true,
                highlightActiveLine: false,
                tabSize: 2,
                useSoftTabs: true
            });

            this.editor.$blockScrolling = 'Infinity';

            this.editor.getSession().setValue(JSON.stringify(JSON.parse(this.definition), null, 2));
            this.editor.getSession().on('change', () => {
                this.definition = this.editor.getSession().getValue();
            });

            // For the moment we use a simple selector to enable / disable form submit, this should be refactored!
            let submitButtons = this.$el.closest('form').querySelector('*[type="submit"]');
            this.editor.getSession().on('changeAnnotation', () => {
                if(this.editor.getSession().getAnnotations().filter((a) => {
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

        methods: {
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
</style>