# API

**Table of Contents**

- [General notes](#general-notes)
	- [URLs](#urls)
	- [Compatibility](#compatibility)
	- [Unless stated otherwise ...](#unless-stated-otherwise-)
	- [Access levels](#access-levels)
- [Endpoints](#endpoints)
	- [Examples](#examples)
- [Error handling](#error-handling)
	- [General errors](#general-errors)
- [Overview over API versions](#overview-over-api-versions)

The API provides programmatic access to upload, delete files, view the
currently uploaded ones and combine them to multipastes, as well as functions
to manage api keys. Responses are available in JSON.

## General notes

### URLs

The URLs for API endpoints start with the base URL followed by `/api/`, the
version supported by the client and the endpoint. For example:
`https://paste.xinu.at/api/v1.0.0/some/endpoint`.

The version number follows the [semantic versioning guidelines](http://semver.org/).
The requested version number must be of the format `vX[.X[.X]]` with X being
a positive number. `v1` and `v1.0` will both be treated as `v1.0.0`.

The most recent API version is `v2.1.0`.

### Compatibility

The API will evolve by adding and removing endpoints, parameters and keywords.
Unknown keywords should be treated gracefully by your application.

Behavior not documented here should not be expected to be available and may be
changed without notice.

### Unless stated otherwise ...


*  ... requests should be sent via POST.
*  ... endpoints expect an apikey with access level `apikey` to be sent with the request.
*  ... requests should not be assumed to be atomic (i.e. data may be changed on the server despite an error being returned).


*  ... timestamps are returned as UNIX timestamps (seconds).
*  ... sizes are returned in bytes.
*  ... values are specific to the user owning the apikey (e.g. the `total_size` field of file/history).


*  ... errors will generate a response with `status=error`.
*  ... error messages may differ from those listed in the tables.
*  ... errors listed are only the most common ones (i.e. the lists are non-exhaustive).

### Access levels

An api key can have one of the following access levels. Levels further down in
the table include those above themselves.

| access-level | Comment                                       |
| ------------ | -------                                       |
| basic        | Allows only uploading of files                |
| apikey       | Allows to delete uploads and view the history |
| full         | Allows everything                             |

## Endpoints

Documentation for specific endpoints can be found in `./doc/api/`:

* [Documentation for the /file/ endpoints](api/file.md)
* [Documentation for the /user/ endpoints](api/user.md)

Responses will always contain a `status` field which can contain the following
values: `success`, `error`. If the response does not contain the status field
it should be regarded as invalid.

If `status=success` the response will contain a `data` field which contains
function specific data.


```javascript
// General definitions

// Listed above
access-level: String;

// An api key
apikey: String;

// An ID that can be used to display a multipaste or a single file
upload-id: String;


// General success response
responseSuccess = {
	"status": "success",
	"data": object or array, // Differs per endpoint. Refer to the specific endpoint documentation.
}
```

### Examples

For the examples given in the endpoint documentation, set the following
variables in your shell. Please note that they and their values will show up in
your shell history and in top/ps if used.  Be careful on untrusted systems.

```bash
apikey="anApiKey"
base="https://paste.xinu.at/api/v2.0.0"
```

## Error handling

If `status=error` the response will be of the following format:

```javascript
// Error response format
responseError = {
	"status": "error",
	"message": "A message that can be displayed to the user",
	"error_id": "program/useable/error/id",
	"data": object or array, // optional, only used if mentioned
}
```


### General errors

These are the most common errors that can be returned by any API call.

| error_id                     | Message                                    | Note                      |
| --------                     | -------                                    | ----                      |
| api/invalid-version          | Invalid API version requested              | Failed syntax check       |
| api/invalid-endpoint         | Invalid endpoint requested                 | Failed syntax check       |
| api/version-not-supported    | Requested API version is not supported     |                           |
| api/unknown-endpoint         | Unknown endpoint requested                 | Likely a typo in your URL |
| internal-error               | An unhandled internal server error occured |                           |
| user/api-login-failed        | API key login failed                       |                           |
| api/insufficient-permissions | Access denied: Access level too low        |                           |
| api/not-authenticated        | Not authenticated                          | Likely no apikey was sent |

## Overview over API versions

| Version | Endpoint | Note |
| ------- | -------- | ---- |
| 2.1.0   | file/history | Add ''item.thumbnail'' |
| 2.0.0   | file/history | Add ''multipaste_item.date'' |
| 2.0.0   | file/history | Remove private fields in response |
| 1.4.0   | file/get_config | Add data.{max_input_vars,request_max_size} |
| 1.3.0   | file/create_multipaste | Allow multipaste creation for basic access level |
| 1.2.0   | user/delete_apikeys | Add this endpoint |
| 1.1.0   | file/create_multipaste | Add url key to response |
| 1.0.0   | * | Initial API version |
