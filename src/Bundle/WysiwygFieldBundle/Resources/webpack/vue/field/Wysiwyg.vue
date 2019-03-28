<template>
    <div>
        <textarea class="placeholder_textarea" :id="id" :name="name" v-model="content"></textarea>
        <ckeditor :editor="editor" v-model="content" :config="editorConfig"></ckeditor>
    </div>
</template>

<script>
    import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
    import Highlight from '@ckeditor/ckeditor5-highlight/src/highlight';
    import Alignment from '@ckeditor/ckeditor5-alignment/src/alignment';

    export default {
        data: function() {
            let options = JSON.parse(this.dataOptions);
            return {
                content: this.value,
                editor: ClassicEditor,
                editorConfig: {
                    plugins: ClassicEditor.builtinPlugins.filter((plugin) => { return [
                        'Essentials',
                        'Bold',
                        'Italic',
                        'BlockQuote',
                        'Heading',
                        'Link',
                        'List',
                        'Paragraph',
                        'Table',
                    ].indexOf(plugin.pluginName) !== -1; }).concat([ Highlight, Alignment ]),
                    toolbar: options.toolbar,
                    heading: { options: options.heading }
                }
            }
        },
        props: [
            'value',
            'dataOptions',
            'id',
            'name'
        ]
    };
</script>

<style lang="scss">
    @import "../../../../../CoreBundle/Resources/webpack/sass/base/variables";

    unite-cms-wysiwyg-field {
        display: block;
        margin: 5px 0;

        .placeholder_textarea {
            display: none;
        }

        .ck.ck-editor__editable:not(.ck-editor__nested-editable) {
            padding: 0 15px;
            &.ck-focused {
                box-shadow: none;
                border: 1px solid map-get($colors, grey-dark);
            }
        }

        .ck .ck-insert-table-dropdown__grid {
            width: calc(var(--ck-insert-table-dropdown-box-width)*10 + var(--ck-insert-table-dropdown-box-margin)*20 + var(--ck-insert-table-dropdown-padding)*2);
        }

        .ck .ck-insert-table-dropdown-grid-box {
            width: var(--ck-insert-table-dropdown-box-width);
            height: var(--ck-insert-table-dropdown-box-height);
        }
        .ck.ck-icon {
            width: var(--ck-icon-size);
            height: var(--ck-icon-size);
        }
    }
</style>
