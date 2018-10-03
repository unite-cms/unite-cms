<template>
    <div v-on:click="recalcColumnWidth" class="unite-div-table">
        <div class="unite-div-table-thead">
            <div :style="rowStyle" uk-grid class="unite-div-table-row">
                <div :key="identifier" v-for="(field,identifier) in fields">{{ field.label }}</div>
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

    export default {
        extends: BaseViewContent,
        data() {
            return {
                columnWidth: [],
            };
        },
        updated() {
            this.recalcColumnWidth();
        },
        computed: {
            rowStyle() {
                console.log("computed rowStyle");
                let columns = this.columnWidth.map((column) => {
                    return 'auto';
                });

                console.log(this.columnWidth.length);
                console.log(columns);

                return {
                    'grid-template-columns': columns.join(' '),
                }
            }
        },
        methods: {
            recalcColumnWidth() {
                console.log("recalcColumnWidth");
                this.$el.querySelectorAll('.unite-div-table-row').forEach((row) => {
                    row.childNodes.forEach((column, delta) => {
                        let width = parseInt(window.getComputedStyle(column).getPropertyValue('width'));
                        let innerWidth = parseInt(column.firstChild.clientWidth || width);
                        let fixedWith = Math.abs(width - innerWidth) > 2 ? innerWidth : 0;
                        this.columnWidth[delta] = this.columnWidth[delta] == null || this.columnWidth[delta] < fixedWith ? fixedWith : this.columnWidth[delta];
                    });
                });
            }
        }
    }
</script>

<style scoped>

</style>
