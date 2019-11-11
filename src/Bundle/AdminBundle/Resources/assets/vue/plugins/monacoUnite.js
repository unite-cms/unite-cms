
export function graphQLLanguageProvider (monaco){
    monaco.languages.registerCompletionItemProvider('graphql', {
        provideCompletionItems: () => {
            let suggestions = [

                {
                    label: 'type UniteContent',
                    kind: monaco.languages.CompletionItemKind.Snippet,
                    insertText: [
                        '"""${1:Name}"""',
                        'type ${1:Name} implements UniteContent {',
                        '\tid: ID!',
                        '\t$0',
                        '}'
                    ].join('\n'),
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet
                },

                {
                    label: 'type UniteUser',
                    kind: monaco.languages.CompletionItemKind.Snippet,
                    insertText: [
                        '"""${1:Name}"""',
                        'type ${1:Name} implements UniteUser {',
                        '\tid: ID!',
                        '\tusername: String!',
                        '\t$0',
                        '}'
                    ].join('\n'),
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet
                }

            ];
            return { suggestions: suggestions };
        }
    });
};

export const editorOptions = {
    automaticLayout: true,
    language: 'graphql',
    theme: 'vs-dark',
    fontSize: 14,
    minimap: {
        enabled: false,
    },
    rulers: [80],
};
