
import Vue from 'vue';
import router from "../plugins/router";

export const Route = new Vue({
    data() {
        return {
            previous: null
        }
    },
    created() {
        this.$on('setPreviousRoute', (route) => {
            this.previous = route;
        });
    },
    methods: {
        back(query = {}) {

            // Find route to go back to.
            let route = {
                path: '/',
                query,
            };

            // If we have a previous route, lets use it.
            if(this.previous.matched.length > 0) {
                route = { path: this.previous.path, query: Object.assign({}, this.previous.query, query) };
            }

            // If we found a parent for this route, lets us the parent.
            else if(router.currentRoute.matched.length > 1) {
                let path = router.currentRoute.matched[router.currentRoute.matched.length - 2].path;
                Object.keys(router.currentRoute.params).forEach((key) => {
                    path = path.replace(`:${key}`, router.currentRoute.params[key]);
                });

                route = { path: path, query: query };
            }

            router.push(route);
        }
    }
});
export default Route;
