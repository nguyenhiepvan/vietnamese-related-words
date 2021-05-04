# Installation

```shell
    composer require nguyenhiep/vietnamese-related-words
```

# Configuration

To configure the package you need to publish settings first:

```shell
    php artisan vendor:publish --provider="Nguyenhiep\VietnameseRelatedWords\VietnameseRelatedWordsServiceProvider"
```

Option | Description
--- | ---
es_host | elasticsearch host
mapping | additional mapping rules

# Usage

```phpt
    $analyer = new Nguyenhiep\VietnameseRelatedWords\VietnameseAnalyzer();
    //using vncorenlp
    $analyer->vncorenlp("một chuỗi tiếng việt"); //["chuỗi tiếng","một chuỗi","chuỗi tiếng việt",]
    //using coccoc tokenizer
    $analyer->es_analyze("một chuỗi tiếng việt"); //["một","chuỗi","tiếng việt",]
    //using  VnTokenizer library
    $analyer->es_analyze("một chuỗi tiếng việt"); //["một","chuỗi","tiếng","việt",]
```

**Notice:** 
    - if you want to see analying results, add param `true` to analyer construct
    - if you want to add more mapping rules, add them in `vietnamese-related-words.php`

# Reference

- [VncoreNlp](https://github.com/vncorenlp/VnCoreNLP)
- [Coccoc tokenizer](https://github.com/coccoc/coccoc-tokenizer)
- [Vietnamese Analysis Plugin for Elasticsearch](https://github.com/duydo/elasticsearch-analysis-vietnamese)
- [Vietnamese Analysis Plugin for Elasticsearch](https://github.com/duydo/elasticsearch-analysis-vietnamese/tree/vntokenizer)
- [Laravel package tools](https://github.com/spatie/laravel-package-tools)
