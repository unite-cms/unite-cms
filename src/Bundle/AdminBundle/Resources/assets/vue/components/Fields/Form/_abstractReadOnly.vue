<script>
    import _abstract from "./_abstract";

    export default {
        extends: _abstract,
        data() {
            return {
                internalVal: null,
            }
        },
        watch: {
            val: {
                deep: true,
                handler(value) {
                    if(value) {
                        this.internalVal = value;
                    }
                    this.$emit('input', undefined);
                }
            }
        },
        computed: {
            values() {
                if(!this.internalVal) {
                    return [];
                }
                return this.field.list_of ? this.internalVal : [this.internalVal]
            },
        }
    }
</script>
