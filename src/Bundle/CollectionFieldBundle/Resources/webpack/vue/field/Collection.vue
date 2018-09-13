<template>
    <div>
        <div uk-sortable="handle: .uk-sortable-handle; animation: 300">
            <unite-cms-collection-field-row
                    v-for="row in sortedRows"
                    :key="row.delta"
                    :delta="row.delta"
                    :prototype="row.prototype"
                    :form-layout="rowFormLayout"
                    :hide-labels="rowLabelHidden"
                    @remove="removeRow"
                    @add="addRow"
            ></unite-cms-collection-field-row>
        </div>
        <div v-if="!maxRows || rows.length < maxRows" class="collection-add-button-wrapper uk-sortable-nodrag">
            <button  class="uk-button uk-button-default" v-on:click.prevent="addRow" v-html="feather.icons['plus'].toSvg({ width: 20, height: 20 })"></button>
        </div>
    </div>
</template>

<script>
    import feather from 'feather-icons';

    export default {
        data() {

            // Add init rows to the rows array.
            let rows = this.initRows ? JSON.parse(this.initRows).map((row, index) => {
                return {
                    delta: index,
                    position: index,
                    prototype: row
                }
            }) : [];

            // If min_rows is greater than the current rows length, add empty rows.
            if(rows.length < this.minRows) {
                for(let i = 0; i <= (this.minRows - rows.length); i++) {
                    rows.push({
                        delta: rows.length,
                        prototype: this.rowPrototype(rows.length)
                    });
                }
            }

            return {
                rowFormLayout: (this.labelLayout && this.labelLayout === 'inline') ? 'uk-form-horizontal' : 'uk-form-vertical',
                rowLabelHidden: (this.labelLayout && this.labelLayout === 'hidden') ? true : false,
                counter: rows.length,
                rows: rows,
                feather: feather
            };
        },
        computed: {
            sortedRows() {
                return this.rows.sort((a, b) => { return a.position - b.position; });
            }
        },
        props: [
            'initRows',
            'minRows',
            'maxRows',
            'labelLayout',
            'dataPrototype',
            'dataIdentifier'
        ],
        mounted() {

            // After an element was moved, update all sort positions.
            this.$el.addEventListener('moved', () => {
                this.calculatePositions();
            });
        },
        methods: {
            getRow(delta){
                let result = this.rows.filter((row) => { return row.delta == delta; });
                if(result.length === 1) {
                    return result[0];
                }
                return null;
            },
            calculatePositions() {
                this.$el.querySelectorAll('unite-cms-collection-field-row').forEach((element, index) => {
                    let row = this.getRow(element.attributes.delta.value);
                    if(row) {
                        row.position = index;
                    }
                });
            },
            rowPrototype(delta) {
                return this.dataPrototype.replace(new RegExp('__' + this.dataIdentifier + 'Name__', 'g'), (delta));
            },
            addRow(event) {
                if(!this.maxRows || this.rows.length < this.maxRows) {

                    let position = (event && event.detail && event.detail[0] && event.detail[0].delta !== null) ? this.getRow(event.detail[0].delta).position : null;

                    // If we insert the new row anywhere in the middle, we need to increase the position of all rows below.
                    if(position !== null) {
                        this.rows.forEach((row) => {
                            if(row.position >= position) {
                                row.position ++;
                            }
                        });
                    }

                    this.rows.push({
                        delta: this.counter,
                        prototype: this.rowPrototype(this.counter),
                        position: (position !== null) ? position : this.counter,
                    });

                    this.counter++;
                }
            },
            removeRow(event) {
                var item = this.rows.find((row) => { return row.delta === event.detail[0].delta });
                if(item) {
                    this.rows.splice(this.rows.indexOf(item), 1);
                }

                // On remove we need to check min_rows.
                if(this.rows.length < this.minRows) {
                    this.addRow();
                }

                // Recalculate positions for all rows after dom element was removed.
                setTimeout(() => {
                    this.calculatePositions();
                });
            }
        }
    };
</script>

<style lang="scss">
    @import "../../../../../CoreBundle/Resources/webpack/sass/base/variables";

    unite-cms-collection-field {
        display: block;
        margin: 5px 0;
        border: 1px solid map-get($colors, grey-medium);
        background: map-get($colors, white);
        padding: 5px;

        .uk-sortable-empty {
            min-height: 0;
        }

        .collection-add-button-wrapper {
            width: 100%;
            text-align: center;
            margin: 0;
            padding: 10px 0;
            position: relative;
            z-index: 5;

            button.uk-button:not(.uk-button-text):not(.uk-button-link) {
                padding: 0;
                width: 30px;
                height: 30px;
                line-height: 0;
                border-radius: 100%;
                display: block;
                margin: 0 auto;
                background: white;

                svg {
                    width: 18px;
                    height: 18px;
                }
            }
        }
    }

    unite-cms-collection-field-row + unite-cms-collection-field-row {
        .collection-add-button-wrapper {
            padding: 5px auto;

            button.uk-button:not(.uk-button-text):not(.uk-button-link) {
                margin: -15px auto;
                transform: scale(0.25);
                opacity: 0.5;
                transition: all 0.1s ease-out;
            }

            &:hover {
                button.uk-button:not(.uk-button-text):not(.uk-button-link) {
                    opacity: 1;
                    transform: scale(1);
                }
            }
        }
    }

    /*unite-cms-collection-field:hover {
        .collection-add-button-wrapper {
            button.uk-button:not(.uk-button-text):not(.uk-button-link) {
                display: block;
            }
        }
    }*/

    unite-cms-collection-field-row {
        position: relative;
        display: block;
        padding: 0;

        &[hide-labels="true"] {
            .uk-form-label {
                display: none;
            }
        }

        &.uk-sortable-placeholder {
            &, &:hover {
                opacity: 0;
            }
        }

        &.uk-sortable-drag {
            > div {
                > .uk-placeholder {
                    > .uk-sortable-handle { display: block; }
                    > .close-button { display: none; }
                }
                > .collection-add-button-wrapper { opacity: 0; }
            }
        }


        > div {
            > .uk-placeholder {
                position: relative;
                display: block;
                background: $global-muted-background;
                opacity: 0.75;
                padding: 15px 15px 0;
                margin: 0 25px;

                > div > div > .uk-margin {
                    margin-bottom: 15px;
                }

                > .uk-sortable-handle {
                    display: none;
                    color: map-get($colors, grey-dark);
                    width: 30px;
                    height: 30px;
                    top: 10px;
                    left: -30px;
                    position: absolute;
                    text-align: center;

                    svg {
                        width: 16px;
                        height: 16px;
                    }
                }

                > .close-button {
                    display: none;
                    color: map-get($colors, red);
                    width: 30px;
                    height: 30px;
                    top: 10px;
                    right: -30px;

                    svg {
                        width: 16px;
                        height: 16px;
                    }
                }
            }

            &:hover {
                > .uk-placeholder {
                    opacity: 1;

                    > .uk-sortable-handle,
                    > .close-button {
                        display: block;
                    }
                }
            }
        }
    }

    html.uk-drag {
        unite-cms-collection-field {
            .collection-add-button-wrapper {
                opacity: 0;
            }
        }
    }
</style>
