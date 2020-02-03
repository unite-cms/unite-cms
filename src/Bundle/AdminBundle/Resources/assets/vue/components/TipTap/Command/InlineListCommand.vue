<template>
    <div v-if="active" class="button-group">
        <button @click.prevent="toggle" class="uk-button uk-button-link" v-if="active" title="Toggle List type" uk-tooltip><icon name="list" /></button><divider /><button @click.prevent="remove" class="uk-button uk-button-link" v-if="active" title="Remove list type" uk-tooltip><icon name="x" /></button>
    </div>
    <span v-else></span>
</template>

<script>
    import Icon from "../../Icon";
    import { Divider } from "./InlineMenuCommand";

    export default {
        name: "InlineListCommand",
        components: {Icon, Divider},
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
            active() {
                return this.isActive.bullet_list() || this.isActive.ordered_list();
            },
        },
        methods: {
            toggle() {
                if(this.isActive.bullet_list()) {
                    this.editor.commands.ordered_list();
                } else {
                    this.editor.commands.bullet_list();
                }
            },
            remove() {
                if(this.isActive.bullet_list()) {
                    this.editor.commands.bullet_list();
                } else {
                    this.editor.commands.ordered_list();
                }
            },
        }
    }
</script>

<style scoped>

</style>