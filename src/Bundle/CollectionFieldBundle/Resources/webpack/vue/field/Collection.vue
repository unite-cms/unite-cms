<template>
    <div>
        <united-cms-collection-field-row
                v-for="row in rows"
                :key="row.delta"
                :delta="row.delta"
                :prototype="row.prototype"
                @remove="removeRow"
        ></united-cms-collection-field-row>
        <button v-if="!maxRows || rows.length < maxRows" class="uk-button uk-button-default add" v-on:click.prevent="addRow" v-html="feather.icons['plus'].toSvg({ width: 20, height: 20 })"></button>
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
                counter: rows.length,
                rows: rows,
                feather: feather
            };
        },
        props: [
            'initRows',
            'minRows',
            'maxRows',
            'dataPrototype',
            'dataIdentifier'
        ],
        methods: {
            rowPrototype(delta) {
                return this.dataPrototype.replace(new RegExp('__' + this.dataIdentifier + 'Name__', 'g'), delta);
            },
            addRow() {
                if(!this.maxRows || this.rows.length < this.maxRows) {
                    this.counter++;
                    this.rows.push({
                        delta: this.counter,
                        prototype: this.rowPrototype(this.counter)
                    });
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
            }
        }
    };
</script>

<style lang="scss">
    @import "../../../../../CoreBundle/Resources/webpack/sass/base/variables";

    united-cms-collection-field {
        display: block;
        margin: 5px 0;
        border: 1px solid map-get($colors, grey-medium);
        background: map-get($colors, white);
        padding: 10px;

        united-cms-collection-field-row {
            position: relative;
            display: block;
            background: $global-muted-background;
            opacity: 0.75;

            > .uk-placeholder {
                margin-bottom: 10px;
                padding: 15px 15px 0;

                > div > div > .uk-margin {
                    margin-bottom: 15px;
                }

                > .close-button {
                    display: none;
                    background: map-get($colors, red);
                    color: map-get($colors, white);
                    width: 24px;
                    height: 24px;
                    top: 0;
                    right: 0;
                    border-radius: 0 0 0 2px;

                    svg {
                        width: 16px;
                        height: 16px;
                    }
                }
            }

            &:hover {
                opacity: 1;

                > .uk-placeholder > .close-button {
                    display: block;
                }
            }
        }

        > div > button.add {
            padding: 0;
            width: 40px;
            border-radius: 100%;
            margin: 10px auto;
            display: block;
        }
    }
</style>