<script>
    export default {
        props: {
            view: Object,
            field: Object,
            row: Object,
            embedded: Boolean,
        },
        methods: {
            is_granted(attribute) {
                return this.row._meta.permissions[attribute] || false;
            },
            to(action) {

                let group = this.$route.params.viewGroup;

                if(this.view.groups.length === 0) {
                    group = '_all_';
                }

                else if (this.view.groups.indexOf(group) < 0) {
                    group = this.view.groups[0];
                }

                return ["", this.view.category, group, this.view.id, this.id, action].join('/');
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
                if(this.row[this.field.id] === null) {
                    return [];
                }
                return this.field.list_of ? this.row[this.field.id] : [this.row[this.field.id]]
            }
        }
    }
</script>
