<template>
    <button @click.prevent="onClick" class="uk-button tiptap-block-menu-command" :class="{ 'active': active }" :title="description" uk-tooltip>
        <icon :name="icon" :width="20" :height="20" /><br />
        {{ label }}
    </button>
</template>

<script>
    import Icon from "../Icon";
    export const AbstractMenuCommand = {
        components: {Icon},
        props: {
            editor: {
                type: Object,
            },
            isActive: {
                type: Object,
                default: () => {},
            }
        },
        computed: {
            icon: () => 'star',
            description:() => '',
            active: () => false,
            label: () => ''
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
            description: () => 'TODO paragraph'
        },
        methods: {
            onClick() {
                this.editor.commands.paragraph();
                this.$emit('selected');
            }
        }
    };

    export const HeadingCommand = {
        extends: AbstractMenuCommand,
        computed: {
            icon: () => 'feather',
            label: () => 'Heading',
            description: () => 'TODO headline'
        },
        methods: {
            onClick() {
                this.editor.commands.heading({ level: 1 });
                this.$emit('selected');
            }
        }
    };

    export default AbstractMenuCommand;
</script>
