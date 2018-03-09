<template>
    <div>
        <united-cms-collection-field-row
                v-for="row in rows"
                :key="row.delta"
                :delta="row.delta"
                :prototype="row.prototype"
                @remove="removeRow"
        ></united-cms-collection-field-row>
        <button v-if="!maxRows || rows.length < maxRows" class="uk-button uk-button-default" v-on:click.prevent="addRow">
            <span uk-icon="icon: plus"></span>
            Add Row
        </button>
    </div>
</template>

<script>
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

<style lang="scss" scoped>
</style>