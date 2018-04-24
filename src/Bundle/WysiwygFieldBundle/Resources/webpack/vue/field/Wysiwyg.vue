<template>
    <div>
        <quill-editor v-model="value" :ref="id" :options="options">
        </quill-editor>
        <input type="hidden" :name="name" :value="value" />
    </div>
</template>

<script>
    import feather from 'feather-icons';
    import Quill from 'quill';
    import { quillEditor } from 'vue-quill-editor';

    export default {
        props: [
            'value',
            'options',
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
    }
</style>
