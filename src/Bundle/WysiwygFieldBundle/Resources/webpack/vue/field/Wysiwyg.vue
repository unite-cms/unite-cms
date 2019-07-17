<template>
    <div>
        <textarea class="placeholder_textarea" :id="id" :name="name" v-model="content"></textarea>
        <ckeditor :editor="editor" v-model="content" :config="editorConfig"></ckeditor>
    </div>
</template>

<script>

    import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor';
    import EssentialsPlugin from '@ckeditor/ckeditor5-essentials/src/essentials';
    import BoldPlugin from '@ckeditor/ckeditor5-basic-styles/src/bold';
    import ItalicPlugin from '@ckeditor/ckeditor5-basic-styles/src/italic';
    import BlockQuotePlugin from '@ckeditor/ckeditor5-block-quote/src/blockquote';
    import HeadingPlugin from '@ckeditor/ckeditor5-heading/src/heading';
    import LinkPlugin from '@ckeditor/ckeditor5-link/src/link';
    import ListPlugin from '@ckeditor/ckeditor5-list/src/list';
    import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph';
    import TablePlugin from '@ckeditor/ckeditor5-table/src/table';
    import HighlightPlugin from '@ckeditor/ckeditor5-highlight/src/highlight';
    import AlignmentPlugin from '@ckeditor/ckeditor5-alignment/src/alignment';
    import MediaEmbedPlugin from '@ckeditor/ckeditor5-media-embed/src/mediaembed';

    export default {
        data: function() {
            let options = JSON.parse(this.dataOptions);
            return {
                content: this.value,
                editor: ClassicEditor,
                editorConfig: {
                    plugins: [
                        EssentialsPlugin,
                        BoldPlugin,
                        ItalicPlugin,
                        BlockQuotePlugin,
                        HeadingPlugin,
                        LinkPlugin,
                        ListPlugin,
                        TablePlugin,
                        ParagraphPlugin,
                        HighlightPlugin,
                        AlignmentPlugin,
                        MediaEmbedPlugin,
                    ],
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
    }
</style>
