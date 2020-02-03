<template>
    <button @click.prevent="onClick" class="uk-button tiptap-block-menu-command" :class="{ 'active': active }" :title="description" uk-tooltip :disabled="disabled">
        <icon :name="icon" :width="20" :height="20" /><br />
        {{ label }}
    </button>
</template>

<script>

    import Icon from "../../Icon";
    export const AbstractMenuCommand = {
        components: {Icon},
        props: {
            editor: {
                type: Object,
            },
            isActive: {
                type: Object,
                default: () => {},
            },
            config: {
                type: Object
            }
        },
        computed: {
            icon: () => 'star',
            description:() => '',
            active: () => false,
            disabled: () => false,
            label: () => '',
        },
        methods: {
            onClick() {}
        }
    };

    export const ParagraphCommand = {
        extends: AbstractMenuCommand,
        computed: {
            icon: () => 'feather',
            label: () => 'Paragraph',
            description: () => 'TODO Paragraph',
        },
        methods: {
            onClick() {
                this.editor.commands.paragraph();
                this.$emit('selected');
            }
        }
    };

    export const BlockQuoteCommand = {
        extends: AbstractMenuCommand,
        computed: {
            icon: () => 'message-square',
            label: () => 'Blockquote',
            description: () => 'TODO Blockquote',
        },
        methods: {
            onClick() {
                this.editor.commands.blockquote();
                this.$emit('selected');
            }
        }
    };

    export const CodeBlockCommand = {
        extends: AbstractMenuCommand,
        computed: {
            icon: () => 'code',
            label: () => 'Code Block',
            description: () => 'TODO Code Block',
        },
        methods: {
            onClick() {
                this.editor.commands.code_block();
                this.$emit('selected');
            }
        }
    };

    export const OrderedListCommand = {
        extends: AbstractMenuCommand,
        computed: {
            icon: () => 'list',
            label: () => 'Ordered List',
            description: () => 'TODO Ordered List',
        },
        methods: {
            onClick() {
                this.editor.commands.ordered_list();
                this.$emit('selected');
            }
        }
    };

    export const BulletListCommand = {
        extends: AbstractMenuCommand,
        computed: {
            icon: () => 'list',
            label: () => 'Bullet List',
            description: () => 'TODO Bullet List',
        },
        methods: {
            onClick() {
                this.editor.commands.bullet_list();
                this.$emit('selected');
            }
        }
    };

    export default AbstractMenuCommand;
</script>
