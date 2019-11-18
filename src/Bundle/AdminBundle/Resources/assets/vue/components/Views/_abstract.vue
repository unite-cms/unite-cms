<script>
    export default {
        props: {
            view: Object,
        },
        methods: {
            is_granted(attribute) {
                return this.view.permissions[attribute] || false;
            },
            to(action) {
                return this.$route.path + '/' + action;
            },
        },
    }
</script>
