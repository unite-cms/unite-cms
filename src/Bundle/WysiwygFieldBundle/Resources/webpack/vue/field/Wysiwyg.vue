<template>
    <div>
        <textarea :id="id" :name="name" v-model="content"></textarea>
    </div>
</template>

<script>
    import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

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

            // Create CK Editor.
            ClassicEditor
                .create(this.$el.childNodes[0], { toolbar: this.options.toolbar, heading: { options: this.options.heading }})
                .then(editor => {})
                .catch(error => { console.error(error); })
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
