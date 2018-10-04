<template>
    <div class="unite-div-table">
        <div class="unite-div-table-thead">
            <div :style="rowStyle" uk-grid class="unite-div-table-row">
                <div class="rowControl" v-if="rowControl"><span></span></div>
                <div :key="identifier" v-for="(field,identifier) in fields">
                    <span>{{ field.label }}</span>
                </div>
            </div>
        </div>
        <div class="unite-div-table-tbody" uk-sortable="handle: .uk-sortable-handle" v-on:moved="moved">
            <div :style="rowStyle" uk-grid class="unite-div-table-row" :data-id="row.id" :key="row.id" v-for="row in rows">
                <div class="rowControl" v-if="rowControl">
                    <div v-if="rowControl === 'SORT'" class="uk-sortable-handle" v-html="feather.icons['move'].toSvg({ width: 16, height: 16 })"></div>
                </div>
                <div class="unite-div-table-cell" :key="identifier" v-for="(field,identifier) in fields">
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

    import TableContent from './TableContent.vue';
    import UIkit from 'uikit';

    export default {
        extends: TableContent,
        data() {
            return {
                rowControl: this.selectable || 'SORT',
            };
        },
        methods: {
            moved(event) {
                let id = event.detail[1].dataset.id;
                let position = UIkit.util.index(event.detail[1]);
                console.log(position);
                console.log(id);
                console.log(this);
                this.$emit('updateRow', {
                    id: event.detail[1].dataset.id,
                    data: {
                        position: UIkit.util.index(event.detail[1])
                    }
                });
            }
        }
    }
</script>

<style scoped>

</style>
