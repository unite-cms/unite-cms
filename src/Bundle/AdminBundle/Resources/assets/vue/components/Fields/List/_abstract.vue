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
        },
        computed: {
            id() {
                return this.row._meta.id;
            },
            deleted() {
                return this.row._meta.deleted;
            },
        }
    }
</script>
