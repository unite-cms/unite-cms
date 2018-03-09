united CMS API documentation
============================

To get content out of united CMS developers can consume the graphQL API at: 

    https://yourdomain.com/{organization}/{domain}/api

Or if you are using the subdomain-approach: 

    https://{organization}.yourdomain.com/{domain}/api
    
Note, that united CMS do not use API version numbers. This is because of the flexibility of GraphQL.  

At the root leve, united CMS supports two types of operations: **query** to get data and **mutation** to create, update 
or delete data.

## querying data

The following example shows how to get content and settings from an united CMS domain with a content type *News* and a 
setting type *General*. To find a single news article you can simply use: 

    query {
        getNews(id: "ad982ed") {
            id,
            type,
            created,
            updated,
            title,
            slug,
            teaser,
            content
        }
    }

This returns an instance of Type *UnitedCMS\GraphQLBundle\SchemaType\Types\ContentInterface*. If you want to get a list of 
content you can use:

    query {
        findNews {
            page,
            total,
            result {
                id,
                ...
                content
            }
        }
    }
    
Which returns an instance of Type *UnitedCMS\GraphQLBundle\SchemaType\Types\ContentResultType*. findNews also except 
one or more of the following optional parameters to filter the result: 

    findNews(
        limit: 20,
        page: 1,
        collection: 'all',
        filter: {
            AND: [
                OR: [
                    { field: 'slug', operator: '=', value: 'hello-world'},
                    { field: 'title', operator: 'LIKE', value: '%world%'}
                ],
                { field: 'created', operator: '>', value: '1028383' }
            ]
        }, sort: [
            { field: 'created', order: 'DESC' },
            { field: 'title', order: 'ASC' }
        ]
    ) {
        page, 
        total,
        ...
    }

The united CMS API also supports getting content from different content types within a single search. The next example
shows how we could get a list of recent News and Event content that gets ordered by date:

    query {
        find(
            limit: 20,
            page: 1,
            types: [
                { type: 'news', collection: 'all' },
                { type: 'event', collection: 'public' }
            ],
            filter: {
                AND: [
                    OR: [
                        { field: 'slug', operator: '=', value: 'hello-world'},
                        { field: 'title', operator: 'LIKE', value: '%world%'}
                    ],
                    { field: 'created', operator: '>', value: '1028383' }
                ]
            }, sort: [
                { field: 'created', order: 'DESC' },
                { field: 'title', order: 'ASC' }
            ]
        ) {
            page,
            total,
            result {
                id,
                type,
                created,
                updated,
                
                ... on News {
                    title,
                    slug
                }
                
                ... on Event {
                    title,
                    slug,
                    location
                }
            }
        }
    }
    
Finally, by using the API you can get your setting data. In this example for the *General* setting type:

    query {
        GeneralSetting {
            title,
            contact
        }
    }

### Implementation reference

A list of all query types under the namespace *UnitedCMS\GraphQLBundle\SchemaType\Types* and their response:

- **query**: QueryType
- **find**: ContentResultType, implementation of: ContentResultInterface
- **find{Content}**: Implementation of: ContentResultInterface
- **get{Content}**: Implementation of: ContentInterface
- **{Setting}Setting**: Implementation of: SettingInterface
- The field **collections** on ContentInterface: A list of implementations of CollectionInterface

And the following input types on **find** and **find{Content}** types.
 
- **filter**: FilterInput
- **sort**: SortInput
- **types**: ContentTypeCollectionInputType 

## mutating data

*At the moment, mutation is not implemented. However, this will be done before the final release of version 1.*