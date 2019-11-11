<template>
  <div id="playground" ref="playground" class="uk-position-relative">
    <div uk-spinner class="uk-position-center"></div>
  </div>
</template>

<script>
    import User from "../state/User";

    export default {
        mounted() {

            let scriptUrl = '//cdn.jsdelivr.net/npm/graphql-playground-react/build/static/js/middleware.js';
            if(!document.querySelector(`script[src="${scriptUrl}"]`)) {
                let script = document.createElement("script");
                script.src = scriptUrl;
                script.onload = () => {
                    this.mountPlayground();
                };
                document.body.appendChild(script);
            } else {
                this.mountPlayground();
            }
        },
        computed: {
            playgroundConfig() {

                let endpoint = location.origin + UNITE_ADMIN_CONFIG.baseurl;

                return {
                    endpoint: endpoint,
                    settings: {
                        'editor.reuseHeaders': true,
                        'editor.theme': 'dark',
                    },
                    tabs: [
                        {
                            endpoint: endpoint,
                            query: `query {
  unite {
    _version
  }
}
`,
                            name: 'unite cms',
                            headers: {
                              'Authorization': `Bearer ${ User.token }`,
                            }
                        }
                    ],
                };
            }
        },
        methods: {
            mountPlayground() {
                GraphQLPlayground.init(this.$refs.playground, this.playgroundConfig);
            }
        }
    }
</script>
<style scoped lang="scss">
  #playground {
    width: 100%;
    height: 100%;
    overflow: hidden;

    a,code,h1,h2,h3,h4,html,p,pre,ul{margin:0;padding:0;color:inherit}
    a:active,a:focus,button:focus,input:focus{outline:none}
    button,input,submit{border:none}
    button,input,pre{font-family:Open Sans,sans-serif}
    code{font-family:Consolas,monospace}
  }
</style>