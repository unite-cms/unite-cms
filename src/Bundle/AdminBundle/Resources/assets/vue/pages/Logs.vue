<template>
  <log-viewer :log="log" :loading="loading" />
</template>

<script>
    import LogViewer from '@femessage/log-viewer';

    export default {
        components: {LogViewer},
        data() {
            return {
                log: 'FOO',
                hasMore: true,
                loading: true,
            };
        },
        mounted(){
            this.loadMore();
        },
        methods: {
            loadMore() {
                setTimeout(() => {
                    for(let i = 0; i < 10000; i++) {
                        this.log += "\nFake Log Entry " + i;
                    }
                }, 300);
            }
        }
    }
</script>
<style scoped lang="scss">
  .log-viewer {
    height: calc(100% - 40px) !important;;
  }
</style>