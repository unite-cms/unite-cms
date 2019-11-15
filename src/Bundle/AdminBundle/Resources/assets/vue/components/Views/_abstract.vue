<script>
    export default {
        props: {
            view: Object,
        },
        computed: {
            fields() {
                return this.view.fields;
            },
        }
    }
</script>
