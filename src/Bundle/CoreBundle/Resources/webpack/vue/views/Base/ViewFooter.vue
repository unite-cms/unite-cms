<template>
    <footer v-if="pages.length > 1">
        <ul class="uk-pagination uk-flex-center uk-margin-small-bottom">
            <li class="first uk-visible@s" v-if="showArrows && config.page > 1">
                <a v-on:click="change(1)" v-html="feather.icons['chevrons-left'].toSvg({ width: 16, height: 16 })"></a>
            </li>
            <li v-if="showArrows && config.page > 1">
                <a v-on:click="change(config.page-1)" v-html="feather.icons['chevron-left'].toSvg({ width: 16, height: 16 })"></a>
            </li>
            <template v-for="p in pages">
                <li :key="p.page" v-if="!showArrows || p.page > lowerCutLeft && p.page <= lowerCutRight" v-bind:class="{'uk-active': p.page === config.page }">
                    <a v-on:click="change(p.page)">{{p.page}}</a>
                </li>
            </template>
            <li v-if="showArrows && config.page < pages.length">
                <a v-on:click="change(config.page+1)" v-html="feather.icons['chevron-right'].toSvg({ width: 16, height: 16 })"></a>
            </li>
            <li class="last uk-visible@s" v-if="showArrows && config.page < pages.length">
                <a v-on:click="change(pages.length)" v-html="feather.icons['chevrons-right'].toSvg({ width: 16, height: 16 })"></a>
            </li>
        </ul>
        <div class="uk-text-center"><small>{{fromItem}} - {{toItem}} of {{config.total}}</small></div>
    </footer>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
                visibleCount: 5,
                feather: feather
            };
        },
        props: {
            config: Object
        },
        created: function() {
            this.change();
        },
        computed: {
            showArrows() {
                return (this.pages.length > this.visibleCount + 3);
            },
            visibleCountHalf() {
                return Math.ceil(this.visibleCount / 2);
            },
            lowerCutLeft() {
                return this.config.page > this.pages.length - this.visibleCountHalf ? this.pages.length - this.visibleCount : this.lowerCutRight - this.visibleCount;
            },
            lowerCutRight() {
                if(this.pages.length > this.visibleCount) {
                    return this.config.page < this.visibleCountHalf ? this.visibleCount : this.config.page + this.visibleCountHalf - 1;
                }

                return this.pages.length;
            },
            pages(){
                let pages = [];
                for(let i = 1; i <= Math.ceil(this.config.total / this.config.limit); i++) {
                    pages.push({
                        page: i,
                        active: (this.config.page === i)
                    });
                }

                if(pages.length === 0) {
                    pages.push({ page: 1, active: true });
                }

                return pages;
            },
            fromItem() {
                let pageFromZero = this.config.page - 1;
                return pageFromZero * this.config.limit + 1;
            },
            toItem() {
                let endItem = this.config.page * this.config.limit;
                if (endItem > this.config.total) {
                    endItem = this.config.total;
                }

                return endItem;
            },
        },
        methods: {
            change(page = 1) {
                if(page !== this.config.page) {
                    this.config.page = page;
                }
            }
        }
    }
</script>

<style scoped>
    footer {
        margin: 20px -20px 0;
    }
</style>
