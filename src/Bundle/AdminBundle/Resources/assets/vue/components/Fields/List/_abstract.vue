<script>
    export default {
        props: {
            field: Object,
            row: Object,
        },
        methods: {
            is_granted(attribute) {
                return this.row._meta.permissions[attribute] || false;
            },
            to(action) {
                return this.$route.path + '/' + this.id + '/' + action;
            },
            isLastValue(value) {
              let values = this.values;
              return values.indexOf(value) === values.length - 1;
            }
        },
        computed: {
            id() {
                return this.row._meta.id;
            },
            deleted() {
                return this.row._meta.deleted;
            },
            values() {
                if(!this.row[this.field.id]) {
                    return [];
                }
                return this.field.list_of ? this.row[this.field.id] : [this.row[this.field.id]]
            }
        }
    }
</script>
