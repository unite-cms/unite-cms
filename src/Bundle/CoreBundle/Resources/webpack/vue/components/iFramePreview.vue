<template>
    <div>
        <div v-if="active" class="iframe-wrapper">
            <section class="uk-card uk-card-default no-padding uk-flex uk-flex-column" v-bind:style="{ width: width + 'px' }">
                <header class="uk-card-header uk-text-center">
                    <span @mousedown.stop.prevent="startResize($event)" @touchstart.stop.prevent="startResize($event)" class="move uk-drag"></span>
                    <button v-on:click="changeSize(320)" v-html="feather.icons['smartphone'].toSvg({ width: 18, height: 18 })"></button>
                    <button v-on:click="changeSize(768)" v-html="feather.icons['tablet'].toSvg({ width: 18, height: 18 })"></button>
                    <button v-on:click="changeSize(1200)" v-html="feather.icons['monitor'].toSvg({ width: 18, height: 18 })"></button>
                    <button v-on:click="active = false" class="close" v-html="feather.icons['x'].toSvg({ width: 24, height: 24 })"></button>
                </header>
                <div class="uk-flex-1">
                    <iframe v-bind:src="previewUrl"></iframe>
                </div>
            </section>
        </div>
        <div class="button-wrapper" v-if="!active" >
            <button v-on:click="active = true" class="uk-button uk-button-default uk-button-small show-preview">Show preview</button>
        </div>
    </div>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        name: "iFramePreview",

        data() {
            return {
                active: false,
                previewUrl: this.url,
                width: 0,
                minWidth: 50,
                feather: feather
            };
        },
        props: [
            'url',
        ],
        watch: {
            width: function(width) {
                if(this.mainSection) {
                    if(width < this.mainSection.clientWidth) {
                        this.mainSection.style.marginRight = width + 'px';
                    }
                }
            },
            active: function(active) {
                if(active) {
                    this.width = window.innerWidth / 5;
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

            this.iFrame = this.$el.querySelector('iframe');

            window.addEventListener('resize', this.handleResize);
            this.handleResize();

            document.documentElement.addEventListener('mousemove', this.resize);
            document.documentElement.addEventListener('mouseup', this.stopResize);
            document.documentElement.addEventListener('mouseleave', this.stopResize);
            document.documentElement.addEventListener('touchmove', this.resize, true);
            document.documentElement.addEventListener('touchend touchcancel', this.stopResize, true);
            document.documentElement.addEventListener('touchstart', this.stopResize, true);

        },
        beforeDestroy: function () {
            window.removeEventListener('resize', this.handleResize);
        },
        methods: {
            changeSize: function(size){

                // TODO: Change Size.
                console.log("TODO: Change Size to: " + size);

                // Reload
                let oldUrl = this.previewUrl;
                this.previewUrl += '';
                setTimeout(() => { this.previewUrl = oldUrl; }, 5);
            },
            startResize: function($event) {
                this.initialWidth = this.width;
                this.initialClientX = $event.clientX;
                this.isResizing = true;
            },
            stopResize: function($event) {
                this.isResizing = false;
            },
            resize: function($event) {
                if(this.isResizing) {
                    this.width = this.initialWidth + (this.initialClientX - $event.clientX);

                    if(this.width < this.minWidth) {
                        this.stopResize();
                        this.active = false;
                        this.width = 0;
                    }

                    if(this.width > this.initialWidth && this.width > (window.innerWidth - 50)) {
                        this.stopResize();
                        this.width = (window.innerWidth - 50);
                    }
                }
            },
            handleResize: function() {
                if(window.innerWidth > 960) {
                    this.width = window.innerWidth / 5;
                    this.active = true;
                } else {
                    this.width = 0;
                    this.active = false;
                }
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
        white-space: nowrap;
        overflow: hidden;

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
            cursor: col-resize;

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