# /file API endpoints

**Table of Contents**

- [file/get_config](#fileget_config)
- [file/upload](#fileupload)
- [file/history](#filehistory)
- [file/delete](#filedelete)
- [file/create_multipaste](#filecreate_multipaste)

## file/get_config

Request method: GET
This is a public method and does not require an apikey.

Return some useful values that may differ between service installations/subsequent requests.

Success response:
```javascript
// Success response
responseSuccess.data = {
	// Maximum size of a single file
	"upload_max_size": int,

	// Maximum number of files sent with one request
	"max_files_per_request": int,

	// Maximum number of variables sent with one request
	// (be sure to account for the api key when using this)
	"max_input_vars": int,

	// Maximum size of a complete request
	"request_max_size": int,
}
```

Example:
```
> curl -s $base/file/get_config | json_pp
{
   "data" : {
	  "max_files_per_request" : 20,
	  "upload_max_size" : 1073741824,
	  "request_max_size" : 1073741824,
	  "max_input_vars" : 1000
   },
   "status" : "success"
}
```

| Version | Change                                     |
| ------- | ------                                     |
| 1.4.0   | Add data.{max_input_vars,request_max_size} |

## file/upload

Required access level: `basic`

Upload a new file.

| POST field        | Type | Comment                    |
| ----------        | ---- | -------                    |
| file[`<index>`]   | File | Required. Arbitrary index. |
| minimum-id-length | Int  | Optional. Values >= 2 only |

| error_id                   | Message                                        | Note                                  |
| --------                   | -------                                        | ----                                  |
| file/no-file               | No file was uploaded or unknown error occurred |                                       |
| file/bad-minimum-id-length | Invalid value passsed to bad-minimum-id-length |                                       |
| file/upload-verify         | Failed to verify uploaded file(s)              | This error provides additional detail |

```javascript
// Success response
responseSuccess.data = {
	"ids": [upload-id, ...],
	"urls": [String, ...],
}

// Error file/upload-verify
responseError.data = {
	String-formfield: {
		"filename": String, // from the request
		"formfield": String-formfield, // from the request, this is the same as the key of this object
		"message": String, // can be displayed to the user
	},
	...
}
```

Example:
```
> echo test | curl -s $base/file/upload -F apikey=$apikey -F "file=@-" | json_pp
{
   "status" : "success",
   "data" : {
	  "ids" : [
		 "uu28"
	  ],
	  "urls" : [
		 "http://filebin.localhost/uu28/"
	  ]
   }
}
```

| Version | Change                                                                            |
| ------- | ------                                                                            |
| 2.2.0   | Add parameter ''minimum-id-length'' to control the length of generated content id |

## file/history

Return the currently available files/multipastes.

```javascript
// Definitions
item = {
	"id": upload-id,
	"filename": String,
	"mimetype": String,
	"date": Timestamp,
	"hash": String,
	"filesize": int,
	"thumbnail": String, // URL. only set when there is a thumbnail available
}

multipaste_item = {
	"url_id": upload-id,
	"id": upload-id, // this references item.id described above
	"date": Timestamp,
}

// Success response
responseSuccess.data = {
	"items": {item.id: item, ...},
	"multipaste_items": {multipaste_item.url_id: multipaste_item, ...},
	"total_size": int, // total size of all files (excluding duplicates)
}
```

Example:
```
> curl -s $base/file/history -F apikey=$apikey | json_pp
{
   "status" : "success",
   "data" : {
	  "multipaste_items" : {
		 "m-JcK" : {
			"items" : {
			   "oeL" : {
				  "id" : "oeL"
			   },
			   "7kn" : {
				  "id" : "7kn"
			   }
			},
			"url_id" : "m-JcK",
			"date" : "1444119317"
		 }
	  },
	  "total_size" : "164006",
	  "items" : {
		 "oeL" : {
			"id" : "oeL",
			"hash" : "098f6bcd4621d373cade4e832627b4f6",
			"date" : "1444119317",
			"filename" : "test2",
			"filesize" : "4",
			"mimetype" : "text/plain"
		 },
		 "7kn" : {
			"date" : "1444119317",
			"hash" : "6a72c253e8e9e6d01544f1c6b4573e6e",
			"id" : "7kn",
			"thumbnail" : "http://filebin.localhost/file/thumbnail/7kn",
			"mimetype" : "image/jpeg",
			"filesize" : "164001",
			"filename" : "Relax.jpg"
		 },
		 "l7p" : {
			"date" : "1444116077",
			"hash" : "cfcd208495d565ef66e7dff9f98764da",
			"id" : "l7p",
			"mimetype" : "application/octet-stream",
			"filesize" : "1",
			"filename" : "stdin"
		 }
	  }
   }
}
```

| Version | Change                                                                            |
| ------- | ------                                                                            |
| 2.0.0   | Add ''multipaste_item.date''. Remove ''multipaste_item.{multipaste_id,user_id}''. |
| 2.1.0   | Add ''item.thumbnail''                                                            |
| 2.1.1   | Empty objects (values of `items` and `multipaste_items`) are now always returned as {}. Before they were returned as [] |

## file/delete

Delete files or multipastes. Multipastes containing deleted files will also be silently removed.

Note: This function returns some errors in the success response.

| POST field | Type      | Comment                    |
| ---------- | ----      | -------                    |
| ids[`<index>`] | upload-id | Required. Arbitrary index. |

| error_id           | Message          | Note |
| --------           | -------          | ---- |
| file/delete/no-ids | No IDs specified |      |

```javascript
// Success response
responseSuccess.data = {
	"errors": {
		upload-id: {
			"id": upload-id, // this is the same as the key of this object
			"reason": String,
		},
		...
	},
	"deleted": {
		upload-id: {
			"id": upload-id, // this is the same as the key of this object
		},
		...
	},
	"total_count": int,
	"deleted_count": int,
}
```


Example:
```
> curl -s $base/file/delete -F apikey=$apikey -F "ids[1]=uu28" | json_pp
{
   "data" : {
      "errors" : {},
      "total_count" : 1,
      "deleted" : {
         "uu28" : {
            "id" : "uu28"
         }
      },
      "deleted_count" : 1
   },
   "status" : "success"
}
```

| Version | Change                                                    |
| ------- | ------                                                    |
| 2.1.1   | Empty objects (values of `errors` and `deleted`) are now always returned as {}. Before they were returned as [] |

## file/create_multipaste

Required access level: `basic`

Create a new multipaste.

| POST field        | Type      | Comment                                                                           |
| ----------        | ----      | -------                                                                           |
| ids[`<index>`]    | upload-id | Required. Arbitrary index. This only accepts IDs of files, not other multipastes. |
| minimum-id-length | Int       | Optional. Values >= 2 only                                                        |

| error_id                             | Message                                        | Note                                  |
| --------                             | -------                                        | ----                                  |
| file/bad-minimum-id-length           | Invalid value passsed to bad-minimum-id-length |                                       |
| file/create_multipaste/no-ids        | No IDs specified                               |                                       |
| file/create_multipaste/duplicate-id  | Duplicate IDs are not supported                |                                       |
| file/create_multipaste/verify-failed | Failed to verify ID(s)                         | This error provides additional detail |

```javascript
// Success response
responseSuccess.data = {
    "url_id": upload-id,
    "url": String, // Complete URL to url_id
}

// Error file/create_multipaste/verify-failed
responseError.data = {
    upload-id: {
        "id": upload-id, // this is the same as the key of this object
        "reason": String,
    },
    ...
}
```

Example:
```
> curl -s $base/file/create_multipaste -F apikey=$apikey -F "ids[1]=uu28" | json_pp
{
   "data" : {
      "url" : "http://filebin.localhost/m-J250b/",
      "url_id" : "m-J25Ob"
   },
   "status" : "success"
}
```

| Version | Change                                                                           |
| ------- | ------                                                                           |
| 1.1.0   | Add url key to response                                                          |
| 1.3.0   | Change required access level from ''apikey'' to ''basic''                        |
| 2.2.0   | Add paramter ''minimum-id-length'' to control the length of generated content id |
