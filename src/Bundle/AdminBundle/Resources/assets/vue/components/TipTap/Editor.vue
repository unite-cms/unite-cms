<template>
    <div class="tiptap-editor" :class="{ fullscreen: fullscreen }">
        <a class="fullscreen-toggle" href="#" @click.prevent="fullscreen = !fullscreen"><icon :name="fullscreen ? 'x-circle' : 'maximize-2'" /></a>
        <editor-floating-menu :editor="editor" v-slot="{ isActive, menu }">
            <block-menu :commands="blockCommands" :editor="editor" :menu="menu" :is-active="isActive" />
        </editor-floating-menu>

        <editor-menu-bubble :editor="editor" v-slot="{ isActive, menu }" :keep-in-bounds="true">
            <inline-menu :commands="inlineCommands" :editor="editor" :menu="menu" :is-active="isActive" />
        </editor-menu-bubble>

        <editor-content class="editor-wrapper" :editor="editor" />
    </div>
</template>

<script>
    import Icon from "../Icon";
    import { Editor, EditorContent, EditorFloatingMenu, EditorMenuBubble } from 'tiptap';
    import InlineMenu from "./InlineMenu";
    import BlockMenu from "./BlockMenu";

    export default {
        components: {Icon, BlockMenu, InlineMenu, EditorContent, EditorFloatingMenu, EditorMenuBubble},
        props: {
            value: Object,
            extensions: {
                type: Array,
                default: () => [],
            },
            inlineCommands: {
                type: Array,
                default: () => [],
            },
            blockCommands: {
                type: Array,
                default: () => [],
            },
            attr: Object,
        },
        data() {
            return {
                fullscreen: false,
                editor: null,
            }
        },
        mounted() {
            let content = '';

            if(this.value) {
                let content = this.value.HTML;
                if (!content && this.value.JSON) {
                    content = typeof this.value.JSON === "string" ? JSON.parse(this.value.JSON) : this.value.JSON;
                }
            }

            this.editor = new Editor({
                extensions: this.extensions,
                onUpdate: ( { state, getHTML, getJSON, transaction } ) => {
                    this.$emit('input', {
                        HTML: getHTML(),
                        JSON: getJSON()
                    });
                },
                editorProps: {
                    content: content,
                    attributes: this.attr
                },
            });
        },
        beforeDestroy() {
            this.editor.destroy();
        },
    }
</script>
