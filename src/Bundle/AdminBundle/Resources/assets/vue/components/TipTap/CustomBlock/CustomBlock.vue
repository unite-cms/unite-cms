<template>
    <div class="tiptap-custom-unite-block" :class="{'with-children': allowChildren}">
        <div class="content" ref="content" v-if="allowChildren"></div>
        <div class="description"><icon :name="icon" :width="24" :height="24" /><br /><span class="uk-text-meta">{{ label }}</span></div>
        <template v-if="isFieldable">
            <button class="configure-icon uk-button uk-button-link" @click.prevent="openConfigModal"><icon name="settings" /></button>
            <div ref="configCanvas" uk-offcanvas="flip: true; mode: push; overlay: true; container: #app" @hide="onConfigModalHide">
                <div class="uk-offcanvas-bar">
                    <button class="uk-offcanvas-close" type="button" uk-close></button>

                    <h3>Configure</h3>
                    <form @submit.prevent="saveConfig">
                        <form-fields :view="adminView" :form-data="options" :root-form-data="options" @input="data => options = data" />
                        <button class="uk-button uk-button-primary">Save</button>
                    </form>
                </div>
            </div>
        </template>
    </div>
</template>

<script>

    import UIkit from 'uikit';
    import Icon from "../../Icon";
    import { allowChildren, isFieldable } from "./CustomBlockNode";
    import FormFields from "../../Form/_formFields";

    export default {
        name: "CustomBlock",
        props: ['node', 'updateAttrs', 'view'],
        components: {FormFields, Icon},
        data() {
            return {
                options: {}
            }
        },
        computed: {
            customType() {},
            adminView() {
                let views = Object.values(this.$unite.adminViews).filter((adminView) => {
                    return adminView.type === this.customType.name;
                });
                return views.length > 0 ? views[0] : null;
            },
            icon() {
                return this.adminView && this.adminView.icon || 'square';
            },
            label() {
                return this.adminView ? this.adminView.name : this.customType.name;
            },
            description() {
                if(!this.config.type.description) {
                    return '';
                }
                let parts = this.config.type.description.split("\n");
                return parts.length > 1 ? parts[1] : '';
            },
            allowChildren() {
                return allowChildren(this.customType);
            },
            isFieldable() {
                return isFieldable(this.customType);
            }
        },
        methods: {
            openConfigModal() {
                UIkit.offcanvas(this.$refs.configCanvas).show();
            },
            onConfigModalHide(event) {
                if(event.target === this.$refs.configCanvas) {
                    this.options = Object.assign({}, this.node.attrs);
                }
            },
            saveConfig() {
                this.updateAttrs(Object.assign({}, this.options));
                UIkit.offcanvas(this.$refs.configCanvas).hide();
            }
        }
    }
</script>

<style scoped>

</style>