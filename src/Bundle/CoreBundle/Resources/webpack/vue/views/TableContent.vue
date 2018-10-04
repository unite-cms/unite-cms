<template>
    <div class="unite-div-table">
        <div class="unite-div-table-thead">
            <div :style="rowStyle" uk-grid class="unite-div-table-row">
                <div :key="identifier" v-for="(field,identifier) in fields">
                    <a href="#" v-on:click.prevent="setSort(identifier)">
                        {{ field.label }}
                        <span v-if="sort.field === identifier" v-html="feather.icons[sort.asc ? 'arrow-down' : 'arrow-up'].toSvg({width: 16, height: 16})"></span>
                    </a>
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
                columnWidth: Object.keys(this.fields).map(() => { return { min: 100, max: 0 } }),
                rowStyle: {},
                feather: feather,
            };
        },
        mounted() {
            this.$nextTick(() => {
                addListener(this.$el, this.recalcColumnWidth);
                this.recalcColumnWidth();
            });
        },
        destroyed() {
          removeListener(this.$el, this.recalcColumnWidth);
        },
        watch: {
            columnWidth: {
                handler(value) {
                    this.rowStyle = {
                        'grid-template-columns': value.map((column) => {
                            return 'minmax(' + column.min + 'px,' + (column.max === 0 ? '1fr' : (column.max + 'px')) + ')';
                        }).join(' ')
                    };
                },
                deep: true
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
                        if(column.firstChild.offsetWidth < column.offsetWidth) {
                            if(column.firstChild.offsetWidth > this.columnWidth[delta].min) {
                                this.columnWidth[delta].min = column.firstChild.offsetWidth;
                            }

                            if(row.parentElement.classList.contains('unite-div-table-tbody')) {
                                if(column.firstChild.offsetWidth > this.columnWidth[delta].max) {
                                    this.columnWidth[delta].max = column.firstChild.offsetWidth;
                                }
                            }
                        }
                    });
                });
            }
        }
    }
</script>

<style scoped>

</style>
