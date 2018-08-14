<template>
    <article v-if="active" class="uk-placeholder uk-padding-small">
        <header class="uk-flex">
            <a class="clear" v-on:click="clear()"><i data-feather="arrow-left" w="20" h="20"></i></a>
            <span>{{ title }}</span>
        </header>
        <div class="variants-variant-content" v-html="content"></div>
    </article>
</template>
<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
                active: false
            };
        },
        created() {
            window.UniteCMSEventBus.$on('variantsChanged', (data) => {
                if(data.input === this.input) {
                    this.active = (data.variant === this.value);
                }
            });
        },
        methods: {
            clear(){
                window.UniteCMSEventBus.$emit('variantsChanged', {
                    input: this.input,
                    variant: null
                });
            }
        },
        props: ['input', 'value', 'title', 'content'],
        watch: {
            active(value) {
                if(value) {
                    setTimeout(()=> {
                        feather.replace();
                    }, 2);
                }
            }
        }
    };
</script>