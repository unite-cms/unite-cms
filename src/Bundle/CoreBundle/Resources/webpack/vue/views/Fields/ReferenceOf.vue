<template>
    <span v-if="field.settings && field.settings.templateParameters">
        <button v-if="value.total > 0" @click="openReferencedContent" class="uk-badge">
            {{ value.total }}
        </button>
        <div :key="'offcanvas' + _uid" :id="'_offcanvas' + _uid" uk-offcanvas ref="offcanvas" class="reference-of-views-field-offcanvas-overlay" v-if="value.total > 0" @hide="onHide">
            <div class="uk-offcanvas-bar reference-of-views-field-offcanvas">
                <button class="uk-offcanvas-close" type="button" uk-close></button>
                <unite-cms-core-view-table v-if="open" :parameters="viewParameters"></unite-cms-core-view-table>
            </div>
        </div>
    </span>
    <span v-else>
        <span v-if="value.total > 0" class="uk-badge">{{ value.total }}</span>
    </span>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';
    import UIkit from 'uikit';
    import cloneDeep from 'lodash/cloneDeep';

    export default {
        FIELD_WIDTH_COLLAPSED: true,
        extends: BaseField,
        data() {
            return {
                open: false,
                viewParameters: '',
                offcanvas: null,
            }
        },
        methods: {

            /**
             * {@inheritdoc}
             */
            fieldQuery(identifier, field) {
                return identifier + ' { total }';
            },

            /**
             * {@inheritdoc}
             */
            filterQuery(identifier, field) {
                return null;
            },

            onHide() {
              this.open = false;
            },

            openReferencedContent() {

                if(!this.viewParameters) {
                    let parameters = cloneDeep(this.field.settings.templateParameters);
                    parameters.csrf_token = this.config.csrfToken;
                    parameters.settings = parameters.settings || {};
                    parameters.settings.embedded = true;
                    parameters.title = this.field.label;

                    let refOfFilter = {
                        field: this.field.settings.reference_field + '.content',
                        operator: '=',
                        value: this.row.id,
                    };

                    parameters.settings.filter = parameters.settings.filter ? {
                        AND: [parameters.settings.filter, refOfFilter]
                    } : refOfFilter;

                    this.viewParameters = JSON.stringify(parameters);
                }

                this.open = true;

                this.$nextTick(() => {
                    UIkit.offcanvas(this.$refs.offcanvas, {
                        flip: true,
                        overlay: true,
                    }).show();
                });
            }
        }
    }
</script>

<style scoped lang="scss">
    span {
        display: block;
        text-align: center;

        button.uk-badge {
            cursor: pointer;
            background: #999;
            border: none;
        }
    }
</style>
<style lang="scss">
    .reference-of-views-field-offcanvas {
        background: white;
        width: calc(100vw - 25px);

        .unite-table-header {
            margin: 0 20px 0 10px;
        }
    }

    @media all and (min-width: 960px) {
        .reference-of-views-field-offcanvas {
            width: calc(100vw - 340px);
        }
    }

    .reference-of-views-field-offcanvas-overlay::before {
        background: rgba(0,0,0,0.5);
    }

</style>