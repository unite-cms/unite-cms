<template>
  <li>
    <a class="menu-item-button uk-icon" :class="{ 'uk-active': active, }" @click.prevent="onClick"><icon :name="icon" /></a>
  </li>
</template>
<script>
    import Icon from "../../Icon";

    export const basicMenuItem = {
        components: { Icon },
        props: {
            commands: Object,
            isActive: Object,
            field: Object,
        },
        computed: {
            icon() {
                return 'square';
            },
            command() {
                return null;
            },
            active() {
                return this.command ? this.isActive[this.command]() : false;
            }
        },
        methods: {
            onClick() {
                if(!this.command) {
                    return;
                }
                this.commands[this.command]();
            }
        }
    };

    export const createGenericMenuItem = function(command, icon){
        return {
            extends: basicMenuItem,
            computed: {
                icon() { return icon; },
                command() { return command; },
            }
        }
    };

    export default basicMenuItem;

</script>
<style scoped lang="scss">
  .menu-item-button {
    padding: 5px;
    margin: 15px 0 3px 0;

    &.uk.active {
      color: rgba(0,0,0,0.8);
    }
    &.uk.active,
    &:hover {
      background: rgba(0,0,0,0.10);
    }
  }
</style>

