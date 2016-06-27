# /user API endpoints
**Table of Contents**

- [user/apikeys](#userapikeys)
- [user/create_apikey](#usercreate_apikey)
- [user/delete_apikey](#userdelete_apikey)

## user/apikeys

Required access level: `full`

Return apikeys of the user.

```javascript
// Success response
responseSuccess.data = {
    "apikeys": {
        apikey: {
            "key": apikey, // this is the same as the key of this object
            "created": Timestamp,
            "comment": String,
            "access_level": access-level,
        },
        ...
    },
]
```

Example:
```
> curl -s $base/user/apikeys -F apikey=$apikey | json_pp
{
   "data" : {
      "apikeys" : {
         "Sa71PVwKthRrrIEUIacqN9PNTouVQAWz" : {
            "key" : "Sa71PVwKthRrrIEUIacqN9PNTouVQAWz",
            "comment" : "fb-client flo@Marin",
            "created" : 1378389775,
            "access_level" : "full"
         },
      }
   },
   "status" : "success"
}
```

## user/create_apikey

Required access level: `full`

Create a new apikey.

This is the only endpoint that may be called without an apikey, but with username and password instead. Sending both, username/password and an api key results in undefined behaviour.

| POST field   | Type         | Comment |
| ----------   | ----         | ------- |
| username     | String       | Required if not called with api key. |
| password     | String       | Required if not called with api key. |
| access_level | access-level | Required |
| comment      | String       | Optional but recommended (username, hostname, client software name, ...). Maximum 255 chars. |


```javascript
// Success response
responseSuccess.data = {
    "new_key": String,
}
```

Example:
```
> curl -s $base/user/create_apikey -F username=test -F password=test -F access_level=apikey -F "comment=This is a test key" | json_pp
{
   "data" : {
      "new_key" : "2qXhd9E4ezBE53KiRtB5EE95r6m6ZeI1"
   },
   "status" : "success"
}
```

## user/delete_apikey

Required access level: `full`

Delete an apikey.

| POST field | Type   | Comment       |
| ---------- | ----   | -------       |
| delete_key | apikey | Key to delete |

| error_id                  | Message                                       | Note |
| --------                  | -------                                       | ---- |
| user/delete_apikey/failed | Apikey deletion failed. Possibly wrong owner. |      |

```javascript
// Success response
responseSuccess.data = {
    "deleted_keys": {
        apikey: {
            "key": apikey, // this is the same as the key of this object
        },
    },
}
```

Example:
```
> curl -s $base/user/delete_apikey -F apikey=$apikey -F delete_key=o0fDrc0LF8Kemqb9qzXXaScGsz9XCegj | json_pp
{
   "data" : {
      "deleted_keys" : {
         "o0fDrc0LF8Kemqb9qzXXaScGsz9XCegj" : {
            "key" : "o0fDrc0LF8Kemqb9qzXXaScGsz9XCegj"
         }
      }
   },
   "status" : "success"
}
```

| Version | Change            |
| ------- | ------            |
| 1.2.0   | Add this endpoint |
