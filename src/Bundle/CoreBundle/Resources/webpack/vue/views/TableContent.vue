<template>
    <div class="unite-div-table">
        <div class="unite-div-table-thead">
            <div :style="rowStyle" uk-grid class="unite-div-table-row">
                <div :key="identifier" v-for="(field,identifier) in fields">
                    <a v-if="!isSortable && updateable" href="#" v-on:click.prevent="setSort(identifier)">
                        {{ field.label }}
                        <span v-if="sortConfig.field === identifier" v-html="feather.icons[sortConfig.asc ? 'arrow-down' : 'arrow-up'].toSvg({width: 16, height: 16})"></span>
                    </a>
                    <span v-else >
                        <template v-if="identifier !== sortConfig.field">{{ field.label }}</template>
                        <span v-if="updateable && sortConfig.field === identifier" v-html="feather.icons[sortConfig.asc ? 'arrow-down' : 'arrow-up'].toSvg({width: 16, height: 16})"></span>
                    </span>
                </div>
                <div v-if="showActions">
                    <span></span>
                </div>
            </div>
        </div>
        <div class="unite-div-table-tbody" :uk-sortable="isSortable && updateable ? 'handle: .uk-sortable-handle' : null" v-on:moved="moved">
            <div :style="rowStyle" uk-grid class="unite-div-table-row" :data-id="row.id" :key="row.id" v-for="row in rows">
                <div class="unite-div-table-cell" :key="identifier" v-for="(field,identifier) in fields">
                    <component :is="$uniteCMSViewFields.resolve(field.type)" v-if="!(isSortable && !updateable && sortConfig.field === identifier)"
                               :identifier="identifier"
                               :label="field.label"
                               :config="field.config"
                               :settings="field.settings"
                               :sortable="isSortable"
                               :row="row"></component>
                </div>
                <div v-if="showActions" class="unite-div-table-cell">
                    <base-view-row-actions :row="row" :urls="urls" ></base-view-row-actions>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

    import feather from 'feather-icons';
    import UIkit from 'uikit';

    import BaseViewContent from './Base/BaseViewContent.vue';
    import BaseViewRowActions from './Base/BaseViewRowActions.vue';

    export default {
        extends: BaseViewContent,
        data() {
            return {
                columnWidth: [],
                columnWidthChanged: 0,
                feather: feather,
                sortConfig: this.sort,
            };
        },
        updated() {
            this.$nextTick(() => {
                setTimeout(() => {
                    this.recalcColumnWidth();
                }, 1);
            });
        },
        computed: {
            showActions() {
                return !this.selectable;
            },
            isSortable() {
                return !!this.sort.sortable;
            },
            rowStyle() {
                this.columnWidthChanged; // We listen to changes on columnWidthChanged
                return {
                    'grid-template-columns': this.columnWidth.map((column) => {
                        return 'minmax(' + column.min + 'px,' + (column.max === 0 ? '1fr' : (column.max + 'px')) + ')';
                    }).join(' ')
                };
            }
        },
        methods: {
            setSort(identifier) {
                if(!this.isSortable) {
                    this.sortConfig.field = identifier;
                    this.sortConfig.asc = this.sortConfig.field === identifier ? !this.sortConfig.asc : true;
                    this.$emit('updateSort', this.sortConfig);
                }
            },
            recalcColumnWidth() {
                let changed = false;

                this.$el.querySelectorAll('.unite-div-table-row').forEach((row) => {
                    row.querySelectorAll('.unite-div-table-cell').forEach((column, delta) => {
                        if(!this.columnWidth[delta]) {
                            this.columnWidth[delta] = { min: 50, max: 0 };
                            changed = true;
                        }

                        if(column.firstChild.offsetWidth < column.offsetWidth) {
                            if(column.firstChild.offsetWidth > this.columnWidth[delta].min) {
                                this.columnWidth[delta].min = column.firstChild.offsetWidth;
                                changed = true;
                            }

                            if(row.parentElement.classList.contains('unite-div-table-tbody')) {
                                if(column.firstChild.offsetWidth > this.columnWidth[delta].max) {
                                    this.columnWidth[delta].max = column.firstChild.offsetWidth;
                                    changed = true;
                                }
                            }
                        }
                    });
                });

                if(changed) {
                    this.columnWidthChanged++;
                }
            },
            moved(event) {
                if(this.isSortable) {
                    this.$emit('updateRow', {
                        id: event.detail[1].dataset.id,
                        data: {
                            position: UIkit.util.index(event.detail[1])
                        }
                    });
                }
            }
        },
        components: {
            'base-view-row-actions': BaseViewRowActions
        }
    }
</script>

<style scoped>

</style>
