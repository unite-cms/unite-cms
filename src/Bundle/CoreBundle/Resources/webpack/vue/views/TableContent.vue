<template>
    <div v-on:click="recalcColumnWidth" class="unite-div-table">
        <div class="unite-div-table-thead">
            <div :style="rowStyle" uk-grid class="unite-div-table-row">
                <div :key="identifier" v-for="(field,identifier) in fields">
                    <p>
                        <a href="#" v-on:click.prevent="setSort(identifier)">
                            {{ field.label }}
                            <span v-if="sort.field === identifier" v-html="feather.icons[sort.asc ? 'arrow-down' : 'arrow-up'].toSvg({width: 16, height: 16})"></span>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <div class="unite-div-table-tbody">
            <div :style="rowStyle" uk-grid class="unite-div-table-row" :key="row.id" v-for="row in rows">
                <div :key="identifier" v-for="(field,identifier) in fields">
                    <component :is="$uniteCMSViewFields.resolve(field.type)"
                               :identifier="identifier"
                               :label="field.label"
                               :settings="field.settings"
                               :row="row"></component>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

    import BaseViewContent from './Base/BaseViewContent.vue';
    import { addListener, removeListener } from 'resize-detector';
    import feather from 'feather-icons';

    export default {
        extends: BaseViewContent,
        data() {
            return {
                columnWidth: null,
                feather: feather,
            };
        },
        mounted() {
            addListener(this.$el, this.recalcColumnWidth);
        },
        destroyed() {
          removeListener(this.$el, this.recalcColumnWidth);
        },
        computed: {
            rowStyle() {
                return !this.columnWidth ? {} : {
                    'grid-template-columns': this.columnWidth.map((column) => {
                        return column === 0 ? 'minmax(100px, 1fr)' : column + 'px';
                    }).join(' ')
                }
            }
        },
        methods: {
            setSort(identifier) {
                if(this.sort.field === identifier) {
                    this.sort.asc = !this.sort.asc;
                } else {
                    this.sort.field = identifier;
                    this.sort.asc = true;
                }
            },
            recalcColumnWidth() {
                this.columnWidth = this.columnWidth || [];

                this.$el.querySelectorAll('.unite-div-table-row').forEach((row) => {
                    row.childNodes.forEach((column, delta) => {
                        let style = window.getComputedStyle(column);
                        let columnWidth = parseInt(style.getPropertyValue('width'));
                        let innerWidth = column.firstChild.clientWidth || columnWidth;
                        let fixedWith = Math.abs(columnWidth - innerWidth) > 2 ? innerWidth : 0;
                        this.columnWidth[delta] = this.columnWidth[delta] == null || this.columnWidth[delta] < fixedWith ? fixedWith : this.columnWidth[delta];
                    });
                });
            }
        }
    }
</script>

<style scoped>

</style>
