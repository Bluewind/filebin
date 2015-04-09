#!/usr/bin/python

import pygments.lexers
import json

ret = []

def dictify(list):
    return {k:True for k in list}

for fullname, names, exts, mimetypes in pygments.lexers.get_all_lexers():
    ret.append({
        'fullname': fullname,
        'names': names,
        'extentions': dictify(exts),
        'mimetypes': dictify(mimetypes),
        })
print(json.dumps(ret))
