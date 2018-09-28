<template>
    <footer>
        <ul class="uk-pagination uk-flex-center" uk-margin>
            <li class="first" v-if="current > 1">
                <a v-on:click="change(current - 1)" v-html="feather.icons['arrow-left'].toSvg()"></a>
            </li>
            <li v-for="p in pages" v-bind:class="{'uk-active': page.active }">
                <a v-on:click="change(p.page)">{{p.page}}</a>
            </li>
            <li class="last" v-if="current < pages.length">
                <a v-on:click="change(current + 1)" v-html="feather.icons['arrow-right'].toSvg()"></a>
            </li>
        </ul>
    </footer>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
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
            pages: function(){
                let pages = [];
                for(let i = 1; i <= Math.ceil(this.total / this.limit); i++) {
                    pages.push({
                        page: i,
                        active: (this.current === i)
                    });
                }
                return pages;
            },
        },
        methods: {
            change(page) {
                this.$emit('change', {
                    page: page,
                    offset: this.limit * (page - 1),
                    limit: this.limit
                });
            }
        }
    }
</script>

<style scoped>

</style>