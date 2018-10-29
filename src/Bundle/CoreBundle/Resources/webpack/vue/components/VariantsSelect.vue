<template>
    <div v-show="!variant" v-html="content"></div>
</template>
<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
                variant: null
            };
        },
        props: ['input', 'content'],
        watch: {
            variant(value) {
                window.UniteCMSEventBus.$emit('variantsChanged', {
                    input: this.input,
                    variant: this.variant
                });
            }
        },
        created() {
            window.UniteCMSEventBus.$on('variantsChanged', (data) => {
                if(data.input === this.input && !data.variant) {
                    this.variant = null;

                    this.$el.querySelectorAll('*[name="'+this.input+'"]').forEach((input) => {
                        if(input.checked) {
                            input.checked = false;
                        }
                    });
                }
            });
        },
        mounted() {
            this.$el.querySelectorAll('*[name="'+this.input+'"]').forEach((input) => {
                input.addEventListener('change', (event) => {
                    if(event.srcElement.checked) {
                        this.variant = event.srcElement.value;
                    }
                });

                if(input.checked) {
                    setTimeout(()=>{
                        this.variant = input.value;
                    }, 2);
                }
            });
        }
    };
</script>