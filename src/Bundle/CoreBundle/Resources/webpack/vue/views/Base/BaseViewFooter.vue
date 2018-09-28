<template>
    <footer>
        <ul class="uk-pagination uk-flex-center" uk-margin>
            <li class="first uk-visible@s" v-if="showArrows && current > 1">
                <a v-on:click="change(1)" v-html="feather.icons['chevrons-left'].toSvg({ width: 16, height: 16 })"></a>
            </li>
            <template v-for="p in pages">
                <li v-if="!showArrows || p.page > lowerCutLeft && p.page <= lowerCutRight" v-bind:class="{'uk-active': p.page === current }">
                    <a v-on:click="change(p.page)">{{p.page}}</a>
                </li>
                <li v-if="showArrows && p.page === lowerCutLeft">
                    <a v-on:click="change(current-1)" v-html="feather.icons['chevron-left'].toSvg({ width: 16, height: 16 })"></a>
                </li>
                <li v-if="showArrows && p.page === lowerCutRight + 1">
                    <a v-on:click="change(current+1)" v-html="feather.icons['chevron-right'].toSvg({ width: 16, height: 16 })"></a>
                </li>
            </template>
            <li class="last uk-visible@s" v-if="showArrows && current < pages.length">
                <a v-on:click="change(pages.length)" v-html="feather.icons['chevrons-right'].toSvg({ width: 16, height: 16 })"></a>
            </li>
        </ul>
    </footer>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
                visibleCount: 5,
                current: 1,
                feather: feather
            };
        },
        props: [
            'total',
            'limit'
        ],
        created: function() {
            this.$on('goto', this.change);
        },
        computed: {
            showArrows() {
                return (this.pages.length > this.visibleCount + 3);
            },
            visibleCountHalf() {
                return Math.ceil(this.visibleCount / 2);
            },
            lowerCutLeft() {
                return this.current > this.pages.length - this.visibleCountHalf ? this.pages.length - this.visibleCount : this.lowerCutRight - this.visibleCount;
            },
            lowerCutRight() {
                if(this.pages.length > this.visibleCount) {
                    return this.current < this.visibleCountHalf ? this.visibleCount : this.current + this.visibleCountHalf - 1;
                }

                return this.pages.length;
            },
            pages(){
                let pages = [];
                for(let i = 1; i <= Math.ceil(this.total / this.limit); i++) {
                    pages.push({
                        page: i,
                        active: (this.current === i)
                    });
                }

                if(pages.length === 0) {
                    pages.push({ page: 1, active: true });
                }

                return pages;
            },
        },
        methods: {
            change(page) {
                if(page !== this.current) {
                    this.current = page;
                    this.$emit('change', {
                        page: this.current,
                        offset: this.limit * (this.current - 1),
                        limit: this.limit
                    });
                }
            }
        }
    }
</script>

<style scoped>
    footer {
        margin: 0 -20px;
    }
</style>