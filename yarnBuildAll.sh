#!/bin/bash
yarn --cwd src/Bundle/CollectionFieldBundle/ run build
yarn --cwd src/Bundle/CoreBundle/ run build
yarn --cwd src/Bundle/StorageBundle/ run build
yarn --cwd src/Bundle/VariantsFieldBundle/ run build
yarn --cwd src/Bundle/WysiwygFieldBundle/ run build
