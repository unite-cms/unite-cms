<template>
  <span class="uk-icon" v-html="svg"></span>
</template>

<script>
    import feather from 'feather-icons';

    export default {
        name: "Icon",
        props: {
            name: String,
            width: {
                type: Number,
                default: 16
            },
            height: {
                type: Number,
                default: 16
            }
        },
        computed: {
            svg() {
                return feather.icons[this.name].toSvg({
                    width: this.width,
                    height: this.height
                });
            }
        }
    }
</script>
