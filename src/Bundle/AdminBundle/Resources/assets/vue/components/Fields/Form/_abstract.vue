<script>
    export default {
        data() {
            return {
                val: this.value
            }
        },
        props: {
            field: Object,
            value: {}
        },
        watch: {
            value: {
                deep: true,
                handler(value) {
                    this.val = value;
                }
            },
            val: {
                deep: true,
                handler(value) {
                    this.$emit('input', value);
                }
            }
        },
        computed: {
            name() {
                return this.field.name.slice(0, 1).toUpperCase() + this.field.name.slice(1);
            },
            domID() {
                return 'form-field-' + this.field.id + '-' + this._uid;
            }
        },
    }
</script>
