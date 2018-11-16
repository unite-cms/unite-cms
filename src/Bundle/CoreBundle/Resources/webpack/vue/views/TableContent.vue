<template>
    <div class="unite-card-table">
        <div class="unite-div-table">
            <div class="unite-div-table-thead">
                <table-content-header-row :fields="fields"
                                          :is-sortable="isSortable"
                                          :is-updateable="updateable"
                                          :show-actions="showActions"
                                          :sort-config="sortConfig"
                                          @updateSort="updateSort"
                                          :urls="urls"
                                          :embedded="embedded"
                ></table-content-header-row>
            </div>
            <div class="unite-div-table-tbody">
                <table-content-row v-for="row in rows"
                                   :row="row"
                                   :fields="fields"
                                   :is-sortable="isSortable"
                                   :is-updateable="updateable"
                                   :show-actions="showActions"
                                   :sort-config="sortConfig"
                                   :urls="urls"
                                   :embedded="embedded"
                ></table-content-row>
            </div>
        </div>
    </div>
</template>

<script>

    import UIkit from 'uikit';
    import BaseViewContent from './Base/BaseViewContent.vue';
    import TableContentRow from './TableContentRow.vue';
    import TableContentHeaderRow from './TableContentHeaderRow.vue';

    export default {
        extends: BaseViewContent,
        data() {
            return {
                sortConfig: this.sort,
            };
        },
        mounted() {
            if(this.isSortable && this.updateable) {
                UIkit.sortable(this.$el.querySelector('.unite-div-table-tbody'), {
                    handle: '.uk-sortable-handle',
                    nestable: true
                });
                UIkit.util.on(this.$el, 'moved', this.moved);
            }
        },
        updated() {
            [].forEach.call(this.$el.querySelectorAll('.unite-div-table-row > *'), (cell) => {
                cell.style.width = cell.clientWidth + 'px';
            });
        },
        computed: {
            showActions() {
                return !this.selectable;
            },
            isSortable() {
                return !!this.sort.sortable && !this.selectable && this.updateable;
            }
        },
        methods: {
            updateSort(event) {
                this.$emit('updateSort', event);
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
            'table-content-row': TableContentRow,
            'table-content-header-row': TableContentHeaderRow,
        }
    }
</script>

<style scoped>

</style>
