# adClassify

Implementation of adClassify service in Adshares Network

adClassify provides data about banners and allow Publishers to effectively filter unwanted content

# API
### Submit banner to classify
`POST /submit/`
* body: json with banner details

Return request id which can be used to query about the classification results. If request could be instantly processed classification is immediately returned.
```
[
    'request_id' => 'abcdef',
    'processed' => true,
    'result' => [
      'keywords' => [
          'category' => 0,
          'safe' => true,
      ],
      'banner' => [
        'hash' => ''abcdef...'
      ],
      'signature' => 'abcdef...'
    ]
]
```

### Get classification
`GET /get_data/{requestId}`
* requestId - request id 

Return classification of provided banner. If classification is complete `processed` is true. If not, one need to query it again later.
```
[
    'request_id' => 'abcdef',
    'processed' => true,
    'result' => [
      'keywords' => [
          'category' => 0,
          'safe' => true,
      ],
      'banner' => [
        'hash' => ''abcdef...'
      ],
      'signature' => 'abcdef...'
    ]
]
```

### Get info
`GET /info`
Return info about adClassify service 

```
[
  'submit_url' => 'https://example.com/submit',
  'data_url' => 'https://example.com/get_data/:id'
  'public_key' => 'abcdef...',
  'schema' => []
]
