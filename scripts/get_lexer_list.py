#!/usr/bin/python

import pygments.lexers
import json

ret = []

for fullname, names, exts, mimetypes in pygments.lexers.get_all_lexers():
    ret.append({
        'fullname': fullname,
        'names': names,
        'extentions': exts,
        'mimetypes': mimetypes,
        })
print(json.dumps(ret))
