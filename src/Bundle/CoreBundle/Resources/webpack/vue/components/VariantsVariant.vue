<template>
    <article v-if="active" class="uk-placeholder uk-padding-small" :class="{ collapsed: collapsed }">
        <header class="uk-flex">
            <span class="uk-margin-small-right" v-if="icon" v-html="feather.icons[icon].toSvg({ width: 18, height: 18 })"></span>
            <span class="uk-flex-1">{{ title }}</span>
            <a v-show="!collapsed" class="clear" v-on:click="clear()"><i data-feather="x" w="20" h="20"></i></a>
            <a class="collapse" v-on:click="collapsed = !collapsed" v-html="feather.icons[collapsed ? 'edit-2' : 'check'].toSvg({ width: 20, height: 20 })"></a>
        </header>
        <div v-show="!collapsed" class="variants-variant-content" v-html="content"></div>
    </article>
</template>
<script>

    import feather from 'feather-icons';
    import UIkit from 'uikit';

    export default {
        data() {
            return {
                active: false,
                collapsed: false,
                feather: feather
            };
        },
        created() {
            window.UniteCMSEventBus.$on('variantsChanged', (data) => {
                if(data.input === this.input) {
                    this.active = (data.variant === this.value);
                }
            });
            window.UniteCMSEventBus.$on('variantsShouldCollapse', (data) => {
                if(this.active && (!data.parent || data.parent.contains(this.$el))) {
                    this.collapsed = true;
                }
            });
        },
        methods: {
            clear(){
                UIkit.modal.confirm('Do you really want to remove the current variant and select another one?').then(() => {
                    window.UniteCMSEventBus.$emit('variantsChanged', {
                        input: this.input,
                        variant: null
                    });
                }, () => {});
            }
        },
        props: ['input', 'value', 'title', 'content', 'icon'],
        watch: {
            active(value) {
                if(value) {
                    setTimeout(()=> {
                        feather.replace();

                        // When variant was selected, enable all disabled input elements if they are not disabled intentionally.
                        this.$el.querySelectorAll('input, textarea, select').forEach((element) => {
                            if(!element.classList.contains('disabled')) {
                                element.disabled = false;
                            }
                        });

                    }, 2);
                }
            }
        }
    };
</script>