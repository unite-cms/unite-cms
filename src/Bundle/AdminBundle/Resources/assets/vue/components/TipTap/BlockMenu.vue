<template>
    <div class="tiptap-block-menu" :class="{ active: menuActive || modalOpen }" :style="menuPosition">
        <a href="#" @click.prevent="modalOpen = true" class="uk-icon-button uk-button-light uk-icon-button-small"><icon name="plus" /></a>
        <modal :container="false" v-if="modalOpen" @hide="modalOpen = false">
            <div class="uk-flex uk-flex-center uk-flex-middle uk-flex-wrap" style="min-height: 150px">
                <component :is="command.component || command" :editor="editor" :is-active="isActive" :key="delta" :config="command.config || null" v-for="(command, delta) in commands" @selected="modalOpen = false" />
            </div>
        </modal>
    </div>
</template>

<script>
    import Icon from "../Icon";
    import Modal from "../Modal";
    export default {
        components: {Modal, Icon},
        data() {
            return {
                modalOpen: false
            }
        },
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
                return this.menu.isActive &&

                    // Make sure that we show the menu only for a path full of block nodes
                    this.editor.state.selection.$anchor.path.filter(
                    node => node.type && node.type.name !== 'doc' && node.type.groups.indexOf('block') === -1
                ).length === 0;
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
