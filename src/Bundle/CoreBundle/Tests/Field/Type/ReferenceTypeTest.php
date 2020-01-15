<?php


namespace UniteCMS\CoreBundle\Tests\Field\Type;


use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class ReferenceTypeTest extends SchemaAwareTestCase
{
    public function testQueryMutate() {

        $this->buildSchema('
            type Test implements UniteContent @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") {
                id: ID
                _meta: UniteContentMeta
                ref: TypeB @referenceField
                ref_user: User @referenceField
            }
            type TypeB implements UniteContent @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") {
                id: ID
                _meta: UniteContentMeta
                field: Float @floatField
                ref_of: UniteContentResult @referenceOfField(content_type: "Test", reference_field: "ref")
                user_ref_of: UniteContentResult @referenceOfField(content_type: "User", reference_field: "ref")
            }
            type User implements UniteUser @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") {
                id: ID
                username: String @textField
                _meta: UniteContentMeta
                ref: TypeB @referenceField
                ref_of: UniteContentResult @referenceOfField(content_type: "Test", reference_field: "ref_user")
            }
        ');

        $createTest = 'mutation($data: TestInput!) { createTest(data: $data, persist: true) { id, ref { id, field }, ref_user { id, ref { id, field } } }}';
        $createRef = 'mutation($data: TypeBInput!) { createTypeB(data: $data, persist: true) { id, field }}';
        $createUserRef = 'mutation($data: UserInput!) { createUser(data: $data, persist: true) { id, ref { id, field } }}';

        // Create reference
        list($refId) = $this->assertGraphQL([
            'createTypeB' => [
                'id' => '{id}',
                'field' => 42.5
            ],
        ], $createRef, ['persist' => true, 'data' => ['field' => 42.5]]);

        // Create user reference
        list($userRefId) = $this->assertGraphQL([
            'createUser' => [
                'id' => '{id}',
                'ref' => [
                    'id' => $refId,
                    'field' => 42.5
                ]
            ],
        ], $createUserRef, ['persist' => true, 'data' => ['ref' => $refId]]);

        // Create test content
        list($id) = $this->assertGraphQL([
            'createTest' => [
                'id' => '{id}',
                'ref' => [
                    'id' => $refId,
                    'field' => 42.5
                ],
                'ref_user' => [
                    'id' => $userRefId,
                    'ref' => [
                        'id' => $refId,
                        'field' => 42.5
                    ]
                ],
            ],
        ], $createTest, ['persist' => true, 'data' => ['ref' => $refId, 'ref_user' => $userRefId]]);

        // Test ref of
        $this->assertGraphQL([
            'getTypeB' => [
                'ref_of' => [
                    'total' => 1,
                    'result' => [
                        [
                            'id' => $id,
                        ]
                    ],
                ],
                'user_ref_of' => [
                    'total' => 1,
                    'result' => [
                        [
                            'id' => $userRefId,
                            'ref_of' => [
                                'total' => 1,
                                'result' => [
                                    [
                                        'id' => $id,
                                    ]
                                ],
                            ],
                        ]
                    ],
                ],
            ],
        ], 'query($id: ID!) {
            getTypeB(id: $id) {
                ref_of {
                    total,
                    result {
                        ...on Test { id }
                    }
                }
                user_ref_of {
                    total,
                    result {
                        ...on User {
                            id, 
                            ref_of {
                                total,
                                result {
                                    ...on Test { id }
                                }
                            } 
                        }
                    }
                }
            }
        }', ['id' => $refId]);
    }
}
