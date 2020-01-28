<template>
    <button @click.prevent="onClick" class="uk-button uk-button-link" :class="{ 'active': active }" :title="label" uk-tooltip>
        <icon :name="icon" />
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
            active: () => false,
            label: () => ''
        },
        methods: {
            onClick() {}
        }
    };

    export const BoldCommand = {
        extends: AbstractMenuCommand,
        computed: {
            icon: () => 'bold',
            active() { return this.isActive && this.isActive.bold(); },
            label: () => 'Toggle Bold: TODO translate'
        },
        methods: {
            onClick() { this.editor.commands.bold(); }
        }
    };

    export const ItalicCommand = {
        extends: AbstractMenuCommand,
        computed: {
            icon: () => 'italic',
            active() { return this.isActive && this.isActive.italic(); },
            label: () => 'Toggle Italc: TODO translate'
        },
        methods: {
            onClick() { this.editor.commands.italic(); }
        }
    };

    export default AbstractMenuCommand;
</script>
