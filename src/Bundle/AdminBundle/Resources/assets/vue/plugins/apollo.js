import Vue from 'vue';
import VueApollo from 'vue-apollo';
import ApolloClient from 'apollo-boost';
import User from "../state/User";
import Alerts from "../state/Alerts";
import { Unite } from "./unite";
import { InMemoryCache } from 'apollo-cache-inmemory';

Vue.use(VueApollo);

const cache = new InMemoryCache({
    fragmentMatcher: Unite.fragmentMatcher,
    dataIdFromObject: object => {

        // For the moment, prevent cache ids. It is just not working with many sub-types.
        /*if (object.__typename) {
            return defaultDataIdFromObject(object);
        }*/
        return null;
    }
});

const apolloClient = new ApolloClient({
    cache,
    uri: location.origin + UNITE_ADMIN_CONFIG.baseurl,
    request: (operation) => {
        if(User.token) {
            operation.setContext({
                headers: {
                    authorization: `Bearer ${ User.token }`
                }
            })
        }
    }
});


export const apolloProvider = new VueApollo({
    defaultClient: apolloClient,
    watchLoading: () => { Alerts.$emit('clear'); },
    errorHandler: Alerts.apolloErrorHandler
});
export default apolloProvider;
