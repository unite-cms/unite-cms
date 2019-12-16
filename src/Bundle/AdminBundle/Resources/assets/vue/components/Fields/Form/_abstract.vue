<script>
    export default {
        data() {
            return {
                val: this.value || (this.field.list_of ? [] : null)
            }
        },
        props: {
            contentId: String,
            field: Object,
            formData: Object,
            violations: Array,
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
            values() {
                if(!this.val) {
                    return [];
                }
                return this.field.list_of ? this.val : [this.val]
            },
            name() {
                return this.field.name.slice(0, 1).toUpperCase() + this.field.name.slice(1);
            },
            domID() {
                return 'form-field-' + this.field.id + '-' + this._uid;
            }
        },
        methods: {
            removeValue(value = null) {
                if(this.field.list_of) {
                    this.val = this.val.filter((val) => {
                        return val !== value;
                    });
                } else {
                    this.val = null;
                }
            },
            removeByKey(key) {
                if(this.field.list_of) {
                    this.val = this.val.filter((val, val_key) => {
                        return val_key !== key;
                    });
                }
            },
            setValue(args, key) {

                let value = args ? args[0] : null;

                if(value && typeof value === "object" && value.constructor.name === 'InputEvent') {
                   value = value.target.value;
                }

                else if(value && typeof value === "object" && value.constructor.name === 'Event') {
                    value = value.target.checked;
                }

                if(this.field.list_of) {
                    this.$set(this.val, key, value);
                } else {
                    this.val = value;
                }
            }
        },
    }
</script>
