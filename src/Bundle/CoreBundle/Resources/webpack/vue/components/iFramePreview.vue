<template>
    <section class="uk-card uk-card-default no-padding uk-flex uk-flex-column" v-bind:style="{ width: width + 'px' }">
        <header class="uk-card-header uk-text-center">
            <span class="move"></span>
            <button v-on:click="changeSize(320)" v-html="feather.icons['smartphone'].toSvg({ width: 18, height: 18 })"></button>
            <button v-on:click="changeSize(768)" v-html="feather.icons['tablet'].toSvg({ width: 18, height: 18 })"></button>
            <button v-on:click="changeSize(1200)" v-html="feather.icons['monitor'].toSvg({ width: 18, height: 18 })"></button>
            <button class="close" v-html="feather.icons['x'].toSvg({ width: 24, height: 24 })"></button>
        </header>
        <div class="uk-flex-1">
            <iframe v-bind:src="previewUrl"></iframe>
        </div>
    </section>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        name: "iFramePreview",

        data() {
            return {
                previewUrl: this.url,
                width: 0,
                minWidth: 250,
                feather: feather
            };
        },
        props: [
            'url',
        ],
        watch: {
            width: function(width) {
                if(width < this.minWidth) { this.width = this.minWidth; }
                if(width > this.mainSection.clientWidth) { this.width = this.mainSection.clientWidth; }

                if(this.mainSection) {
                    this.mainSection.style.marginRight = width + 'px';
                }
            }
        },
        mounted: function() {
            let findMainSection = function(parent){
                if(!parent) { return null; }
                if(parent.classList.contains('uk-card')) { return parent; }
                return findMainSection(parent.parentElement);
            };
            this.mainSection = findMainSection(this.$el.parentElement);
            if(this.mainSection) {
                this.mainSection.classList.add('iframe-preview-parent');
                this.mainSection.classList.add('always-full');
            }

            this.width = 50;
            this.iFrame = this.$el.querySelector('iframe');
        },
        methods: {
            changeSize: function(size){

                // TODO: Change Size.
                console.log("TODO: Change Size to: " + size);

                // Reload
                let oldUrl = this.previewUrl;
                this.previewUrl += '';
                setTimeout(() => { this.previewUrl = oldUrl; }, 5);
            }
        }
    }
</script>

<style lang="scss" scoped>

    @import "../../sass/base/variables";

    :scope {
        display: block;
        width: 100%;
    }

    .uk-card {
        overflow: hidden;
        border-radius: 3px;
        background: map-get($colors, grey-very-dark);
    }

    header {
        position: relative;

        button {
            cursor: pointer;
            opacity: 0.3;
            background: none;
            border: none;

            &:hover {
                opacity: 0.75;
            }

            &.active {
                opacity: 1;
            }
        }

        .move {
            position: absolute;
            left: 0;
            width: 24px;
            top: 0;
            bottom: 0;
            display: block;
            cursor: move;

            &:before {
                content: "";
                position: absolute;
                display: block;
                height: 30px;
                width: 2px;
                border-radius: 2px;
                top: 50%;
                left: 50%;
                margin-top: -15px;
                margin-left: -1px;
                background: map-get($colors, grey);
            }

            &:hover:before {
                background-color: map-get($colors, grey-dark);
            }
        }

        .close {
            width: 50px;
            height: 50px;
            position: absolute;
            right: 0;
            top: 50%;
            margin-top: -25px;
            padding: 0;
            color: map-get($colors, red);
            opacity: 1;
        }
    }

    iframe {
        width: 100%;
        height: 100%;
    }

</style>