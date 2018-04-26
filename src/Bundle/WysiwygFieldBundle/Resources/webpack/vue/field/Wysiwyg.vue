<template>
    <div>
        <quill-editor v-model="content" :ref="id" :options="options">
        </quill-editor>
        <input type="hidden" :name="name" :value="content" />
    </div>
</template>

<script>
    import { quillEditor } from 'vue-quill-editor';

    export default {
        data: function() {
            return {
                'content': this.value,
                'options': JSON.parse(this.dataOptions),
            }
        },
        props: [
            'value',
            'dataOptions',
            'id',
            'name'
        ],
        methods: {

        },
        components: {
            quillEditor
        }
    };
</script>

<style lang="scss">
    @import "../../../../node_modules/quill/dist/quill.core.css";
    @import "../../../../node_modules/quill/dist/quill.snow.css";
    @import "../../../../node_modules/quill/dist/quill.bubble.css";
    @import "../../../../../CoreBundle/Resources/webpack/sass/base/variables";

    unite-cms-wysiwyg-field {
        display: block;
        margin: 5px 0;

        .ql-editor {
            background: map-get($colors, white);
        }

        .ql-bubble {
            border: 1px solid map-get($colors, grey-medium);
        }
    }
</style>
