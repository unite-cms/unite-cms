<template>
    <div>
        <textarea :id="id" :name="name" v-model="content"></textarea>
    </div>
</template>

<script>
    import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
    import AutoSave from '@ckeditor/ckeditor5-autosave/src/autosave';

    export default {
        data: function() {
            return {
                'options': JSON.parse(this.dataOptions),
                'content': this.value
            }
        },
        props: [
            'value',
            'dataOptions',
            'id',
            'name'
        ],
        mounted () {

            let textarea = this.$el.childNodes[0];

            let plugins = ClassicEditor.builtinPlugins.filter((plugin) => { return [
                'Essentials',
                //'Autoformat',
                'Bold',
                'Italic',
                'BlockQuote',
                'Heading',
                'Link',
                'List',
                'Paragraph',
            ].indexOf(plugin.pluginName) !== -1; }).concat([ AutoSave ]);

            // Create CK Editor.
            ClassicEditor
                .create(textarea, {
                    plugins: plugins,
                    toolbar: this.options.toolbar,
                    heading: { options: this.options.heading },
                    autosave: {
                        save: ( editor ) => {
                            textarea.value = editor.getData();
                            textarea.dispatchEvent(new Event('change'));
                        }
                    }
                }).then(editor => {}).catch(error => { console.error(error); })
        }
    };
</script>

<style lang="scss">
    @import "../../../../../CoreBundle/Resources/webpack/sass/base/variables";

    unite-cms-wysiwyg-field {
        display: block;
        margin: 5px 0;

        .ck.ck-editor__editable:not(.ck-editor__nested-editable) {
            padding: 0 15px;
            &.ck-focused {
                box-shadow: none;
                border: 1px solid map-get($colors, grey-dark);
            }
        }
    }
</style>
