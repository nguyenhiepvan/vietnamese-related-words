# Installation

```shell
    composer require nguyenhiep/vietnamese-related-words
```

# Configuration

To configure the package you need to publish settings first:

```shell
    php artisan vendor:publish --provider=" Nguyenhiep\VietnameseRelatedWords\VietnameseRelatedWordsServiceProvider"
```
Option | Description
--- | ---
es_host | elasticsearch host
mapping | additional mapping rules
# Usage

```phpt
    $analyer = new Nguyenhiep\VietnameseRelatedWords\VietnameseAnalyzer();
    //using vncorenlp
    $analyer->vncorenlp("mối quan hệ biện chứng giữa vật chất và ý thức trong học tập"); //["quan hệ","vật chất","ý thức","học tập","quan hệ biện chứng","mối quan hệ","vật chất và ý thức",]
    //using coccoc tokenizer
    $analyer->es_analyze("mối quan hệ biện chứng giữa vật chất và ý thức trong học tập"); //["mối","quan hệ","biện chứng","giữa","vật chất","ý thức","trong","học tập",]
```

# Reference
- [VncoreNlp](https://github.com/vncorenlp/VnCoreNLP)
- [Coccoc tokenizer](https://github.com/coccoc/coccoc-tokenizer)
- [Vietnamese Analysis Plugin for Elasticsearch](https://github.com/duydo/elasticsearch-analysis-vietnamese)
- [Laravel package tools](https://github.com/spatie/laravel-package-tools)
