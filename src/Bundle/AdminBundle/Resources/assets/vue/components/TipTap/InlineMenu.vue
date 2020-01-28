<template>
    <div class="tiptap-inline-menu" :class="{ active: menuActive }" :style="menuPosition">
        <component :is="command" :editor="editor" :is-active="isActive" :key="delta" v-for="(command, delta) in commands" />
    </div>
</template>

<script>
    import Icon from "../Icon";
    export default {
        components: {Icon},
        props: {
            editor: {
                type: Object,
            },
            menu: {
                type: Object,
                default: () => {},
            },
            commands: {
                type: Array,
                default: () => [],
            },
            isActive: {
                type: Object,
                default: () => {},
            }
        },
        computed: {
            menuActive() {
                return this.menu.isActive;
            },
            menuPosition() {
                return {
                    left: `${this.menu.left}px`,
                    top: `${this.menu.top}px`,
                }
            }
        }
    }
</script>
